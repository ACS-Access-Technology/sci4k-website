<?php

use App\Livewire\Admin\CategorieEnsemble;
use App\Livewire\Admin\PageAccueil;
use App\Models\ActiviteJournalisee;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\Commentaire;
use App\Models\Encart;
use App\Models\ReglageDeSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Les defauts critiques releves par l'audit, un test chacun.
 *
 * Ils n'ont rien en commun sinon d'etre tous du meme rang : chacun laissait
 * passer une ecriture, une lecture ou une panne que personne n'avait decidee.
 * Le fichier les tient ensemble pour qu'aucun ne revienne en silence.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'redacteur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->editeur = User::factory()->create(['statut' => User::ACTIF]);
    $this->editeur->assignRole('editeur');
});

/*
 * L'EN-TETE DE SECTION N'ECRIT QUE CE QU'ELLE DECLARE
 *
 * `$entete` est une propriete publique Livewire : le navigateur en fixe le
 * contenu, CLES COMPRISES. Passee telle quelle a fill(), elle laissait ecrire
 * toute colonne fillable que l'ecran n'expose pas.
 */
it("refuse d'ecrire une colonne que l'ecran ne declare pas", function () {
    ReglageDeSection::pour('home.hero')->update(['contenu_fr' => 'Texte legitime']);

    Livewire::actingAs($this->editeur)
        ->test(PageAccueil::class)
        ->set('entete.titre_fr', 'Nouveau titre')
        // Ni declaree par le module, ni bornee par aucune regle, et elle PRIME
        // sur le chapo sur la page publique.
        ->set('entete.contenu_fr', 'Injecte depuis le navigateur')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $section = ReglageDeSection::where('slug', 'home.hero')->first();

    expect($section->titre_fr)->toBe('Nouveau titre')
        ->and($section->contenu_fr)->toBe('Texte legitime');
});

/*
 * LE JOURNAL NE COMPTE PAS LES VISITES
 *
 * Le compteur d'impressions des encarts passait par increment(), qui declenche
 * l'evenement `updated` : chaque visite de la page d'accueil ecrivait deux
 * lignes dans le journal d'activite, sous le nom de personne.
 */
it("n'inscrit rien au journal quand un visiteur voit un encart", function () {
    // Le seeder pose deja cet encart : on le reprend plutot que d'en creer un
    // second sous le meme slug, que l'unicite refuserait.
    Encart::updateOrCreate(
        ['slug' => 'accueil.annonce'],
        Encart::factory()->make(['slug' => 'accueil.annonce'])->only([
            'titre_fr', 'titre_en', 'texte_fr', 'texte_en', 'cible_bouton',
        ]) + ['visible' => true, 'impressions' => 0, 'diffusion_de' => null, 'diffusion_a' => null],
    );

    ActiviteJournalisee::query()->delete();

    $this->get('/')->assertOk();

    expect(ActiviteJournalisee::count())->toBe(0)
        ->and(Encart::where('slug', 'accueil.annonce')->value('impressions'))->toBe(1);
});

/*
 * UNE CATEGORIE UTILISEE NE PEUT PAS ETRE EFFACEE PAR LA BANDE
 *
 * `$aSupprimer` est publique : le refus pose dans retirer() ne vaut que pour le
 * chemin normal. Sans second controle, l'identifiant atteignait le DELETE, la
 * contrainte de cle etrangere le refusait, et l'ecran rendait une erreur 500
 * APRES avoir deja enregistre le reste.
 */
it('refuse un retrait de categorie force depuis le navigateur', function () {
    $categorie = Categorie::factory()->create(['nom_fr' => 'Foncier']);
    Article::factory()->create(['categorie_id' => $categorie->id]);

    $autre = Categorie::factory()->create(['nom_fr' => 'Marché']);

    Livewire::actingAs($this->editeur)
        ->test(CategorieEnsemble::class)
        ->set('lignes.'.$autre->id.'.nom_fr', 'Marché renommé')
        // Le retrait n'est jamais passe par retirer() : il est pose directement.
        ->set('aSupprimer', [$categorie->id])
        ->call('enregistrer')
        ->assertHasErrors('aSupprimer');

    expect(Categorie::whereKey($categorie->id)->exists())->toBeTrue()
        // Refuse AVANT la moindre ecriture : le reste de l'ecran est intact.
        ->and(Categorie::whereKey($autre->id)->value('nom_fr'))->toBe('Marché');
});

/*
 * Le role redacteur, devenu inatteignable, a ses propres tests : ils vivent
 * dans RoleRedacteurTest, aupres de ceux qui decrivent deja ce que ce role
 * peut et ne peut pas faire.
 */

/*
 * UN COMPTE DESACTIVE PERD SA SESSION EN COURS
 *
 * Le refus pose sur l'authentification ne regarde que le MOMENT de la
 * connexion. Un editeur deja connecte continuait de travailler jusqu'a
 * l'expiration de sa session — c'est pourtant le cas qui compte le plus.
 */
it('deconnecte un compte desactive en cours de session', function () {
    $this->actingAs($this->editeur)->get(route('dashboard'))->assertOk();

    $this->editeur->update(['statut' => User::INACTIF]);

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

/*
 * LE COMPTE DES COMMENTAIRES INCLUT LES REPONSES
 *
 * Les reponses sont affichees comme des commentaires. Ne pas les compter
 * annoncait « 1 commentaire » au-dessus de trois messages.
 */
it('compte les reponses parmi les commentaires', function () {
    $article = Article::factory()->create([
        'categorie_id' => Categorie::factory()->create()->id,
        'statut' => 'publie',
        'date_publication' => now()->subDay(),
        'commentaires_ouverts' => true,
    ]);

    $parent = Commentaire::factory()->create([
        'article_id' => $article->id,
        'statut' => Commentaire::PUBLIE,
        'parent_id' => null,
    ]);

    Commentaire::factory()->count(2)->create([
        'article_id' => $article->id,
        'statut' => Commentaire::PUBLIE,
        'parent_id' => $parent->id,
    ]);

    $this->get(route('actualites.detail', $article))
        ->assertOk()
        ->assertSee('3 commentaires');
});
