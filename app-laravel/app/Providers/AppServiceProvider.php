<?php

namespace App\Providers;

use App\Livewire\Admin\Menus;
use App\Models\EntreeDeMenu;
use App\Models\ImageDeFond;
use App\Models\Parametre;
use App\Models\ReglageDeSection;
use App\Models\Service;
use App\Services\Traduction\Traducteur;
use App\Services\Traduction\TraducteurDeepL;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            Traducteur::class,
            fn () => new TraducteurDeepL(config('services.deepl.key')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->appliquerLeFuseauHoraire();
        $this->composerLePiedDePage();
        $this->appliquerLaMessagerieEnregistree();
    }

    /**
     * Applique le fuseau horaire saisi dans la configuration.
     *
     * Il y etait propose depuis le debut, avec deux choix, et n'etait lu nulle
     * part : l'application restait sur celui de config/app.php. Toutes les
     * dates affichees — publication d'un article, creneau d'une visite,
     * horodatage du journal — s'ecartaient donc de l'heure d'Abidjan des que le
     * serveur etait ailleurs.
     *
     * Le reglage est applique a Carbon ET a la configuration : la premiere
     * gouverne l'affichage, la seconde ce que Laravel ecrit en base.
     */
    protected function appliquerLeFuseauHoraire(): void
    {
        $fuseau = (string) $this->parametre('fuseau_horaire', '');

        // Un fuseau inconnu ferait tomber l'application entiere au demarrage,
        // pour un champ de formulaire. On l'ignore plutot.
        if ($fuseau === '' || ! in_array($fuseau, timezone_identifiers_list(), true)) {
            return;
        }

        config(['app.timezone' => $fuseau]);
        date_default_timezone_set($fuseau);
    }

    /**
     * Applique les reglages SMTP saisis dans la configuration.
     *
     * Sans cela l'ecran de configuration serait un ecran menteur de plus : il
     * enregistrerait un serveur, un port et un mot de passe que rien
     * n'emploierait, et l'editeur croirait avoir branche sa messagerie. Les
     * valeurs du fichier .env restent le defaut ; la base ne prend le dessus
     * que si un serveur y est renseigne.
     *
     * Le garde-fou sur l'existence de la table n'est pas decoratif : ce
     * provider tourne AUSSI pendant « migrate » sur une base encore vide, et
     * pendant les tests avant que les migrations ne soient jouees.
     */
    protected function appliquerLaMessagerieEnregistree(): void
    {
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            // Les commandes d'installation et de migration n'ont pas besoin de
            // la messagerie, et la table peut ne pas exister encore.
            return;
        }

        try {
            if (! Schema::hasTable('parametres')) {
                return;
            }

            $hote = Parametre::lire('smtp_hote');

            if (! $hote) {
                return;
            }

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $hote,
                'mail.mailers.smtp.port' => (int) Parametre::lire('smtp_port', 587),
                'mail.mailers.smtp.encryption' => Parametre::lire('smtp_chiffrement') ?: null,
                'mail.mailers.smtp.username' => Parametre::lire('smtp_identifiant'),
                'mail.mailers.smtp.password' => Parametre::lire('smtp_mot_de_passe'),
                'mail.from.address' => Parametre::lire('expediteur_adresse', config('mail.from.address')),
                'mail.from.name' => Parametre::lire('expediteur_nom', config('mail.from.name')),
            ]);
        } catch (\Throwable) {
            // Une base injoignable ne doit pas empecher l'application de
            // demarrer : on retombe alors sur la configuration du fichier.
        }
    }

    /**
     * Alimente la colonne « Nos Services » du pied de page depuis la base.
     *
     * Le pied listait les six services en dur. Depuis que l'administration
     * peut en ajouter et en retirer, cette liste figee mentirait deux fois :
     * un service cree n'y apparaitrait pas, et un service supprime y
     * laisserait un lien vers une ancre qui n'existe plus.
     *
     * Le composer plutot qu'une variable passee par chaque controleur : le
     * pied est inclus par la mise en page commune, donc par toutes les pages
     * publiques, y compris celles qui ne parlent pas de services.
     */
    protected function composerLePiedDePage(): void
    {
        View::composer('public.partials.pied', function ($vue) {
            $vue->with([
                'chrome' => $this->textesDeLHabillage(),
                'servicesDuPied' => Service::visibles()->ordonnees()->get(),
                'menuPiedNavigation' => $this->entreesDeMenu('pied_navigation'),
                'menuPiedLegal' => $this->entreesDeMenu('pied_legal'),
                'langueDuSite' => app()->getLocale(),
                'nomDuSite' => $this->parametre('nom_du_site', 'SCI4K'),
                'logoPublic' => $this->parametre('logo', 'images/image (3).png'),
                'descriptionCourte' => $this->parametre('description_courte', __('Société Civile Immobilière basée à Abidjan — Cocody, Cité des Arts. Achat, vente, location, construction et gestion de patrimoine immobilier.')),
                'liensSociaux' => $this->liensSociaux(),
                'copyrightPublic' => $this->parametre('copyright', __('© :annee SCI4K — Tous droits réservés.', ['annee' => now()->year])),
                'sousTitrePied' => $this->parametre('sous_titre_pied', __("Société Civile Immobilière — Abidjan, Côte d'Ivoire")),
                // Les coordonnees viennent de la configuration. Le repli sur le
                // texte d'origine couvre une base pas encore renseignee : un
                // pied de page sans adresse serait pire qu'un pied de page
                // portant l'ancienne.
                // Le repli reprend les TROIS cles existantes plutot qu'une
                // seule chaine a sauts de ligne : le controle des traductions
                // lit le texte source, ou « \n » compte pour deux caracteres,
                // et ne retrouverait jamais la cle resolue. Le gabarit le
                // disait deja — je venais de refaire l'erreur qu'il decrit.
                'adressePostale' => Parametre::lire('adresse_postale', implode("\n", [
                    __('Cocody, Cité des Arts'),
                    __('Résidence Paon, 3ème étage'),
                    __("Abidjan, Côte d'Ivoire"),
                ])),
                'telephonePublic' => Parametre::lire('telephone', '+225 07 06 16 50 29'),
                'emailPublic' => Parametre::lire('email_public', 'contact@sci4k.com'),
            ]);
        });

        View::composer('public.partials.entete', function ($vue) {
            $vue->with([
                'chrome' => $this->textesDeLHabillage(),
                'menuPrincipal' => $this->entreesDeMenu('principal'),
                'nomDuSite' => $this->parametre('nom_du_site', 'SCI4K'),
                'logoPublic' => $this->parametre('logo', 'images/image (3).png'),
                'ctaHeaderActif' => $this->parametreActif('cta_header_actif', true),
                'ctaHeaderLibelle' => $this->parametre('cta_header_libelle_'.app()->getLocale(), __('Nous contacter')),
                'ctaHeaderUrl' => $this->parametre('cta_header_url', route('contact.index')),
            ]);
        });

        View::composer(['public.layout', 'public.layout-livewire'], function ($vue) {
            $logo = $this->parametre('logo', 'images/image (3).png');
            $favicon = $this->parametre('favicon', $logo);

            // La description affichee dans le pied de page et celle que Google
            // reprend sous le lien ne poursuivent pas le meme but : la premiere
            // se lit, la seconde doit tenir en 160 caracteres. Les deux
            // reglages existaient donc bel et bien, mais un seul etait lu — le
            // champ « Description meta par défaut » ne servait a rien.
            //
            // Il PRIME desormais, et retombe sur la description courte quand
            // l'editeur n'en a pas saisi : une installation qui n'a rempli
            // qu'un champ garde le comportement qu'elle avait.
            $descriptionCourte = $this->parametre('description_courte', __("Société Civile Immobilière à Abidjan : achat, vente, location, construction et gestion de patrimoine immobilier."));

            $vue->with([
                'nomDuSite' => $this->parametre('nom_du_site', 'SCI4K'),
                'descriptionSite' => $this->parametre('meta_description') ?: $descriptionCourte,
                // Le titre que porte une page sans titre a elle. Le gabarit
                // affichait « — SCI4K », precede d'un vide.
                'titreParDefaut' => $this->parametre('meta_titre', ''),
                'logoPublic' => $logo,
                'faviconPublic' => $favicon,
                'googleAnalytics' => $this->parametre('google_analytics'),
                'searchConsole' => $this->parametre('search_console'),
                'autoriserIndexation' => $this->parametreActif('autoriser_indexation', true),
                'variablesImagesDeFond' => $this->variablesImagesDeFond(),
                'tawkActif' => $this->parametreActif('chat_actif', false),
                'tawkIdentifiant' => $this->parametre('tawk_identifiant'),
            ]);
        });

        View::composer('public.partials.flottants', function ($vue) {
            $chrome = $this->textesDeLHabillage();

            $numero = preg_replace('/\D+/', '', (string) $this->parametre('whatsapp', '2250706165029'));

            $vue->with([
                'whatsappPublic' => $numero ?: '2250706165029',
                'whatsappMessage' => $this->parametre('whatsapp_message_'.app()->getLocale(), __('Bonjour SCI4K, je souhaite avoir des informations.')),
                'chatActif' => $this->parametreActif('chat_actif', false),
                'chrome' => $chrome,
            ]);
        });

        // Le formulaire « poser une question » de la FAQ ouvre lui aussi une
        // conversation WhatsApp, depuis assets/main.js, qui lit
        // window.SCI4K_WHATSAPP. Seule la page Contact posait cette variable :
        // sur /faq, main.js retombait sur le numero qu'il porte en dur et le
        // reglage du backoffice restait sans effet sur ce seul formulaire.
        View::composer('public.faq', function ($vue) {
            $numero = preg_replace('/\D+/', '', (string) $this->parametre('whatsapp', '2250706165029'));

            $vue->with('whatsappPublic', $numero ?: '2250706165029');
        });
    }

    /**
     * Les entrees visibles d'un menu, dans l'ordre.
     *
     * Rend une collection VIDE si la table n'existe pas encore — pendant une
     * migration, ou sur une base fraichement clonee. Le site doit rester
     * servable dans cet etat : un menu absent degrade la navigation, une
     * exception rend la page entiere introuvable.
     */
    protected function entreesDeMenu(string $menu)
    {
        try {
            return EntreeDeMenu::duMenu($menu)->visibles()->ordonnees()->get();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function parametre(string $cle, mixed $defaut = null): mixed
    {
        try {
            return Schema::hasTable('parametres') ? Parametre::lire($cle, $defaut) : $defaut;
        } catch (\Throwable) {
            return $defaut;
        }
    }

    /**
     * La section qui porte les textes de l'en-tete, du pied et des boutons
     * flottants, editee depuis l'ecran « Menus ».
     *
     * Elle est lue une fois par requete et non une fois par partiel : les trois
     * l'emploient, et trois requetes pour une meme ligne n'auraient rien
     * apporte.
     */
    protected function textesDeLHabillage(): ?ReglageDeSection
    {
        // PAS de memoisation statique ici. Une variable `static` vit aussi
        // longtemps que le processus, pas que la requete : elle figeait la
        // premiere lecture — un `null` sur une base encore vide — et l'editeur
        // ne voyait plus jamais ses textes apparaitre. Trois lectures d'une
        // ligne indexee coutent moins cher qu'un cache qui ment.
        try {
            return Schema::hasTable('reglages_de_section')
                ? ReglageDeSection::where('slug', Menus::SECTION)->first()
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parametreActif(string $cle, bool $defaut = false): bool
    {
        try {
            return Schema::hasTable('parametres') ? Parametre::actif($cle, $defaut) : $defaut;
        } catch (\Throwable) {
            return $defaut;
        }
    }

    protected function liensSociaux(): array
    {
        return collect(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube'])
            ->map(fn ($intitule, $cle) => ['intitule' => $intitule, 'url' => $this->parametre($cle)])
            ->filter(fn ($lien) => filled($lien['url']))
            ->values()
            ->all();
    }

    protected function variablesImagesDeFond(): array
    {
        try {
            if (! Schema::hasTable('images_de_fond')) {
                return [];
            }

            return ImageDeFond::query()
                ->where('visible', true)
                ->whereNotNull('fichier')
                ->orderBy('ordre')
                ->get(['slug', 'fichier'])
                ->mapWithKeys(fn (ImageDeFond $image) => [$image->slug => asset($image->fichier)])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
