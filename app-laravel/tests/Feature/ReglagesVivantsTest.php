<?php

use App\Livewire\Admin\Configuration;
use App\Models\Bien;
use App\Models\Parametre;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Spatie\Permission\Models\Role;
use Symfony\Component\Finder\Finder;

/*
 * Les reglages doivent FAIRE quelque chose.
 *
 * L'audit a compte huit champs de l'ecran « Configuration » qui n'etaient lus
 * nulle part : slogan, langue par defaut, fuseau horaire, devise, mode
 * maintenance, titre meta, description meta, robots.txt. L'editeur pouvait les
 * remplir, les enregistrer, les relire — le site ne changeait pas d'un octet.
 *
 * C'est le pire genre de defaut : il ne casse rien, il ment. Personne ne
 * signale un reglage qui « ne marche pas », on suppose qu'on s'y est mal pris.
 *
 * Ces tests mesurent au bout de la chaine : ce que le VISITEUR voit, et non ce
 * que la table des reglages contient.
 */
beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/*
 * Le garde-fou. Il ne nomme aucun champ : il parcourt la DECLARATION de
 * l'ecran et exige que chaque cle soit lue quelque part dans le code. Un
 * neuvieme reglage mort le ferait tomber sans que personne ait pense a
 * l'ajouter ici.
 */
it('ne declare aucun reglage que personne ne lit', function () {
    $declarees = [];

    foreach ((new Configuration)->onglets() as $onglet) {
        foreach (array_keys($onglet['champs']) as $cle) {
            $declarees[] = $cle;
        }
    }

    $sources = '';

    foreach (['app', 'resources/views', 'routes'] as $dossier) {
        foreach (Finder::create()->files()->in(base_path($dossier))->name('*.php') as $fichier) {
            // La declaration elle-meme ne compte pas : c'est precisement ce
            // qu'on veut confronter au reste du code.
            if (! str_ends_with($fichier->getPathname(), 'Admin/Configuration.php')) {
                $sources .= $fichier->getContents();
            }
        }
    }

    // Certaines cles sont CONSTRUITES : `whatsapp_message_'.app()->getLocale()`
    // et `cta_header_libelle_'.$langue`. Chercher la cle entiere ne les
    // trouverait jamais, et le garde-fou accuserait un reglage bien vivant. On
    // accepte donc aussi le prefixe, jusqu'au dernier souligne.
    $lue = function (string $cle) use ($sources): bool {
        if (str_contains($sources, "'".$cle."'")) {
            return true;
        }

        $prefixe = $cle;

        while (($coupe = strrpos($prefixe, '_')) !== false) {
            $prefixe = substr($prefixe, 0, $coupe);

            if (str_contains($sources, "'".$prefixe."_'")) {
                return true;
            }
        }

        return false;
    };

    $mortes = array_values(array_filter($declarees, fn (string $cle) => ! $lue($cle)));

    expect($mortes)->toBe([], 'Ces reglages sont proposes a l’editeur mais lus nulle part : '.implode(', ', $mortes));
});

/* ------------------------------------------------ un par un, au bout du fil */

it('applique la devise aux prix', function () {
    $bien = Bien::factory()->create(['prix' => 250000, 'prix_unite' => 'total']);

    Parametre::poser('devise', 'XOF', 'general');
    expect($bien->prixFormate())->toContain('FCFA');

    Parametre::poser('devise', 'EUR', 'general');
    expect($bien->fresh()->prixFormate())->toContain('€')
        ->and($bien->fresh()->prixFormate())->not->toContain('FCFA');

    // Le dollar s'ecrit AVANT le montant, contrairement aux deux autres.
    Parametre::poser('devise', 'USD', 'general');
    expect($bien->fresh()->prixFormate())->toStartWith('$');
});

/*
 * La langue par defaut a CHANGE DE PORTEE.
 *
 * Le site public prend desormais sa langue dans l'adresse — /services et
 * /en/services — parce qu'une session ne se partage pas et qu'un moteur de
 * recherche n'en a pas. Ce reglage decide donc de la langue d'un compte qui
 * n'a pas encore choisi la sienne, dans le backoffice ; son intitule le dit.
 */
it('applique la langue par defaut a qui n a rien choisi', function () {
    Parametre::poser('langue_par_defaut', 'en', 'general');

    // Une route sans version anglaise : le backoffice, ou la session decide.
    $this->actingAs($this->admin)->get('/dashboard')->assertOk();

    expect(app()->getLocale())->toBe('en');
});

it('laisse l adresse primer sur la langue par defaut, sur le site public', function () {
    Parametre::poser('langue_par_defaut', 'en', 'general');

    // Une page publique NUE est francaise, quoi qu'en dise le reglage ou la
    // session : sans cela, l'adresse ne voudrait plus rien dire et les deux
    // versions serviraient le meme contenu.
    $this->withSession(['langue' => 'en'])->get('/')->assertOk();

    expect(app()->getLocale())->toBe('fr');
});

