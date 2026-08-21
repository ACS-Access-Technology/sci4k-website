# -*- coding: utf-8 -*-
"""Normalisation des accents sur le HTML genere.
Applique uniquement aux noeuds de texte et aux libelles visibles ; les entites
HTML sont masquees pendant l'operation pour ne jamais etre alterees."""
import re

DICT = {
 "Activite":"Activité","Actualites":"Actualités","Apercu":"Aperçu","Archive":"Archivé",
 "Abonnes":"Abonnés","basee":"basée","Caracteristiques":"Caractéristiques",
 "caracteres":"caractères","Categorie":"Catégorie","Categories":"Catégories",
 "categories":"catégories","cles":"clés","complete":"complète","concretiser":"concrétiser",
 "consultees":"consultées","Coordonnees":"Coordonnées","Cote":"Côte","Cree":"Créé",
 "Creee":"Créée","decroissant":"décroissant","Decouvrez":"Découvrez","Deconnexion":"Déconnexion",
 "defaut":"défaut","degagee":"dégagée","Deposer":"Déposer","Derniere":"Dernière",
 "Duree":"Durée","Editeur":"Éditeur","Equipe":"Équipe","Equipements":"Équipements",
 "estime":"estimé","Etapes":"Étapes","Etiquettes":"Étiquettes","etiquette":"étiquette",
 "Francais":"Français","Frequentation":"Fréquentation","General":"Général",
 "generales":"générales","Gerer":"Gérer","Identite":"Identité","immobiliere":"immobilière",
 "l'expediteur":"l'expéditeur","Libelle":"Libellé","Marche":"Marché","Mediatheque":"Médiathèque",
 "Modifie":"Modifié","Modifiee":"Modifiée","Numero":"Numéro","oublie":"oublié",
 "Parametres":"Paramètres","Pieces":"Pièces","pieces":"pièces","Planifie":"Planifié",
 "Precedent":"Précédent","Pret":"Prêt","Publie":"Publié","publie":"publié",
 "recent":"récent","recente":"récente","recents":"récents","recommande":"recommandé",
 "Redacteur":"Rédacteur","Redigez":"Rédigez","Reference":"Référence","reference":"référence",
 "Referencement":"Référencement","Reglages":"Réglages","Reinitialiser":"Réinitialiser",
 "Repartition":"Répartition","Reseaux":"Réseaux","reserve":"réservé","Resume":"Résumé",
 "resultats":"résultats","Role":"Rôle","Roles":"Rôles","roles":"rôles",
 "Selection":"Sélection","Taches":"Tâches","Telephone":"Téléphone","Televerser":"Téléverser",
 "Temoignages":"Témoignages","utilisee":"utilisée","utilises":"utilisés",
 "verification":"vérification","coproprietaires":"copropriétaires","notarie":"notarié",
 "securisation":"sécurisation","negociation":"négociation","affiches":"affichés",
 "presentees":"présentées","presente":"présenté","Ecoute":"Écoute",
}

# expressions a corriger avant le dictionnaire mot a mot
PAIRS = [("Titre affiche", "Titre affiché"), ("Resume affiche", "Résumé affiché"),
         ("Résumé affiche", "Résumé affiché")]

# preposition « a » -> « a` », sauf dans « il y a » / « n'y a »
PREP = re.compile(r"(?<![yY] )(?<![\w'&])a(?=\s)")
RX = re.compile(r"(?<![\w'&])(" + "|".join(sorted(map(re.escape, DICT), key=len, reverse=True)) + r")(?![\w'])")
ENTITY = re.compile(r'&[A-Za-z]+;|&#\d+;')
ATTRS = re.compile(r'\b(value|placeholder|title|data-bs-title|aria-label|alt)="([^"]*)"')

def _sub(text):
    keep = []
    masked = ENTITY.sub(lambda m: keep.append(m.group(0)) or ("\x00%d\x00" % (len(keep) - 1)), text)
    for old, new in PAIRS:
        masked = masked.replace(old, new)
    masked = RX.sub(lambda m: DICT[m.group(1)], masked)
    masked = PREP.sub("à", masked)
    return re.sub(r'\x00(\d+)\x00', lambda m: keep[int(m.group(1))], masked)

def normalise(html):
    html = re.sub(r'>([^<]+)<', lambda m: '>' + _sub(m.group(1)) + '<', html)
    return ATTRS.sub(lambda m: '%s="%s"' % (m.group(1), _sub(m.group(2))), html)
