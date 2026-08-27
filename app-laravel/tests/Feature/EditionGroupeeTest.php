<?php

use App\Livewire\Admin\ChiffreCleEnsemble;
use App\Livewire\Admin\EtapeProcessusEnsemble;
use App\Livewire\Admin\ValeurEnsemble;
use App\Models\ChiffreCle;
use App\Models\EtapeProcessus;
use App\Models\User;
use App\Models\Valeur;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Ecrans des petits ensembles edites d'un bloc.
 *
 * Le cadrage les distingue des collections : tous les elements cote a cote, un
 * seul bouton, ni creation ni suppression. Un tableau pagine avec recherche et
 * filtres pour quatre lignes couterait trois clics pour changer un mot.
 */
beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create();
    $this->lecteur->assignRole('lecteur');

    $this->premiere = Valeur::factory()->create(['ordre' => 1, 'titre_fr' => 'Rigueur', 'titre_en' => 'Rigour']);
    $this->seconde = Valeur::factory()->create(['ordre' => 2, 'titre_fr' => 'Écoute', 'titre_en' => 'Listening']);
});

it('charge toutes les lignes a l ouverture', function () {
    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->assertSet("lignes.{$this->premiere->id}.titre_fr", 'Rigueur')
        ->assertSet("lignes.{$this->seconde->id}.titre_fr", 'Écoute');
});

it('enregistre toutes les lignes d un seul bouton', function () {
    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$this->premiere->id}.titre_fr", 'Rigueur et sécurité')
        ->set("lignes.{$this->seconde->id}.titre_fr", 'Écoute active')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($this->premiere->fresh()->titre_fr)->toBe('Rigueur et sécurité');
    expect($this->seconde->fresh()->titre_fr)->toBe('Écoute active');
});

it('n ecrit pas une ligne laissee intacte', function () {
    $avant = $this->seconde->updated_at;

    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$this->premiere->id}.titre_fr", 'Modifiée')
        ->call('enregistrer');

    expect($this->seconde->fresh()->titre_fr)->toBe('Écoute');
    expect($this->seconde->fresh()->updated_at->eq($avant))->toBeTrue();
});

it('refuse un champ vide et nomme la ligne en cause', function () {
    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$this->premiere->id}.titre_fr", '')
        ->set("lignes.{$this->premiere->id}.titre_en", '')
        ->call('enregistrer')
        ->assertHasErrors(["lignes.{$this->premiere->id}.titre_fr"]);

    expect($this->premiere->fresh()->titre_fr)->toBe('Rigueur');
});

it('ignore un identifiant qui ne designe rien', function () {
    // Les identifiants viennent du navigateur : l'un d'eux pourrait designer
    // une ligne d'une autre table, ou une ligne disparue depuis l'ouverture.
    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->set('lignes.999999', ['titre_fr' => 'Injectée', 'titre_en' => 'Injected', 'texte_fr' => 'x', 'texte_en' => 'y'])
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Valeur::count())->toBe(2);
    expect(Valeur::where('titre_fr', 'Injectée')->exists())->toBeFalse();
});

it('interdit l enregistrement a un lecteur', function () {
    Livewire::actingAs($this->lecteur)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$this->premiere->id}.titre_fr", 'Renommée par un lecteur')
        ->call('enregistrer')
        ->assertForbidden();

    expect($this->premiere->fresh()->titre_fr)->toBe('Rigueur');
});

it('laisse un lecteur consulter l ecran', function () {
    $this->actingAs($this->lecteur)->get(route('admin.valeurs'))->assertOk();
});

it('valide le nombre d un chiffre cle', function () {
    $chiffre = ChiffreCle::factory()->create(['ordre' => 1, 'valeur' => 120]);

    Livewire::actingAs($this->editeur)
        ->test(ChiffreCleEnsemble::class)
        ->set("lignes.{$chiffre->id}.valeur", 'beaucoup')
        ->call('enregistrer')
        ->assertHasErrors(["lignes.{$chiffre->id}.valeur"]);

    expect($chiffre->fresh()->valeur)->toBe(120);
});

it('accepte un nombre valide', function () {
    $chiffre = ChiffreCle::factory()->create(['ordre' => 1, 'valeur' => 120]);

    Livewire::actingAs($this->editeur)
        ->test(ChiffreCleEnsemble::class)
        ->set("lignes.{$chiffre->id}.valeur", '150')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($chiffre->fresh()->valeur)->toBe(150);
});

it('sert les trois ecrans d ensembles', function () {
    EtapeProcessus::factory()->create(['ordre' => 1]);

    foreach (['admin.valeurs', 'admin.chiffres-cles', 'admin.etapes-processus'] as $route) {
        $this->actingAs($this->editeur)->get(route($route))->assertOk();
    }
});

it('presente les elements dans leur ordre d affichage', function () {
    $this->premiere->update(['ordre' => 2]);
    $this->seconde->update(['ordre' => 1]);

    $corps = Livewire::actingAs($this->editeur)->test(ValeurEnsemble::class)->html();

    expect(strpos($corps, 'Écoute'))->toBeLessThan(strpos($corps, 'Rigueur'));
});

it('couvre aussi les etapes du processus', function () {
    $etape = EtapeProcessus::factory()->create(['ordre' => 1, 'titre_fr' => 'Écoute & Analyse']);

    Livewire::actingAs($this->editeur)
        ->test(EtapeProcessusEnsemble::class)
        ->set("lignes.{$etape->id}.titre_fr", 'Écoute renforcée')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($etape->fresh()->titre_fr)->toBe('Écoute renforcée');
});

/*
 * L'ecran des valeurs ne demande pas de pictogramme.
 *
 * Il en demandait un — « Icône (tracé SVG) », 4000 caracteres libres — que rien
 * n'affichait : la carte d'une valeur porte un NUMERO, sur le site statique
 * comme sur la page portee. Le champ faisait donc saisir un contenu sans effet,
 * et laissait dormir un risque : les icones de service, elles, sont rendues
 * sans echappement. Le jour ou l'on aurait affiche celle d'une valeur en
 * copiant ce motif, un trace saisi en administration se serait execute chez le
 * visiteur.
 */
it('ne propose aucun trace SVG sur les valeurs', function () {
    $corps = Livewire::actingAs($this->editeur)->test(ValeurEnsemble::class)->html();

    expect($corps)->not->toContain('icone_svg')
        ->and($corps)->not->toContain('SVG');
});

it('n a plus de colonne d icone sur les valeurs', function () {
    expect(Schema::hasColumn('valeurs', 'icone_svg'))->toBeFalse();
});

/*
 * La page de presentation numerote les valeurs, elle ne les illustre pas.
 * C'est ce qui rendait le champ mensonger ; le test le dit a l'endroit ou le
 * visiteur le constate.
 */
it('numerote les valeurs sur la page publique', function () {
    Valeur::query()->update(['visible' => true]);

    $corps = $this->get('/presentation')->assertOk()->getContent();

    // L'assertion vise la GRILLE DES VALEURS, pas la page : celle-ci contient
    // des SVG legitimes — bascule de theme, fleche de la newsletter — et
    // chercher « <svg » partout les aurait attrapes. Premier jet rouge pour
    // cette raison exactement.
    $grille = mb_substr($corps, mb_strpos($corps, 'values-grid'));
    $grille = mb_substr($grille, 0, mb_strpos($grille, '</section>'));

    expect($grille)->toContain('value-num')
        ->and($grille)->toContain('01')
        ->and($grille)->not->toContain('<svg');
});