/**
 * Rejoue l'etape de demarrage qui applique le fuseau.
 *
 * Le fournisseur de services l'a deja executee AVANT que le test ne pose le
 * reglage. Relancer l'application entiere emporterait la base SQLite en
 * memoire, donc la table des reglages : on rappelle la seule methode
 * concernee, sur le fournisseur deja enregistre.
 */
function rejouerLeFuseau(): void
{
    $methode = new ReflectionMethod(AppServiceProvider::class, 'appliquerLeFuseauHoraire');
    $methode->invoke(app()->getProvider(AppServiceProvider::class));
}

it('applique le fuseau horaire', function () {
    Parametre::poser('fuseau_horaire', 'Europe/Paris', 'general');

    rejouerLeFuseau();

    expect(config('app.timezone'))->toBe('Europe/Paris');
});

it('ignore un fuseau horaire fantaisiste au lieu de tomber', function () {
    $avant = config('app.timezone');

    Parametre::poser('fuseau_horaire', 'Mars/Olympus', 'general');

    // Un fuseau inconnu ferait tomber l'application entiere au demarrage, pour
    // un champ de formulaire mal rempli.
    rejouerLeFuseau();

    expect(config('app.timezone'))->toBe($avant);
    $this->get('/')->assertOk();
});

it('applique le titre et la description meta', function () {
    Parametre::poser('meta_titre', 'Immobilier à Abidjan', 'seo');
    Parametre::poser('meta_description', 'Achat, vente et gestion de biens à Abidjan.', 'seo');

    // Le catalogue des biens est la seule page publique qui n'annonce ni titre
    // ni description a elle : c'est donc la que les valeurs par defaut se
    // voient.
    $corps = $this->get(route('biens.index'))->assertOk()->getContent();

    expect($corps)->toContain('Achat, vente et gestion de biens à Abidjan.')
        ->and($corps)->toContain('Immobilier à Abidjan');
});

it('retombe sur la description courte quand la description meta est vide', function () {
    // Une installation qui n'a rempli qu'un champ garde le comportement
    // qu'elle avait.
    Parametre::poser('meta_description', '', 'seo');
    Parametre::poser('description_courte', 'Agence immobilière à Cocody.', 'general');

    expect($this->get(route('biens.index'))->assertOk()->getContent())
        ->toContain('Agence immobilière à Cocody.');
});

it('n affiche plus un titre qui commence par un tiret', function () {
    // Le gabarit ecrivait « @yield('titre') — SCI4K » : une page sans titre a
    // elle s'annonçait « — SCI4K », precede d'un blanc.
    Parametre::poser('meta_titre', '', 'seo');

    $corps = $this->get('/')->assertOk()->getContent();

    expect($corps)->not->toContain('<title> — ');
});

/* ------------------------------------------------ mode maintenance */

it('ferme le site public quand la maintenance est activee', function () {
    Parametre::poser('mode_maintenance', '1', 'general');

    // 503 et non 200 : une page d'attente servie en 200 dit aux moteurs
    // « voici le contenu de ce site », et ils la gardent.
    $this->get('/')->assertStatus(503)->assertSee('Nous revenons très vite');
});

it('laisse la connexion et le backoffice ouverts pendant la maintenance', function () {
    Parametre::poser('mode_maintenance', '1', 'general');

    // Sans quoi l'administrateur qui vient de cocher la case s'enfermerait
    // dehors, sans aucun moyen de decocher.
    $this->get('/login')->assertOk();
    $this->actingAs($this->admin)->get('/dashboard')->assertOk();
});

it('laisse un compte connecte relire le site pendant la maintenance', function () {
    Parametre::poser('mode_maintenance', '1', 'general');

    // C'est souvent la raison meme d'avoir declare les travaux.
    $this->actingAs($this->admin)->get('/')->assertOk();
});

it('laisse robots.txt repondre pendant la maintenance', function () {
    Parametre::poser('mode_maintenance', '1', 'general');

    // Pour que les moteurs lisent le refus d'indexation au lieu d'enregistrer
    // une page d'attente a la place de l'accueil.
    $this->get('/robots.txt')->assertOk();
    $this->get('/sitemap.xml')->assertOk();
});

it('rouvre le site quand la case est decochee', function () {
    Parametre::poser('mode_maintenance', '0', 'general');

    $this->get('/')->assertOk();
});

/* ------------------------------------------------ le champ retire */

it('ne propose plus le slogan', function () {
    $cles = [];

    foreach ((new Configuration)->onglets() as $onglet) {
        $cles = array_merge($cles, array_keys($onglet['champs']));
    }

    // « Sous-titre du pied de page » fait ce travail, lui, pour de vrai. Deux
    // champs pour une meme phrase, dont un sans effet, n'auraient produit que
    // des questions.
    expect($cles)->not->toContain('slogan')
        ->and($cles)->toContain('sous_titre_pied');
});
