# Rapport de préparation au déploiement — 13 octobre 2026

**Rien de ce qui suit n'est déployé.** La production (`master` = `b1a8c20`,
déploiement `b3e087cb`) n'a pas été modifiée. Aucune PR n'est fusionnée ; les
trois sont en brouillon vers `dev`.

## 1. Branches et vérifications réelles

| PR | Branche | Commit | Contenu | CI GitHub (constatée) |
|---|---|---|---|---|
| #14 | `feat/sauvegardes-externes` | `db19a6c` | sauvegarde chiffrée vers S3, inerte par défaut | Pint, PHPStan, tests SQLite, tests MySQL 9.7.2, Sonar, Semgrep : **tous réussis** |
| #15 | `fix/seo-ux` (contient `fix/adresses-obsoletes`) | `3e76055` | `/index.php` en 301, actualités vides, mentions légales publiables, chiffres clés à 768 px, zones tactiles, adresses obsolètes | idem : **tous réussis** |
| #16 | `docs/etat-post-lancement` | voir la PR | documentation seule | idem : **tous réussis** |

- Local, `fix/seo-ux` fusionnée : 1 499 tests, 0 échec (SQLite), PHPStan 0 erreur, Pint propre.
- Local, `feat/sauvegardes-externes` : 1 508 réussis, 12 ignorés ; tests de sauvegarde passés aussi sur MySQL.
- Un premier échec SonarCloud sur #14 (`require` → `require_once`, règle S2003, mineur) a été corrigé en `db19a6c` ; la CI a été relancée et constatée verte.
- `fix/adresses-obsoletes` n'a pas de PR propre : son unique commit est inclus dans #15.
- Les exécutions locales ont d'abord échoué par manque de mémoire Windows (fichier de pagination), pas à cause des tests.

## 2. Ce qui n'est PAS prouvé

- Sauvegarde : jamais essayée sur un vrai Backblaze B2 (aucun compte), ni dans le conteneur Railway, ni avec la vraie alerte externe. Le stockage utilisé était un S3 local (SeaweedFS). L'absence de droit de suppression de la clé B2 est une configuration **à vérifier chez le fournisseur** avant de la dire valide.
- Planificateur à 03:20 UTC : le mécanisme (`schedule:work`) est prouvé par les tâches de 03:10 et 03:40 en production, pas par la sauvegarde elle-même.
- Restauration : prouvée en local (base MySQL neuve, lignes et fichiers identiques à la source), jamais sur la production.
- Rien de public n'a été vu en ligne pour les correctifs `fix/seo-ux` : tout est testé localement et en CI.

## 3. Risques connus

- **Certificat de `sci4k.com`** (Plesk) : expire le 10 décembre 2026 ; à contrôler vers le 10 novembre.
- **Région Railway `sfo`** : premier octet HTML de 0,9 à 1,5 s mesuré ; migration de la base nécessaire pour changer de région.
- **Enregistrements `nouveau.sci4k.com`** orphelins vers Railway : à retirer côté Plesk.
- Port MySQL 3306 de l'ancien serveur joignable depuis Internet : à signaler à l'hébergeur.
- Politique de confidentialité : ne nomme ni Railway ni Resend, ne traite pas du transfert hors de Côte d'Ivoire : relecture juridique.
- Mentions légales : ligne d'agrément d'exercice omise volontairement, décision juridique en attente.
- Sauvegardes de la production : toujours manuelles tant que la sauvegarde externe n'est pas activée.

## 4. Ordre de fusion et de mise en ligne recommandés

1. Fusionner #15, #14 et #16 dans `dev` (indépendantes ; #15 avant #14 si conflit de documentation).
2. PR `dev` → `preprod`, CI verte.
3. PR `preprod` → `master`, CI verte.
4. **Sauvegarde préalable** de la base et des fichiers (procédure de `PREMIER_DEPLOIEMENT.md`), vérifiée.
5. `./tools/verifier-avant-deploiement.sh && railway up --service sci4k` depuis `master` propre.
6. Contrôles : `/index.php/biens` répond 301 vers `/biens`, `/actualites` sans article, `/robots.txt` inchangé (`Disallow: /`).

La sauvegarde externe reste **désactivée** après ce déploiement (`SAUVEGARDE_ACTIVE` absent).

## 5. Retour arrière

- Application : *Rollback* Railway vers le déploiement précédent (72 h).
- Code : aucun correctif ne modifie le schéma de base ; la migration de `fix/adresses-obsoletes` ne s'applique qu'aux pages légales vides. Sauvegarde de la base avant toute migration.
- Jamais de retour arrière sur MySQL sans la sauvegarde vérifiée.

## 6. Autorisations et actions restantes (rien n'est fait sans accord)

| Action | Qui |
|---|---|
| Fusionner #14, #15, #16 puis `dev` → `preprod` → `master` | vous |
| Déployer (`railway up`) | vous |
| Créer le compte Backblaze B2, le bucket, la clé sans suppression, les règles de cycle de vie, vérifier les droits réels | vous (compte, éventuellement payant) |
| Générer et conserver la clé de chiffrement hors de Railway | vous |
| Créer le contrôle d'alerte (healthchecks ou équivalent) | vous |
| Renseigner les variables Railway de la sauvegarde, puis activer | vous, après essai sur un vrai bucket |
| `mentions-legales:publier` | vous, après décision sur l'agrément |
| Adresse affichée de `audray@sci4k.com` | vous : confirmer auprès de l'administrateur Google Workspace, ou vider le champ |
| Témoignages et chiffres de démonstration | inchangés, par décision |
| Indexation, comptes, rôles, DNS, messagerie | inchangés, par décision |
