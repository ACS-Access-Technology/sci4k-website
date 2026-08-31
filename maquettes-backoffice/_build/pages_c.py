# -*- coding: utf-8 -*-
"""Ecrans combles suite a l'analyse ECARTS_FRONT_BACKOFFICE.md."""
from layout import render, page_head, icon
from pages_a import badge, tile, ROW_ACTIONS

def sort_handle():
    return '<span class="text-body-secondary" style="cursor:grab">&#8942;&#8942;</span>'

def switch(on=True, label=""):
    return ('<div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" %s>'
            '%s</div>' % ("checked" if on else "", ('<label class="form-check-label fs-13">%s</label>' % label) if label else ""))

def order_input(n):
    # les positions sont affichees a partir de 0 (0, 1, 2...)
    return '<input type="text" class="form-control form-control-sm text-center" style="width:62px" value="%d">' % (n - 1)

# ============================================================ 1. TEMOIGNAGES
def testimonials_list():
    rows = [("Kouadio N'Guessan","Propriétaire, Cocody","SCI4K a vendu ma villa en six semaines, au prix que nous avions fixé ensemble. Le suivi a été irréprochable du premier appel à la signature.",5,1),
            ("Fatou Bamba","Investisseuse, Bingerville","J'ai acheté deux terrains viabilisés par leur intermédiaire. La vérification des ACD m'a évité un litige que je n'avais pas vu venir.",5,2),
            ("Sylvain Adou","Gérant, Le Plateau","Ils gèrent la location de mon immeuble depuis trois ans. Les loyers tombent à date et je n'ai plus à courir après les locataires.",4,3)]
    trs = "".join("""
          <tr>
            <td>{h}</td>
            <td><div class="d-flex align-items-center gap-3">
              <span class="avatar-initial rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;font-size:13px;font-weight:600">{ini}</span>
              <div><span class="d-block fw-medium">{n}</span><span class="text-body-secondary fs-12">{r}</span></div>
            </div></td>
            <td class="text-body-secondary fs-13" style="max-width:420px">{t}</td>
            <td class="text-warning text-nowrap">{st}</td>
            <td>{o}</td>
            <td>{sw}</td>
            <td class="text-end">{act}</td>
          </tr>""".format(h=sort_handle(), ini="".join(p[0] for p in n.split()[:2]), n=n, r=r, t=t,
                          st="&#9733;" * s + '<span class="text-body-secondary">' + "&#9734;" * (5 - s) + "</span>",
                          o=order_input(o), sw=switch(True), act=ROW_ACTIONS.format(edit="javascript:void(0);"))
        for n, r, t, s, o in rows)

    body = page_head("Témoignages clients", "Avis affichés dans le carrousel de la page d'accueil",
        [("Accueil","dashboard.html"),("Blocs du site",None),("Témoignages",None)],
        '<button class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#addTestimonial">%s Ajouter un témoignage</button>' % icon("plus",16))
    body += """
    <div class="row g-3 mb-3">%s%s%s%s</div>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">3 témoignages</h5>
        <span class="text-body-secondary fs-13">Glissez les lignes pour changer l'ordre du carrousel</span>
      </div>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th style="width:36px"></th><th>Auteur</th><th>Témoignage</th><th>Note</th><th>Ordre</th><th>Visible</th><th class="text-end">Actions</th></tr></thead>
        <tbody>%s</tbody></table></div>
      <div class="card-footer text-end"><button class="btn btn-primary waves-effect waves-light">%s Enregistrer l'ordre</button></div>
    </div>

    <div class="modal fade" id="addTestimonial" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Nouveau témoignage</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Nom de l'auteur</label><input type="text" class="form-control" placeholder="Ex : Kouadio N'Guessan"></div>
              <div class="col-md-6"><label class="form-label">Fonction ou quartier</label><input type="text" class="form-control" placeholder="Ex : Propriétaire, Cocody"></div>
              <div class="col-12"><label class="form-label">Témoignage</label><textarea class="form-control" rows="4" placeholder="Texte affiché sur le site"></textarea></div>
              <div class="col-md-4"><label class="form-label">Note</label>
                <select class="form-select"><option>5 étoiles</option><option>4 étoiles</option><option>3 étoiles</option></select></div>
              <div class="col-md-4"><label class="form-label">Ordre</label><input type="text" class="form-control" value="4"></div>
              <div class="col-md-4"><label class="form-label">Photo</label><input type="file" class="form-control"></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
            <button class="btn btn-primary">Ajouter</button></div>
        </div>
      </div>
    </div>""" % (tile("quote","primary","Témoignages publiés","3","1"),
                 tile("check","success","Note moyenne","4,7","0,2"),
                 tile("eye","info","Affichages / mois","3 420","11 %"),
                 tile("edit","warning","En attente","1","1", up=False), trs, icon("check",16))
    return render("testimonials-list.html", "Témoignages", body, "blocksTab")

# ============================================================ 2. PARTENAIRES
def partners_list():
    parts = [("BMS-CI","Banque","bms-ci.png",1,"https://www.bms-ci.net"),
             ("NSIA Banque","Banque","nsia-banque.png",2,"https://www.nsiabanque.ci"),
             ("Credit Access","Financement","credit-access.png",3,"https://www.creditaccess.ci"),
             ("CIE","Service public","cie.webp",4,"https://www.cie.ci"),
             ("CNPS","Institution","cnps.jpeg",5,"https://www.cnps.ci"),
             ("FPPN","Institution","fppn.png",6,""),
             ("Ordre des Architectes","Ordre professionnel","ordre-architectes.png",7,"")]
    cards = "".join("""
        <div class="col-xl-3 col-lg-4 col-sm-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge bg-secondary-subtle text-body fs-12">{c}</span>
                {sw}
              </div>
              <div class="media-ph rounded-3 mb-3" style="aspect-ratio:16/7;display:flex;align-items:center;justify-content:center;background:var(--bs-secondary-bg);color:var(--bs-secondary-color)">{i}</div>
              <div class="fw-medium">{n}</div>
              <div class="text-body-secondary fs-12 text-truncate">{f}</div>
              <label class="form-label fs-12 text-body-secondary mt-3 mb-1">Site officiel</label>
              <input type="url" class="form-control form-control-sm" value="{url}" placeholder="https://…">
              {alerte}
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
              <span class="text-body-secondary fs-12">Ordre {o0}</span>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-light waves-effect">{e}</button>
                <button class="btn btn-sm btn-light text-danger waves-effect">{t}</button>
              </div>
            </div>
          </div>
        </div>""".format(n=n, c=c, f=f, o=o, o0=o-1, url=url, i=icon("image",26), sw=switch(bool(url)),
                         alerte=('' if url else '<div class="form-text text-warning">Sans lien, le logo n\'est pas cliquable sur le site.</div>'),
                         e=icon("edit",14), t=icon("trash",14)) for n, c, f, o, url in parts)

    body = page_head("Partenaires", "Logos affichés en bas de la page d'accueil",
        [("Accueil","dashboard.html"),("Blocs du site",None),("Partenaires",None)],
        '<button class="btn btn-primary waves-effect waves-light">%s Ajouter un partenaire</button>' % icon("plus",16))
    vides = "".join("""
        <div class="col-xl-3 col-lg-4 col-sm-6">
          <div class="card h-100 border-warning-subtle">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="badge bg-warning-subtle text-warning fs-12">À compléter</span>
                <div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" disabled></div>
              </div>
              <div class="media-ph rounded-3 mb-3" style="aspect-ratio:16/7;display:flex;align-items:center;justify-content:center;background:var(--bs-secondary-bg);color:var(--bs-secondary-color)">{i}</div>
              <input type="text" class="form-control form-control-sm mb-2" placeholder="Nom du partenaire">
              <input type="url" class="form-control form-control-sm mb-2" placeholder="https://site-officiel.ci">
              <select class="form-select form-select-sm">
                <option selected>Catégorie...</option><option>Banque</option><option>Financement</option>
                <option>Institution</option><option>Ordre professionnel</option>
              </select>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
              <span class="text-body-secondary fs-12">Emplacement {o}</span>
              <button class="btn btn-sm btn-light waves-effect">{u} Logo</button>
            </div>
          </div>
        </div>""".format(i=icon("plus",26), o=o, u=icon("upload",14)) for o in (8, 9, 10))

    body += """
    <div class="alert alert-success d-flex gap-2 align-items-start" role="alert">
      <span class="flex-shrink-0">%s</span>
      <div><strong>Écart corrigé.</strong> Les trois logos qui pointaient vers Wikimedia
      (Bank of Africa, CEDEAO/ECOWAS, RTI Groupe) ont été retirés du site : ils dépendaient
      d'un serveur externe. Ils sont remplacés ci-dessous par trois emplacements à compléter,
      masqués sur le site public tant qu'un nom et un logo n'y sont pas renseignés.</div>
    </div>
    <div class="card mb-3">
      <div class="card-body d-flex flex-wrap gap-3 align-items-center">
        <input type="text" class="form-control" placeholder="Rechercher un partenaire..." style="max-width:260px">
        <select class="form-select w-auto"><option>Toutes les catégories</option><option>Banque</option><option>Financement</option><option>Institution</option><option>Ordre professionnel</option></select>
        <span class="ms-auto text-body-secondary fs-13">7 partenaires actifs &middot; 3 emplacements libres</span>
      </div>
    </div>
    <div class="row g-3">%s%s</div>""" % (icon("check",18), cards, vides)
    return render("partners-list.html", "Partenaires", body, "blocksTab")

