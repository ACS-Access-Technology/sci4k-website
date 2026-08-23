#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Controles de non-regression du site SCI4K.

Rejoue les verifications faites a la main lors des corrections : references
mortes, donnees structurees, champs de formulaire, syntaxe JavaScript,
coherence du plan de site. Sortie 1 si un controle echoue.

    python3 tools/verifier-site.py
"""
import io, json, os, re, subprocess, sys, urllib.parse
from glob import glob

RACINE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FRONT = os.path.join(RACINE, 'frontoffice')
BACK = os.path.join(RACINE, 'backoffice')

anomalies = []


def lire(chemin):
    return io.open(chemin, encoding='utf-8').read()


def pages_html(dossier):
    return sorted(glob(os.path.join(dossier, '*.html')))


def chemin_depuis(base, ref):
    """Resout une reference relative, en decodant l'encodage d'URL."""
    ref = urllib.parse.unquote(ref.split('#')[0].split('?')[0])
    return os.path.normpath(os.path.join(os.path.dirname(base), ref))


# --------------------------------------------------------- references mortes
def controle_references():
    for page in pages_html(FRONT) + pages_html(BACK):
        s = lire(page)
        refs = re.findall(r'(?:href|src)="([^"]+)"', s)
        refs += re.findall(r"url\(['\"]?([^)'\"]+)", s)
        for r in refs:
            if r.startswith(('http', 'mailto:', 'tel:', 'data:', 'javascript:',
                              '#', '//')):
                continue
            cible = chemin_depuis(page, r)
            if not os.path.exists(cible):
                anomalies.append('reference morte : %s -> %s'
                                 % (os.path.relpath(page, RACINE), r))


# ------------------------------------------------------- donnees structurees
def controle_donnees_structurees():
    for page in pages_html(FRONT):
        s = lire(page)
        for bloc in re.findall(r'<script type="application/ld\+json">(.*?)</script>', s, re.S):
            nom = os.path.basename(page)
            try:
                donnees = json.loads(bloc)
            except ValueError as e:
                anomalies.append('JSON-LD illisible dans %s : %s' % (nom, e))
                continue
            # une FAQ declaree doit exister sur la page : l'inverse est du spam
            for noeud in donnees.get('@graph', []):
                if noeud.get('@type') == 'FAQPage':
                    for q in noeud.get('mainEntity', []):
                        if q['name'] not in s:
                            anomalies.append('question declaree absente de %s : %s'
                                             % (nom, q['name'][:60]))


# ------------------------------------------------------ champs de formulaire
def controle_formulaires():
    for page in pages_html(FRONT):
        s = lire(page)
        ids_labels = set(re.findall(r'<label[^>]*\bfor="([^"]+)"', s))
        for champ in re.findall(r'<(?:input|select|textarea)\b[^>]*>', s):
            if re.search(r'type="(hidden|submit|button)"', champ):
                continue
            ident = re.search(r'\bid="([^"]+)"', champ)
            if (ident and ident.group(1) in ids_labels) or 'aria-label' in champ:
                continue
            anomalies.append('champ sans intitule dans %s : %s'
                             % (os.path.basename(page), champ[:70]))


# ----------------------------------------------------------------- syntaxe JS
def controle_javascript():
    for js in sorted(glob(os.path.join(FRONT, 'assets', '*.js'))):
        r = subprocess.run(['node', '--check', js], capture_output=True, text=True)
        if r.returncode != 0:
            anomalies.append('syntaxe JavaScript : %s\n%s'
                             % (os.path.basename(js), r.stderr.strip()[:200]))


# ---------------------------------------------------------------- plan de site
def controle_plan_de_site():
    chemin = os.path.join(FRONT, 'sitemap.xml')
    if not os.path.exists(chemin):
        anomalies.append('sitemap.xml absent')
        return
    for url in re.findall(r'<loc>([^<]+)</loc>', lire(chemin)):
        fichier = url.rstrip('/').split('/')[-1] or 'index.html'
        if not fichier.endswith('.html'):
            fichier = 'index.html'
        if not os.path.exists(os.path.join(FRONT, fichier)):
            anomalies.append('plan de site : %s ne correspond a aucune page' % url)


# ------------------------------------------------------- chargement du script
def controle_chargement_script():
    """main.js doit etre differe.

    Sans `defer`, un script s'execute a l'endroit ou il est rencontre : tout
    element declare apres lui est absent du DOM a ce moment-la. Le bouton
    flottant, place juste apres la balise, est reste inerte sur les douze
    pages pour cette seule raison. `defer` repousse l'execution apres
    l'analyse complete du document et rend l'ordre indifferent.
    """
    for page in pages_html(FRONT):
        s = lire(page)
        for balise in re.findall(r'<script[^>]*src="[^"]*main\.js"[^>]*>', s):
            if 'defer' not in balise and 'async' not in balise:
                anomalies.append('script non differe dans %s : %s'
                                 % (os.path.basename(page), balise))


CONTROLES = [
    ('references internes', controle_references),
    ('donnees structurees', controle_donnees_structurees),
    ('champs de formulaire', controle_formulaires),
    ('syntaxe JavaScript', controle_javascript),
    ('plan de site', controle_plan_de_site),
    ('chargement du script', controle_chargement_script),
]

if __name__ == '__main__':
    for intitule, fonction in CONTROLES:
        avant = len(anomalies)
        fonction()
        ecart = len(anomalies) - avant
        print('%-24s %s' % (intitule, 'ok' if ecart == 0 else '%d anomalie(s)' % ecart))

    if anomalies:
        print('\n%d anomalie(s) :\n' % len(anomalies))
        for a in anomalies:
            print('  - %s' % a)
        sys.exit(1)
    print('\nTous les controles passent.')
