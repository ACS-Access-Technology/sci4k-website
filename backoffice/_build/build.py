# -*- coding: utf-8 -*-
"""Genere les maquettes HTML du backoffice. Usage: python3 _build/build.py"""
import os, sys, glob
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
os.chdir(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
import pages_a as A, pages_b as B, pages_c as C
from accents import normalise

PAGES = [
  ("Pilotage",      [A.dashboard, A.analytics]),
  ("Contenu",       [A.bien_list, A.bien_edit, A.article_list, A.article_edit,
                     B.faq_list, B.service_list, B.pages_list, B.pages_edit, B.media_gallery]),
  ("Blocs du site", [C.testimonials_list, C.partners_list, C.stats_list,
                     C.communes_band, C.ads_list, C.backgrounds, C.team_list, C.values_list, C.process_list]),
  ("Demandes",      [C.messages_list, C.visits_list, C.newsletter_list]),
  ("Reglages",      [B.settings, B.users_list, C.menus, C.referentials]),
  ("Acces",         [B.login, B.index, C.error_500]),
]

n = 0
for groupe, fns in PAGES:
    print("\n%s" % groupe)
    for fn in fns:
        print("   ->", fn()); n += 1

for f in glob.glob('*.html'):
    src = open(f, encoding='utf-8').read()
    out = normalise(src)
    if out != src: open(f, 'w', encoding='utf-8').write(out)
print("\n%d pages generees, accents normalises." % n)