# =========================================================== 3. CHIFFRES CLES
def stats_list():
    rows = [("Biens commercialisés","245","","Compteur animé au défilement",1),
            ("Années d'expérience","11","","Depuis la création en 2015",2),
            ("Clients satisfaits","98","%","Basé sur les enquêtes de satisfaction",3)]
    cards = "".join("""
        <div class="col-lg-4">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0 fs-14">Compteur {o}</h5>{sw}
            </div>
            <div class="card-body">
              <label class="form-label">Libellé affiché</label>
              <input type="text" class="form-control mb-3" value="{l}">
              <div class="row g-2 mb-3">
                <div class="col-8"><label class="form-label">Valeur</label><input type="text" class="form-control" value="{v}"></div>
                <div class="col-4"><label class="form-label">Suffixe</label><input type="text" class="form-control" value="{s}"></div>
              </div>
              <label class="form-label">Note interne</label>
              <input type="text" class="form-control" value="{n}">
            </div>
          </div>
        </div>""".format(l=l, v=v, s=s, n=n, o=o, sw=switch(True)) for l, v, s, n, o in rows)

    body = page_head("Chiffres clés", "Compteurs animés de la page d'accueil",
        [("Accueil","dashboard.html"),("Blocs du site",None),("Chiffres clés",None)],
        '<button class="btn btn-primary waves-effect waves-light">%s Enregistrer</button>' % icon("check",16))
    body += """
    <div class="card mb-3">
      <div class="card-body">
        <h6 class="mb-3">Aperçu sur le site</h6>
        <div class="row g-3 text-center">
          <div class="col-md-4"><div class="stat-tile"><div class="stat-value text-primary">245</div><div class="text-body-secondary fs-13">Biens commercialisés</div></div></div>
          <div class="col-md-4"><div class="stat-tile"><div class="stat-value text-primary">11</div><div class="text-body-secondary fs-13">Années d'expérience</div></div></div>
          <div class="col-md-4"><div class="stat-tile"><div class="stat-value text-primary">98 %%</div><div class="text-body-secondary fs-13">Clients satisfaits</div></div></div>
        </div>
      </div>
    </div>
    <div class="row g-3 mb-3">%s</div>
    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0">Options d'affichage</h5></div>
      <div class="card-body" style="max-width:620px">
        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="anim" checked>
          <label class="form-check-label" for="anim">Animer les compteurs au défilement</label></div>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="autosync">
          <label class="form-check-label" for="autosync">Calculer « Biens commercialisés » automatiquement depuis le catalogue</label>
          <div class="form-text">Le compteur reprendrait le nombre de biens au statut « Vendu ».</div></div>
        <label class="form-label">Durée de l'animation</label>
        <div class="input-group" style="max-width:220px"><input type="text" class="form-control" value="2000"><span class="input-group-text">ms</span></div>
      </div>
    </div>""" % cards
    return render("stats-list.html", "Chiffres clés", body, "blocksTab")

# ================================================================= 4. EQUIPE
def team_list():
    rows = [("M. Jean-Philippe Yao","Directeur général","Fondateur de SCI4K, 20 ans d'expérience dans le foncier ivoirien.",1),
            ("Mme Sarah Koné","Responsable transactions","Pilote les mandats de vente et l'accompagnement des acquéreurs.",2),
            ("M. Marc Kouassi","Responsable construction","Supervise les chantiers et la coordination des corps de métier.",3),
            ("Mme Aminata Diop","Responsable gestion locative","Gère le portefeuille locatif et la relation avec les locataires.",4)]
    trs = "".join("""
          <tr>
            <td>{h}</td>
            <td><div class="d-flex align-items-center gap-3">
              <span class="thumb-64" style="width:48px;height:48px;border-radius:50%">{ph}</span>
              <div><span class="d-block fw-medium">{n}</span><span class="text-body-secondary fs-12">{f}</span></div>
            </div></td>
            <td class="text-body-secondary fs-13" style="max-width:380px">{b}</td>
            <td>{o}</td><td>{sw}</td>
            <td class="text-end">{act}</td>
          </tr>""".format(h=sort_handle(), ph=icon("badge",18), n=n, f=f, b=b, o=order_input(o),
                          sw=switch(True), act=ROW_ACTIONS.format(edit="javascript:void(0);")) for n, f, b, o in rows)

    body = page_head("Équipe", "Membres présentés sur la page Présentation",
        [("Accueil","dashboard.html"),("Blocs du site",None),("Équipe",None)],
        '<button class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#addMember">%s Ajouter un membre</button>' % icon("plus",16))
    body += """
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">4 membres</h5>
        <span class="text-body-secondary fs-13">Glissez les lignes pour changer l'ordre d'affichage</span>
      </div>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th style="width:36px"></th><th>Membre</th><th>Biographie</th><th>Ordre</th><th>Visible</th><th class="text-end">Actions</th></tr></thead>
        <tbody>%s</tbody></table></div>
      <div class="card-footer text-end"><button class="btn btn-primary waves-effect waves-light">%s Enregistrer l'ordre</button></div>
    </div>

    <div class="modal fade" id="addMember" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Nouveau membre</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nom complet</label><input type="text" class="form-control" placeholder="Ex : Mme Sarah Koné"></div>
            <div class="col-md-6"><label class="form-label">Fonction</label><input type="text" class="form-control" placeholder="Ex : Responsable transactions"></div>
            <div class="col-12"><label class="form-label">Biographie</label><textarea class="form-control" rows="3"></textarea></div>
            <div class="col-md-6"><label class="form-label">Photo</label><input type="file" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Profil LinkedIn</label><input type="text" class="form-control" placeholder="https://linkedin.com/in/..."></div>
            <div class="col-md-6"><label class="form-label">E-mail professionnel</label><input type="text" class="form-control" placeholder="prenom@sci4k.ci"></div>
            <div class="col-md-6"><label class="form-label">Ordre</label><input type="text" class="form-control" value="5"></div>
          </div></div>
          <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
            <button class="btn btn-primary">Ajouter</button></div>
        </div>
      </div>
    </div>""" % (trs, icon("check",16))
    return render("team-list.html", "Équipe", body, "blocksTab")

