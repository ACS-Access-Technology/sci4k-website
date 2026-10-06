# Domaine `sci4k.com` : pré-production, bascule, retour arrière

État relevé le 6 octobre 2026, en lecture seule (DNS publics, pages publiques,
API Railway). **Rien n'a été modifié sur `sci4k.com`, son DNS ni le serveur
Plesk.**

## 1. Ce qui existe aujourd'hui

| Élément | État vérifié |
|---|---|
| Site public `sci4k.com` | **WordPress** (thème immobilier Apus, Elementor, WooCommerce), « Societé Civile Immobiliere – SCI 4K », sur le serveur `135.125.145.3` (hébergement Plesk, kaeweb) |
| `www.sci4k.com` | CNAME vers `sci4k.com` : même site |
| DNS | servi par le serveur Plesk lui-même (`ns1.sci4k.com`, `ns2.sci4k.com`) : les enregistrements se modifient dans Plesk (`https://sci4k.com:8443/`) |
| Courriel `@sci4k.com` | **Google Workspace** : MX `1 smtp.google.com` |
| SPF | `v=spf1 +a +mx +a:prime.kaeweb.com -all` (strict) |
| DMARC | `v=DMARC1; p=quarantine; adkim=s; aspf=s` (strict) |
| Vérification Google | TXT `google-site-verification=…` |

**À ne jamais toucher dans les opérations ci-dessous :** MX, SPF, DMARC,
vérification Google — le courriel de l'entreprise en dépend.

### WooCommerce

Vu de l'extérieur : **9 produits, tous ceux de démonstration du thème**
(« pocket linen shirt », « t-shirt », « fashion watch », « una chair »…), et
**aucun moyen de paiement activé** (`payment_methods: []` dans l'API publique
de la boutique). WooCommerce n'est donc pas un outil de vente de l'agence.
Le site WordPress porte aussi 2 biens (`/property/villa-f6/`,
`/property/villa-f6-2/`), une agence et 66 pages.

Ce qui ne se voit pas de l'extérieur et se vérifie dans
`https://sci4k.com/wp-admin/` : *WooCommerce → Commandes* (en existe-t-il ?)
et *Utilisateurs* (des comptes clients ?).

## 2. Pré-production : `nouveau.sci4k.com`

Le nouveau site est validé sur un sous-domaine, sans rien changer au site
actuel ni au courriel. Domaine personnalisé créé sur Railway le 6 octobre
2026 (service `sci4k`). Deux enregistrements à **ajouter** dans le DNS Plesk —
ils ne modifient aucun enregistrement existant :

| Type | Nom | Valeur |
|---|---|---|
| CNAME | `nouveau` | `6lbbuvub.up.railway.app` |
| TXT | `_railway-verify.nouveau` | `railway-verify=577fb89f16c25009fff95f48ce7e4d0d63f7aa182030fb43dea35b22a088c2b6` |

Railway émet ensuite le certificat HTTPS de lui-même. Contrôles : `railway
domain status nouveau.sci4k.com --service sci4k`, puis le site sur
`https://nouveau.sci4k.com`. L'indexation reste désactivée (`robots.txt` :
`Disallow: /`).

`APP_URL` reste sur l'adresse Railway tant que le sous-domaine n'est pas
vérifié, puis passe à `https://nouveau.sci4k.com` (liens des courriels, plan
du site, identifiant des passkeys).

**Fait le 6 octobre 2026.** Les deux enregistrements ont été ajoutés dans
Plesk (les 20 existants relus avant et après : MX, SPF, DMARC, vérification
Google et `www` inchangés) ; Railway a vérifié le domaine et émis le
certificat. `APP_URL=https://nouveau.sci4k.com` posé le même jour (aucune
passkey n'existait, rien n'a été invalidé) ; redéploiement `470a1692` en
`SUCCESS`. Contrôlé : pages FR/EN, `/up`, filtres Livewire, plan du site
(18 adresses, toutes sur `nouveau.sci4k.com`), liens générés hors requête
(réinitialisation de mot de passe, fichiers téléversés), `robots.txt`
(`Disallow: /`) et `noindex, nofollow`.

L'adresse `sci4k-production.up.railway.app` répond toujours, avec sa propre
URL canonique (tirée de la requête). Sans conséquence tant que l'indexation
est fermée ; à rediriger vers le domaine définitif au moment de la bascule.

WooCommerce, relu dans l'administration le même jour : **aucune commande**,
et deux comptes seulement, tous deux administrateurs — aucun compte client.

## 3. Deux contraintes qui décident de la bascule

