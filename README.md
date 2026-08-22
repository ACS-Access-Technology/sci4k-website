# SCI4K — site vitrine et maquettes d'administration

Site de la Société Civile Immobilière SCI4K, à Abidjan : achat, vente,
location, construction et gestion de patrimoine immobilier.

Le dépôt contient deux ensembles distincts.

| Dossier | Contenu | État |
|---|---|---|
| `frontoffice/` | Le site public : 12 pages HTML statiques, une feuille de style, un script | Fonctionnel |
| `backoffice/` | 30 maquettes d'écrans d'administration, générées par des scripts Python | Maquettes only — aucune donnée, aucun serveur |

## Le site public

Aucune dépendance, aucune étape de construction : ce sont des fichiers
statiques. Pour les consulter en local :

```bash
cd frontoffice && python3 -m http.server 8777
```

Puis ouvrir <http://localhost:8777>.

**Bilingue.** Les textes proviennent d'un dictionnaire unique en tête de
`assets/main.js` (`window.SCI4K_I18N`). Le HTML porte des attributs
`data-i18n`, `data-i18n-html`, `data-i18n-ph` et `data-i18n-aria` ; le
contenu affiché est écrit par le script au chargement. **Modifier le HTML
seul ne suffit donc pas** : le dictionnaire l'écrase. Les deux langues se
corrigent ensemble.

**Images de fond.** Toutes sont déclarées comme variables CSS dans
`assets/images.css`, à raison d'une ligne par emplacement. Changer un visuel
revient à changer cette ligne. Les visuels les plus lourds ont une variante
WebP de 800 px, servie sous 820 px de large uniquement.

**Formulaires.** Le contact et la question de la FAQ composent un message
WhatsApp vers le numéro de l'agence, puis laissent le visiteur l'envoyer.
Il n'y a pas de serveur : c'est un choix, pas un manque.

## Les maquettes d'administration

Les 30 pages de `backoffice/` sont **générées**. Ne pas les modifier à la
main : la prochaine génération écraserait le changement.

```bash
cd backoffice && python3 _build/build.py
```

Les sources sont `_build/pages_a.py`, `pages_b.py`, `pages_c.py` et
`layout.py`. La médiathèque construit son inventaire en parcourant réellement
`frontoffice/images/` : ajouter ou retirer une image suffit, il faut ensuite
régénérer.

Ces écrans n'ont ni base de données, ni authentification, ni serveur. Ils
décrivent une administration à construire ; ils ne l'implémentent pas.

## Contrôles

```bash
python3 tools/verifier-site.py
```

Rejoue les vérifications de non-régression : références mortes, données
structurées (dont la concordance entre la FAQ balisée et la FAQ affichée),
intitulés de formulaire, syntaxe JavaScript, cohérence du plan de site.
Ces contrôles tournent aussi à chaque push et sur chaque pull request.

## Documents de conception

| Fichier | Objet |
|---|---|
| `ECARTS_FRONT_BACKOFFICE.md` | Confrontation du site public au périmètre couvert par l'administration |
| `BACKOFFICE_SECTIONS.md` | Champs attendus, section par section |
| `WIREFRAME_BACKOFFICE.md` | Maquettes filaires des écrans |
| `frontoffice/images/A-REMPLACER.md` | Six visuels provisoires issus de Wikimedia, à remplacer par des photographies de l'agence |

## Points ouverts

- Les mentions légales attendent le numéro **RCCM**, le **Compte
  Contribuable** et l'**hébergeur** ; ces valeurs ne figurent nulle part et
  sont à obtenir auprès de la direction.
- Six visuels sont provisoires (voir `A-REMPLACER.md`).
- L'administration reste à implémenter : hébergement, persistance et
  authentification n'ont pas encore été arbitrés.