# ================================================================ 5. VALEURS
def values_list():
    rows = [("Rigueur & Sécurité","shield","Vérification systématique des titres et des ACD avant toute mise en ligne.",1),
            ("Transparence Totale","eye","Frais annoncés à l'avance et communiqués dans le mandat signé.",2),
            ("Ancrage Abidjanais","building","Une équipe présente sur le terrain, quartier par quartier.",3),
            ("Service Client VIP","users","Un interlocuteur unique du premier contact jusqu'après la signature.",4)]
    cards = "".join("""
        <div class="col-lg-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex align-items-start gap-3 mb-3">
                <span class="stat-ico bg-primary-subtle text-primary flex-shrink-0">{i}</span>
                <div class="flex-grow-1">
                  <label class="form-label fs-12 text-body-secondary mb-1">Titre</label>
                  <input type="text" class="form-control" value="{t}">
                </div>
                {sw}
              </div>
              <label class="form-label fs-12 text-body-secondary mb-1">Description</label>
              <textarea class="form-control mb-3" rows="2">{d}</textarea>
              <div class="d-flex gap-2 align-items-end">
                <div><label class="form-label fs-12 text-body-secondary mb-1">Ordre</label>{o}</div>
                <button class="btn btn-sm btn-light waves-effect ms-auto">{ic} Changer l'icône</button>
                <button class="btn btn-sm btn-light text-danger waves-effect">{tr}</button>
              </div>
            </div>
          </div>
        </div>""".format(i=icon(ic,22), t=t, d=d, o=order_input(o), sw=switch(True),
                         ic=icon("image",14), tr=icon("trash",14)) for t, ic, d, o in rows)

    body = page_head("Valeurs et engagements", "Bloc « Les engagements de SCI4K » de la page Présentation",
        [("Accueil","dashboard.html"),("Blocs du site",None),("Valeurs",None)],
        '<button class="btn btn-light waves-effect">%s Ajouter une valeur</button>'
        '<button class="btn btn-primary waves-effect waves-light">%s Enregistrer</button>'
        % (icon("plus",16), icon("check",16)))
    body += '<div class="row g-3">%s</div>' % cards
    return render("values-list.html", "Valeurs", body, "blocksTab")

# ============================================================== 6. PROCESSUS
def process_list():
    rows = [("Écoute & Analyse","Nous cernons votre projet, votre budget et vos contraintes de délai.",1),
            ("Sélection & Audit","Nous présentons les biens correspondants et vérifions leur situation juridique.",2),
            ("Négociation & Acte","Nous négocions les conditions et coordonnons le passage chez le notaire.",3),
            ("Suivi Continu","Nous restons disponibles après la signature pour la gestion ou les travaux.",4)]
    steps = "".join("""
            <div class="d-flex gap-3 py-3 border-bottom">
              {h}
              <span class="stat-ico bg-primary-subtle text-primary flex-shrink-0" style="width:38px;height:38px;border-radius:50%">{n}</span>
              <div class="flex-grow-1">
                <input type="text" class="form-control mb-2" value="{t}">
                <textarea class="form-control" rows="2">{d}</textarea>
              </div>
              <div class="d-flex flex-column gap-2 align-items-end">
                {sw}
                <button class="btn btn-sm btn-light text-danger waves-effect">{tr}</button>
              </div>
            </div>""".format(h=sort_handle(), n=n, t=t, d=d, sw=switch(True), tr=icon("trash",14))
        for t, d, n in rows)

    body = page_head("Processus d'accompagnement", "Bloc « Comment nous travaillons avec vous » de la page Services",
        [("Accueil","dashboard.html"),("Blocs du site",None),("Processus",None)],
        '<button class="btn btn-light waves-effect">%s Ajouter une étape</button>'
        '<button class="btn btn-primary waves-effect waves-light">%s Enregistrer</button>'
        % (icon("plus",16), icon("check",16)))
    body += """
    <div class="row g-3">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">4 étapes</h5>
            <span class="text-body-secondary fs-13">L'ordre détermine la numérotation sur le site</span>
          </div>
          <div class="card-body pt-0">%s</div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Réglages du bloc</h5></div>
          <div class="card-body">
            <label class="form-label">Titre de la section</label>
            <input type="text" class="form-control mb-3" value="Comment nous travaillons avec vous">
            <label class="form-label">Chapô</label>
            <textarea class="form-control mb-3" rows="3">Une méthode éprouvée, de la première rencontre au suivi après signature.</textarea>
            <label class="form-label">Mise en page</label>
            <select class="form-select"><option selected>Frise horizontale</option><option>Liste verticale</option><option>Cartes numérotées</option></select>
          </div>
        </div>
      </div>
    </div>""" % steps
    return render("process-list.html", "Processus", body, "blocksTab")

# ============================================================== 7. MESSAGES
def messages_list():
    msgs = [("Léon Kouassi","+225 07 08 11 22 33","leon.k@email.ci","Demande de visite — Villa Cocody","Bonjour, je souhaiterais visiter la villa de la zone Château ce samedi matin si possible.","il y a 15 min","Nouveau","primary",True),
            ("Emma Diarra","+225 05 44 21 09 77","emma.d@email.ci","Question sur les frais d'agence","Pouvez-vous me préciser le pourcentage appliqué sur une vente de terrain ?","il y a 1 h","Nouveau","primary",True),
            ("Marc Touré","+225 01 76 30 55 12","marc.t@email.ci","Recherche bureau Plateau","Je cherche un plateau de 150 m² environ, climatisé, pour une installation en novembre.","il y a 2 h","Nouveau","primary",True),
            ("Awa Sylla","+225 07 12 88 40 06","awa.s@email.ci","Financement d'un achat","Travaillez-vous avec des banques pour le montage du crédit ?","il y a 5 h","En cours","warning",False),
            ("Ibrahim Cissé","+225 05 09 63 71 84","ibrahim.c@email.ci","Gestion locative d'un immeuble","J'ai un immeuble de six appartements à Marcory à confier en gestion.","hier","Traité","success",False),
            ("Nadia Bamba","+225 01 23 45 67 89","nadia.b@email.ci","Terrain viabilisé Bingerville","Le terrain de 500 m² annoncé est-il toujours disponible ?","hier","Traité","success",False)]
    lst = "".join("""
          <a href="javascript:void(0);" class="d-flex gap-3 p-3 border-bottom text-body text-decoration-none{bg}">
            <input class="form-check-input mt-1 flex-shrink-0" type="checkbox">
            <div class="flex-grow-1 min-w-0">
              <div class="d-flex justify-content-between gap-2">
                <span class="{fw} fs-14">{n}</span>
                <span class="text-body-secondary fs-12 text-nowrap">{w}</span>
              </div>
              <div class="{fw} fs-13 text-truncate">{s}</div>
              <div class="text-body-secondary fs-12 text-truncate">{p}</div>
              <div class="mt-1">{b}</div>
            </div>
          </a>""".format(n=n, w=w, s=s, p=p, b=badge(st, tone), fw="fw-semibold" if new else "",
                         bg=" bg-primary-subtle bg-opacity-25" if new else "")
        for n, tel, mail, s, p, w, st, tone, new in msgs)

    body = page_head("Messages de contact", "Formulaire de la page Contact — 18 messages, 3 non lus",
        [("Accueil","dashboard.html"),("Demandes",None),("Messages",None)],
        '<button class="btn btn-light waves-effect">%s Exporter</button>' % icon("upload",16))
    body += """
    <div class="row g-3 mb-3">%s%s%s%s</div>
    <div class="row g-3">
      <div class="col-xl-5">
        <div class="card h-100">
          <div class="card-header">
            <div class="d-flex gap-2 mb-2">
              <input type="text" class="form-control form-control-sm" placeholder="Rechercher un expéditeur...">
              <select class="form-select form-select-sm w-auto"><option>Tous</option><option>Nouveau</option><option>En cours</option><option>Traité</option><option>Archivé</option></select>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="fs-13 text-body-secondary">6 messages affichés</span>
              <button class="btn btn-sm btn-light text-danger waves-effect">%s Supprimer la sélection</button>
            </div>
          </div>
          <div class="card-body p-0" style="max-height:640px;overflow-y:auto">%s</div>
        </div>
      </div>

      <div class="col-xl-7">
        <div class="card h-100">
          <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <h5 class="card-title mb-1">Demande de visite — Villa Cocody</h5>
              <span class="text-body-secondary fs-13">Reçu il y a 15 minutes via /contact</span>
            </div>
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-light waves-effect" title="Archiver">%s</button>
              <button class="btn btn-sm btn-light text-danger waves-effect" title="Supprimer">%s</button>
            </div>
          </div>
          <div class="card-body">
            <div class="row g-3 mb-4">
              <div class="col-sm-6"><div class="text-body-secondary fs-12">Expéditeur</div><div class="fw-medium">Léon Kouassi</div></div>
              <div class="col-sm-6"><div class="text-body-secondary fs-12">Statut</div><div>%s</div></div>
              <div class="col-sm-6"><div class="text-body-secondary fs-12">Téléphone</div><div class="fw-medium">+225 07 08 11 22 33</div></div>
              <div class="col-sm-6"><div class="text-body-secondary fs-12">E-mail</div><div class="fw-medium">leon.k@email.ci</div></div>
              <div class="col-sm-6"><div class="text-body-secondary fs-12">Bien concerné</div><div><a href="bien-edit.html">Villa Cocody — SCI4K-0123</a></div></div>
              <div class="col-sm-6"><div class="text-body-secondary fs-12">Assigné à</div>
                <select class="form-select form-select-sm mt-1"><option>Non assigné</option><option selected>Sarah Koné</option><option>Marc Kouassi</option></select></div>
            </div>
            <div class="border rounded-3 p-3 mb-4 bg-body-tertiary">
              <p class="mb-0">Bonjour, je souhaiterais visiter la villa de la zone Château ce samedi matin si possible.
              Je suis disponible entre 9 h et midi. Merci de me confirmer un créneau.</p>
            </div>
            <label class="form-label">Réponse</label>
            <textarea class="form-control mb-3" rows="5" placeholder="Rédigez votre réponse..."></textarea>
            <div class="d-flex flex-wrap gap-2">
              <button class="btn btn-primary waves-effect waves-light">%s Envoyer la réponse</button>
              <button class="btn btn-light waves-effect">Marquer comme traité</button>
              <button class="btn btn-light waves-effect">%s Créer une demande de visite</button>
            </div>
          </div>
        </div>
      </div>
    </div>""" % (tile("inbox","primary","Messages reçus","18","5 %"),
                 tile("bell","danger","Non lus","3","3", up=False),
                 tile("check","success","Traités ce mois","41","12 %"),
                 tile("calendar","info","Délai moyen de réponse","4 h","1 h"),
                 icon("trash",14), lst, icon("layers",14), icon("trash",14),
                 badge("Nouveau","primary"), icon("send",16), icon("key",16))
    return render("messages-list.html", "Messages de contact", body, "leadsTab")

