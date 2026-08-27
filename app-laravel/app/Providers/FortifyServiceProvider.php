<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->refuserLesComptesInactifs();
        $this->noterLaDerniereConnexion();
    }

    /**
     * Un compte desactive ne se connecte plus.
     *
     * Le controle est pose sur l'AUTHENTIFICATION elle-meme, et non sur une
     * page ou un middleware d'administration : desactiver un compte doit lui
     * fermer la porte, pas seulement lui cacher les meubles. Sans cela un
     * employe parti gardait un acces valide a toute route non protegee.
     *
     * Le message reste le meme que pour un mot de passe faux. Distinguer
     * « compte desactive » de « identifiants incorrects » dirait a un inconnu
     * quelles adresses existent dans la maison.
     */
    private function refuserLesComptesInactifs(): void
    {
        Fortify::authenticateUsing(function (Request $requete) {
            $utilisateur = User::where('email', $requete->email)->first();

            if (! $utilisateur || ! Hash::check($requete->password, $utilisateur->password)) {
                return null;
            }

            return $utilisateur->peutSeConnecter() ? $utilisateur : null;
        });
    }

    /**
     * Note la date de connexion, pour la colonne « Derniere connexion ».
     *
     * Elle sert a reperer les comptes qui ne servent plus : un acces oublie est
     * un acces ouvert. Une invitation acceptee passe du meme coup en « actif » —
     * c'est la premiere connexion qui le prouve, pas l'envoi du courriel.
     */
    private function noterLaDerniereConnexion(): void
    {
        Event::listen(Login::class, function (Login $evenement) {
            $utilisateur = $evenement->user;

            if (! $utilisateur instanceof User) {
                return;
            }

            $champs = ['derniere_connexion_a' => now()];

            if ($utilisateur->statut === User::INVITE) {
                $champs['statut'] = User::ACTIF;
            }

            $utilisateur->forceFill($champs)->saveQuietly();
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
