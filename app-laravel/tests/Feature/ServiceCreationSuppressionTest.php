<?php

use App\Livewire\Admin\ServiceFormulaire;
use App\Livewire\Admin\ServiceListe;
use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Creation et suppression d'un service.
 *
 * Les six services repris du site n'etaient au depart que modifiables : leur
 * nombre suivait les six metiers. Le client demande de pouvoir en ajouter et
 * en retirer, ce qui deplace trois choses au-dela du seul formulaire — les
 * liens du pied de page, le fond de la tuile publique, et le lien vers les
 * questions de la FAQ. Chacune est couverte ici.
 */
beforeEach(function () {
    Storage::fake('public');
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create();
    $this->lecteur->assignRole('lecteur');
});

/** Jeu de champs minimal accepte par le formulaire. */
function champsDeService(array $remplaces = []): array
{
    return array_merge([
        'slug' => 'expertise',
        'nomFr' => 'Expertise', 'nomEn' => 'Appraisal',
        'accrocheFr' => 'Estimer un bien', 'accrocheEn' => 'Value a property',
        'descriptionFr' => 'Texte long.', 'descriptionEn' => 'Long text.',
    ], $remplaces);
}

/* ---------------------------------------------------------------- creation */

it('cree un service depuis le formulaire', function () {
    $composant = Livewire::actingAs($this->editeur)->test(ServiceFormulaire::class);

    foreach (champsDeService() as $champ => $valeur) {
        $composant->set($champ, $valeur);
    }

    $composant->set('categorieId', (string) $this->categorie->id)
        ->call('enregistrer')
        ->assertHasNoErrors();

    $service = Service::where('slug', 'expertise')->first();

    expect($service)->not->toBeNull();
    expect($service->nom_fr)->toBe('Expertise');
    expect($service->nom_en)->toBe('Appraisal');
});

it('place le service cree en fin de liste, pas en tete', function () {
    // Sans rang explicite, `ordre` vaut 0 par defaut et le nouveau service
    // passerait devant les six existants : scopeOrdonnees trie sur `ordre`.
    Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'a', 'ordre' => 1]);
    Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'b', 'ordre' => 2]);

    $composant = Livewire::actingAs($this->editeur)->test(ServiceFormulaire::class);
    foreach (champsDeService() as $champ => $valeur) {
        $composant->set($champ, $valeur);
    }
    $composant->set('categorieId', (string) $this->categorie->id)->call('enregistrer');

    expect(Service::ordonnees()->pluck('slug')->all())->toBe(['a', 'b', 'expertise']);
});

it('refuse un slug deja pris', function () {
    Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'expertise']);

    $composant = Livewire::actingAs($this->editeur)->test(ServiceFormulaire::class);
    foreach (champsDeService() as $champ => $valeur) {
        $composant->set($champ, $valeur);
    }

    $composant->set('categorieId', (string) $this->categorie->id)
        ->call('enregistrer')
        ->assertHasErrors(['slug']);
});

it('refuse un slug malforme, qui casserait l ancre et la classe CSS', function () {
    foreach (['Expertise', 'expertise immobiliere', 'expertise_immo', 'expertisé', '-expertise'] as $mauvais) {
        $composant = Livewire::actingAs($this->editeur)->test(ServiceFormulaire::class);
        foreach (champsDeService(['slug' => $mauvais]) as $champ => $valeur) {
            $composant->set($champ, $valeur);
        }
        $composant->set('categorieId', (string) $this->categorie->id)
            ->call('enregistrer')
            ->assertHasErrors(['slug']);
    }
});

it('ne laisse pas modifier le slug d un service existant', function () {
    // Le slug porte l'ancre du pied de page ET la classe CSS du fond : le
    // changer apres coup casserait les deux sans que rien ne le signale.
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'foncier',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $service])
        ->set('slug', 'foncier-renomme')
        ->set('nomFr', 'Foncier')->set('nomEn', 'Land')
        ->set('accrocheFr', 'a')->set('accrocheEn', 'b')
        ->set('descriptionFr', 'c')->set('descriptionEn', 'd')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($service->fresh()->slug)->toBe('foncier');
});

