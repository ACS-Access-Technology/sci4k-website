<?php

namespace App\Livewire\Admin;

use App\Mail\EssaiDeMessagerie;
use App\Models\Parametre;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Parametres generaux du site.
 *
 * Cinq onglets, une trentaine de reglages sans rapport les uns avec les
 * autres. Ils sont decrits ici sous forme de tableau, lu par la validation
 * autant que par la vue : une description unique, plutot qu'une dans le
 * composant et une autre dans le gabarit qui pourraient diverger. C'est le
 * meme choix qu'au lot 2 pour les formulaires de bloc.
 */
#[Layout('layouts.app')]
class Configuration extends Component
{
    use WithFileUploads;

    /**
     * Valeurs saisies, par cle de reglage.
     *
     * @var array<string, string>
     */
    public array $valeurs = [];

    /** Onglet ouvert. */
    public string $onglet = 'general';

    /** Fichiers choisis mais pas encore enregistres. */
    public $logo = null;

    public $favicon = null;

    /**
     * Chemins des visuels actuels. Verrouilles : ce sont des proprietes
     * d'etat, jamais saisies, et elles servent de chemin d'effacement.
     */
    #[Locked]
    public ?string $logoActuel = null;

    #[Locked]
    public ?string $faviconActuel = null;

    /** Compte rendu du dernier essai d'envoi. */
    public ?string $resultatEssai = null;

    /** Dossier des visuels d'identite. */
    public const DOSSIER = 'identite';

