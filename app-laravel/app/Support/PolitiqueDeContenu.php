<?php

namespace App\Support;

use Illuminate\Support\Facades\Vite;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;
use Livewire\Mechanisms\FrontendAssets\FrontendAssets;

/**
 * La politique de securite du contenu (CSP) d'une reponse.
 *
 * Etablie a partir des ressources que le site charge REELLEMENT, relevees
 * dans les gabarits, main.js, les feuilles de style et resources/js :
 *
 * - tout script vient du site, ou porte le nonce de la requete : les quelques
 *   scripts en ligne (theme avant le premier rendu, numero WhatsApp, bouton
 *   « Répondre », chargeurs des services tiers) le recoivent par @nonce ;
 * - toute feuille de style, toute police, toute image vient du site — plus
 *   data: (deux pictogrammes SVG de style.css) et blob: (l'apercu du recadrage
 *   d'image, dans l'administration) ;
 * - toute connexion (fetch des formulaires, Livewire) et tout envoi de
 *   formulaire reste sur le site ;
 * - aucun cadre, sauf la carte Google de la page contact ; personne n'encadre
 *   le site ; ni object, ni embed, ni <base>.
 *
 * TROIS EXCEPTIONS, et pourquoi :
 *
 * 1. 'unsafe-eval', sur les seules reponses qui chargent Livewire (le
 *    catalogue /biens, l'administration, les pages de compte). Alpine, embarque
 *    par Livewire, evalue ses expressions avec new Function(). Livewire a bien
 *    une version sans evaluation (livewire.csp_safe), mais elle est GLOBALE et
 *    refuse les composants Alpine ecrits en ligne avec des methodes — cles
 *    d'acces, double authentification, notifications de l'administration.
 *    Les pages publiques sans Livewire, elles, n'en recoivent pas.
 *
 * 2. style-src-attr 'unsafe-inline' : les images de fond venues de la base
 *    (services, articles, biens) et l'index d'animation « --i » s'ecrivent en
 *    attribut style, et Livewire les reapplique en mettant a jour la page. Un
 *    attribut style n'execute rien ; les balises <style>, elles, restent sous
 *    nonce — ce sont elles qui permettraient de lire la page par selecteurs.
 *
 * 3. Les services tiers — statistiques, chat, carte — n'ouvrent leurs domaines
 *    que sur une page qui les charge : le gabarit le declare par autoriser().
 *    Activer le chat depuis l'ecran Configuration l'autorise donc du meme coup,
 *    sans lot de code a deployer.
 *
 * Une instance par application, remise a zero au debut de chaque requete par
 * PoseLesEnTetesDeSecurite.
 */
class PolitiqueDeContenu
{
    /**
     * Les domaines de chaque service tiers, selon la documentation de son
     * editeur.
     *
     * @var array<string, array<string, list<string>>>
     */
    public const SERVICES = [
        // Google Analytics 4 : guide CSP de Google (developers.google.com/tag-platform/security/guides/csp).
        'google-analytics' => [
            'script-src' => ['https://*.googletagmanager.com'],
            'img-src' => ['https://*.google-analytics.com', 'https://*.googletagmanager.com'],
            'connect-src' => ['https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://*.googletagmanager.com'],
        ],
        // Chat tawk.to : liste publiee par tawk.to pour une page sous CSP.
        'tawk' => [
            'script-src' => ['https://embed.tawk.to', 'https://cdn.jsdelivr.net'],
            'style-src' => ['https://embed.tawk.to', 'https://fonts.googleapis.com'],
            'img-src' => ['https://*.tawk.to', 'https://cdn.jsdelivr.net', 'https://tawk.link', 'https://s3.amazonaws.com'],
            'font-src' => ['https://embed.tawk.to', 'https://fonts.gstatic.com'],
            'connect-src' => ['https://*.tawk.to', 'wss://*.tawk.to'],
            'frame-src' => ['https://embed.tawk.to', 'https://*.tawk.to'],
        ],
        // La carte de la page contact.
        'google-maps' => [
            'frame-src' => ['https://www.google.com'],
        ],
    ];

    /** @var list<string> */
    protected array $services = [];

    /**
     * Une requete commence : nouveau nonce, aucun service tiers — et un
     * Livewire qui n'a encore rien rendu. Ses indicateurs sont statiques : sans
     * cette remise a zero, une page sans composant heriterait de 'unsafe-eval'
     * parce que la requete PRECEDENTE en avait un (dans un test, ou en mode
     * worker). C'est ce que fait Livewire lui-meme entre deux rendus de test.
     */
    public function nouvelleRequete(): void
    {
        $this->services = [];

        app('livewire')->flushState();

        Vite::useCspNonce();
    }

    /** Une page declare charger un service tiers. */
    public static function autoriser(string $service): void
    {
        $politique = app(self::class);

        if (isset(self::SERVICES[$service]) && ! in_array($service, $politique->services, true)) {
            $politique->services[] = $service;
        }
    }

    public function nonce(): ?string
    {
        return Vite::cspNonce();
    }

    /** L'en-tete sous lequel la politique part : bloquante, ou en observation. */
    public function nomDeLEnTete(): string
    {
        return config('app.csp_observation')
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';
    }

    public function enTete(): string
    {
        $nonce = "'nonce-".$this->nonce()."'";

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", $nonce],
            'style-src' => ["'self'", $nonce],
            'style-src-attr' => ["'unsafe-inline'"],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'font-src' => ["'self'"],
            'connect-src' => ["'self'"],
            'frame-src' => [],
            'object-src' => ["'none'"],
            'base-uri' => ["'none'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        if ($this->livewireEstCharge()) {
            $directives['script-src'][] = "'unsafe-eval'";
        }

        foreach ($this->services as $service) {
            foreach (self::SERVICES[$service] as $directive => $sources) {
                array_push($directives[$directive], ...$sources);
            }
        }

        $this->ouvrirAuServeurDeDeveloppement($directives);

        return collect($directives)
            ->map(fn (array $sources, string $directive) => $directive.' '.($sources === [] ? "'none'" : implode(' ', array_unique($sources))))
            ->implode('; ');
    }

    /**
     * Livewire a-t-il rendu un composant, ou ses scripts, pendant cette
     * requete ? Ses scripts sont injectes APRES les middlewares ; c'est donc
     * ce qu'il a rendu, et non le HTML, qui le dit.
     */
    protected function livewireEstCharge(): bool
    {
        return SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest
            || app(FrontendAssets::class)->hasRenderedScripts;
    }

    /**
     * « npm run dev » sert les ressources depuis son propre serveur. Jamais en
     * production : le fichier public/hot n'existe que pendant qu'il tourne.
     *
     * @param  array<string, list<string>>  $directives
     */
    protected function ouvrirAuServeurDeDeveloppement(array &$directives): void
    {
        if (! Vite::isRunningHot()) {
            return;
        }

        $origine = rtrim(trim((string) file_get_contents(public_path('hot'))), '/');

        if (! str_starts_with($origine, 'http')) {
            return;
        }

        // http://… devient ws://…, https://… devient wss://… : le rechargement
        // a chaud passe par une websocket sur la meme origine.
        $websocket = 'ws'.substr($origine, strlen('http'));

        array_push($directives['script-src'], $origine);
        array_push($directives['style-src'], $origine);
        array_push($directives['font-src'], $origine);
        array_push($directives['connect-src'], $origine, $websocket);
    }
}
