<?php

/*
 * Le meme defaut que SaisieBilingueSansTraducteurTest, aux deux autres
 * endroits ou il se trouvait.
 *
 * Trois composants portent une saisie bilingue a onglets, et tous trois
 * exigeaient les DEUX langues : FormulaireDeBloc (encarts, temoignages,
 * membres, images de fond), ArticleFormulaire, et EditionGroupee (services,
 * valeurs, questions, etapes...). Les trois masquent l'onglet inactif ; les
 * trois rendaient donc un refus invisible des que la traduction automatique
 * n'etait pas configuree.
 *
 * Les corriger separement aurait laisse l'editeur buter deux fois sur le meme
 * mur apres l'avoir franchi une premiere fois.
 */

use App\Livewire\Admin\ArticleFormulaire;
use App\Livewire\Admin\ValeurEnsemble;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Valeur;
use App\Services\Traduction\Traducteur;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    app()->bind(Traducteur::class, fn () => new class implements Traducteur
    {
        public function disponible(): bool
        {
            return false;
        }

        public function traduire(array $textes, string $vers, ?string $depuis = null): ?array
        {
            return null;
        }
    });
});

it('publie un article dont seul le francais est redige', function () {
    $categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'terrains-bingerville')
        ->set('categorieId', $categorie->id)
        ->set('datePublication', now()->toDateString())
        ->set('statut', 'publie')
        ->set('titreFr', 'Terrains viabilisés à Bingerville')
        ->set('resumeFr', 'Des lots de 500 m² viennent de sortir.')
        ->set('contenuFr', 'Le détail complet de l’opération.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get('/actualites')->assertOk()->assertSee('Terrains viabilisés à Bingerville', false);
    $this->get('/en/actualites')->assertOk()->assertSee('Terrains viabilisés à Bingerville', false);
});

it('enregistre une liste groupee dont seul le francais est rempli', function () {
    $valeur = Valeur::factory()->create([
        'ordre' => 1, 'titre_fr' => 'Rigueur', 'titre_en' => 'Rigour',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$valeur->id}.titre_fr", 'Rigueur et sécurité')
        ->set("lignes.{$valeur->id}.titre_en", '')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($valeur->fresh()->titre_fr)->toBe('Rigueur et sécurité');
});

/*
 * Le refus doit se VOIR ici aussi : les deux ecrans masquent l'onglet inactif
 * exactement comme les formulaires de blocs.
 */
it('bascule sur l onglet fautif dans le formulaire d article', function () {
    $categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ArticleFormulaire::class)
        ->set('langueActive', 'en')
        ->set('slug', 'terrains-bingerville')
        ->set('categorieId', $categorie->id)
        ->set('datePublication', now()->toDateString())
        ->set('titreEn', 'Serviced plots')
        ->set('resumeEn', 'New plots.')
        ->set('contenuEn', 'Full details.')
        ->call('enregistrer')
        ->assertHasErrors(['titreFr'])
        ->assertSet('langueActive', 'fr');
});

it('bascule sur l onglet fautif dans une liste groupee', function () {
    $valeur = Valeur::factory()->create([
        'ordre' => 1, 'titre_fr' => 'Rigueur', 'titre_en' => 'Rigour',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ValeurEnsemble::class)
        ->set('langueActive', 'en')
        ->set("lignes.{$valeur->id}.titre_fr", '')
        ->call('enregistrer')
        ->assertHasErrors(["lignes.{$valeur->id}.titre_fr"])
        ->assertSet('langueActive', 'fr');
});
