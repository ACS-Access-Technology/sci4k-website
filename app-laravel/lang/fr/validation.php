<?php

/*
 * Messages de validation en francais.
 *
 * Laravel ne livre ses messages qu'en anglais. Sans ce fichier, une erreur de
 * saisie s'affiche « validation.required » a l'ecran : la cle brute, pas une
 * phrase. Le defaut ne leve aucune exception et ne laisse aucune trace dans les
 * journaux — il ne se voit qu'en soumettant un formulaire incomplet.
 *
 * Seules les regles employees par le projet sont traduites. La langue de repli
 * etant l'anglais, toute regle absente d'ici retombe sur le message anglais du
 * framework, lisible, plutot que sur sa cle.
 */

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'after' => 'Le champ :attribute doit être une date postérieure au :date.',
    'array' => 'Le champ :attribute doit être une liste.',
    'before' => 'Le champ :attribute doit être une date antérieure au :date.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date' => 'Le champ :attribute doit être une date valide.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'exists' => 'La valeur choisie pour :attribute est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'La valeur choisie pour :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'max' => [
        'array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
        'file' => 'Le fichier :attribute ne peut pas dépasser :max kilo-octets.',
        'numeric' => 'Le champ :attribute ne peut pas être supérieur à :max.',
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'min' => [
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'file' => 'Le fichier :attribute doit peser au moins :min kilo-octets.',
        'numeric' => 'Le champ :attribute doit être au moins égal à :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être du texte.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'uploaded' => "Le fichier :attribute n'a pas pu être envoyé.",
    'url' => 'Le champ :attribute doit être une adresse valide.',

    /*
     * Noms lisibles des champs. Sans eux, le message designerait la propriete
     * du composant — « le champ titreEn est obligatoire » — plutot que ce que
     * l'editeur voit a l'ecran.
     */
    'attributes' => [
        'slug' => "identifiant d'adresse",
        'categorieId' => 'catégorie',
        'datePublication' => 'date de publication',
        'statut' => 'statut',
        'titreFr' => 'titre en français',
        'titreEn' => 'titre en anglais',
        'resumeFr' => 'résumé en français',
        'resumeEn' => 'résumé en anglais',
        'contenuFr' => 'contenu en français',
        'contenuEn' => 'contenu en anglais',
        'metaTitreFr' => 'titre pour les moteurs, en français',
        'metaTitreEn' => 'titre pour les moteurs, en anglais',
        'metaDescriptionFr' => 'description pour les moteurs, en français',
        'metaDescriptionEn' => 'description pour les moteurs, en anglais',
        'couverture' => 'image de couverture',
        'nomFr' => 'nom en français',
        'nomEn' => 'nom en anglais',
        'accrocheFr' => 'accroche en français',
        'accrocheEn' => 'accroche en anglais',
        'descriptionFr' => 'description en français',
        'descriptionEn' => 'description en anglais',
        'atout1Fr' => 'premier atout en français',
        'atout1En' => 'premier atout en anglais',
        'atout2Fr' => 'deuxième atout en français',
        'atout2En' => 'deuxième atout en anglais',
        'atout3Fr' => 'troisième atout en français',
        'atout3En' => 'troisième atout en anglais',
        'libelleBoutonFr' => 'libellé du bouton en français',
        'libelleBoutonEn' => 'libellé du bouton en anglais',
        'serviceId' => 'service',
        'questionFr' => 'question en français',
        'questionEn' => 'question en anglais',
        'reponseFr' => 'réponse en français',
        'reponseEn' => 'réponse en anglais',
        'email' => 'adresse email',
        'password' => 'mot de passe',
        'name' => 'nom',
    ],
];
