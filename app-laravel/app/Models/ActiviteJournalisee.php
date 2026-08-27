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
