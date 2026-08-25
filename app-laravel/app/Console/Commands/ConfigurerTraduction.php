<?php

namespace App\Console\Commands;

use App\Services\Traduction\TraducteurDeepL;
use Illuminate\Console\Command;

/**
 * Pose la cle DeepL dans .env, sans avoir a ouvrir le fichier.
 *
 * Le .env vit sous .claude/worktrees/, un dossier cache : ni le Finder ni la
 * plupart des editeurs ne le montrent. Cette commande evite d'avoir a le
 * trouver, et verifie dans la foulee que la cle repond — sans quoi on ne
 * decouvrirait le probleme qu'au premier article enregistre.
 *
 * La cle est saisie masquee et n'apparait jamais a l'ecran ni dans
 * l'historique du terminal.
 */
class ConfigurerTraduction extends Command
{
    protected $signature = 'traduction:configurer {--retirer : efface la cle enregistree}';

    protected $description = 'Enregistre la cle DeepL et verifie qu elle repond';

    public function handle(): int
    {
        $fichier = base_path('.env');

        if (! is_writable($fichier)) {
            $this->error("Le fichier .env n'est pas accessible en écriture : {$fichier}");

            return self::FAILURE;
        }

        if ($this->option('retirer')) {
            $this->ecrireLaCle($fichier, '');
            $this->info('Clé effacée. La traduction automatique est désactivée.');

            return self::SUCCESS;
        }

        $this->line('Créez une clé gratuite sur https://www.deepl.com/pro-api');
        $this->line('Elle ressemble à : 12345678-abcd-1234-abcd-123456789abc:fx');
        $this->newLine();

        $cle = trim((string) $this->secret('Collez la clé (elle ne s\'affichera pas)'));

        if ($cle === '') {
            $this->warn('Aucune clé saisie, rien n\'a été modifié.');

            return self::FAILURE;
        }

        if (! preg_match('/^[0-9a-f-]{20,}(:fx)?$/i', $cle)) {
            $this->error('Cette clé n\'a pas le format attendu. Vérifiez qu\'elle a été collée en entier.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Vérification auprès de DeepL…');

        $essai = (new TraducteurDeepL($cle))->traduire(['Bonjour'], 'en', 'fr');

        if ($essai === null) {
            $this->error('DeepL n\'a pas accepté cette clé. Rien n\'a été enregistré.');
            $this->line('Vérifiez qu\'elle est complète, et que le compte est bien activé.');

            return self::FAILURE;
        }

        $this->ecrireLaCle($fichier, $cle);

        $this->newLine();
        $this->info('Clé enregistrée et vérifiée.');
        $this->line('  « Bonjour » → « '.$essai[0].' »');
        $this->line('  Point d\'accès : '.(str_ends_with($cle, ':fx') ? 'offre gratuite' : 'offre payante'));
        $this->newLine();
        $this->line('Les articles se traduiront désormais tout seuls, dans la langue laissée vide.');

        return self::SUCCESS;
    }

    /** Remplace la ligne existante, ou l'ajoute si elle a disparu. */
    protected function ecrireLaCle(string $fichier, string $cle): void
    {
        $contenu = file_get_contents($fichier);
        $ligne = 'DEEPL_API_KEY='.$cle;

        $contenu = preg_match('/^DEEPL_API_KEY=.*$/m', $contenu)
            ? preg_replace('/^DEEPL_API_KEY=.*$/m', $ligne, $contenu)
            : rtrim($contenu)."\n\n".$ligne."\n";

        file_put_contents($fichier, $contenu);

        $this->call('config:clear');
    }
}