    /**
     * Les onglets, et les reglages de chacun.
     *
     * Chaque champ : cle => [
     *   'intitule' => texte affiche,
     *   'type'     => 'texte'|'zone'|'liste'|'case'|'secret'|'courriel'|'url'|'nombre',
     *   'regles'   => regles de validation,
     *   'aide'     => precision sous le champ, facultative,
     *   'choix'    => valeurs d'une liste deroulante,
     * ]
     *
     * @return array<string, array{intitule: string, champs: array<string, array<string, mixed>>}>
     */
    public function onglets(): array
    {
        return [
            'general' => [
                'intitule' => __('Général'),
                'champs' => [
                    'nom_du_site' => ['intitule' => __('Nom du site'), 'type' => 'texte', 'regles' => ['required', 'string', 'max:120'], 'defaut' => 'SCI4K'],
                    'slogan' => ['intitule' => __('Slogan'), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:180']],
                    'description_courte' => ['intitule' => __('Description courte'), 'type' => 'zone', 'regles' => ['nullable', 'string', 'max:400'],
                        'aide' => __("Sert de description par défaut quand une page n'en fournit pas.")],
                    'langue_par_defaut' => ['intitule' => __('Langue'), 'type' => 'liste', 'regles' => ['required', 'in:fr,en'],
                        'choix' => ['fr' => __('Français'), 'en' => __('Anglais')], 'defaut' => 'fr'],
                    'fuseau_horaire' => ['intitule' => __('Fuseau horaire'), 'type' => 'liste', 'regles' => ['required', 'timezone'],
                        'choix' => ['Africa/Abidjan' => 'Africa/Abidjan (UTC+0)', 'Europe/Paris' => 'Europe/Paris (UTC+1)'], 'defaut' => 'Africa/Abidjan'],
                    'devise' => ['intitule' => __('Devise'), 'type' => 'liste', 'regles' => ['required', 'in:XOF,EUR,USD'],
                        'choix' => ['XOF' => __('FCFA (XOF)'), 'EUR' => __('Euro'), 'USD' => __('Dollar US')], 'defaut' => 'XOF'],
                    'mode_maintenance' => ['intitule' => __('Activer le mode maintenance'), 'type' => 'case', 'regles' => ['nullable'],
                        'aide' => __("Le site public affiche une page d'attente ; le backoffice reste accessible.")],
                ],
            ],

            'seo' => [
                'intitule' => __('Référencement'),
                'champs' => [
                    'meta_titre' => ['intitule' => __('Titre meta par défaut'), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:70'],
                        'aide' => __('Au-delà de 70 caractères, Google tronque le titre dans ses résultats.')],
                    'meta_description' => ['intitule' => __('Description meta par défaut'), 'type' => 'zone', 'regles' => ['nullable', 'string', 'max:160'],
                        'aide' => __('160 caractères au plus, pour la même raison.')],
                    'google_analytics' => ['intitule' => __('Identifiant Google Analytics'), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:40'],
                        'aide' => __('Laissez vide pour ne poser aucun traceur.')],
                    'search_console' => ['intitule' => __('Identifiant Search Console'), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:120']],
                    'robots_txt' => ['intitule' => __('Fichier robots.txt'), 'type' => 'zone', 'regles' => ['nullable', 'string', 'max:2000']],
                    'autoriser_indexation' => ['intitule' => __("Autoriser l'indexation par les moteurs de recherche"), 'type' => 'case', 'regles' => ['nullable'],
                        'aide' => __("Décoché, le site demande aux moteurs de ne pas l'indexer. À décocher tant que le site n'est pas ouvert au public.")],
                ],
            ],

            'contact' => [
                'intitule' => __('Contact'),
                'champs' => [
                    'telephone' => ['intitule' => __('Téléphone principal'), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:40']],
                    'whatsapp' => ['intitule' => __('WhatsApp'), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:40'],
                        'aide' => __("Numéro au format international, sans espace ni signe — c'est celui vers lequel le formulaire de contact ouvre la conversation.")],
                    'email_public' => ['intitule' => __('Adresse e-mail publique'), 'type' => 'courriel', 'regles' => ['nullable', 'email', 'max:120']],
                    'destinataire_formulaire' => ['intitule' => __('Destinataire du formulaire'), 'type' => 'courriel', 'regles' => ['nullable', 'email', 'max:120'],
                        'aide' => __('Adresse qui reçoit une notification à chaque message. Laissez vide pour ne notifier personne : les messages restent consultables dans le backoffice.')],
                    'adresse_postale' => ['intitule' => __('Adresse postale'), 'type' => 'zone', 'regles' => ['nullable', 'string', 'max:300']],
                    'horaires' => ['intitule' => __('Horaires'), 'type' => 'zone', 'regles' => ['nullable', 'string', 'max:300']],
                    'coordonnees_carte' => ['intitule' => __('Coordonnées de la carte'), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:60'],
                        'aide' => __('Latitude et longitude séparées par une virgule. Exemple : 5.3600, -3.9800')],
                ],
            ],

            'social' => [
                'intitule' => __('Réseaux sociaux'),
                'champs' => [
                    'facebook' => ['intitule' => 'Facebook', 'type' => 'url', 'regles' => ['nullable', 'url', 'max:200']],
                    'instagram' => ['intitule' => 'Instagram', 'type' => 'url', 'regles' => ['nullable', 'url', 'max:200']],
                    'linkedin' => ['intitule' => 'LinkedIn', 'type' => 'url', 'regles' => ['nullable', 'url', 'max:200']],
                    'youtube' => ['intitule' => 'YouTube', 'type' => 'url', 'regles' => ['nullable', 'url', 'max:200']],
                    'boutons_partage' => ['intitule' => __('Afficher les boutons de partage sur les articles'), 'type' => 'case', 'regles' => ['nullable']],
                ],
            ],

            'messagerie' => [
                'intitule' => __('Messagerie'),
                'champs' => [
                    'smtp_hote' => ['intitule' => __('Serveur SMTP'), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:160']],
                    'smtp_port' => ['intitule' => __('Port'), 'type' => 'nombre', 'regles' => ['nullable', 'integer', 'min:1', 'max:65535']],
                    'smtp_chiffrement' => ['intitule' => __('Chiffrement'), 'type' => 'liste', 'regles' => ['nullable', 'in:tls,ssl,'],
                        'choix' => ['tls' => 'TLS', 'ssl' => 'SSL', '' => __('Aucun')]],
                    'smtp_identifiant' => ['intitule' => __('Identifiant'), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:160']],
                    'smtp_mot_de_passe' => ['intitule' => __('Mot de passe'), 'type' => 'secret', 'regles' => ['nullable', 'string', 'max:200'],
                        'aide' => __("Laissez vide pour conserver le mot de passe actuel. Il n'est jamais réaffiché : une fois enregistré, seul son remplacement est possible.")],
                    'expediteur_nom' => ['intitule' => __("Nom de l'expéditeur"), 'type' => 'texte', 'regles' => ['nullable', 'string', 'max:120']],
                    'expediteur_adresse' => ['intitule' => __("Adresse de l'expéditeur"), 'type' => 'courriel', 'regles' => ['nullable', 'email', 'max:160']],
                ],
            ],
        ];
    }

    /**
     * Tous les champs, tous onglets confondus.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function champs(): array
    {
        $tous = [];

        foreach ($this->onglets() as $onglet) {
            $tous += $onglet['champs'];
        }

        return $tous;
    }

    protected function peutEcrire(): bool
    {
        // La configuration touche au referencement, au mode maintenance et aux
        // identifiants d'envoi. Contrairement aux ecrans de contenu, elle est
        // reservee aux administrateurs — la maquette le dit aussi : « Acces
        // complet, y compris la configuration et les utilisateurs ».
        return (bool) auth()->user()?->hasRole('administrateur');
    }

    public function mount(): void
    {
        abort_unless($this->peutEcrire(), 403);

        foreach ($this->champs() as $cle => $description) {
            // Un secret n'est jamais charge dans le formulaire : le renvoyer
            // au navigateur le deposerait dans le HTML de la page.
            // Le defaut declare vaut pour une installation neuve : sans lui,
            // les trois listes obligatoires partiraient vides et le PREMIER
            // enregistrement echouerait sur des champs que l'administrateur
            // n'a jamais touches.
            $this->valeurs[$cle] = ($description['type'] ?? '') === 'secret'
                ? ''
                : (string) (Parametre::lire($cle, $description['defaut'] ?? '') ?? '');
        }

        $this->logoActuel = Parametre::lire('logo');
        $this->faviconActuel = Parametre::lire('favicon');
    }

    /** Un mot de passe SMTP est-il deja enregistre ? */
    public function secretEnregistre(): bool
    {
        return (string) Parametre::lire('smtp_mot_de_passe', '') !== '';
    }

    protected function rules(): array
    {
        $regles = [];

        foreach ($this->champs() as $cle => $description) {
            $regles['valeurs.'.$cle] = $description['regles'] ?? ['nullable', 'string', 'max:255'];
        }

        $regles['logo'] = ['nullable', 'image', 'max:2048'];
        // Le favicon accepte aussi l'ICO, que la regle « image » refuse.
        $regles['favicon'] = ['nullable', 'file', 'mimes:png,ico,svg', 'max:512'];

        return $regles;
    }

    protected function validationAttributes(): array
    {
        $intitules = [];

        foreach ($this->champs() as $cle => $description) {
            $intitules['valeurs.'.$cle] = mb_strtolower($description['intitule'] ?? $cle);
        }

        return $intitules + ['logo' => __('le logo'), 'favicon' => __('le favicon')];
    }

    public function enregistrer(): void
    {
        // La route protege l'ecran, pas l'action : Livewire ne rejoue pas le
        // middleware de role sur /livewire/update.
        abort_unless($this->peutEcrire(), 403);

        $this->validate();

        $groupes = [];

        foreach ($this->onglets() as $nom => $onglet) {
            foreach (array_keys($onglet['champs']) as $cle) {
                $groupes[$cle] = $nom;
            }
        }

        foreach ($this->champs() as $cle => $description) {
            $valeur = $this->valeurs[$cle] ?? '';

            // Un champ secret laisse vide CONSERVE la valeur enregistree : le
            // formulaire ne peut pas la reafficher, donc un vide ne veut pas
            // dire « efface », il veut dire « je n'y touche pas ».
            if (($description['type'] ?? '') === 'secret' && $valeur === '') {
                continue;
            }

            Parametre::poser($cle, $valeur, $groupes[$cle] ?? 'general');
        }

        foreach (['logo', 'favicon'] as $visuel) {
            if ($this->$visuel) {
                $ancien = Parametre::lire($visuel);
                $chemin = $this->$visuel->store(self::DOSSIER, 'public');

                Parametre::poser($visuel, 'storage/'.$chemin, 'general');
                $this->effacerSiTeleverse($ancien);

                $this->{$visuel.'Actuel'} = 'storage/'.$chemin;
                $this->$visuel = null;
            }
        }

        $this->dispatch('toast', message: __('Configuration enregistrée.'), variant: 'success');
    }

    /**
     * Envoie un message d'essai a l'administrateur connecte.
     *
     * Le destinataire n'est PAS saisissable, et c'est deliberé : un champ
     * libre ferait de l'ecran un relais d'envoi vers n'importe quelle adresse,
     * signe du nom de domaine du site. L'essai part vers l'adresse du compte
     * qui clique — la seule que l'on sait deja lui appartenir.
     *
     * Les reglages doivent avoir ete ENREGISTRES avant : l'essai vaut pour ce
     * que le serveur utilisera vraiment, pas pour ce qui est affiche a
     * l'ecran.
     */
    public function envoyerUnEssai(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $destinataire = auth()->user()?->email;

        if (! $destinataire) {
            $this->resultatEssai = __("Votre compte n'a pas d'adresse e-mail.");

            return;
        }

        if (! Parametre::lire('smtp_hote')) {
            $this->resultatEssai = __('Renseignez puis enregistrez un serveur SMTP avant de tester.');

            return;
        }

        try {
            Mail::to($destinataire)->send(
                new EssaiDeMessagerie((string) Parametre::lire('nom_du_site', 'SCI4K')),
            );

            $this->resultatEssai = __('Message envoyé à :adresse.', ['adresse' => $destinataire]);
        } catch (\Throwable $e) {
            // Le message du serveur est repris tel quel : « Connection could
            // not be established » dit ou chercher, la ou « échec de l'envoi »
            // laisse l'editeur sans piste.
            $this->resultatEssai = __("Échec de l'envoi : :raison", ['raison' => $e->getMessage()]);
        }
    }

    /**
     * Efface un visuel remplace, s'il vient bien de notre dossier.
     *
     * Le controle du prefixe ET des segments de remontee reprend celui pose
     * sur les images de service au lot 2 : le seul prefixe laissait passer
     * « storage/identite/../../autre.jpg », que Flysystem resout en un fichier
     * bien reel hors du dossier.
     */
    protected function effacerSiTeleverse(?string $chemin): void
    {
        $chemin = (string) $chemin;

        if (! str_starts_with($chemin, 'storage/'.self::DOSSIER.'/')) {
            return;
        }

        $relatif = substr($chemin, strlen('storage/'));

        if (in_array('..', explode('/', $relatif), true)) {
            return;
        }

        Storage::disk('public')->delete($relatif);
    }

    public function render(): View
    {
        return view('livewire.admin.configuration', [
            'onglets' => $this->onglets(),
        ])->title(__('Configuration'));
    }
}
