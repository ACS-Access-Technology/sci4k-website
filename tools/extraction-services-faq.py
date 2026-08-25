#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Extraction ponctuelle des services et de la FAQ du frontoffice.

    python3 tools/extraction-services-faq.py

PONCTUEL, PAS UN PIPELINE. A relancer seulement si frontoffice/services.html,
frontoffice/faq.html ou frontoffice/assets/main.js changent.

NE JAMAIS DECODER PAR unicode_escape : voir l'en-tete de
tools/extraction-articles.py. Les textes de main.js sont deja en UTF-8 et
seuls quelques echappements JavaScript sont a defaire ; un decodage
unicode_escape les relit en Latin-1 et corrompt tous les accents, sans qu'aucun
test ne le voie — les tests verifient que les champs ne sont pas vides, et un
champ corrompu ne l'est pas.
"""
import io
import json
import re

svc_html = io.open('frontoffice/services.html', encoding='utf-8').read()
faq_html = io.open('frontoffice/faq.html', encoding='utf-8').read()
js = io.open('frontoffice/assets/main.js', encoding='utf-8').read()


def denoter(s):
    """Defait les echappements JavaScript sans toucher a l'encodage."""
    return (s.replace('\\\\', '\x00')
             .replace("\\'", "'").replace('\\"', '"')
             .replace('\\n', '\n').replace('\\t', '\t')
             .replace('\x00', '\\'))


def texte(cle):
    m = re.search(r'"' + re.escape(cle) + r'":\s*\{\s*fr:\s*"((?:[^"\\]|\\.)*)"\s*,\s*en:\s*"((?:[^"\\]|\\.)*)"', js)
    return (denoter(m.group(1)), denoter(m.group(2))) if m else ('', '')


# --- services, dans l'ordre d'apparition sur la page ---
slugs = []
for m in re.finditer(r'data-svc="([a-z-]+)"', svc_html):
    if m.group(1) not in slugs:
        slugs.append(m.group(1))

services = []
for rang, slug in enumerate(slugs, start=1):
    nom = texte('svc.%s.name' % slug)
    accroche = texte('svc.%s.short' % slug)
    description = texte('svc.%s.desc' % slug)
    bouton = texte('svc.%s.cta' % slug)

    if not nom[0]:
        raise SystemExit('nom manquant pour le service ' + slug)

    entree = {
        'slug': slug, 'ordre': rang,
        'nom_fr': nom[0], 'nom_en': nom[1],
        'accroche_fr': accroche[0], 'accroche_en': accroche[1],
        'description_fr': description[0], 'description_en': description[1],
        'libelle_bouton_fr': bouton[0], 'libelle_bouton_en': bouton[1],
    }

    # accroche, description et libelle_bouton sont attendus sur les six
    # services (contrairement aux atouts, ci-dessous, dont le nombre varie).
    # Une cle renommee dans main.js ferait revenir '' ici sans lever d'erreur
    # plus loin : le champ ne serait pas vide au sens SQL (chaine vide != NULL)
    # mais serait absent au sens metier, sans qu'aucun test ne le voie.
    for champ in ('accroche_fr', 'accroche_en', 'description_fr', 'description_en',
                  'libelle_bouton_fr', 'libelle_bouton_en'):
        if not entree[champ]:
            raise SystemExit('%s manquant pour le service %s' % (champ, slug))

    for n in (1, 2, 3):
        fr, en = texte('svc.%s.feat%d' % (slug, n))
        entree['atout%d_fr' % n] = fr
        entree['atout%d_en' % n] = en

    # Un service peut legitimement n'avoir qu'un ou deux atouts : ce n'est
    # pas une erreur en soi. Ce qui doit alerter, c'est un trou dans la
    # sequence - un atout 3 present alors que l'atout 2 est vide - signe
    # d'une cle renommee ou deplacee dans main.js plutot que d'un choix
    # editorial assume.
    for n in (2, 3):
        present = bool(entree['atout%d_fr' % n]) or bool(entree['atout%d_en' % n])
        precedent = bool(entree['atout%d_fr' % (n - 1)]) or bool(entree['atout%d_en' % (n - 1)])
        if present and not precedent:
            raise SystemExit(
                'atout%d present alors que atout%d est vide pour le service %s (trou dans la sequence)'
                % (n, n - 1, slug))

    # Le trace SVG de la tuile, repris tel quel.
    bloc = re.search(r'data-svc="%s".*?</button>' % re.escape(slug), svc_html, re.S)
    icone = re.search(r'<svg .*?</svg>', bloc.group(0), re.S) if bloc else None
    entree['icone_svg'] = icone.group(0) if icone else None

    services.append(entree)

# --- questions, groupees par service ---
questions = []
for morceau in re.split(r'faq-group-title', faq_html)[1:]:
    groupe = re.search(r'data-i18n="svc\.([a-z-]+)\.name"', morceau)
    if not groupe:
        continue
    slug = groupe.group(1)
    rang = 0
    for q in re.finditer(r'data-i18n="(faq\.q\d+)\.q"', morceau):
        rang += 1
        cle = q.group(1)
        qf, qe = texte(cle + '.q')
        rf, re_ = texte(cle + '.a')
        if not qf or not rf:
            raise SystemExit('question ou reponse manquante : ' + cle)
        questions.append({
            'service_slug': slug, 'ordre': rang,
            'question_fr': qf, 'question_en': qe,
            'reponse_fr': rf, 'reponse_en': re_,
        })

if len(services) != 6:
    raise SystemExit('%d services extraits, 6 attendus' % len(services))
if len(questions) != 12:
    raise SystemExit('%d questions extraites, 12 attendues' % len(questions))

io.open('app-laravel/database/data/services.json', 'w', encoding='utf-8').write(
    json.dumps(services, ensure_ascii=False, indent=2))
io.open('app-laravel/database/data/questions-faq.json', 'w', encoding='utf-8').write(
    json.dumps(questions, ensure_ascii=False, indent=2))

print('%d services, %d questions' % (len(services), len(questions)))

# Controle de non-regression sur le decodage, sur TOUS les champs textuels
# traduits importes - accroche, atouts et libelle de bouton compris, pas
# seulement le nom et la description. icone_svg est exclu : ce n'est pas un
# texte traduit passe par denoter(), mais un trace SVG repris tel quel du
# HTML.
champs_services = (
    'nom_fr', 'nom_en', 'accroche_fr', 'accroche_en',
    'description_fr', 'description_en',
    'libelle_bouton_fr', 'libelle_bouton_en',
    'atout1_fr', 'atout1_en', 'atout2_fr', 'atout2_en', 'atout3_fr', 'atout3_en',
)
champs_questions = ('question_fr', 'question_en', 'reponse_fr', 'reponse_en')

suspects = []
for lot, champs in ((services, champs_services), (questions, champs_questions)):
    for e in lot:
        for c in champs:
            t = e.get(c) or ''
            if 'Ã' in t or 'â€' in t or '\\u' in t:
                suspects.append(c)
print('textes suspects (encodage) :', len(suspects))
