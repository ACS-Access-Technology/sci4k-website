<?php

namespace App\Models;

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
    use TraduitParColonnes;

    protected $table = 'reglages_de_section';

    protected $fillable = ['slug', 'etiquette_fr', 'etiquette_en', 'titre_fr', 'titre_en', 'chapo_fr', 'chapo_en', 'options'];

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

    /** L'en-tete d'une section, cree au besoin. */
    public static function pour(string $slug): self
    {
        return static::firstOrCreate(['slug' => $slug]);
    }
}
