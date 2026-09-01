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
FRONT = os.path.join(RACINE, 'maquettes-frontoffice')
BACK = os.path.join(RACINE, 'maquettes-backoffice')

anomalies = []


def lire(chemin):
    return io.open(chemin, encoding='utf-8').read()


def pages_html(dossier):
    return sorted(glob(os.path.join(dossier, '*.html')))


def chemin_depuis(base, ref):
    """Resout une reference relative, en decodant l'encodage d'URL."""
    ref = urllib.parse.unquote(ref.split('#')[0].split('?')[0])
    # Les maquettes backoffice referencent encore ../frontoffice/ qui a ete
    # renomme maquettes-frontoffice/.
    ref = ref.replace('../frontoffice/', '../maquettes-frontoffice/')
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
    """Le plan du site ne doit plus exister sous forme de fichier.

    Il est rendu par Laravel depuis la base (PlanDuSiteController). Le fichier
    fige annonçait des adresses qui ne repondent plus que par une redirection,
    et ne connaissait aucun article — un plan de site fige ne peut pas suivre
    un contenu editable.

    Le controle precedent validait ces adresses contre l'existence de FICHIERS
    sources : il restait vert alors meme que /services.html avait cesse d'etre
    servi. Un controle qui porte a cote du point sensible donne exactement la
    meme assurance qu'un vrai. Le contenu du plan est desormais eprouve par
    tests/Feature/PlanDuSiteTest.php, qui appelle la route.
    """
    fige = os.path.join(FRONT, 'sitemap.xml')
    if os.path.exists(fige):
        anomalies.append(
            'plan de site : %s subsiste alors que la route /sitemap.xml fait foi'
            % os.path.relpath(fige, RACINE))


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


# ------------------------------------------------- couleurs et theme sombre
def controle_couleurs_inline():
    """Aucune couleur figee ne doit etre posee en attribut style.

    Un attribut style l'emporte sur tout selecteur : une couleur ecrite la
    empeche les regles du theme sombre de s'appliquer, et le texte reste
    sombre sur fond sombre. Les couleurs neutres sur fond image (blanc,
    gold-300) sont tolerees, leur lisibilite ne dependant pas du theme.
    """
    figees = re.compile(r'(?<!-)\bcolor\s*:\s*var\(--(navy-\d+|gold-(?:500|600))\)')
    for page in pages_html(FRONT):
        s = lire(page)
        for balise in re.findall(r'<[^>]*style="[^"]*"[^>]*>', s):
            m = figees.search(balise)
            if m:
                anomalies.append('couleur figee en attribut style dans %s : --%s'
                                 % (os.path.basename(page), m.group(1)))


def controle_selecteurs_sombres_morts():
    """Aucun selecteur de theme sombre ne doit exiger l'attribut plusieurs fois.

    Seul <html> porte data-theme. Un selecteur comme

        [data-theme="dark"] [data-theme="dark"] .news-card

    reclame donc un ancetre portant l'attribut A L'INTERIEUR d'un autre : il ne
    matche jamais. La regle est morte, et la seule chose qui la trahit est
    l'ecran — le titre d'une carte passe en clair par une regle voisine tandis
    que son fond reste clair, ce qui donne un contraste de 1,1.

    Ce defaut a vecu en ligne : introduit en cherchant a gagner en specificite,
    il n'a ete vu qu'en mesurant le contraste a l'ecran. Le controle des
    attributs style ne pouvait pas le voir, ne regardant que le HTML.
    """
    repete = re.compile(r'\[data-theme=(["\'])dark\1\]\s+[^{,]*\[data-theme=')
    for feuille in sorted(glob(os.path.join(FRONT, 'assets', '*.css'))):
        s = lire(feuille)
        for regle in re.findall(r'[^{}]+(?=\{)', s):
            if repete.search(regle):
                anomalies.append('selecteur de theme sombre mort dans %s : %s'
                                 % (os.path.basename(feuille), regle.strip()[:90]))


CONTROLES = [
    ('references internes', controle_references),
    ('donnees structurees', controle_donnees_structurees),
    ('champs de formulaire', controle_formulaires),
    ('syntaxe JavaScript', controle_javascript),
    ('plan de site', controle_plan_de_site),
    ('chargement du script', controle_chargement_script),
    ('couleurs et theme sombre', controle_couleurs_inline),
    ('selecteurs de theme sombre', controle_selecteurs_sombres_morts),
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