# ============================================================== 8. VISITES
def visits_list():
    rows = [("Léon Kouassi","+225 07 08 11 22 33","Villa Cocody","SCI4K-0123","Sam. 23/08 — 09:00","À confirmer","warning","Sarah Koné"),
            ("Nadia Bamba","+225 01 23 45 67 89","Terrain Bingerville","SCI4K-0187","Lun. 25/08 — 15:30","Confirmée","success","Marc Kouassi"),
            ("Marc Touré","+225 01 76 30 55 12","Bureau Plateau","SCI4K-0142","Mar. 26/08 — 11:00","Confirmée","success","Sarah Koné"),
            ("Awa Sylla","+225 07 12 88 40 06","Appartement Plateau","SCI4K-0131","Jeu. 21/08 — 10:00","Réalisée","info","Sarah Koné"),
            ("Ibrahim Cissé","+225 05 09 63 71 84","Maison Riviera","SCI4K-0155","Mer. 20/08 — 16:00","Annulée","secondary","Marc Kouassi")]
    trs = "".join("""
          <tr>
            <td><input class="form-check-input" type="checkbox"></td>
            <td><div><span class="d-block fw-medium">{n}</span><span class="text-body-secondary fs-12">{t}</span></div></td>
            <td><a href="bien-edit.html" class="fw-medium">{b}</a><span class="d-block text-body-secondary fs-12">{r}</span></td>
            <td class="text-nowrap">{d}</td>
            <td>{s}</td>
            <td class="fs-13">{a}</td>
            <td class="text-end">{act}</td>
          </tr>""".format(n=n, t=t, b=b, r=r, d=d, s=badge(st, tone), a=ag,
                          act=ROW_ACTIONS.format(edit="javascript:void(0);"))
        for n, t, b, r, d, st, tone, ag in rows)

    body = page_head("Demandes de visite", "Rendez-vous demandés depuis les fiches de biens",
        [("Accueil","dashboard.html"),("Demandes",None),("Visites",None)],
        '<button class="btn btn-light waves-effect">%s Vue calendrier</button>' % icon("calendar",16))
    body += """
    <div class="row g-3 mb-3">%s%s%s%s</div>
    <div class="card mb-3">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-lg-3 col-md-6"><label class="form-label">Recherche</label>
            <input type="text" class="form-control" placeholder="Demandeur, bien, référence..."></div>
          <div class="col-lg-2 col-md-6"><label class="form-label">Statut</label>
            <select class="form-select"><option>Tous</option><option>À confirmer</option><option>Confirmée</option><option>Réalisée</option><option>Annulée</option></select></div>
          <div class="col-lg-2 col-md-6"><label class="form-label">Assigné à</label>
            <select class="form-select"><option>Tous</option><option>Sarah Koné</option><option>Marc Kouassi</option></select></div>
          <div class="col-lg-3 col-md-6"><label class="form-label">Période</label>
            <input type="text" class="form-control flatpickr-date" value="Août 2026" readonly></div>
          <div class="col-lg-2"><button class="btn btn-primary w-100 waves-effect waves-light">%s Filtrer</button></div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">5 demandes</h5>
        <div class="d-flex gap-1">
          <button class="btn btn-sm btn-light waves-effect">Confirmer la sélection</button>
          <button class="btn btn-sm btn-light waves-effect">Exporter</button>
        </div>
      </div>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th style="width:36px"><input class="form-check-input" type="checkbox"></th>
        <th>Demandeur</th><th>Bien concerné</th><th>Créneau souhaité</th><th>Statut</th><th>Assigné à</th><th class="text-end">Actions</th></tr></thead>
        <tbody>%s</tbody></table></div>
    </div>""" % (tile("key","primary","Demandes ce mois","24","18 %"),
                 tile("calendar","warning","À confirmer","1","1", up=False),
                 tile("check","success","Réalisées","16","9 %"),
                 tile("chart","info","Taux de concrétisation","22 %","4 %"), icon("filter",16), trs)
    js = '<script src="assets/libs/flatpickr/flatpickr.min.js"></script>'
    return render("visits-list.html", "Demandes de visite", body, "leadsTab", js)

