# Failles connues des dépendances

Le workflow `.github/workflows/audit-dependances.yml` confronte `composer.lock`
et `package-lock.json` aux bases de failles publiées. Il tourne à chaque
poussée sur `dev`, `preprod` et `master`, sur chaque demande de fusion, et
**chaque lundi** sur `master`, même sans changement de code : une faille se
publie un jour quelconque, sur un paquet déjà installé.

## Ce qui bloque

| Contrôle | Bloque | Signale seulement |
|---|---|---|
| `composer audit --locked --no-dev` | toute faille d'un paquet de `require`, quelle que soit sa gravité | — |
| `composer audit --locked` | — | les outils de développement (`require-dev`) |
| `npm audit --audit-level=high` | les failles hautes et critiques | — |
| `npm audit` | — | les failles faibles et moyennes |
| paquet abandonné | jamais | toujours (`--abandoned=report`) |

**Pourquoi toute gravité côté PHP.** Les paquets de `require` sont le code qui
répond aux visiteurs — Laravel, Livewire, Fortify. Une faille « moyenne » d'un
framework web peut suffire, et sa correction est presque toujours une mise à
jour mineure. Les outils de développement (Pest, Pint, phpstan) ne partent pas
dans l'image : `composer install --no-dev` dans le Dockerfile.

**Pourquoi un seuil côté npm.** Tous les paquets sont déclarés en
`dependencies`, outils de construction compris : `--omit=dev` ne trierait
rien. Un seul atteint le navigateur, `@laravel/passkeys`. Les autres — Vite,
Tailwind, leurs greffons — ne tournent qu'à la construction, et leurs failles
faibles ou moyennes visent pour l'essentiel le serveur de développement
(`npm run dev`), qui ne tourne jamais en production.

**Un paquet abandonné n'est pas une faille.** Il mérite un remplaçant, pas un
déploiement bloqué.

Comportement vérifié sur des fichiers de verrouillage d'essai, avec des
versions connues pour être vulnérables : une faille critique ou haute fait
échouer l'étape npm bloquante, une faille moyenne ou faible la laisse passer ;
une faille dans `require-dev` laisse passer l'étape Composer bloquante, la
même faille dans `require` la fait échouer.

## Quand une alerte tombe

1. Lire l'avis : le paquet, la version corrigée, et **si le code du site
   emprunte le chemin vulnérable**.
2. Mettre à jour, sur `dev` :
   - PHP : `composer update <paquet> --with-dependencies`, puis la suite de
     tests ;
   - npm : `npm install <paquet>@<version corrigée>`, puis `npm run build` et
     la suite de tests.
3. Faire suivre le correctif `dev` → `preprod` → `master`, puis déployer : la
   CI n'est pas sur le chemin de `railway up`, un correctif poussé n'est pas
   un correctif en ligne.

## Exceptions

**PHP.** Si aucune version corrigée n'existe, ou si le site n'emprunte pas le
chemin vulnérable, l'avis peut être ignoré dans `app-laravel/composer.json`,
**avec sa raison** :

```json
"config": {
    "audit": {
        "ignore": {
            "PKSA-xxxx-xxxx-xxxx": "Raison précise, date, et condition de retrait"
        }
    }
}
```

L'avis reste affiché dans le journal de la CI avec cette raison ; seule
l'étape cesse d'échouer. Vérifié sur un fichier d'essai.

**npm.** `npm audit` n'a pas de liste d'exceptions. Une faille haute ou
critique sans correction disponible est une décision humaine, prise dans une
demande de fusion relue, et consignée ici.

## Exceptions en vigueur

Aucune. Au 29 septembre 2026 : `composer audit` ne signale aucune faille ni
aucun paquet abandonné, `npm audit` ne signale aucune faille.

## Ce que ces contrôles ne font pas

- **Ils ne détectent pas un paquet malveillant** qui n'a pas encore fait
  l'objet d'un avis. `npm ci --ignore-scripts` (CI et Dockerfile) empêche
  déjà un paquet d'exécuter du code à l'installation.
- **Ils ne protègent pas un déploiement fait sans passer par la CI.** La mise
  en ligne est un `railway up` lancé depuis un poste : il envoie l'arbre de
  travail, que la CI ait réussi ou non.
