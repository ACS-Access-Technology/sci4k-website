<?php

namespace App\Providers;

use App\Models\Parametre;
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
        $this->composerLePiedDePage();
        $this->appliquerLaMessagerieEnregistree();
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
            $vue->with('servicesDuPied', Service::visibles()->ordonnees()->get());
        });
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
