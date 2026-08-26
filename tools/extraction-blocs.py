#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Reprend les blocs de contenu de l'accueil et de la presentation.

Meme motif que tools/extraction-articles.py et tools/extraction-services-faq.py,
et memes garde-fous, qui ne sont pas negociables :

  - JAMAIS de `unicode_escape`. Le piege avait corrompu tous les accents des
    douze articles du lot 1 sans qu'aucun test ne le voie. `denoter()` defait
    les echappements JavaScript sans toucher a l'encodage.
  - L'anglais vient du dictionnaire de main.js, jamais d'une traduction
    automatique : les textes anglais du site sont humains, et leur recuperation
    a coute une investigation entiere.
  - Verifier AVANT d'ecrire : un champ introuvable interrompt le script en le
    nommant, plutot que d'ecrire une chaine vide que personne ne remarquerait
    avant de relire le site.

    python3 tools/extraction-blocs.py
"""
import io
import json
import os
import re
import sys

RACINE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FRONT = os.path.join(RACINE, 'frontoffice')
SORTIE = os.path.join(RACINE, 'app-laravel', 'database', 'data')


def lire(chemin):
    return io.open(os.path.join(FRONT, chemin), encoding='utf-8').read()


accueil = lire('index.html')
presentation = lire('presentation.html')
js = lire('assets/main.js')
css = lire('assets/images.css')

manques = []


def denoter(s):
    """Defait les echappements JavaScript sans toucher a l'encodage."""
    return (s.replace('\\\\', '\x00')
             .replace("\\'", "'").replace('\\"', '"')
             .replace('\\n', '\n').replace('\\t', '\t')
             .replace('\x00', '\\'))


def texte(cle, obligatoire=True):
    """Le couple (francais, anglais) d'une cle du dictionnaire."""
    motif = (r'"' + re.escape(cle) + r'":\s*\{\s*fr:\s*"((?:[^"\\]|\\.)*)"'
             r'\s*,\s*en:\s*"((?:[^"\\]|\\.)*)"')
    m = re.search(motif, js)

    if not m:
        if obligatoire:
            manques.append(cle)
        return ('', '')

    return (denoter(m.group(1)), denoter(m.group(2)))


def paire(prefixe, champ, obligatoire=True):
    fr, en = texte('%s.%s' % (prefixe, champ), obligatoire)
    return {champ + '_fr': fr, champ + '_en': en}


# ------------------------------------------------- reglages de section (23)
# Chaque section du site porte le meme triplet etiquette / titre / chapo. Le
# cadrage n'en annonçait que deux ; les relever tous coute le meme code.
SECTIONS = [
    'home.hero', 'home.services', 'home.testimonials', 'home.partners',
    'home.articles', 'home.cta',
    'about.page', 'about.overview', 'about.dg', 'about.values', 'about.team',
    'services.page', 'services.process',
    'news.page', 'news.cta',
    'faq.page', 'faq.ask',
    'contact.page', 'contact.form', 'contact.map',
    'biens.page', 'biens.filters',
    'ad.house',
]

reglages = []

for slug in SECTIONS:
    # tag, title et lede ne sont pas tous presents partout : une section qui
    # n'a pas d'etiquette n'est pas une anomalie, c'est un choix de maquette.
    etiquette = texte(slug + '.tag', obligatoire=False)
    titre = texte(slug + '.title', obligatoire=False)
    chapo = texte(slug + '.lede', obligatoire=False)

    if not any(v for couple in (etiquette, titre, chapo) for v in couple):
        manques.append('section entierement vide : ' + slug)
        continue

    reglages.append({
        'slug': slug,
        'etiquette_fr': etiquette[0], 'etiquette_en': etiquette[1],
        'titre_fr': titre[0], 'titre_en': titre[1],
        'chapo_fr': chapo[0], 'chapo_en': chapo[1],
    })


# --------------------------------------------------------- temoignages (3)
#
# Le nom de l'auteur n'est PAS dans le dictionnaire : c'est un nom propre,
# identique dans les deux langues, ecrit dans le HTML. Il en va de meme des
# initiales de l'avatar et du nombre d'etoiles. Les chercher dans le
# dictionnaire aurait produit trois champs vides — le garde-fou l'a signale
# avant qu'ils ne soient ecrits.
temoignages = []

cartes = re.findall(r'<div class="testimonial-card.*?(?=<div class="testimonial-card|</div>\s*</div>\s*</section>)',
                    accueil, re.S)

if len(cartes) != 3:
    manques.append('temoignages : %d cartes trouvees sur la page, 3 attendues' % len(cartes))

for rang, carte in enumerate(cartes, start=1):
    prefixe = 'home.testimonials.t%d' % rang
    citation = texte(prefixe + '.quote')
    role = texte(prefixe + '.role')

    nom = re.search(r'class="testimonial-name">([^<]+)<', carte)
    initiales = re.search(r'class="testimonial-avatar">([^<]+)<', carte)
    etoiles = re.search(r'class="testimonial-stars">([^<]+)<', carte)

    for champ, trouve in (('nom', nom), ('initiales', initiales), ('etoiles', etoiles)):
        if not trouve:
            manques.append('temoignage %d : %s introuvable sur la page' % (rang, champ))

    temoignages.append({
        'ordre': rang,
        'auteur': nom.group(1).strip() if nom else '',
        'initiales': initiales.group(1).strip() if initiales else '',
        'note': etoiles.group(1).count('★') if etoiles else 5,
        'citation_fr': citation[0], 'citation_en': citation[1],
        'role_fr': role[0], 'role_en': role[1],
    })


# --------------------------------------------------------- chiffres cles (3)
chiffres = []