# =========================================================== 9. NEWSLETTER
def newsletter_list():
    rows = [("leon.k@email.ci","Pied de page","18/08/2026","Actif","success"),
            ("emma.d@email.ci","Pied de page","17/08/2026","Actif","success"),
            ("marc.t@email.ci","Page Actualités","15/08/2026","Actif","success"),
            ("awa.s@email.ci","Pied de page","12/08/2026","Actif","success"),
            ("ibrahim.c@email.ci","Page Contact","09/08/2026","Désabonné","secondary"),
            ("nadia.b@email.ci","Pied de page","05/08/2026","Actif","success"),
            ("serge.a@email.ci","Page Actualités","01/08/2026","Non confirmé","warning")]
    trs = "".join("""
          <tr>
            <td><input class="form-check-input" type="checkbox"></td>
            <td class="fw-medium">{e}</td><td>{s}</td><td>{d}</td><td>{st}</td>
            <td class="text-end"><div class="d-flex gap-1 justify-content-end">
              <button class="btn btn-sm btn-icon btn-action-gray rounded-circle waves-effect" title="Renvoyer la confirmation">{sd}</button>
              <button class="btn btn-sm btn-icon btn-action-gray rounded-circle waves-effect text-danger" title="Supprimer">{tr}</button>
            </div></td>
          </tr>""".format(e=e, s=s, d=d, st=badge(st, tone), sd=icon("send",16), tr=icon("trash",16))
        for e, s, d, st, tone in rows)

    body = page_head("Abonnés newsletter", "Inscriptions via le bloc du pied de page",
        [("Accueil","dashboard.html"),("Demandes",None),("Newsletter",None)],
        '<button class="btn btn-light waves-effect">%s Exporter en CSV</button>'
        '<button class="btn btn-primary waves-effect waves-light">%s Ajouter un abonné</button>'
        % (icon("upload",16), icon("plus",16)))
    body += """
    <div class="row g-3 mb-3">%s%s%s%s</div>
    <div class="row g-3">
      <div class="col-xl-8">
        <div class="card h-100">
          <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h5 class="card-title mb-0">7 abonnés</h5>
            <div class="d-flex gap-2">
              <input type="text" class="form-control form-control-sm" placeholder="Rechercher..." style="width:180px">
              <select class="form-select form-select-sm w-auto"><option>Tous</option><option>Actif</option><option>Non confirmé</option><option>Désabonné</option></select>
            </div>
          </div>
          <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th style="width:36px"><input class="form-check-input" type="checkbox"></th>
            <th>Adresse e-mail</th><th>Source</th><th>Inscription</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
            <tbody>%s</tbody></table></div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Bloc d'inscription</h5></div>
          <div class="card-body">
            <label class="form-label">Titre affiché</label>
            <input type="text" class="form-control mb-3" value="Restez informé de nos nouveautés">
            <label class="form-label">Texte du bouton</label>
            <input type="text" class="form-control mb-3" value="S'abonner">
            <label class="form-label">Message de confirmation</label>
            <textarea class="form-control mb-3" rows="2">Merci ! Vérifiez votre boîte de réception pour confirmer votre inscription.</textarea>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="dbl" checked>
              <label class="form-check-label" for="dbl">Confirmation par e-mail obligatoire</label></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Service d'emailing</h5></div>
          <div class="card-body">
            <label class="form-label">Plateforme</label>
            <select class="form-select mb-3"><option selected>Aucune (stockage local)</option><option>Mailchimp</option><option>Brevo</option><option>SendGrid</option></select>
            <label class="form-label">Clé d'API</label>
            <input type="password" class="form-control mb-3" value="123456789">
            <button class="btn btn-light w-100 waves-effect">Tester la connexion</button>
          </div>
        </div>
      </div>
    </div>""" % (tile("send","primary","Abonnés actifs","5","2"),
                 tile("plus","success","Nouveaux ce mois","7","3"),
                 tile("bell","warning","Non confirmés","1","1", up=False),
                 tile("trash","info","Désabonnements","1","0", up=False), trs)
    return render("newsletter-list.html", "Newsletter", body, "leadsTab")

# ================================================================ 10. MENUS
def menus():
    header = [("Accueil","/",1),("Présentation","/presentation",2),("Biens Immobiliers","/biens",3),
              ("Nos Services","/services",4),("Actualités","/actualites",5),("FAQ","/faq",6),("Contact","/contact",7)]
    col1 = [("Accueil","/"),("Présentation","/presentation"),("Biens immobiliers","/biens"),
            ("Nos Services","/services"),("Actualités","/actualites"),("FAQ","/faq"),("Contact","/contact")]
    col2 = [("Foncier","/services#foncier"),("Construction","/services#construction"),
            ("Gestion / Location","/services#gestion"),("Achat","/services#achat"),
            ("Vente","/services#vente"),("Administration de biens","/services#administration")]

    def line(label, url, order=None):
        return """
            <div class="d-flex align-items-center gap-2 py-2 border-bottom">
              {h}
              <input type="text" class="form-control form-control-sm" value="{l}" style="max-width:190px">
              <input type="text" class="form-control form-control-sm text-body-secondary" value="{u}">
              {o}{sw}
              <button class="btn btn-sm btn-light text-danger waves-effect flex-shrink-0">{tr}</button>
            </div>""".format(h=sort_handle(), l=label, u=url,
                             o=order_input(order) if order is not None else "", sw=switch(True), tr=icon("trash",14))

    body = page_head("Menus du site", "Navigation de l'en-tête et colonnes du pied de page",
        [("Accueil","dashboard.html"),("Réglages",None),("Menus",None)],
        '<button class="btn btn-primary waves-effect waves-light">%s Enregistrer les menus</button>' % icon("check",16))
    body += """
    <div class="row g-3">
      <div class="col-xl-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div><h5 class="card-title mb-0">Menu principal</h5>
              <span class="text-body-secondary fs-12">Barre de navigation en haut de chaque page</span></div>
            <span class="badge bg-secondary-subtle text-body">7 entrées</span>
          </div>
          <div class="card-body pt-0">
            <div class="d-flex gap-2 py-2 text-body-secondary fs-12 border-bottom">
              <span style="width:16px"></span><span style="width:190px">Libellé</span>
              <span class="flex-grow-1">Cible</span><span style="width:62px">Ordre</span><span>Visible</span><span style="width:34px"></span>
            </div>
            %s
            <button class="btn btn-sm btn-light waves-effect mt-3">%s Ajouter une entrée</button>
          </div>
        </div>
      </div>

      <div class="col-xl-6">
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div><h5 class="card-title mb-0">Pied de page — colonne « Navigation »</h5></div>
            <span class="badge bg-secondary-subtle text-body">7 liens</span>
          </div>
          <div class="card-body pt-0">%s
            <button class="btn btn-sm btn-light waves-effect mt-3">%s Ajouter un lien</button></div>
        </div>
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div><h5 class="card-title mb-0">Pied de page — colonne « Nos Services »</h5></div>
            <span class="badge bg-secondary-subtle text-body">6 liens</span>
          </div>
          <div class="card-body pt-0">%s
            <button class="btn btn-sm btn-light waves-effect mt-3">%s Ajouter un lien</button></div>
        </div>
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Pied de page — colonne « Nous contacter »</h5></div>
          <div class="card-body">
            <div class="alert alert-info d-flex gap-2 mb-3" role="alert">
              <span class="flex-shrink-0">%s</span>
              <div class="fs-13">Cette colonne reprend automatiquement les coordonnées saisies dans
              <a href="settings.html">Configuration &rsaquo; Contact</a>.</div>
            </div>
            <div class="fs-13 text-body-secondary">
              <div class="d-flex justify-content-between py-1"><span>Adresse</span><span>Cocody, Riviera Golf, Abidjan</span></div>
              <div class="d-flex justify-content-between py-1"><span>Téléphone</span><span>+225 07 06 16 50 29</span></div>
              <div class="d-flex justify-content-between py-1"><span>E-mail</span><span>contact@sci4k.com</span></div>
            </div>
            <hr>
            <label class="form-label">Liens légaux du bas de page</label>
            %s
          </div>
        </div>
      </div>
    </div>""" % ("".join(line(l, u, o) for l, u, o in header), icon("plus",14),
                 "".join(line(l, u) for l, u in col1), icon("plus",14),
                 "".join(line(l, u) for l, u in col2), icon("plus",14), icon("help",16),
                 "".join(line(l, u) for l, u in [("Mentions légales","/mentions-legales"),
                                                 ("Politique de confidentialité","/politique-confidentialite")]))
    return render("menus.html", "Menus du site", body, "settingsTab")

