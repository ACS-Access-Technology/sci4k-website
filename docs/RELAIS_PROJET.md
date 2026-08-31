# SCI4K - Document de relais projet

> Document de référence pour reprendre le développement du site SCI4K sans perdre le contexte fonctionnel, technique ou l'avancement.
>
> Dernière mise à jour : 27 août 2026
>
> Dépôt : `https://github.com/yutomase99-blip/sci4k-website`

## 1. Résumé du projet

SCI4K est un site vitrine immobilier pour une agence basée à Abidjan. Le projet est organisé en deux interfaces :

- `frontoffice/` : site public statique en HTML/CSS/JavaScript, bilingue français/anglais.
- `backoffice/` : maquettes HTML de l'administration, générées par des scripts Python.
- `app-laravel/` : application Laravel serveur existante. La branche de référence active est `worktree-lot3-reglages` (`0129ae0`, également publiée comme `origin/worktree-lot3-reglages`) ; elle contient les sources Laravel fonctionnelles.

Le dépôt contient les maquettes HTML historiques, le frontoffice statique et, sur la branche du lot 3, l'application Laravel fonctionnelle. C'est cette branche et cette application qui constituent la base de travail ; les maquettes HTML servent de référence visuelle et fonctionnelle.

## 2. Structure et conventions à préserver

- La branche de travail doit être `worktree-lot3-reglages` ou la branche active équivalente du lot 3 ; ne pas revenir à `master` par défaut.
- L'application Laravel se trouve dans `app-laravel/` sur cette branche.
- Les tests Laravel se lancent avec `cd app-laravel && php artisan test --compact` après installation de `vendor/`.
- Les pages du back-office historique sont générées par `backoffice/_build/build.py`.
- Les sources des écrans sont `backoffice/_build/layout.py`, `pages_a.py`, `pages_b.py` et `pages_c.py`.
- Ne pas modifier directement les fichiers HTML générés du back-office : une régénération les écraserait.
- Régénération : `cd backoffice && python3 _build/build.py`.
- Le site public centralise une partie des traductions dans `frontoffice/assets/main.js`, notamment `window.SCI4K_I18N`.
- Les images de fond du frontoffice sont déclarées dans `frontoffice/assets/images.css`.
- Le contrôle existant est `python3 tools/verifier-site.py`.
- Le frontoffice peut être servi localement avec `cd frontoffice && python3 -m http.server 8777`.
- Le backoffice est prévu pour être servi comme HTML statique. Une configuration locale historique mentionne `127.0.0.1:24282/dashboard/`, mais il faut vérifier le serveur réellement actif avant de s'y fier.

## 3. État fonctionnel constaté

### Frontoffice

Pages présentes : accueil, présentation, biens, services, actualités, détail d'actualité, FAQ, contact, mentions légales, politique de confidentialité, 404 et 500.

Le catalogue des biens est désormais servi par Laravel/Livewire sur la branche du lot 3, avec filtres serveur, pagination, photos et demandes de visite. Les fichiers HTML statiques restent l'ancienne version de référence. Les formulaires contact et FAQ gardent leur comportement WhatsApp historique côté site statique.

Le dossier `frontoffice/images/` contient 49 fichiers réels selon la demande métier. Il comprend notamment les visuels d'équipe, services, partenaires, héros et articles.

### Backoffice

Les maquettes couvrent notamment les biens, articles, FAQ, services, pages, médiathèque, témoignages, partenaires, statistiques, encarts, équipe, valeurs, processus, messages, visites, newsletter, réglages, utilisateurs, menus et référentiels. L'application Laravel de la branche du lot 3 contient déjà les composants Livewire correspondants, notamment `BienFormulaire`, `BienListe`, `CatalogueDesBiens`, `DemandeDeVisiteListe` et les composants de réglages.

Les icônes de navigation de la sidebar sont déjà centralisées dans `backoffice/_build/layout.py` via le dictionnaire `ICONS` et la structure `MENUS`. Ce point doit être vérifié visuellement et complété uniquement pour les modules qui n'ont pas encore un pictogramme pertinent.

