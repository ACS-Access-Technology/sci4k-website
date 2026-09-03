<?php

namespace App\Models;

use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * En-tete d'une section du site : etiquette, titre, chapo.
 *
 * Le cadrage n'annonçait que deux « reglages de section » — l'en-tete du
 * processus et la banderole. Le dictionnaire du site en montre vingt-trois,
 * toutes batie sur le meme triplet. Une table indexee par section les couvre
 * donc toutes, la ou deux cas particuliers auraient laisse les vingt et une
 * autres ecrites en dur.
 *
 * Ni creation ni suppression : les sections sont celles du site.
 */
class ReglageDeSection extends Model
{
    use HasFactory;
    use JournaliseSesChangements;
    use TraduitParColonnes;

    protected $table = 'reglages_de_section';

    protected $fillable = ['slug', 'etiquette_fr', 'etiquette_en', 'titre_fr', 'titre_en', 'chapo_fr', 'chapo_en', 'contenu_fr', 'contenu_en', 'options'];

    protected $casts = ['options' => 'array'];

    protected $attributes = [];

    /** Etiquette affichee au-dessus du titre de section. */
    public function etiquette(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('etiquette', $langue);
    }

    /** Titre de la section. */
    public function titre(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('titre', $langue);
    }

    /** Phrase d'introduction, sous le titre. */
    public function chapo(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('chapo', $langue);
    }

    /**
     * Corps de texte de la section, en plusieurs paragraphes.
     *
     * Retombe sur « chapo » tant que « contenu » est vide : ces textes y
     * logeaient avant d'avoir leur propre champ, et une base non migree — ou
     * un bloc qui n'a jamais eu de corps de texte — doit continuer de
     * s'afficher comme avant.
     */
    public function contenu(string $langue = 'fr'): string
    {
        $texte = $this->texteDansLaLangue('contenu', $langue);

        return $texte !== '' ? $texte : $this->chapo($langue);
    }

    /**
     * Le corps de texte decoupe en paragraphes, sur les lignes vides.
     *
     * @return list<string>
     */
    public function paragraphes(string $langue = 'fr'): array
    {
        $texte = trim($this->contenu($langue));

        if ($texte === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/u', $texte) ?: []),
            'strlen',
        ));
    }

    /**
     * Le titre decoupe en lignes, debarrasse de tout balisage.
     *
     * Le titre du bandeau d'accueil s'affiche sur deux lignes, la seconde mise
     * en valeur. La coupure vit en base sous forme de saut de ligne et non de
     * <br> : un champ que l'administration ecrit ne doit pas pouvoir injecter
     * du balisage dans la page.
     *
     * Les <br> heritees sont converties et le reste des balises retire — un
     * import ancien, ou un copier-coller depuis une page, ne doit pas afficher
     * « &lt;em&gt; » en clair au visiteur.
     *
     * @return list<string>
     */
    public function titreEnLignes(string $langue = 'fr'): array
    {
        $titre = preg_replace('/<br\s*\/?>/i', "\n", $this->titre($langue));

        $lignes = preg_split('/\R/u', trim(strip_tags((string) $titre))) ?: [];

        return array_values(array_filter(array_map('trim', $lignes), 'strlen'));
    }

    /**
     * Une option du bloc, ou sa valeur par defaut.
     *
     * Les options vivent en JSON plutot qu'en colonnes : une colonne par
     * reglage aurait fait une migration a chaque nouveau, pour des donnees que
     * seul l'affichage consulte et qui different d'un bloc a l'autre — la
     * duree d'une animation n'a de sens que pour les chiffres, la mise en page
     * en frise que pour le processus.
     */
    public function option(string $nom, mixed $defaut = null): mixed
    {
        return data_get($this->options, $nom, $defaut);
    }

    /** Pose des options sans effacer celles qu'on ne touche pas. */
    public function poserOptions(array $valeurs): void
    {
        $this->options = array_merge($this->options ?? [], $valeurs);
    }

    /**
     * Une option BILINGUE, avec repli sur l'autre langue.
     *
     * Les libelles d'un formulaire n'ont pas de colonne a eux : ils sont neuf
     * dans le seul bloc « poser une question », et neuf de plus au prochain
     * formulaire. Ils vivent donc dans le sac d'options, sous des cles
     * suffixees `_fr` / `_en`.
     *
     * Le repli sur l'autre langue evite qu'un bloc a demi traduit affiche un
     * libelle vide : mieux vaut la mauvaise langue qu'un champ sans nom.
     */
    public function texteBilingue(string $nom, string $langue = 'fr'): string
    {
        $autre = $langue === 'fr' ? 'en' : 'fr';

        $valeur = trim((string) $this->option($nom.'_'.$langue, ''));

        return $valeur !== '' ? $valeur : trim((string) $this->option($nom.'_'.$autre, ''));
    }

    /** L'en-tete d'une section, cree au besoin. */
    public static function pour(string $slug): self
    {
        return static::firstOrCreate(['slug' => $slug]);
    }
}
