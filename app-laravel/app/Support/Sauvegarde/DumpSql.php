<?php

namespace App\Support\Sauvegarde;

use PDO;
use RuntimeException;

/**
 * Exporte la base MySQL en un fichier SQL compresse, sans binaire externe.
 *
 * POURQUOI PAS mysqldump. Le conteneur de l'application n'en a pas, et y
 * ajouter le client de Debian (MariaDB) pour parler a un MySQL 9.7 est un
 * pari : les options et les plugins d'authentification different. Un export
 * ecrit en PHP, a travers la connexion que l'application utilise deja, ne
 * depend de rien de plus. La base pese quelques megaoctets.
 *
 * COHERENCE : une seule transaction « WITH CONSISTENT SNAPSHOT » (InnoDB, lecture
 * repetable) pour toutes les tables. Une demande de visite recue pendant
 * l'export ne produit pas un fichier ou une table est a l'heure H et la suivante
 * a H + 3 secondes. La production ne verrouille rien.
 *
 * CE QUE CONTIENT LE FICHIER : pour chaque table, DROP + CREATE (tel que
 * « SHOW CREATE TABLE » le rend) puis les lignes. Le tout avec les controles de
 * cles etrangeres suspendus, l'ordre des tables est donc indifferent. Aucun
 * declencheur, aucune procedure, aucune vue : la base n'en a pas (verifie le
 * 12 octobre 2026) ; le jour ou elle en aura, cette classe devra les ajouter,
 * et le test de restauration le montrera.
 *
 * Le fichier se restaure par « mysql base < fichier.sql ».
 */
final class DumpSql
{
    /** La derniere ligne d'un export complet. Son absence denonce un fichier coupe. */
    public const FIN = '-- Fin de la sauvegarde';

    /** Lignes par instruction INSERT. */
    private const LIGNES_PAR_INSERT = 200;

    public function __construct(private readonly PDO $pdo) {}

    /**
     * @return array<string, int> le nombre de lignes exportees, par table
     */
    public function ecrire(string $cheminGz): array
    {
        $sortie = @gzopen($cheminGz, 'wb6');

        if ($sortie === false) {
            throw new RuntimeException("Impossible d'ecrire l'export : $cheminGz");
        }

        $comptes = [];

        try {
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $this->pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $this->pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');

            $this->ligne($sortie, '-- Sauvegarde SCI4K — '.gmdate('Y-m-d H:i:s').' UTC');
            $this->ligne($sortie, '-- MySQL '.$this->pdo->query('SELECT VERSION()')->fetchColumn());
            $this->ligne($sortie, '/*!40101 SET NAMES utf8mb4 */;');
            $this->ligne($sortie, 'SET FOREIGN_KEY_CHECKS=0;');
            $this->ligne($sortie, 'SET UNIQUE_CHECKS=0;');
            $this->ligne($sortie, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';");
            $this->ligne($sortie, "SET time_zone='+00:00';");

            foreach ($this->tables() as $table) {
                $comptes[$table] = $this->table($sortie, $table);
            }

            $this->ligne($sortie, 'SET FOREIGN_KEY_CHECKS=1;');
            $this->ligne($sortie, 'SET UNIQUE_CHECKS=1;');

            // Les comptes servent au controle d'une restauration : on compare
            // ces lignes au contenu de la base restauree.
            foreach ($comptes as $table => $n) {
                $this->ligne($sortie, "-- lignes: $table=$n");
            }

            $this->ligne($sortie, self::FIN);
            $this->pdo->exec('COMMIT');
        } catch (\Throwable $e) {
            try {
                $this->pdo->exec('ROLLBACK');
            } catch (\Throwable) {
            }

            throw $e;
        } finally {
            gzclose($sortie);
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        return $comptes;
    }

    /** @return list<string> */
    private function tables(): array
    {
        $liste = [];

        foreach ($this->pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $ligne) {
            $liste[] = (string) array_values($ligne)[0];
        }

        sort($liste);

        return $liste;
    }

    private function table($sortie, string $table): int
    {
        $nom = '`'.str_replace('`', '``', $table).'`';
        $creation = $this->pdo->query("SHOW CREATE TABLE $nom")->fetch(PDO::FETCH_NUM)[1];

        $this->ligne($sortie, '');
        $this->ligne($sortie, "DROP TABLE IF EXISTS $nom;");
        $this->ligne($sortie, $creation.';');

        $requete = $this->pdo->query("SELECT * FROM $nom");
        $total = 0;
        $groupe = [];

        while (($ligne = $requete->fetch(PDO::FETCH_NUM)) !== false) {
            $groupe[] = '('.implode(',', array_map($this->valeur(...), $ligne)).')';
            $total++;

            if (count($groupe) >= self::LIGNES_PAR_INSERT) {
                $this->ligne($sortie, "INSERT INTO $nom VALUES ".implode(",\n", $groupe).';');
                $groupe = [];
            }
        }

        if ($groupe !== []) {
            $this->ligne($sortie, "INSERT INTO $nom VALUES ".implode(",\n", $groupe).';');
        }

        $requete->closeCursor();

        return $total;
    }

    /** Une valeur en litteral SQL : NULL, texte echappe, ou hexadecimal si elle n'est pas de l'UTF-8. */
    private function valeur(mixed $v): string
    {
        if ($v === null) {
            return 'NULL';
        }

        $v = (string) $v;

        if ($v !== '' && ! mb_check_encoding($v, 'UTF-8')) {
            return '0x'.bin2hex($v);
        }

        return $this->pdo->quote($v);
    }

    private function ligne($sortie, string $texte): void
    {
        gzwrite($sortie, $texte."\n");
    }
}
