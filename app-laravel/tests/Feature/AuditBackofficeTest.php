<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/**
 * Audit : chaque ecran du backoffice repond, et chaque composant Livewire se
 * monte.
 *
 * Un test par ecran aurait dit la meme chose ; celui-ci parcourt la table des
 * routes, si bien qu'un ecran ajoute demain y entre sans qu'on y pense.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'redacteur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('sert chaque ecran du backoffice a un administrateur', function () {
    $echecs = [];

    foreach (Route::getRoutes() as $route) {
        $nom = $route->getName();

        if (! $nom || ! str_starts_with($nom, 'admin.')) {
            continue;
        }

        // Les redirections et les adresses a parametre sortent du lot : les
        // premieres n'ont rien a rendre, les secondes n'existent plus.
        if (! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')) {
            continue;
        }

        // /admin redirige vers /dashboard : elle n'a pas d'ecran a elle.
        if ($nom === 'admin.tableau-de-bord') {
            continue;
        }

        $reponse = $this->actingAs($this->admin)->get('/'.$route->uri());

        if ($reponse->getStatusCode() !== 200) {
            $echecs[] = $nom.' ('.$route->uri().') → '.$reponse->getStatusCode();
        }
    }

    expect($echecs)->toBe([], "Ecrans qui ne repondent pas 200 :\n".implode("\n", $echecs));
});

it('sert aussi le tableau de bord et les pages editables', function () {
    foreach (['/dashboard', '/admin/pages-editables'] as $adresse) {
        $this->actingAs($this->admin)->get($adresse)->assertOk();
    }
});

/**
 * Chaque composant Livewire de l'administration doit se monter.
 *
 * Ceux qui attendent un modele sont montes avec une ligne fabriquee : un
 * composant qui ne se monte pas est un ecran mort, que la table des routes ne
 * signale plus depuis qu'ils sont embarques.
 */
it('monte chaque composant embarque', function () {
    $sansModele = [
        \App\Livewire\Admin\ArticleListe::class,
        \App\Livewire\Admin\BienListe::class,
        \App\Livewire\Admin\ServiceListe::class,
        \App\Livewire\Admin\FaqListe::class,
        \App\Livewire\Admin\RubriqueFaqListe::class,
        \App\Livewire\Admin\TemoignageListe::class,
        \App\Livewire\Admin\PartenaireListe::class,
        \App\Livewire\Admin\MembreEquipeListe::class,
        \App\Livewire\Admin\MessageListe::class,
        \App\Livewire\Admin\CategorieEnsemble::class,
        \App\Livewire\Admin\ValeurEnsemble::class,
        \App\Livewire\Admin\ChiffreCleEnsemble::class,
        \App\Livewire\Admin\EtapeProcessusEnsemble::class,
        \App\Livewire\Admin\CommuneBandeauEnsemble::class,
        \App\Livewire\Admin\Referentiels::class,
        \App\Livewire\Admin\ArticleFormulaire::class,
        \App\Livewire\Admin\BienFormulaire::class,
        \App\Livewire\Admin\ServiceFormulaire::class,
        \App\Livewire\Admin\FaqFormulaire::class,
        \App\Livewire\Admin\RubriqueFaqFormulaire::class,
        \App\Livewire\Admin\TemoignageFormulaire::class,
        \App\Livewire\Admin\PartenaireFormulaire::class,
        \App\Livewire\Admin\MembreEquipeFormulaire::class,
    ];

    $echecs = [];

    foreach ($sansModele as $composant) {
        try {
            Livewire\Livewire::actingAs($this->admin)->test($composant)->assertOk();
        } catch (Throwable $e) {
            $echecs[] = class_basename($composant).' → '.mb_substr($e->getMessage(), 0, 120);
        }
    }

    // Les deux formulaires a slug fige refusent la creation : ils se montent
    // avec leur ligne.
    try {
        Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\EncartFormulaire::class, ['element' => \App\Models\Encart::factory()->create()])
            ->assertOk();
    } catch (Throwable $e) {
        $echecs[] = 'EncartFormulaire → '.mb_substr($e->getMessage(), 0, 120);
    }

    try {
        Livewire\Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\ImageDeFondFormulaire::class, ['element' => \App\Models\ImageDeFond::factory()->create()])
            ->assertOk();
    } catch (Throwable $e) {
        $echecs[] = 'ImageDeFondFormulaire → '.mb_substr($e->getMessage(), 0, 120);
    }

    expect($echecs)->toBe([], "Composants qui ne se montent pas :\n".implode("\n", $echecs));
});

/**
 * Chaque module de chaque ecran de page s'ouvre, avec son ecran embarque
 * rendu ENTIEREMENT — la requete HTTP traverse l'arbre, la ou
 * Livewire::test() ne rend les enfants qu'en marque-place.
 */
it('ouvre chaque module de chaque ecran de page', function () {
    $ecrans = [
        \App\Livewire\Admin\PageAccueil::class,
        \App\Livewire\Admin\PagePresentation::class,
        \App\Livewire\Admin\PageBiens::class,
        \App\Livewire\Admin\PageServices::class,
        \App\Livewire\Admin\PageActualites::class,
        \App\Livewire\Admin\PageFaq::class,
        \App\Livewire\Admin\PageContact::class,
    ];

    $echecs = [];

    foreach ($ecrans as $ecran) {
        $composant = Livewire\Livewire::actingAs($this->admin)->test($ecran);

        foreach (array_keys($composant->instance()->modules()) as $module) {
            try {
                $composant->call('ouvrir', $module)->assertOk();
            } catch (Throwable $e) {
                $echecs[] = class_basename($ecran).' / '.$module.' → '.mb_substr($e->getMessage(), 0, 120);
            }
        }
    }

    expect($echecs)->toBe([], "Modules qui ne s'ouvrent pas :\n".implode("\n", $echecs));
});
