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

**Constaté le 6 octobre 2026, depuis le conteneur :** `smtp.gmail.com` sur
587 et 465, et `smtp-relay.gmail.com` sur 587, ne répondent pas (délai
dépassé) ; seul `smtp.resend.com:2587` passe. Le plan Hobby de Railway bloque
les ports SMTP usuels. Google Workspace par SMTP suppose donc le plan Pro.
Le destinataire des notifications (*Configuration* → *Contact*) est vide :
les formulaires s'enregistrent, mais personne n'est prévenu.

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

### État au 6 octobre 2026 (soir)

**Domaine `sci4k.com` vérifié dans Resend** (région Irlande). Trois
enregistrements **ajoutés** dans Plesk, valeurs relues dans l'écran de
Resend (la clé DKIM validée comme clé RSA de 1 024 bits avant saisie) :

| Type | Nom | Valeur |
|---|---|---|
| TXT | `resend._domainkey` | `p=MIGfMA0GCSqGSIb3…IDAQAB` (clé publique DKIM de Resend) |
| CNAME | `rsend` | `rsend-euw1.forge.rmta.net` |
| CNAME | `send` | `send.forge.rmta.net` |

MX, SPF, DMARC et vérification Google relus après : inchangés. La signature
DKIM porte `d=sci4k.com`, ce qui satisfait le DMARC strict (`adkim=s`) sans
toucher au SPF.

Configuration (*Configuration* → *Messagerie* / *Contact*) : expéditeur
`noreply@sci4k.com`, destinataire des formulaires `contact@sci4k.com`.
Variables Railway `MAIL_FROM_ADDRESS=noreply@sci4k.com` et
`MAIL_FROM_NAME=SCI4K`. Essai depuis *Configuration* : **délivré**.

**`contact@sci4k.com` n'existe pas chez Google Workspace** : la notification
d'essai est revenue en erreur permanente (« The email account that you tried
to reach does not exist »), et Resend a placé l'adresse sur sa liste de
suppression. Cette adresse est aussi celle que le site publie. À faire :
créer la boîte ou l'alias dans Workspace, puis retirer l'adresse de la liste
de suppression de Resend.

**Décision du 6 octobre 2026 : la réception passe sur `info@acsgroupe.ci`**,
la boîte Google Workspace que l'entreprise relève réellement. `contact@sci4k.com`
n'est plus utilisée, ni créée. Expédition inchangée : `noreply@sci4k.com` par
Resend.

| Rôle | Adresse | Où |
|---|---|---|
| Destinataire de toutes les notifications (contact, visites, commentaires) et adresse de réponse des réponses aux visiteurs | `info@acsgroupe.ci` | *Configuration* → `destinataire_formulaire` |
| Adresse publique (pied de page, page Contact, page de maintenance, `security.txt`, schema.org) | `info@acsgroupe.ci` | *Configuration* → `email_public` ; repli dans le code : `Parametre::EMAIL_PUBLIC_PAR_DEFAUT` |
| Expéditeur | `noreply@sci4k.com` | *Configuration* → `expediteur_adresse` ; `MAIL_FROM_ADDRESS` sur Railway |

Essais du même jour : formulaire de contact et demande de visite, tous deux
**« Delivered »** vers `info@acsgroupe.ci` dans Resend ; aucun envoi vers
`contact@sci4k.com`.

**Retour arrière Resend** : supprimer les trois enregistrements ci-dessus
dans Plesk ; remettre l'ancien expéditeur dans *Configuration*.

## 7. Sauvegardes du WordPress (point de restauration)

Plesk, *Gestionnaire de sauvegardes* de `sci4k.com` : **11 sauvegardes
planifiées hebdomadaires** « All configuration and content », du 26 juillet
au 4 octobre 2026 (1,10 Go au total) ; complètes les 26 juillet, 23 août et
20 septembre (≈ 258 Mo), incrémentales entre-temps. La page de restauration
de celle du 4 octobre s'ouvre sans avertissement d'intégrité.

**Elles sont toutes stockées sur le serveur lui-même** (`type=local`) : une
perte du serveur les emporterait avec le site. Avant la bascule, en
télécharger une hors du serveur (*Gestionnaire de sauvegardes* → la
sauvegarde → *Télécharger*), ou configurer un stockage distant.

## 8. Bascule publique — phase A faite (6 octobre 2026, 20 h 10 – 20 h 30 UTC)

Bascule en **deux phases**, décidée pour éviter une boucle de redirection :
l'ancien `www` (CNAME `sci4k.com`) avait une durée de vie de 24 h, et le
WordPress renvoie `www.sci4k.com/…` vers `sci4k.com/…`. Rediriger aussitôt
`sci4k.com` vers `www` aurait fait tourner en rond, pendant 24 h, tout
visiteur dont le résolveur gardait l'ancien `www`.

**Phase A (faite)**

| Étape | Résultat |
|---|---|
| État avant, sauvegardé | `~/sauvegardes-sci4k/bascule-20261006-2000-etat-avant.md` |
| Railway | `nouveau.sci4k.com` retiré ; `www.sci4k.com` ajouté (id `c0309451…`) |
| Plesk DNS, `www` | CNAME `sci4k.com.` (TTL 86400) → **`s1vxivt9.up.railway.app.`, TTL 3600** (retour arrière en une heure) |
| Plesk DNS, ajout | TXT `_railway-verify.www` = `railway-verify=a5527706f0c521fa259321a16d50fb92ce22400ec8b163a469e08596620afbd6` |
| Inchangés, relus | `@`, MX, SPF, DMARC, DKIM (Google, Resend), vérification Google |
| Railway | domaine vérifié ; certificat Let's Encrypt valide jusqu'au 4 janvier 2027 |
| `APP_URL` | `https://www.sci4k.com` ; redéploiement `052b690b` en `SUCCESS` |
| Contrôles | 23 adresses publiques en 200 ; `http://www` → 301 `https://www` (chemin et requête conservés) ; plan du site : 18 adresses en `https://www.sci4k.com` ; aucun lien interne cassé (51) ; aucune référence à `nouveau.sci4k.com` ; liens générés hors requête (courriels, passkeys) en `www` ; contact et visite livrés à `info@acsgroupe.ci` ; `Disallow: /` et `noindex, nofollow` maintenus |

`sci4k.com` sert toujours le WordPress. `nouveau.sci4k.com` ne répond plus
(domaine retiré de Railway) ; ses deux enregistrements restent dans Plesk,
sans effet.

**Phase B (à faire, pas avant le 7 octobre 2026 vers 21 h UTC)** : dans
Plesk, redirection 301 de `sci4k.com/*` vers `https://www.sci4k.com/*`, sans
toucher au WordPress ni à la messagerie ; puis contrôle des quatre variantes
(`http`/`https`, avec et sans `www`) et de l'absence de boucle. Ensuite
seulement : indexation.

**Retour arrière de la phase A** : Plesk, `www` = CNAME `sci4k.com.` ; retirer
`_railway-verify.www` ; Railway, `APP_URL` sur l'adresse Railway. Effet en
une heure au plus (TTL 3600). Le WordPress n'a pas été modifié.

