<?php

/*
 * La demande de visite part des DEUX endroits qui la proposent.
 *
 * Signale par le client : une demande faite depuis le site n'arrivait pas dans
 * le backoffice. Trouve en la refaisant dans un navigateur.
 *
 * Le site porte deux formulaires de visite : celui de la fiche complete d'un
 * bien, et celui de la fenetre qui s'ouvre depuis le catalogue. Le premier
 * appelait handleVisiteSubmit(), qui existe. Le second appelait
 * handleModalVisiteSubmit() — une fonction ecrite NULLE PART.
 *
 * Consequence : le clic levait une ReferenceError, donc preventDefault() ne
 * tournait jamais, donc le navigateur soumettait le formulaire nativement, en
 * GET, vers la meme page. Le visiteur voyait sa page se recharger et croyait
 * sa demande partie. Rien n'atteignait le serveur, et aucune erreur ne
 * remontait cote Laravel puisque aucune requete n'y parvenait.
 *
 * Le defaut venait de la duplication : deux formulaires, deux jeux
 * d'identifiants, deux fonctions a tenir en phase. La correction supprime la
 * cause — une seule fonction, qui lit les champs DANS le formulaire soumis, par
 * leur nom. Les deux formulaires portent deja les memes noms.
 */

use App\Livewire\Public\CatalogueDesBiens;
use App\Models\Bien;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * Le formulaire ne nomme plus la fonction a appeler : il declare CE QU'IL EST,
 * et main.js decide. Le « onsubmit="handleVisiteSubmit(event)" » a ete sorti
 * du balisage avec tout le JavaScript en ligne.
 *
 * Le controle, lui, ne change pas de nature : les deux formulaires doivent
 * declarer le meme acheminement, et cet acheminement doit mener a une fonction
 * qui existe. C'est exactement le defaut d'origine — un nom ecrit dans le
 * gabarit, aucune fonction derriere.
 */

/** L'acheminement declare par le formulaire de visite d'une page. */
function acheminementDeVisite(string $html): ?string
{
    preg_match('/data-bien="[^"]*"\s+data-envoi="([a-z]+)"/', $html, $trouve);

    return $trouve[1] ?? null;
}

it('appelle le meme gestionnaire depuis le catalogue et depuis la fiche', function () {
    $bien = Bien::factory()->create(['statut' => 'publie', 'slug' => 'villa-essai']);

    $fenetre = Livewire::test(CatalogueDesBiens::class)
        ->call('ouvrirBien', $bien->id)
        ->html();

    $fiche = $this->get('/biens/villa-essai')->assertOk()->getContent();

    expect(acheminementDeVisite($fenetre))
        ->not->toBeNull('La fenetre du catalogue ne declare aucun acheminement.')
        ->toBe(acheminementDeVisite($fiche));
});

/*
 * Et ce gestionnaire doit EXISTER. C'est le controle qui manquait : le nom
 * etait ecrit dans le gabarit, la fonction nulle part, et rien ne le disait —
 * ni la construction, ni les tests, ni le serveur.
 */
it('declare ce gestionnaire dans le script du site', function () {
    $bien = Bien::factory()->create(['statut' => 'publie', 'slug' => 'villa-essai']);

    $cle = acheminementDeVisite(
        Livewire::test(CatalogueDesBiens::class)->call('ouvrirBien', $bien->id)->html()
    );

    $script = File::get(base_path('../maquettes-frontoffice/assets/main.js'));

    // La table d'acheminement connait-elle cette cle, et vers quelle fonction
    // renvoie-t-elle ? On suit le chemin plutot que de graver un nom ici :
    // c'est la rupture de ce chemin qui avait laisse passer le defaut.
    preg_match('/'.preg_quote($cle, '/').':\s*function[^}]*window\.(\w+)/', $script, $appelle);

    expect($appelle[1] ?? null)
        ->not->toBeNull("Aucun acheminement « {$cle} » dans le script du site.");

    expect($script)->toContain('window.'.$appelle[1].' =');
});

/*
 * Les deux formulaires envoient les memes champs. Le gestionnaire les lit par
 * leur NOM, dans le formulaire soumis : c'est ce qui permet d'en avoir deux
 * sans les tenir en phase a la main.
 */
it('porte les memes champs dans les deux formulaires', function () {
    $bien = Bien::factory()->create(['statut' => 'publie', 'slug' => 'villa-essai']);

    $noms = function (string $html): array {
        preg_match_all('/name="(nom|telephone|email|creneau_souhaite|message|site_web)"/', $html, $trouves);
        $liste = array_unique($trouves[1]);
        sort($liste);

        return $liste;
    };

    $fenetre = Livewire::test(CatalogueDesBiens::class)->call('ouvrirBien', $bien->id)->html();
    $fiche = $this->get('/biens/villa-essai')->assertOk()->getContent();

    expect($noms($fenetre))
        ->toBe(['creneau_souhaite', 'email', 'message', 'nom', 'site_web', 'telephone'])
        ->toBe($noms($fiche));
});

/*
 * La zone de confirmation suit la meme regle que les champs : elle se trouve
 * DANS le formulaire. Les deux pages la nommaient differemment
 * — « visiteConfirmation » et « modalVisiteConfirmation » — et la fenetre du
 * catalogue serait restee muette apres un envoi reussi.
 */
it('porte sa zone de confirmation dans chaque formulaire', function () {
    $bien = Bien::factory()->create(['statut' => 'publie', 'slug' => 'villa-essai']);

    $fenetre = Livewire::test(CatalogueDesBiens::class)->call('ouvrirBien', $bien->id)->html();
    $fiche = $this->get('/biens/villa-essai')->assertOk()->getContent();

    expect($fenetre)->toContain('data-visite-confirmation')
        ->and($fiche)->toContain('data-visite-confirmation');
});
