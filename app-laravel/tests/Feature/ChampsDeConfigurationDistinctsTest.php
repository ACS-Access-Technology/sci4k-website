<?php

/*
 * Chaque champ de Configuration garde SA liaison quand on change d'onglet.
 *
 * Signale par le client, et parfaitement reproductible : « j'ai supprime le
 * lien Facebook et ca a supprime aussi le nom du site. Et si je remets le nom
 * du site ca va mettre le meme texte dans le champ Facebook ».
 *
 * LA CAUSE. L'ecran rend les champs de l'onglet courant dans une boucle sans
 * wire:key. Quand l'onglet change, Livewire compare l'ancien arbre au nouveau
 * et, trouvant un <input> la ou il y en avait un, REUTILISE l'element au lieu
 * de le remplacer. L'element conserve alors la liaison de l'onglet precedent :
 * le champ affiche « Nom du site » et ecrit dans « facebook ».
 *
 * Deux champs pour une seule valeur, dans les deux sens — effacer l'un efface
 * l'autre, saisir dans l'un remplit l'autre.
 *
 * C'est tres probablement ce qui a depose l'identifiant et le mot de passe SMTP
 * dans « Cible du bouton » et « Sous-titre du pied de page », et donc le mot de
 * passe administrateur sur toutes les pages publiques. J'avais d'abord mis cela
 * sur le compte du remplissage automatique du navigateur ; les positions
 * concordent mieux avec ce defaut-ci.
 *
 * wire:key donne une identite a chaque champ : Livewire ne peut plus confondre
 * deux elements de meme forme.
 */

use App\Livewire\Admin\Configuration;
use App\Livewire\Admin\EncartFormulaire;
use App\Models\Encart;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrateur');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('donne une identite propre a chaque champ de chaque onglet', function () {
    $onglets = array_keys((new Configuration)->onglets());

    foreach ($onglets as $onglet) {
        $html = Livewire::actingAs($this->admin)
            ->test(Configuration::class)
            ->set('onglet', $onglet)
            ->html();

        foreach (array_keys((new Configuration)->onglets()[$onglet]['champs']) as $cle) {
            expect(str_contains($html, 'wire:key="champ-'.$cle.'"'))
                ->toBeTrue("Onglet « {$onglet} » : le champ « {$cle} » n'a pas d'identite, Livewire peut le confondre avec celui d'un autre onglet.");
        }
    }
});

/*
 * Les identites doivent etre UNIQUES dans la page : deux champs partageant la
 * meme ramenent exactement le defaut qu'on corrige.
 */
it('n emploie jamais deux fois la meme identite', function () {
    foreach (array_keys((new Configuration)->onglets()) as $onglet) {
        $html = Livewire::actingAs($this->admin)
            ->test(Configuration::class)
            ->set('onglet', $onglet)
            ->html();

        preg_match_all('/wire:key="champ-([^"]+)"/', $html, $trouves);

        expect($trouves[1])->toBe(array_unique($trouves[1]), "Onglet « {$onglet} » : deux champs portent la meme identite.");
    }
});

/*
 * Le meme piege guette les formulaires de blocs : ils changent de module — donc
 * de liste de champs — au meme endroit de la page.
 */
it('donne une identite aux champs des formulaires de blocs', function () {
    $encart = Encart::firstOrCreate(['slug' => 'accueil.annonce']);

    $html = Livewire::actingAs($this->admin)
        ->test(EncartFormulaire::class, ['element' => $encart, 'embarque' => true])
        ->html();

    foreach (['titre', 'texte', 'libelle_bouton'] as $champ) {
        expect(str_contains($html, 'wire:key="champ-'.$champ.'-fr"'))
            ->toBeTrue("Le champ bilingue « {$champ} » n'a pas d'identite.");
    }

    expect(str_contains($html, 'wire:key="champ-cible_bouton"'))
        ->toBeTrue("Le champ simple « cible_bouton » n'a pas d'identite.");
});