La médiathèque parcourt déjà réellement le dossier d'images dans le générateur, mais cela reste un inventaire de maquette tant qu'aucune persistance n'est branchée.

## 4. Liste de suivi demandée

Les cases indiquent l'état au moment de cette documentation, pas une promesse de livraison.

- [~] **1. Images et recadrage à l'import** : un module navigateur commun recadre désormais les fichiers raster de tous les champs `type=file` image Livewire avant leur remise au composant; les SVG restent natifs. Il reste à vérifier visuellement chaque format et à traiter les éventuelles variantes de dimensions par emplacement.
- [~] **2. Boutons WhatsApp et tawk.io** : un include commun est ajouté aux deux layouts publics Laravel, avec ouverture différée de tawk.io. Les pages statiques historiques doivent encore recevoir le même include ou leur équivalent avant de déclarer le point terminé.
- [x] **3. Icônes des modules de sidebar** : chaque entrée de la navigation Laravel utilise maintenant le composant d'icône admin avec un pictogramme sémantique.
- [~] **4. Favicon et identité navigateur** : le favicon et l'icône tactile du backoffice utilisent désormais le logo SCI4K publié. Les pages statiques et la génération des formats ICO/SVG restent à vérifier.
- [x] **5. Filtres de la page des biens** : `/biens` Laravel reprend désormais la structure de `frontoffice/biens.html` — segment offre, quatre filtres, pastilles, grille et modale — avec des données Livewire issues du backoffice. Vérification navigateur effectuée : `villa` + `cocody` + `5+ pièces` restent sur `/biens` et réduisent la grille à 1 résultat ; les options viennent toujours des référentiels administrables.
- [~] **6. Fiche d'un bien en modal + aperçu backoffice** : la fiche s'ouvre maintenant dans le catalogue Livewire sans changement d'URL, et l'éditeur affiche un aperçu vivant. Il reste à couvrir la navigation clavier complète et à factoriser davantage le fragment descriptif.
- [~] **7. Toasts du backoffice** : le composant toast existant est maintenant alimenté par les messages flash après les actions redirigées. Les actions inline et les erreurs doivent encore être harmonisées.
- [~] **8a. Médiathèque** : l’écran Laravel `/admin/mediatheque` inventorie les images réellement synchronisées dans `public/images/`, avec recherche par nom, filtre de format, aperçu et chemin exploitable. Le rattachement persistant aux contenus reste à modéliser.
- [x] **8b. Régie d'encarts** : l'encart d'accueil possède des dates de début/fin, un statut de diffusion calculé et un compteur d'impressions incrémenté lorsqu'il est effectivement servi. L'emplacement reste `ad.house`, conformément au modèle existant.
- [x] **8c. Fréquentation** : le middleware Laravel enregistre les pages publiques avec session anonymisée et l'écran `/admin/frequentation` expose pages vues, visiteurs estimés et agrégats par jour sur 7, 30, 90 ou 365 jours.
- [~] **8d. Pages statiques** : Contact, Mentions légales et Politique de confidentialité sont persistantes, bilingues, publiables et éditables depuis `/admin/pages-editables`. Les contenus juridiques définitifs restent à fournir par la direction.

## 5. Ordre de réalisation recommandé

1. Stabiliser les surfaces partagées : identité/favicon, widgets WhatsApp/tawk.io, icônes sidebar et toasts.
2. Corriger le parcours biens : filtres sans redirection, modal de détail, aperçu dans l'édition.
3. Mettre en place le composant d'upload/crop commun et l'appliquer à tous les emplacements image.
4. Brancher la médiathèque sur l'inventaire réel et définir les relations médias/contenus.
5. Cadrer les encarts et la fréquentation avant de connecter leurs indicateurs.
6. Connecter les pages statiques et les autres contenus à une source de données persistante.
7. Ajouter tests de non-régression frontoffice, tests des interactions et validation des permissions quand l'authentification sera implémentée.

## 6. Risques et décisions à prendre

