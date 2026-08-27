<?php

namespace App\Providers;

use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
            \App\Services\Traduction\Traducteur::class,
            fn () => new \App\Services\Traduction\TraducteurDeepL(config('services.deepl.key')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->composerLePiedDePage();
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