# ========================================================= 11. REFERENTIELS
def referentials():
    sets = [("Types de bien","selectType",
             [("Villa & Duplex",68),("Appartement & Studio",72),("Immeuble de rapport",19),("Terrain viabilisé",45)]),
            ("Zones et communes","selectLoc",
             [("Cocody & Riviera",96),("Bingerville",38),("Marcory",27),("Le Plateau",31),("Abatta",12)]),
            ("Tranches de pièces","selectRooms",
             [("1 à 2 pièces",41),("3 à 4 pièces",88),("5 pièces et plus",52)]),
            ("Tranches de surface","selectSurface",
             [("Moins de 100 m²",34),("100 à 250 m²",79),("250 à 500 m²",56),("Plus de 500 m²",29)])]

    cards = "".join("""
        <div class="col-xl-6">
          <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
              <div><h5 class="card-title mb-0">{t}</h5>
                <span class="text-body-secondary fs-12">Filtre <code>{k}</code> de la page /biens</span></div>
              <span class="badge bg-secondary-subtle text-body">{c} valeurs</span>
            </div>
            <div class="card-body pt-0">{rows}
              <button class="btn btn-sm btn-light waves-effect mt-3">{p} Ajouter une valeur</button>
            </div>
          </div>
        </div>""".format(t=t, k=k, c=len(vals), p=icon("plus",14), rows="".join("""
              <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                {h}
                <input type="text" class="form-control form-control-sm" value="{v}">
                <span class="badge bg-secondary-subtle text-body flex-shrink-0" title="Biens rattachés">{n}</span>
                {sw}
                <button class="btn btn-sm btn-light text-danger waves-effect flex-shrink-0" {dis}>{tr}</button>
              </div>""".format(h=sort_handle(), v=v, n=n, sw=switch(True), tr=icon("trash",14),
                               dis='disabled title="Valeur utilisée par des biens"' if n else "")
            for v, n in vals)) for t, k, vals in sets)

    body = page_head("Référentiels", "Valeurs des listes déroulantes du site public",
        [("Accueil","dashboard.html"),("Réglages",None),("Référentiels",None)],
        '<button class="btn btn-primary waves-effect waves-light">%s Enregistrer</button>' % icon("check",16))
    body += """
    <div class="alert alert-info d-flex gap-2 align-items-start" role="alert">
      <span class="flex-shrink-0">%s</span>
      <div><strong>Pourquoi cet écran.</strong> Les quatre filtres de la page <code>/biens</code> étaient jusqu'ici
      codés en dur dans le HTML. Les valeurs ci-dessous alimentent à la fois ces filtres et les listes déroulantes
      de la fiche bien, ce qui garantit un vocabulaire identique des deux côtés.</div>
    </div>
    <div class="row g-3 mb-3">%s</div>
    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0">Autres référentiels</h5></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-lg-4">
            <label class="form-label">Statuts juridiques</label>
            <div class="d-flex flex-wrap gap-1">
              <span class="badge bg-primary-subtle text-primary">ACD disponible &times;</span>
              <span class="badge bg-primary-subtle text-primary">Titre foncier &times;</span>
              <span class="badge bg-primary-subtle text-primary">Lettre d'attribution &times;</span>
              <span class="badge bg-primary-subtle text-primary">Arrêté de concession &times;</span>
            </div>
            <input type="text" class="form-control form-control-sm mt-2" placeholder="Ajouter un statut...">
          </div>
          <div class="col-lg-4">
            <label class="form-label">Catégories d'articles</label>
            <div class="d-flex flex-wrap gap-1">
              <span class="badge bg-primary-subtle text-primary">Marché &times;</span>
              <span class="badge bg-primary-subtle text-primary">Conseils &times;</span>
              <span class="badge bg-primary-subtle text-primary">Actualités &times;</span>
              <span class="badge bg-primary-subtle text-primary">Juridique &times;</span>
            </div>
            <input type="text" class="form-control form-control-sm mt-2" placeholder="Ajouter une catégorie...">
          </div>
          <div class="col-lg-4">
            <label class="form-label">Catégories de FAQ</label>
            <div class="d-flex flex-wrap gap-1">
              <span class="badge bg-primary-subtle text-primary">À propos &times;</span>
              <span class="badge bg-primary-subtle text-primary">Vente &times;</span>
              <span class="badge bg-primary-subtle text-primary">Location &times;</span>
              <span class="badge bg-primary-subtle text-primary">Processus &times;</span>
            </div>
            <input type="text" class="form-control form-control-sm mt-2" placeholder="Ajouter une catégorie...">
          </div>
        </div>
      </div>
    </div>""" % (icon("help",18), cards)
    return render("referentials.html", "Référentiels", body, "settingsTab")

# ====================================================== 12. BANDEROLE COMMUNES
def communes_band():
    communes = ["Cocody","Riviera","Bingerville","Marcory","Angré","Plateau","Abatta"]
    puces = "".join("""
            <div class="d-flex align-items-center gap-2 py-2 border-bottom">
              {h}
              <input type="text" class="form-control form-control-sm" value="{c}" style="max-width:260px">
              <span class="badge bg-secondary-subtle text-body flex-shrink-0">{n} biens</span>
              {sw}
              <button class="btn btn-sm btn-light text-danger waves-effect flex-shrink-0">{tr}</button>
            </div>""".format(h=sort_handle(), c=c, n=n, sw=switch(True), tr=icon("trash",14))
        for c, n in zip(communes, [96, 54, 38, 27, 33, 31, 12]))

    apercu = "".join('<span class="mx-3">%s</span><span class="text-warning">&middot;</span>' % c for c in communes)

    body = page_head("Banderole des communes", "Bandeau défilant placé entre la bannière et les services, sur l'accueil",
        [("Accueil","dashboard.html"),("Blocs du site",None),("Banderole",None)],
        '<button class="btn btn-primary waves-effect waves-light">%s Enregistrer</button>' % icon("check",16))
    body += """
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Aperçu</h5>
        <span class="text-body-secondary fs-13">Rendu approximatif du bandeau sur le site</span>
      </div>
      <div class="card-body p-0">
        <div class="py-3 overflow-hidden text-uppercase fw-semibold text-white-50"
             style="background:#0b1a33;letter-spacing:.12em;font-size:14px;white-space:nowrap">
          <div class="d-inline-block">%s%s</div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-xl-7">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div><h5 class="card-title mb-0">Communes affichées</h5>
              <span class="text-body-secondary fs-12">La liste est répétée deux fois pour boucler sans coupure</span></div>
            <span class="badge bg-secondary-subtle text-body">7 entrées</span>
          </div>
          <div class="card-body pt-0">%s
            <button class="btn btn-sm btn-light waves-effect mt-3">%s Ajouter une commune</button>
          </div>
        </div>
      </div>

      <div class="col-xl-5">
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Apparence</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-6"><label class="form-label">Fond</label>
                <div class="input-group"><span class="input-group-text p-1"><span class="d-block rounded" style="width:22px;height:22px;background:#0b1a33"></span></span>
                <input type="text" class="form-control" value="#0B1A33"></div></div>
              <div class="col-6"><label class="form-label">Séparateur</label>
                <div class="input-group"><span class="input-group-text p-1"><span class="d-block rounded" style="width:22px;height:22px;background:#d4af37"></span></span>
                <input type="text" class="form-control" value="#D4AF37"></div></div>
              <div class="col-12"><label class="form-label">Casse du texte</label>
                <select class="form-select"><option selected>Majuscules</option><option>Telle que saisie</option></select></div>
            </div>
          </div>
        </div>
      </div>
    </div>""" % (apercu, apercu, puces, icon("plus",14))
    return render("communes-band.html", "Banderole des communes", body, "blocksTab")