- **Branche de travail** : utiliser `worktree-lot3-reglages` comme base confirmée du lot 3 et vérifier les commits plus récents avant chaque lot.
- **Périmètre technique** : Laravel est la cible confirmée. Les choix upload, crop, persistance, auth, analytics et toasts doivent respecter l'architecture déjà en place dans le backoffice fonctionnel, sans recréer un serveur parallèle.
- **Source des contenus** : définir la base de données et les contrats entre frontoffice et backoffice avant de remplacer les données de démonstration.
- **Images** : décider stockage local ou objet, noms générés, variantes, quota, nettoyage des fichiers orphelins et règles de sécurité d'upload.
- **Analytics** : choisir une solution compatible avec les obligations de consentement et le contexte géographique, puis documenter les données collectées.
- **Encarts** : définir les emplacements autorisés, le fuseau horaire, les priorités en cas de chevauchement et la définition exacte d'une impression.
- **Données personnelles** : les messages, demandes de visite, newsletter et statistiques peuvent contenir des données personnelles ; définir conservation, accès, export et suppression.
- **Sécurité** : les uploads, permissions, données de contact et scripts tiers devront faire l'objet d'une revue dédiée avant mise en production.

## 7. Vérifications minimales après chaque lot

```bash
python3 tools/verifier-site.py
cd backoffice && python3 _build/build.py
cd ../frontoffice && python3 -m http.server 8777
```

Vérifier également manuellement les vues desktop et mobile, la console JavaScript, les chemins d'images, le clavier dans les modales et l'absence de références Laravel résiduelles dans les favicons.

## 8. Informations pour une autre IA

- Commencer par lire ce fichier, `README.md`, `ECARTS_FRONT_BACKOFFICE.md`, les sources `_build/` et le code Laravel de `worktree-lot3-reglages` avant toute édition.
- Pour le backoffice, éditer les générateurs Python puis régénérer les HTML.
- Pour le frontoffice, vérifier `assets/main.js` avant de modifier un libellé : le dictionnaire bilingue peut réécrire le HTML au chargement.
- Préserver les changements utilisateurs déjà présents dans le dépôt ; ne jamais réinitialiser ou écraser des fichiers sans les lire.
- Toute nouvelle fonctionnalité nécessitant serveur, base, authentification ou fichiers persistants doit être implémentée dans l'application Laravel existante et alignée avec l'architecture du lot 3 ; ne pas la simuler uniquement dans les HTML.
- Le document doit être mis à jour à la fin de chaque lot, en cochant uniquement ce qui est réellement vérifié.

## 9. Historique de relais

- **27/08/2026** : état initial documenté à partir du dépôt local et de la demande métier. Aucun des huit lots ci-dessus n'est déclaré terminé dans ce document.
- **27/08/2026** : précision projet ajoutée : les serveurs Laravel et un backoffice fonctionnel existent déjà ; le projet est considéré comme étant au lot 3. La branche `worktree-lot3-reglages` contient les sources à utiliser.
- **27/08/2026** : assets frontoffice synchronisés dans Laravel, toasts inline ajoutés aux listes partagées, médiathèque Laravel créée et validée par cache Blade et navigation locale. L'écran est accessible à `/admin/mediatheque` avec recherche, filtre de format et aperçu.
- **27/08/2026** : présentation équipe interactive, favicon/logo public, filtres sans redirection, lien « Voir le site », régie d'encarts, fréquentation réelle et pages éditables ajoutés dans Laravel. Les validations PHP/Blade, routes, HTTP et vérificateur statique passent.
- **27/08/2026** : le catalogue Laravel `/biens` a été réaligné sur la maquette `frontoffice/biens.html` sans basculer le rendu final vers le statique. Test multi-filtres et ouverture de fiche modale validés dans le navigateur.
- **27/08/2026** : correction du blanc après filtre : les cartes du catalogue Laravel n'utilisent plus l'animation `.reveal` qui les laissait invisibles après remplacement Livewire. `/biens.html` redirige vers `/biens`; test navigateur confirmé avec filtres conservés et seules les cartes hors critère masquées.
- **27/08/2026** : correction complémentaire du même défaut sur le hero, la carte de recherche et la barre de pastilles : leur animation `.reveal` les masquait aussi après filtrage. Test navigateur confirmé : ces trois zones restent visibles avant/après, seules les cartes hors critère disparaissent.
