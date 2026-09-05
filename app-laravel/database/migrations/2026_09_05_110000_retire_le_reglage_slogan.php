<?php

use App\Models\Parametre;
use Illuminate\Database\Migrations\Migration;

/**
 * Retire le reglage « Slogan », qui n'etait affiche nulle part.
 *
 * L'ecran « Configuration » le proposait depuis le debut. Aucune vue ne le
 * lisait — pas plus le pied de page que l'en-tete — et « Sous-titre du pied de
 * page » fait exactement ce travail, lui, pour de vrai. Deux champs pour une
 * meme phrase, dont un sans effet, n'auraient produit que des questions.
 *
 * La ligne est effacee et non laissee en place : un reglage retire de l'ecran
 * mais conserve en base reapparait au premier export, et fait croire a une
 * panne d'affichage.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Un a un, et non par requete de masse : l'evenement `deleted` du
        // modele est ce qui vide le cache des reglages. Une suppression en
        // masse ne le declenche pas, et le slogan serait reste en memoire.
        Parametre::where('cle', 'slogan')->get()->each->delete();
    }

    /**
     * Rien a remettre.
     *
     * On ne sait pas ce que la ligne contenait, et la recreer vide ne rendrait
     * pas le champ a l'ecran : c'est la declaration du composant qui le fait
     * apparaitre, pas la presence d'une ligne.
     */
    public function down(): void {}
};