# ============================================================== 13. ERREUR 500
def error_500():
    from layout import HEAD
    html = HEAD.format(title="Erreur serveur").replace('<div class="page-layout">', '<div class="auth-layout">')
    html += """
  <div class="d-flex align-items-center justify-content-center min-vh-100 p-3">
    <div class="w-100 text-center" style="max-width:520px">
      <div class="mb-4">
        <span class="d-inline-flex align-items-center justify-content-center rounded-4 bg-danger-subtle text-danger mb-3" style="width:72px;height:72px">%s</span>
        <div class="fw-bold text-body-secondary" style="font-size:64px;line-height:1">500</div>
        <h4 class="mb-2 mt-2">Une erreur est survenue de notre côté</h4>
        <p class="text-body-secondary mb-0">Le serveur n'a pas pu traiter votre demande. Vos modifications non
        enregistrées ont peut-être été perdues. Réessayez dans quelques instants.</p>
      </div>
      <div class="card text-start mb-4">
        <div class="card-body">
          <div class="d-flex justify-content-between py-1 fs-13"><span class="text-body-secondary">Code</span><span class="fw-medium">HTTP 500 — Internal Server Error</span></div>
          <div class="d-flex justify-content-between py-1 fs-13"><span class="text-body-secondary">Référence</span><span class="fw-medium font-monospace">ERR-20260821-4F2A</span></div>
          <div class="d-flex justify-content-between py-1 fs-13"><span class="text-body-secondary">Horodatage</span><span class="fw-medium">21/08/2026 à 09:14</span></div>
        </div>
      </div>
      <div class="d-flex flex-wrap gap-2 justify-content-center">
        <a href="dashboard.html" class="btn btn-primary waves-effect waves-light">%s Retour au tableau de bord</a>
        <a href="javascript:location.reload();" class="btn btn-light waves-effect">Réessayer</a>
        <a href="../frontoffice/index.html" target="_blank" rel="noopener" class="btn btn-light waves-effect">%s Voir le site public</a>
      </div>
      <p class="text-body-secondary fs-12 mt-4 mb-0">Si le problème persiste, communiquez la référence ci-dessus à votre prestataire technique.</p>
    </div>
  </div>
</div>
<script src="assets/libs/global/global.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
""" % (icon("help",34), icon("arrow-left",16), icon("arrow-ur",16))
    open("error-500.html", "w", encoding="utf-8").write(html)
    return "error-500.html"

# ========================================================= 14. ENCARTS / PUBS
def ads_list():
    MODES = {"maison":("Annonce maison","primary"), "sponsor":("Communiqué sponsorisé","warning"),
             "vide":("Aucune","secondary")}

    emplacements = [
      ("Accueil &mdash; après les services", "accueil-apres-services", "maison",
       "Terrains viabilisés à Bingerville", "Pleine largeur, image + texte", True),
      ("Actualités &mdash; entre les articles", "actus-inter-articles", "vide",
       "&mdash;", "Bandeau horizontal compact", False),
      ("Biens &mdash; dans la liste de résultats", "biens-liste", "vide",
       "&mdash;", "Carte au format d'un bien", False),
    ]
    emp = "".join("""
          <tr>
            <td>
              <span class="d-block fw-medium">{n}</span>
              <code class="fs-12 text-body-secondary">{k}</code>
            </td>
            <td class="fs-13 text-body-secondary">{f}</td>
            <td>
              <select class="form-select form-select-sm" style="min-width:180px">
                {opts}
              </select>
            </td>
            <td class="fs-13">{c}</td>
            <td>{sw}</td>
            <td class="text-end">
              <a href="../frontoffice/index.html#encart-accueil" target="_blank" rel="noopener"
                 class="btn btn-sm btn-light waves-effect">{eye} Voir</a>
            </td>
          </tr>""".format(n=n, k=k, f=f, c=c, sw=switch(on),
              eye=icon("eye",14),
              opts="".join('<option value="%s"%s>%s</option>' % (mk, " selected" if mk == mode else "", MODES[mk][0])
                           for mk in ("maison","sponsor","vide")))
        for n, k, mode, c, f, on in emplacements)

    annonces = [
      ("Terrains viabilisés à Bingerville","maison","Accueil","01/08 &rarr; 30/09/2026","En cours","success","4 812","218"),
      ("Portes ouvertes Riviera Golf","maison","Accueil","15/09 &rarr; 20/09/2026","Planifiée","info","&mdash;","&mdash;"),
      ("Offre crédit habitat &mdash; NSIA Banque","sponsor","Actualités","01/08 &rarr; 31/10/2026","En cours","success","2 140","96"),
      ("Assurance emprunteur &mdash; Credit Access","sponsor","Biens","01/07 &rarr; 31/07/2026","Terminée","secondary","3 908","141"),
    ]
    def ctr(i, c):
        try: return "%.1f %%" % (int(c.replace(" ","")) / int(i.replace(" ","")) * 100)
        except Exception: return "&mdash;"
    ann = "".join("""
          <tr>
            <td><input class="form-check-input" type="checkbox"></td>
            <td><span class="d-block fw-medium">{t}</span><span class="text-body-secondary fs-12">{e}</span></td>
            <td>{m}</td>
            <td class="fs-13 text-nowrap">{p}</td>
            <td>{s}</td>
            <td class="fs-13">{i}</td>
            <td class="fs-13">{c}</td>
            <td class="fs-13 fw-medium">{r}</td>
            <td class="text-end">{act}</td>
          </tr>""".format(t=t, e=e, m=badge(MODES[mode][0], MODES[mode][1]), p=p,
                          s=badge(st, tone), i=i, c=c, r=ctr(i, c),
                          act=ROW_ACTIONS.format(edit="javascript:void(0);"))
        for t, mode, e, p, st, tone, i, c in annonces)

    body = page_head("Encarts &amp; annonces", "Emplacements publicitaires du site public",
        [("Accueil","dashboard.html"),("Blocs du site",None),("Encarts",None)],
        '<button class="btn btn-light waves-effect">%s Rapport</button>'
        '<button class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#addAd">%s Nouvelle annonce</button>'
        % (icon("chart",16), icon("plus",16)))

    body += """
    <div class="row g-3 mb-3">%s%s%s%s</div>

    <div class="card mb-3">
      <div class="card-header">
        <h5 class="card-title mb-0">Emplacements</h5>
        <span class="text-body-secondary fs-12">Chaque emplacement accepte les trois modes &mdash; le choix se fait ligne par ligne</span>
      </div>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Emplacement</th><th>Format</th><th>Mode</th><th>Contenu diffusé</th><th>Actif</th><th class="text-end"></th></tr></thead>
        <tbody>%s</tbody></table></div>
    </div>

    <div class="card mb-3">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="card-title mb-0">Annonces</h5>
        <div class="d-flex gap-2">
          <select class="form-select form-select-sm w-auto"><option>Tous les modes</option><option>Annonce maison</option><option>Communiqué sponsorisé</option></select>
          <select class="form-select form-select-sm w-auto"><option>Tous les statuts</option><option>En cours</option><option>Planifiée</option><option>En pause</option><option>Terminée</option></select>
        </div>
      </div>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th style="width:36px"><input class="form-check-input" type="checkbox"></th>
        <th>Annonce</th><th>Mode</th><th>Diffusion</th><th>Statut</th><th>Impressions</th><th>Clics</th><th>Taux</th><th class="text-end">Actions</th></tr></thead>
        <tbody>%s</tbody></table></div>
      <div class="card-footer text-body-secondary fs-12">
        Les impressions et les clics sont comptabilisés côté serveur, sans traceur tiers.
      </div>
    </div>


    <div class="modal fade" id="addAd" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Nouvelle annonce</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">
            <ul class="nav nav-tabs mb-3" role="tablist">
              <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#adMaison" type="button">Annonce maison</button></li>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#adSponsor" type="button">Communiqué sponsorisé</button></li>
                          </ul>
            <div class="tab-content">
              <div class="tab-pane fade show active" id="adMaison">
                <div class="row g-3">
                  <div class="col-md-4"><label class="form-label">Surtitre</label><input type="text" class="form-control" placeholder="Nouveau programme"></div>
                  <div class="col-md-8"><label class="form-label">Titre</label><input type="text" class="form-control" placeholder="Terrains viabilisés à Bingerville"></div>
                  <div class="col-12"><label class="form-label">Texte</label><textarea class="form-control" rows="2"></textarea></div>
                  <div class="col-md-6"><label class="form-label">Libellé du bouton</label><input type="text" class="form-control" placeholder="Voir les parcelles"></div>
                  <div class="col-md-6"><label class="form-label">Lien</label><input type="text" class="form-control" placeholder="/biens"></div>
                  <div class="col-12"><label class="form-label">Visuel</label><input type="file" class="form-control"></div>
                </div>
              </div>
              <div class="tab-pane fade" id="adSponsor">
                <div class="row g-3">
                  <div class="col-md-6"><label class="form-label">Annonceur</label><input type="text" class="form-control" placeholder="Nom de l'entreprise"></div>
                  <div class="col-md-6"><label class="form-label">Logo</label><input type="file" class="form-control"></div>
                  <div class="col-12"><label class="form-label">Titre du communiqué</label><input type="text" class="form-control"></div>
                  <div class="col-12"><label class="form-label">Texte</label><textarea class="form-control" rows="2"></textarea></div>
                  <div class="col-md-6"><label class="form-label">Lien</label><input type="text" class="form-control" placeholder="https://..."></div>
                  <div class="col-md-6"><label class="form-label">Montant facturé</label>
                    <div class="input-group"><input type="text" class="form-control"><span class="input-group-text">FCFA</span></div></div>
                  <div class="col-12"><div class="alert alert-info mb-0 fs-13">La mention &laquo;&nbsp;Communiqué de [annonceur]&nbsp;&raquo; est ajoutée automatiquement, et le lien reçoit l'attribut <code>rel="sponsored"</code>.</div></div>
                </div>
              </div>
            </div>
            <hr>
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label">Emplacement</label>
                <select class="form-select"><option>Accueil &mdash; après les services</option><option>Actualités &mdash; entre les articles</option><option>Biens &mdash; liste de résultats</option></select></div>
              <div class="col-md-4"><label class="form-label">Début</label><input type="text" class="form-control flatpickr-date" value="21/08/2026" readonly></div>
              <div class="col-md-4"><label class="form-label">Fin</label><input type="text" class="form-control flatpickr-date" value="30/09/2026" readonly></div>
            </div>
          </div>
          <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
            <button class="btn btn-primary">Créer l'annonce</button></div>
        </div>
      </div>
    </div>""" % (tile("layers","primary","Emplacements actifs","1","0"),
                 tile("quote","success","Annonces en cours","2","1"),
                 tile("eye","info","Impressions (30 j)","6 952","14 %"),
                 tile("trending","warning","Clics &mdash; taux moyen","314","0,6 %"),
                 emp, ann)
    js = '<script src="assets/libs/flatpickr/flatpickr.min.js"></script>'
    return render("ads-list.html", "Encarts &amp; annonces", body, "blocksTab", js)

