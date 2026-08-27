<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Une action faite depuis l'administration.
 *
 * Ecrite par le trait JournaliseSesChangements, jamais a la main.
 */
class ActiviteJournalisee extends Model
{
    protected $table = 'journal_activites';

    protected $fillable = [
        'user_id', 'auteur_nom', 'action', 'sujet_type', 'sujet_id', 'sujet_intitule',
    ];

    public const CREATION = 'creation';

    public const MODIFICATION = 'modification';

    public const PUBLICATION = 'publication';

    public const SUPPRESSION = 'suppression';

    public function auteur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeRecentes(Builder $requete): Builder
    {
        return $requete->latest('created_at')->latest('id');
    }

    /**
     * Le compte qui a agi.
     *
     * Ce journal parle des COMPTES du backoffice, et c'est son sujet principal.
     * Le premier jet mettait en avant le contenu touche : on lisait le nom de
     * l'auteur d'un temoignage — donc du contenu — comme si c'etait lui qui
     * avait agi. Signale par le client.
     *
     * Le nom est RECOPIE, donc il survit a la suppression du compte : la
     * contrainte met alors `user_id` a nul, mais la ligne continue de dire qui
     * avait agi. Un nom absent ne peut donc signifier qu'une chose — l'action
     * n'a pas ete faite depuis une session.
     */
    public function nomDeLAuteur(): string
    {
        return $this->auteur_nom ?: __('Import ou tâche automatique');
    }

    /** Initiales du compte, pour la vignette. */
    public function initialesDeLAuteur(): string
    {
        if (! $this->auteur_nom) {
            // Ni un nom ni une personne : une action de la machine.
            return '⚙';
        }

        return collect(preg_split('/\s+/u', trim($this->auteur_nom)))
            ->filter()
            ->take(2)
            ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
            ->implode('');
    }

    /**
     * Ce que la personne a fait, en une phrase.
     *
     * L'intitule du contenu la complete a l'ecran : « a modifié le témoignage »
     * suivi du nom du temoignage.
     */
    public function phrase(): string
    {
        $famille = $this->familleAvecArticle();

        return match ($this->action) {
            self::CREATION => __('a créé :famille', ['famille' => $famille]),
            self::PUBLICATION => __('a publié :famille', ['famille' => $famille]),
            self::SUPPRESSION => __('a supprimé :famille', ['famille' => $famille]),
            default => __('a modifié :famille', ['famille' => $famille]),
        };
    }

    /**
     * La famille precedee de son article defini.
     *
     * L'article est porte ici et non colle a la volee : en francais il depend
     * du genre du mot — « LE temoignage », « LA question », « L'article ». Le
     * deduire d'une regle aurait donne « a modifie temoignage », qui ne se dit
     * pas. La traduction anglaise n'a pas ce probleme et rend simplement le
     * nom, l'article vivant dans la phrase.
     */
    public function familleAvecArticle(): string
    {
        return match ($this->sujet_type) {
            Article::class => __("l'article"),
            Service::class => __('le service'),
            QuestionFaq::class => __('la question'),
            RubriqueFaq::class => __('la rubrique de FAQ'),
            Temoignage::class => __('le témoignage'),
            MembreEquipe::class => __("le membre de l'équipe"),
            Partenaire::class => __('le partenaire'),
            Valeur::class => __('la valeur'),
            ChiffreCle::class => __('le chiffre clé'),
            EtapeProcessus::class => __("l'étape"),
            Encart::class => __("l'encart"),
            ImageDeFond::class => __("l'image de fond"),
            CommuneDuBandeau::class => __('la commune'),
            ReglageDeSection::class => __("l'en-tête de section"),
            Referentiel::class => __('le référentiel'),
            EntreeDeMenu::class => __("l'entrée de menu"),
            User::class => __('le compte'),
            default => __('le contenu'),
        };
    }

    /** Verbe affiche, au feminin ou masculin selon la famille. */
    public function verbe(): string
    {
        return match ($this->action) {
            self::CREATION => __('créé'),
            self::PUBLICATION => __('publié'),
            self::SUPPRESSION => __('supprimé'),
            default => __('modifié'),
        };
    }

    /**
     * Nom lisible de la famille du sujet.
     *
     * Le nom de classe ne sort jamais vers l'ecran : « App\Models\QuestionFaq »
     * ne dit rien a un editeur.
     */
    public function famille(): string
    {
        return match ($this->sujet_type) {
            Article::class => __('Article'),
            Service::class => __('Service'),
            QuestionFaq::class => __('Question'),
            RubriqueFaq::class => __('Rubrique de FAQ'),
            Temoignage::class => __('Témoignage'),
            MembreEquipe::class => __('Membre'),
            Partenaire::class => __('Partenaire'),
            Valeur::class => __('Valeur'),
            ChiffreCle::class => __('Chiffre clé'),
            EtapeProcessus::class => __('Étape'),
            Encart::class => __('Encart'),
            ImageDeFond::class => __('Image de fond'),
            CommuneDuBandeau::class => __('Commune'),
            ReglageDeSection::class => __('En-tête de section'),
            Referentiel::class => __('Référentiel'),
            EntreeDeMenu::class => __('Entrée de menu'),
            User::class => __('Compte'),
            default => __('Contenu'),
        };
    }

    /** Icone de la famille, pour la liste du tableau de bord. */
    public function icone(): string
    {
        return match ($this->sujet_type) {
            Article::class => 'document',
            QuestionFaq::class, RubriqueFaq::class => 'question',
            Temoignage::class => 'guillemets',
            MembreEquipe::class, User::class => 'personne',
            default => 'grille',
        };
    }

    /**
     * Route d'edition du sujet, si elle existe encore et si le sujet aussi.
     *
     * Rend null pour un element supprime : la ligne du journal reste lisible,
     * mais elle ne propose pas d'ouvrir ce qui n'existe plus.
     */
    public function lienDEdition(): ?string
    {
        if ($this->action === self::SUPPRESSION || ! $this->sujet_id) {
            return null;
        }

        $route = match ($this->sujet_type) {
            Article::class => 'admin.articles.edition',
            Service::class => 'admin.services.edition',
            QuestionFaq::class => 'admin.faq.edition',
            RubriqueFaq::class => 'admin.rubriques-faq.edition',
            Temoignage::class => 'admin.temoignages.edition',
            MembreEquipe::class => 'admin.equipe.edition',
            Partenaire::class => 'admin.partenaires.edition',
            Encart::class => 'admin.encarts.edition',
            ImageDeFond::class => 'admin.images-de-fond.edition',
            ReglageDeSection::class => 'admin.reglages-de-section.edition',
            default => null,
        };

        if (! $route) {
            return null;
        }

        // Le sujet a pu disparaitre sans passer par l'ecran — un import, une
        // suppression en cascade. Un lien vers une ligne absente rendrait une
        // page d'erreur au clic.
        return $this->sujet_type::query()->whereKey($this->sujet_id)->exists()
            ? route($route, $this->sujet_id)
            : null;
    }
}
