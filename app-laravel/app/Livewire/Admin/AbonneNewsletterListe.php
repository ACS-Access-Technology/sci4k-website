<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\PorteDesTextesDeBloc;
use App\Models\AbonneNewsletter;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Abonnes a la lettre d'information.
 *
 * L'ecran ne sert qu'a deux choses : voir combien d'adresses ont ete recueillies,
 * et les exporter pour les verser dans l'outil d'envoi. Il n'y a rien a
 * « modifier » chez un abonne — il n'a saisi qu'une adresse.
 *
 * On peut en revanche DESINSCRIRE quelqu'un qui le demande, sans effacer la
 * ligne : garder la trace du retrait est ce qui empeche de le reinscrire par
 * erreur, et c'est aussi ce qu'on doit pouvoir montrer si l'interesse le
 * demande.
 */
#[Layout('layouts.app')]
class AbonneNewsletterListe extends Component
{
    use PorteDesTextesDeBloc;

    /** La section ou vivent les textes de la page de desinscription. */
    public const SECTION = 'newsletter.desinscription';

    /**
     * Les textes de la page ou l'abonne se retire.
     *
     * Elle est servie par le site, hors des sept pages editables : c'est donc
     * ici, sur l'ecran qui gouverne la lettre d'information, que ses mots se
     * changent. Les mettre ailleurs aurait oblige a chercher.
     *
     * Le titre et la description sont ceux que les moteurs reprennent — meme
     * si la page a peu de chances d'y figurer, un texte affiche est un texte
     * modifiable.
     */
    public const TEXTES_DE_LA_DESINSCRIPTION = [
        'meta_titre' => ['intitule' => 'Titre dans l’onglet du navigateur', 'defaut' => 'Se désinscrire de la lettre d’information'],
        'meta_description' => [
            'intitule' => 'Description pour les moteurs',
            'defaut' => 'Retirer votre adresse de la lettre d’information de SCI4K.',
            'long' => true,
        ],
        'titre_page' => ['intitule' => 'Titre affiché en haut de la page', 'defaut' => 'Lettre d’information'],
        'titre_confirmation' => ['intitule' => 'Titre de la demande de confirmation', 'defaut' => 'Confirmer la désinscription'],
        'texte_confirmation' => [
            'intitule' => 'Texte de la demande de confirmation',
            'defaut' => 'Vous êtes sur le point de retirer votre adresse de notre lettre d’information. Cela n’efface aucune demande que vous nous auriez adressée par ailleurs.',
            'long' => true,
        ],
        'libelle_bouton' => ['intitule' => 'Libellé du bouton', 'defaut' => 'Me désinscrire'],
        'libelle_annuler' => ['intitule' => 'Lien pour renoncer', 'defaut' => 'Annuler et revenir à l’accueil'],
        'titre_fait' => ['intitule' => 'Titre après le retrait', 'defaut' => 'C’est fait'],
        'texte_fait' => [
            'intitule' => 'Texte après le retrait',
            'defaut' => 'Votre adresse ne recevra plus notre lettre d’information. Vous pouvez vous réinscrire à tout moment depuis le site.',
            'long' => true,
        ],
        'libelle_retour' => ['intitule' => 'Lien de retour', 'defaut' => 'Retour à l’accueil'],
    ];

    /**
     * Le trait interroge d'ordinaire le module ouvert. Cet ecran n'a pas de
     * modules : il porte une seule liste de textes.
     */
    protected function textesDeclares(): array
    {
        return self::TEXTES_DE_LA_DESINSCRIPTION;
    }

    /**
     * Langue du CONTENU saisi, sans rapport avec celle de l'interface.
     *
     * Propriete PUBLIQUE et non variable de vue : c'est ce qui permet a
     * l'editeur de basculer entre francais et anglais sans quitter l'ecran.
     */
    public string $langueActive = 'fr';

    public function mount(): void
    {
        $this->langueActive = app()->getLocale();
        $this->chargerLesTextes(ReglageDeSection::where('slug', self::SECTION)->first());
    }

    protected function rules(): array
    {
        return $this->reglesDesTextes();
    }

    protected function validationAttributes(): array
    {
        return $this->intitulesDesTextes();
    }

    /** Enregistre les textes de la page de desinscription. */
    public function enregistrerLesTextes(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $this->validate();

        $section = ReglageDeSection::firstOrNew(['slug' => self::SECTION]);
        $this->poserLesTextes($section);
        $section->save();

        $this->chargerLesTextes($section->fresh());

        $this->dispatch('toast', message: __('Textes enregistrés.'), variant: 'success');
    }

    public string $recherche = '';

    /** Inclure les adresses desinscrites. */
    public bool $avecDesinscrits = false;

    public ?string $message = null;

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function basculerLAbonnement(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $abonne = AbonneNewsletter::findOrFail($id);

        $abonne->desinscrit_a = $abonne->estDesinscrit() ? null : now();
        $abonne->save();

        $this->message = $abonne->estDesinscrit()
            ? __('Adresse désinscrite.')
            : __('Adresse réinscrite.');
    }

    /**
     * Exporte les adresses actives au format CSV.
     *
     * Seules les ACTIVES : exporter une adresse desinscrite reviendrait a lui
     * reecrire, ce que la desinscription interdit precisement.
     *
     * Deux colonnes, et la seconde est aussi importante que la premiere. Les
     * lettres partent d'un outil externe, alimente par cet export : c'est donc
     * le SEUL endroit par ou le lien de desinscription peut atteindre le pied
     * des messages. Un export qui ne porte que des adresses produit des envois
     * dont on ne peut pas sortir.
     */
    public function exporter(): StreamedResponse
    {
        abort_unless($this->peutEcrire(), 403);

        $abonnes = AbonneNewsletter::actifs()->orderBy('email')->get();

        return Response::streamDownload(function () use ($abonnes) {
            $sortie = fopen('php://output', 'w');

            // fopen rend FALSE en cas d'echec, et fputcsv sur un false produit
            // une erreur au milieu d'un telechargement deja commence : le
            // navigateur recoit un fichier tronque, sans qu'on sache qu'il
            // l'est. Mieux vaut ne rien envoyer du tout.
            if ($sortie === false) {
                return;
            }

            fputcsv($sortie, ['email', 'lien_desinscription']);

            foreach ($abonnes as $abonne) {
                fputcsv($sortie, [$abonne->email, $abonne->lienDeDesinscription()]);
            }

            fclose($sortie);
        }, 'abonnes-newsletter-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        $abonnes = AbonneNewsletter::query()
            ->when(! $this->avecDesinscrits, fn ($r) => $r->actifs())
            ->when($this->recherche !== '', fn ($r) => $r->where('email', 'like', '%'.trim($this->recherche).'%'))
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return view('livewire.admin.abonne-newsletter-liste', [
            'abonnes' => $abonnes,
            'peutEcrire' => $this->peutEcrire(),
            'description' => ['textes' => self::TEXTES_DE_LA_DESINSCRIPTION],
            'statistiques' => [
                ['intitule' => __('Abonnés actifs'), 'valeur' => AbonneNewsletter::actifs()->count()],
                [
                    'intitule' => __('Désinscrits'),
                    'valeur' => AbonneNewsletter::whereNotNull('desinscrit_a')->count(),
                ],
                [
                    'intitule' => __('Inscrits ce mois'),
                    'valeur' => AbonneNewsletter::where('created_at', '>=', now()->startOfMonth())->count(),
                ],
            ],
        ])->title(__('Abonnés newsletter'));
    }
}
