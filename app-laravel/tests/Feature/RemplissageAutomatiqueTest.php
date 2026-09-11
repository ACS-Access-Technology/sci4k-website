<?php

/*
 * Aucun gestionnaire de mots de passe ne remplit l'ecran Configuration.
 *
 * CE QUI S'EST PASSE EN PRODUCTION
 *
 * Le mot de passe du compte administrateur s'est retrouve enregistre dans le
 * champ « Sous-titre du pied de page » — donc affiche en clair dans le pied de
 * page de TOUTES les pages publiques. Son identifiant, lui, avait atterri dans
 * « Cible du bouton » et dans l'identifiant SMTP.
 *
 * Personne ne les y a tapes. L'ecran porte un champ type="password" — le mot
 * de passe SMTP — au milieu d'une vingtaine de champs texte. Le navigateur y
 * reconnait la forme d'un formulaire de connexion et remplit d'autorite les
 * champs voisins avec ce qu'il a en memoire. Le champ mot de passe, lui, etait
 * protege ; les autres ne l'etaient pas, et c'est precisement par eux que la
 * fuite est passee.
 *
 * La regle est donc : TOUT champ de cet ecran se declare, sans exception.
 * L'oubli d'un seul rouvre le chemin.
 */

use App\Livewire\Admin\Configuration;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrateur');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/** Le rendu de l'ecran, onglet par onglet. */
function ecranDeConfiguration(User $admin, string $onglet): string
{
    return Livewire::actingAs($admin)
        ->test(Configuration::class)
        ->set('onglet', $onglet)
        ->html();
}

it('declare autocomplete sur chaque champ de chaque onglet', function () {
    $onglets = array_keys((new Configuration)->onglets());

    expect($onglets)->not->toBeEmpty();

    foreach ($onglets as $onglet) {
        $html = ecranDeConfiguration($this->admin, $onglet);

        // Chaque <input> et <textarea> saisissable — les cases a cocher et les
        // listes ne recoivent pas de remplissage automatique de texte.
        preg_match_all('/<(input|textarea)\b[^>]*>/i', $html, $balises);

        foreach ($balises[0] as $balise) {
            // Un champ de fichier, une case, un bouton : rien de tout cela ne
            // recoit un remplissage automatique de texte.
            if (preg_match('/type="(checkbox|radio|hidden|submit|button|file)"/i', $balise) === 1) {
                continue;
            }

            // Le message est passe a expect(), et non a toContain() : ce dernier
            // prendrait un second argument pour un second motif a CHERCHER.
            expect(str_contains($balise, 'autocomplete='))
                ->toBeTrue("Onglet « {$onglet} » : un champ sans autocomplete rouvre le chemin de la fuite.\n".$balise);
        }
    }
});

/*
 * Le formulaire lui-meme le declare aussi : certains navigateurs lisent
 * l'attribut du formulaire avant celui de ses champs, et un formulaire muet
 * suffit a relancer l'heuristique.
 */
it('declare autocomplete sur le formulaire', function () {
    expect(ecranDeConfiguration($this->admin, 'general'))
        ->toMatch('/<form[^>]*autocomplete="off"/');
});

/*
 * Le champ du mot de passe SMTP garde « new-password » : c'est la seule valeur
 * qu'un navigateur respecte vraiment sur un champ de ce type, et elle lui
 * interdit de proposer un mot de passe DEJA enregistre pour ce site.
 */
it('garde new-password sur le champ du mot de passe', function () {
    expect(ecranDeConfiguration($this->admin, 'messagerie'))
        ->toContain('autocomplete="new-password"');
});