for rang in range(1, 4):
    intitule = texte('home.hero.stat%d' % rang)
    cible = re.search(r'data-target="(\d+)"[^<]*</b><span data-i18n="home\.hero\.stat%d"' % rang, accueil)

    if not cible:
        manques.append('chiffre cle %d : valeur data-target introuvable' % rang)

    chiffres.append({
        'ordre': rang,
        'valeur': int(cible.group(1)) if cible else 0,
        'intitule_fr': intitule[0], 'intitule_en': intitule[1],
    })


# --------------------------------------------------------------- valeurs (4)
valeurs = []

for rang in range(1, 5):
    titre = texte('about.values.v%dtitle' % rang)
    texte_ = texte('about.values.v%dtext' % rang)

    valeurs.append({
        'ordre': rang,
        'titre_fr': titre[0], 'titre_en': titre[1],
        'texte_fr': texte_[0], 'texte_en': texte_[1],
    })


# ---------------------------------------------------------- partenaires (7)
#
# Le nom, le logo et l'adresse d'un partenaire sont des donnees propres, pas du
# texte traduisible : rien de tout cela ne figure au dictionnaire.
partenaires = []

# Deux des sept partenaires n'ont pas de site : leur carte est un <div> et non
# un <a>. Le motif accepte donc les deux balises, et l'adresse est facultative.
# Ne chercher que les ancres en aurait perdu deux en silence.
cartes_partenaires = re.findall(r'<(?:a|div) class="partner-logo-card".*?</(?:a|div)>\s*(?=<(?:a|div) class="partner-logo-card"|</div>)',
                                accueil, re.S)

if len(cartes_partenaires) < 7:
    cartes_partenaires = re.split(r'(?=<(?:a|div) class="partner-logo-card")', accueil)[1:]

for rang, carte in enumerate(cartes_partenaires, start=1):
    adresse = re.search(r'class="partner-logo-card"\s+href="([^"]+)"', carte)
    logo = re.search(r'<img src="([^"]+)"', carte)
    nom = re.search(r'class="p-name">([^<]+)<', carte)

    for champ, trouve in (('logo', logo), ('nom', nom)):
        if not trouve:
            manques.append('partenaire %d : %s introuvable' % (rang, champ))

    partenaires.append({
        'ordre': rang,
        'nom': nom.group(1).strip() if nom else '',
        'logo': logo.group(1) if logo else '',
        'site': adresse.group(1) if adresse else '',
    })

if len(partenaires) != 7:
    manques.append('partenaires : %d trouves, 7 attendus' % len(partenaires))


# --------------------------------------------------------------- equipe (4)
#
# Le nom est un nom propre, ecrit dans le HTML ; la fonction, la biographie et
# l'etiquette viennent du dictionnaire.
equipe = []

for rang, carte in enumerate(re.findall(r'<div class="team-card.*?(?=<div class="team-card|</div>\s*</div>\s*</section>)',
                                        presentation, re.S), start=1):
    nom = re.search(r'<h4>([^<]+)</h4>', carte)

    if not nom:
        manques.append('membre %d : nom introuvable' % rang)

    etiquette = texte('about.team.badge%d' % rang)
    fonction = texte('about.team.role%d' % rang)
    biographie = texte('about.team.desc%d' % rang)

    equipe.append({
        'ordre': rang,
        'nom': nom.group(1).strip() if nom else '',
        'etiquette_fr': etiquette[0], 'etiquette_en': etiquette[1],
        'fonction_fr': fonction[0], 'fonction_en': fonction[1],
        'biographie_fr': biographie[0], 'biographie_en': biographie[1],
    })

if len(equipe) != 4:
    manques.append('equipe : %d membres trouves, 4 attendus' % len(equipe))


# ---------------------------------------------------------------- encart (1)
titre_cta = texte('home.cta.title')
texte_cta = texte('home.cta.text')
bouton_cta = texte('home.cta.btn')

encarts = [{
    'slug': 'accueil',
    'ordre': 1,
    'titre_fr': titre_cta[0], 'titre_en': titre_cta[1],
    'texte_fr': texte_cta[0], 'texte_en': texte_cta[1],
    'libelle_bouton_fr': bouton_cta[0], 'libelle_bouton_en': bouton_cta[1],
    'cible_bouton': '/contact.html',
}]


# ------------------------------------------------------- images de fond (20)
#
# Relevees depuis images.css, qui reste la source unique : le nom du fichier ne
# se deduit pas de la variable — leçon du lot 2a, ou « gestion » s'appuyait sur
# gestion-location.jpg.
images = []

for rang, (variable, chemin) in enumerate(
        re.findall(r'--img-([a-z0-9-]+):\s*url\([\'"]?\.\./([^\'")]+)', css), start=1):
    images.append({
        'ordre': rang,
        'slug': variable,
        'fichier': 'images/' + chemin.split('images/', 1)[1] if 'images/' in chemin else chemin,
    })

if len(images) != 20:
    manques.append('images de fond : %d trouvees, 20 attendues' % len(images))


# ------------------------------------------------------------- resultat brut
familles = {
    'reglages-de-section.json': reglages,
    'temoignages.json': temoignages,
    'chiffres-cles.json': chiffres,
    'valeurs.json': valeurs,
    'partenaires.json': partenaires,
    'equipe.json': equipe,
    'encarts.json': encarts,
    'images-de-fond.json': images,
}

if manques:
    print("Extraction interrompue : %d champ(s) introuvable(s)." % len(manques))
    for m in manques[:20]:
        print('  -', m)
    sys.exit(1)

for nom, donnees in familles.items():
    chemin = os.path.join(SORTIE, nom)
    with io.open(chemin, 'w', encoding='utf-8') as f:
        f.write(json.dumps(donnees, ensure_ascii=False, indent=2) + "\n")
    print('%-28s %d entrees' % (nom, len(donnees)))