1. **Un domaine racine** (`sci4k.com`, sans `www`) ne peut pointer vers
   Railway qu'avec un DNS offrant le *CNAME flattening* ou un enregistrement
   *ALIAS* (documentation de Railway). Le DNS du serveur Plesk ne l'offre pas.
2. **Le plan Railway n'admet qu'un domaine personnalisé** (`customDomains: 1`,
   lu dans l'API). `nouveau.sci4k.com` l'occupe pendant la pré-production.

D'où le schéma retenu pour la bascule, sans changer de fournisseur DNS ni de
plan :

- **`www.sci4k.com` → Railway** (CNAME), adresse principale du site ;
- **`sci4k.com` reste sur le serveur Plesk**, réduit à une redirection 301
  vers `https://www.sci4k.com` — le MX, lui, ne bouge pas.

(Autre voie : déplacer le DNS vers un fournisseur à *CNAME flattening*, par
exemple Cloudflare, en recopiant fidèlement MX, SPF, DMARC et la vérification
Google. Plus de risques pour le courriel, sans bénéfice ici.)

## 4. Procédure de bascule (après validation explicite)

Préalables : nouveau site validé sur `nouveau.sci4k.com` ; mentions légales
complètes ; contenu de démonstration retiré ; courriel du site authentifié
(§6) ; **sauvegarde complète du WordPress** (fichiers et base) depuis Plesk —
*Sauvegarde et restauration* —, téléchargée hors du serveur.

1. Sauvegarde de la base du nouveau site (`PREMIER_DEPLOIEMENT.md`, §4).
2. Dans Plesk, noter la valeur actuelle de `www` (CNAME vers `sci4k.com`) :
   c'est le retour arrière.
3. Railway : retirer `nouveau.sci4k.com`, ajouter `www.sci4k.com` ; relever
   le CNAME et le TXT de vérification donnés.
4. Plesk, DNS : remplacer le CNAME `www` par la valeur Railway ; ajouter le
   TXT `_railway-verify.www`. **Ne pas toucher** à `@`, MX, SPF, DMARC.
5. Plesk, `sci4k.com` : redirection permanente (301) de tout `sci4k.com/*`
   vers `https://www.sci4k.com/` — le WordPress cesse d'être servi, mais
   reste sur le serveur.
6. Railway : `APP_URL=https://www.sci4k.com` ; attendre le certificat.
7. Vérifier : `https://www.sci4k.com` (pages FR/EN, formulaires, en-têtes,
   journaux), `http://sci4k.com` et `https://sci4k.com` redirigés, courriel
   reçu et envoyé normalement.
8. En dernier : cocher « Autoriser l'indexation », puis déclarer
   `https://www.sci4k.com/sitemap.xml` dans la Search Console.

## 5. Retour arrière

- **DNS** : remettre `www` en CNAME vers `sci4k.com` ; retirer la redirection
  301 de `sci4k.com`. Le WordPress, resté intact sur le serveur, répond de
  nouveau (délai : la durée de vie DNS de l'enregistrement `www`).
- **Railway** : rien à défaire pour le site ; remettre `APP_URL` sur
  l'adresse précédente.
- Le courriel n'a jamais été touché : rien à rétablir.

## 6. Courriel du nouveau site

Configuration actuelle, saisie dans le backoffice (*Configuration*) :
serveur `smtp.resend.com` (port 2587, TLS), expéditeur
**`onboarding@resend.dev`**. C'est l'adresse d'essai de Resend : elle ne
délivre qu'au propriétaire du compte Resend. **Ce n'est pas une
configuration de production.**

Pour envoyer au nom de `@sci4k.com` en passant la politique SPF/DMARC
stricte, deux voies :

- **Resend avec le domaine vérifié** : déclarer `sci4k.com` (ou un
  sous-domaine d'envoi) dans Resend, qui donne des enregistrements DKIM et
  SPF **sur un sous-domaine dédié** — ils s'ajoutent sans modifier le SPF ni
  le DMARC de `sci4k.com`. Puis expéditeur `contact@sci4k.com` ou
  `noreply@sci4k.com`.
- **Google Workspace** : SMTP `smtp.gmail.com` (587, TLS) avec un compte
  `@sci4k.com` et un mot de passe d'application, la signature DKIM de
  Workspace étant activée pour `sci4k.com` dans la console d'administration.

Dans les deux cas, ensuite : un vrai essai de chaque formulaire (contact,
lettre d'information, demande de visite), réception vérifiée et en-têtes
d'authentification (SPF, DKIM, DMARC « pass ») relus.