it('publie le service cree sur la page publique', function () {
    $composant = Livewire::actingAs($this->editeur)->test(ServiceFormulaire::class);
    foreach (champsDeService() as $champ => $valeur) {
        $composant->set($champ, $valeur);
    }
    $composant->set('categorieId', (string) $this->categorie->id)
        ->set('visible', true)
        ->call('enregistrer');

    $this->get('/services')->assertOk()->assertSee('Expertise');
});

it('interdit la creation a un lecteur', function () {
    $this->actingAs($this->lecteur)
        ->get(route('admin.services.creation'))
        ->assertForbidden();
});

/* ------------------------------------------------------------- suppression */

it('supprime un service', function () {
    $service = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'expertise']);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('supprimer', $service->id);

    expect(Service::find($service->id))->toBeNull();
});

it('emporte les questions de FAQ rattachees au service supprime', function () {
    // La cle etrangere est en RESTRICT : les questions doivent partir AVANT
    // le service, sans quoi l'appel leverait une QueryException et l'editeur
    // verrait une page 500.
    $service = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'expertise']);
    $questions = QuestionFaq::factory()->count(2)->create(['service_id' => $service->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('supprimer', $service->id);

    expect(Service::find($service->id))->toBeNull();
    expect(QuestionFaq::whereIn('id', $questions->pluck('id'))->count())->toBe(0);
});

it('ne touche pas aux questions des autres services', function () {
    $supprime = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'expertise']);
    $garde = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'foncier']);

    QuestionFaq::factory()->create(['service_id' => $supprime->id]);
    $epargnee = QuestionFaq::factory()->create(['service_id' => $garde->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('supprimer', $supprime->id);

    expect(QuestionFaq::find($epargnee->id))->not->toBeNull();
    expect(QuestionFaq::count())->toBe(1);
});

it('annonce dans la confirmation le nombre de questions emportees', function () {
    // Sans ce chiffre, l'editeur ne peut pas savoir qu'il detruit aussi du
    // contenu de FAQ : la boite de confirmation est le seul moment ou il peut
    // encore reculer.
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'expertise',
        'nom_fr' => 'Expertise', 'nom_en' => 'Appraisal',
    ]);
    QuestionFaq::factory()->count(2)->create(['service_id' => $service->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->assertSee('2 questions de FAQ', false);
});

it('ne parle pas de FAQ dans la confirmation d un service sans question', function () {
    Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'expertise',
        'nom_fr' => 'Expertise', 'nom_en' => 'Appraisal',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->assertDontSee('questions de FAQ', false);
});

it('efface le fichier d une image televersee avec le service', function () {
    Storage::disk('public')->put('services/photo.jpg', 'contenu');
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'expertise',
        'image_source' => 'storage/services/photo.jpg',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('supprimer', $service->id);

    Storage::disk('public')->assertMissing('services/photo.jpg');
});

it('ne touche jamais au fichier d une image du site statique', function () {
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'foncier',
        'image_source' => 'images/services/foncier.jpg',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('supprimer', $service->id);

    expect(file_exists(public_path('images/services/foncier.jpg')))->toBeTrue();
});

it('interdit la suppression a un lecteur', function () {
    $service = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'expertise']);

    Livewire::actingAs($this->lecteur)
        ->test(ServiceListe::class)
        ->call('supprimer', $service->id)
        ->assertForbidden();

    expect(Service::find($service->id))->not->toBeNull();
});

/* ------------------------------------------------------------ pied de page */

it('liste les services du pied de page depuis la base', function () {
    Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'expertise',
        'nom_fr' => 'Expertise', 'visible' => true, 'ordre' => 1,
    ]);

    $this->get('/services')->assertOk()->assertSee('/services#expertise', false);
});

it('retire du pied de page le service supprime', function () {
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'expertise', 'visible' => true,
    ]);

    Livewire::actingAs($this->editeur)->test(ServiceListe::class)->call('supprimer', $service->id);

    $this->get('/services')->assertOk()->assertDontSee('/services#expertise', false);
});

it('n annonce pas dans le pied de page un service masque', function () {
    Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'expertise', 'visible' => false,
    ]);

    $this->get('/services')->assertOk()->assertDontSee('/services#expertise', false);
});