# ==================================================== 15. IMAGES DE FOND
def _lire_images_css():
    """Lit frontoffice/assets/images.css : [(groupe, variable, fichier, libelle)]."""
    import os, re
    chemin = os.path.join("..", "frontoffice", "assets", "images.css")
    if not os.path.exists(chemin):
        return []
    out, groupe = [], "Divers"
    for ligne in open(chemin, encoding="utf-8"):
        g = re.match(r"\s*/\* --- (.+?) --- \*/", ligne)
        if g:
            groupe = g.group(1); continue
        m = re.match(r"\s*--([\w-]+):\s*url\('\.\./([^']+)'\);\s*/\*\s*(.*?)\s*\*/", ligne)
        if m:
            out.append((groupe, m.group(1), m.group(2), m.group(3)))
    return out

def backgrounds():
    import os
    from urllib.parse import quote
    slots = _lire_images_css()

    groupes, ordre = {}, []
    for grp, var, fic, lib in slots:
        if grp not in groupes:
            groupes[grp] = []; ordre.append(grp)
        groupes[grp].append((var, fic, lib))

    def poids(fic):
        p = os.path.join("..", "frontoffice", fic)
        try:
            t = os.path.getsize(p)
            return "%d Ko" % round(t / 1024) if t < 1048576 else "%.1f Mo" % (t / 1048576.0)
        except OSError:
            return "&mdash;"

    cartes = ""
    for grp in ordre:
        lignes = "".join("""
          <tr>
            <td style="width:132px">
              <img src="../frontoffice/{q}" alt="" class="thumb-64"
                   style="width:116px;height:66px;object-fit:cover" loading="lazy">
            </td>
            <td>
              <span class="d-block fw-medium">{lib}</span>
              <code class="fs-12 text-body-secondary">--{var}</code>
            </td>
            <td><code class="fs-12">{fic}</code><span class="d-block text-body-secondary fs-12">{p}</span></td>
            <td class="text-end">
              <div class="d-flex gap-1 justify-content-end">
                <button class="btn btn-sm btn-light waves-effect">{up} Changer</button>
                <button class="btn btn-sm btn-icon btn-action-gray rounded-circle waves-effect"
                        title="Choisir dans la médiathèque">{im}</button>
              </div>
            </td>
          </tr>""".format(q=quote(fic), lib=lib, var=var, fic=fic, p=poids(fic),
                          up=icon("upload", 14), im=icon("image", 16))
            for var, fic, lib in groupes[grp])
        cartes += """
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{grp}</h5>
        <span class="badge bg-secondary-subtle text-body">{n} emplacements</span>
      </div>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Aperçu</th><th>Emplacement</th><th>Fichier</th><th class="text-end">Actions</th></tr></thead>
        <tbody>{l}</tbody></table></div>
    </div>""".format(grp=grp, n=len(groupes[grp]), l=lignes)

    body = page_head("Images de fond", "%d emplacements pilotés depuis une source unique" % len(slots),
        [("Accueil", "dashboard.html"), ("Blocs du site", None), ("Images de fond", None)],
        '<button class="btn btn-light waves-effect">%s Téléverser</button>'
        '<button class="btn btn-primary waves-effect waves-light">%s Appliquer</button>'
        % (icon("upload", 16), icon("check", 16)))

    body += """
    <div class="alert alert-info d-flex gap-2 align-items-start" role="alert">
      <span class="flex-shrink-0">%s</span>
      <div><strong>Comment cela fonctionne.</strong> Une image de fond écrite en dur dans une feuille
      de style ne peut pas être changée par un CMS. Chaque emplacement ci-dessous pointe désormais vers
      une variable CSS regroupée dans <code>frontoffice/assets/images.css</code>. Cet écran ne fait que
      réécrire ce fichier : une ligne modifiée suffit, aucune autre feuille de style n'est touchée.</div>
    </div>
    %s
    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0">Recommandations</h5></div>
      <div class="card-body">
        <div class="row g-3 fs-13">
          <div class="col-md-4">
            <span class="d-block fw-medium mb-1">Bannières de page</span>
            <span class="text-body-secondary">1920 &times; 800 px minimum, cadrage large.
            Un voile sombre est appliqué automatiquement : privilégiez des images lisibles une fois assombries.</span>
          </div>
          <div class="col-md-4">
            <span class="d-block fw-medium mb-1">Cartes services</span>
            <span class="text-body-secondary">800 &times; 1000 px, format portrait.
            Le sujet doit rester lisible dans un cadre étroit.</span>
          </div>
          <div class="col-md-4">
            <span class="d-block fw-medium mb-1">Poids des fichiers</span>
            <span class="text-body-secondary">Visez moins de 300 Ko par image.
            Au-delà, la page devient lente sur les connexions mobiles.</span>
          </div>
        </div>
      </div>
    </div>""" % (icon("help", 18), cartes)
    return render("backgrounds.html", "Images de fond", body, "blocksTab")
