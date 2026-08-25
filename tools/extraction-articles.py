#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Extraction ponctuelle des douze articles du frontoffice (tache 6).

Lit frontoffice/actualites.html et frontoffice/assets/main.js, et regenere
app-laravel/database/data/articles.json consomme par ArticleImportSeeder.

    python3 tools/extraction-articles.py

CE SCRIPT EST PONCTUEL, PAS UN PIPELINE. Il n'est pas rejoue automatiquement
et n'a pas vocation a etre integre a un CI. Ne le relancer que si le contenu
de frontoffice/actualites.html ou frontoffice/assets/main.js change ; sinon
le JSON deja committe suffit.

--------------------------------------------------------------------------
POURQUOI NE JAMAIS DECODER AVEC `s.encode().decode('unicode_escape')`
--------------------------------------------------------------------------
Le dictionnaire JavaScript de main.js contient des textes deja en UTF-8, avec
seulement quelques echappements litteraux (\', \", \n, \t) a defaire - ce
n'est PAS du texte encode en \\uXXXX qui justifierait un decodage
unicode_escape.

`s.encode().decode('unicode_escape')` traite chaque octet du texte comme du
Latin-1 avant de le reinterpreter : un texte deja en UTF-8 se retrouve
double-decode, et tous les caracteres accentues sont corrompus. Exemple reel
sur cet extrait : "securiser" devient "sÃ©curiser" (le "e accent aigu",
encode en UTF-8 sur deux octets 0xC3 0xA9, est relu comme deux caracteres
Latin-1 distincts : Ã et ©).

C'est un piege silencieux : les tests de la tache 6 verifient seulement que
les champs ne sont pas vides (`empty($a['titre_fr'])`), et "sÃ©curiser"
n'est pas vide - la corruption ne serait visible qu'a l'affichage, jamais
dans une suite de tests automatisee. Elle toucherait les douze articles,
dans les deux langues, sans qu'aucun test ne le detecte.

La fonction `denoter()` ci-dessous evite ce piege : elle ne touche pas a
l'encodage des caracteres, elle defait seulement les quatre echappements
JavaScript reellement presents dans main.js (\\, \', \", \n, \t). Verifiee
sur les 60 textes des douze articles, dans les deux langues : zero caractere
corrompu (controle automatique en fin de script, voir `suspects`).
"""
import io
import json
import re

html = io.open('frontoffice/actualites.html', encoding='utf-8').read()
js = io.open('frontoffice/assets/main.js', encoding='utf-8').read()

# Cartes : slug, categorie, date, couverture
cartes = []
for m in re.finditer(r'<a class="news-card reveal"(.*?)</a>', html, re.S):
    bloc = m.group(0)
    slug = re.search(r'\?id=([a-z0-9-]+)', bloc)
    cat = re.search(r'data-cat="([^"]+)"', bloc)
    date = re.search(r'data-date="([^"]+)"', bloc)
    img = re.search(r"background-image:url\('([^']+)'\)", bloc)
    cle = re.search(r'data-i18n="news\.a(\d+)\.title"', bloc)
    if not (slug and cat and date and cle):
        raise SystemExit('carte incomplete : ' + bloc[:120])
    cartes.append({
        'slug': slug.group(1), 'categorie': cat.group(1), 'date': date.group(1),
        'image': img.group(1) if img else None, 'index': int(cle.group(1)),
    })


def denoter(s):
    """Defait les echappements JavaScript sans toucher a l'encodage."""
    return (s.replace('\\\\', '\x00')
             .replace("\\'", "'").replace('\\"', '"')
             .replace('\\n', '\n').replace('\\t', '\t')
             .replace('\x00', '\\'))


def texte(cle):
    m = re.search(r'"' + re.escape(cle) + r'":\s*\{\s*fr:\s*"((?:[^"\\]|\\.)*)"\s*,\s*en:\s*"((?:[^"\\]|\\.)*)"', js)
    if not m:
        return None
    return denoter(m.group(1)), denoter(m.group(2))


articles = []
for c in cartes:
    i = c['index']
    titre = texte(f'news.a{i}.title')
    if not titre:
        raise SystemExit(f'titre manquant pour l article {i}')
    paras_fr, paras_en = [], []
    for p in range(1, 9):
        t = texte(f'news.a{i}.p{p}')
        if not t:
            break
        paras_fr.append(t[0]); paras_en.append(t[1])
    if not paras_fr:
        raise SystemExit(f'contenu manquant pour l article {i}')
    articles.append({
        'slug': c['slug'], 'categorie': c['categorie'], 'date': c['date'], 'image': c['image'],
        'titre_fr': titre[0], 'titre_en': titre[1],
        'resume_fr': paras_fr[0], 'resume_en': paras_en[0],
        'contenu_fr': "\n\n".join(paras_fr), 'contenu_en': "\n\n".join(paras_en),
    })

if len(articles) != 12:
    raise SystemExit(f'{len(articles)} articles extraits, 12 attendus')

io.open('app-laravel/database/data/articles.json', 'w', encoding='utf-8').write(
    json.dumps(articles, ensure_ascii=False, indent=2))
print(f'{len(articles)} articles extraits')

# Controle de non-regression sur le decodage : aucune sequence corrompue.
suspects = []
for a in articles:
    for champ in ['titre_fr', 'titre_en', 'contenu_fr', 'contenu_en']:
        t = a[champ]
        if 'Ã' in t or 'â€' in t or '\\u' in t:
            suspects.append(f"{a['slug']}.{champ}")
print(f'textes suspects (encodage) : {len(suspects)}')
for s in suspects:
    print('   ', s)
