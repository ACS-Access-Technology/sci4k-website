# -*- coding: utf-8 -*-
from layout import render, page_head, icon, HEAD
from pages_a import badge, tile, ROW_ACTIONS

# ---------------------------------------------------------------------- FAQ
def faq_list():
    cats = [("A propos de SCI4K", [
              ("Depuis combien de temps SCI4K est-elle active ?","SCI4K accompagne les particuliers et les entreprises sur le marche immobilier ivoirien depuis 2015, avec une equipe basee a Abidjan."),
              ("Dans quelles communes intervenez-vous ?","Nous couvrons l'ensemble du district d'Abidjan, avec une specialisation sur Cocody, le Plateau, Marcory et Bingerville.")]),
            ("Services de vente", [
              ("Quels sont vos frais d'agence ?","Les frais se situent entre 5 et 10 % du prix de vente selon le type de bien et le mandat retenu. Ils sont precises dans le mandat signe."),
              ("Comment estimez-vous un bien ?","L'estimation s'appuie sur une visite sur place, les transactions comparables du quartier et l'etat general du bien."),
              ("Combien de temps prend une vente ?","Comptez en moyenne trois a six mois entre la mise en ligne et la signature definitive chez le notaire.")]),
            ("Location et gestion", [
              ("Proposez-vous la gestion locative ?","Oui. La gestion complete comprend la recherche de locataire, l'encaissement des loyers et le suivi technique du bien."),
              ("Quelles garanties demandez-vous ?","Trois mois de caution, un mois d'avance et les justificatifs de revenus du locataire.")]),
            ("Processus et documents", [
              ("Quels documents pour acheter ?","Piece d'identite en cours de validite, justificatif de domicile et attestation de financement ou de fonds."),
              ("Le titre foncier est-il verifie ?","Chaque bien mis en ligne fait l'objet d'une verification du titre foncier avant publication.")])]

    n = 0
    blocks = []
    for ci, (cat, items) in enumerate(cats):
        rows = []
        for q, a in items:
            n += 1
            rows.append("""
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq{n}">
                  {q}
                </button>
              </h2>
              <div id="faq{n}" class="accordion-collapse collapse" data-bs-parent="#acc{ci}">
                <div class="accordion-body">
                  <p class="mb-3">{a}</p>
                  <div class="d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-sm btn-light waves-effect">{e} Modifier</button>
                    <button class="btn btn-sm btn-light text-danger waves-effect">{t} Supprimer</button>
                    <span class="ms-auto text-body-secondary fs-12">Position {n} &mdash; visible sur le site</span>
                  </div>
                </div>
              </div>
            </div>""".format(n=n, ci=ci, q=q, a=a, e=icon("edit",14), t=icon("trash",14)))
        blocks.append("""
      <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
          <h5 class="card-title mb-0">{cat}</h5>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary-subtle text-body">{c} questions</span>
            <button class="btn btn-sm btn-light waves-effect">{p} Ajouter</button>
          </div>
        </div>
        <div class="card-body">
          <div class="accordion accordion-flush" id="acc{ci}">{rows}</div>
        </div>
      </div>""".format(cat=cat, c=len(items), ci=ci, rows="".join(rows), p=icon("plus",14)))

    body = page_head("Foire aux questions", "%d questions reparties en %d categories" % (n, len(cats)),
        [("Accueil","dashboard.html"),("Contenu",None),("FAQ",None)],
        '<button class="btn btn-light waves-effect">%s Gerer les categories</button>'
        '<button class="btn btn-primary waves-effect waves-light">%s Nouvelle question</button>'
        % (icon("layers",16), icon("plus",16)))
    body += "".join(blocks)
    return render("faq-list.html", "FAQ", body, "contentTab")

# ------------------------------------------------------------- pages du site
def pages_list():
    rows = [("Accueil","/","Publie","success","20/08/2026","3 456"),
            ("Presentation","/presentation","Publie","success","18/08/2026","640"),
            ("Nos services","/services","Publie","success","16/08/2026","980"),
            ("Biens immobiliers","/biens","Publie","success","20/08/2026","2 890"),
            ("Actualités","/actualites","Publie","success","18/08/2026","1 234"),
            ("Contact","/contact","Publie","success","15/08/2026","890"),
            ("Mentions legales","/mentions-legales","Publie","success","10/08/2026","112"),
            ("Politique de confidentialite","/politique-confidentialite","Publie","success","10/08/2026","87"),
            ("Page 404","/404","Systeme","secondary","10/08/2026","44"),
            ("Page 500","/500","Systeme","secondary","21/08/2026","6")]
    trs = "".join("""
          <tr>
            <td><div class="d-flex align-items-center gap-3">
              <span class="stat-ico bg-primary-subtle text-primary" style="width:36px;height:36px;border-radius:10px">{i}</span>
              <div><span class="d-block fw-medium">{n}</span><span class="text-body-secondary fs-12">{u}</span></div>
            </div></td>
            <td>{st}</td><td>{d}</td><td>{v}</td>
            <td class="text-end">{act}</td>
          </tr>""".format(i=icon("pages",16), n=n, u=u, st=badge(st, tone), d=d, v=v,
                          act=ROW_ACTIONS.format(edit="pages-edit.html")) for n, u, st, tone, d, v in rows)
    body = page_head("Pages du site", "Contenu editorial des pages statiques du site public",
        [("Accueil","dashboard.html"),("Contenu",None),("Pages",None)],
        '')
    body += """
    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0">10 pages</h5></div>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Page</th><th>Statut</th><th>Derniere modification</th><th>Vues (30 j)</th><th class="text-end">Actions</th></tr></thead>
        <tbody>%s</tbody></table></div>
    </div>""" % trs
    return render("pages-list.html", "Pages du site", body, "contentTab")

def pages_edit():
    secs = [("Banniere principale","Titre, sous-titre, image de fond et bouton d'action"),
            ("Presentation rapide","Bloc de trois colonnes sous la banniere"),
            ("Services en avant","Selection des services affiches sur l'accueil"),
            ("Biens en vedette","Selection manuelle ou automatique des biens mis en avant"),
            ("Temoignages clients","Avis affiches dans le carrousel"),
            ("Partenaires","Logos affiches en bas de page"),
            ("Bandeau d'appel a l'action","Bloc &laquo; Pret a concretiser votre projet ? &raquo; avant le pied de page")]
    ss = "".join("""
            <div class="d-flex align-items-center gap-3 py-3 border-bottom">
              <span class="text-body-secondary" style="cursor:grab">&#8942;&#8942;</span>
              <div class="flex-grow-1">
                <span class="d-block fw-medium fs-14">{t}</span>
                <span class="text-body-secondary fs-12">{d}</span>
              </div>
              <div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" checked></div>
              <button class="btn btn-sm btn-light waves-effect">{e}</button>
            </div>""".format(t=t, d=d, e=icon("edit",14)) for t, d in secs)
    body = page_head("Modifier une page", "Page d'accueil &mdash; /",
        [("Accueil","dashboard.html"),("Pages","pages-list.html"),("Modifier",None)],
        '<a href="pages-list.html" class="btn btn-light waves-effect">%s Retour</a>'
        '<button class="btn btn-light waves-effect">%s Apercu</button>'
        '<button class="btn btn-primary waves-effect waves-light">%s Enregistrer</button>'
        % (icon("arrow-left",16), icon("eye",16), icon("check",16)))
    body += """
    <div class="row g-3">
      <div class="col-xl-8">
        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Identite de la page</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-8"><label class="form-label">Titre</label><input type="text" class="form-control" value="Accueil"></div>
              <div class="col-md-4"><label class="form-label">Adresse</label><input type="text" class="form-control" value="/"></div>
              <div class="col-12"><label class="form-label">Titre de la banniere</label>
                <input type="text" class="form-control" value="Trouvez le bien qui vous ressemble a Abidjan"></div>
              <div class="col-12"><label class="form-label">Sous-titre</label>
                <textarea class="form-control" rows="2">Vente, location et construction : SCI4K accompagne vos projets immobiliers en Cote d'Ivoire.</textarea></div>
              <div class="col-md-6"><label class="form-label">Libelle du bouton</label><input type="text" class="form-control" value="Voir les biens"></div>
              <div class="col-md-6"><label class="form-label">Lien du bouton</label><input type="text" class="form-control" value="/biens"></div>
            </div>
          </div>
        </div>
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Sections de la page</h5>
            <button class="btn btn-sm btn-light waves-effect">%s Ajouter une section</button>
          </div>
          <div class="card-body pt-0">%s</div>
        </div>
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Bandeau d'appel a l'action</h5>
            <div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" checked></div>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12"><label class="form-label">Titre</label>
                <input type="text" class="form-control" value="Pret a concretiser votre projet immobilier ?"></div>
              <div class="col-12"><label class="form-label">Texte</label>
                <textarea class="form-control" rows="2">Decouvrez notre catalogue complet d'appartements, villas et terrains disponibles a l'achat et a la location.</textarea></div>
              <div class="col-md-6"><label class="form-label">Libelle du bouton</label>
                <input type="text" class="form-control" value="Consulter les biens"></div>
              <div class="col-md-6"><label class="form-label">Lien du bouton</label>
                <input type="text" class="form-control" value="/biens"></div>
              <div class="col-12"><label class="form-label">Image de fond</label>
                <input type="file" class="form-control"></div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Referencement</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12"><label class="form-label">Titre meta</label>
                <input type="text" class="form-control" value="SCI4K &mdash; Immobilier a Abidjan : vente, location, construction"></div>
              <div class="col-12"><label class="form-label">Description meta</label>
                <textarea class="form-control" rows="3">Agence immobiliere basee a Abidjan. Villas, appartements, terrains et bureaux a la vente et a la location.</textarea></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Publication</h5></div>
          <div class="card-body">
            <label class="form-label">Statut</label>
            <select class="form-select mb-3"><option selected>Publie</option><option>Brouillon</option></select>
            <div class="fs-12 text-body-secondary">
              <div class="d-flex justify-content-between"><span>Creee le</span><span>10/08/2026</span></div>
              <div class="d-flex justify-content-between"><span>Modifiee le</span><span>20/08/2026</span></div>
              <div class="d-flex justify-content-between"><span>Vues (30 j)</span><span>3 456</span></div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Image de partage</h5></div>
          <div class="card-body">
            <div class="media-ph rounded-3 mb-3" style="aspect-ratio:16/9;display:flex;align-items:center;justify-content:center;background:var(--bs-secondary-bg);color:var(--bs-secondary-color)">%s</div>
            <button class="btn btn-light w-100 waves-effect">%s Remplacer</button>
          </div>
        </div>
      </div>
    </div>""" % (icon("plus",14), ss, icon("image",34), icon("upload",16))
    return render("pages-edit.html", "Modifier une page", body, "contentTab")

# ------------------------------------------------------------------ services
def service_list():
    rows = [("Foncier","Recherche, verification des titres et securisation des parcelles","pages","success",1,"services/foncier.png"),
            ("Construction","Suivi de chantier et coordination des corps de metier","tools","warning",2,"services/construction.jpg"),
            ("Gestion / Location","Recherche de locataire, encaissement des loyers et suivi technique","key","info",3,"services/gestion-location.jpg"),
            ("Achat","Accompagnement de l'acquereur, de la recherche a la signature","building","primary",4,"services/achat.jpg"),
            ("Vente","Mandat, estimation et negociation jusqu'a l'acte notarie","trending","danger",5,"services/vente.jpg"),
            ("Administration de biens","Gestion courante, charges et relation avec les coproprietaires","badge","secondary",6,"services/administration.jpg")]
    trs = "".join("""
          <tr>
            <td><span class="text-body-secondary" style="cursor:grab">&#8942;&#8942;</span></td>
            <td><div class="d-flex align-items-center gap-3">
              <span class="stat-ico bg-{tone}-subtle text-{tone}" style="width:38px;height:38px;border-radius:10px">{i}</span>
              <div><span class="d-block fw-medium">{t}</span><span class="text-body-secondary fs-12">{d}</span></div>
            </div></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <img src="../frontoffice/images/{img}" alt="" class="thumb-64" style="object-fit:cover">
                <div class="lh-sm">
                  <code class="fs-12 d-block">{img}</code>
                  <button class="btn btn-sm btn-light waves-effect mt-1">{up} Changer</button>
                </div>
              </div>
            </td>
            <td><input type="text" class="form-control form-control-sm" style="width:70px" value="{o0}"></td>
            <td>{st}</td>
            <td class="text-end">{act}</td>
          </tr>""".format(i=icon(ic,18), tone=tone, t=t, d=d, o=o, o0=o-1, img=img, up=icon("upload",14),
                          st=badge("Publie","success"),
                          act=ROW_ACTIONS.format(edit="javascript:void(0);")) for t, d, ic, tone, o, img in rows)
    body = page_head("Services", "Les 6 prestations presentees sur la page /services",
        [("Accueil","dashboard.html"),("Contenu",None),("Services",None)],
        '<button class="btn btn-primary waves-effect waves-light">%s Nouveau service</button>' % icon("plus",16))
    body += """
    <div class="alert alert-info d-flex gap-2 align-items-start" role="alert">
      <span class="flex-shrink-0">%s</span>
      <div><strong>Visuels rapatries en local.</strong> Les six images de fond des cartes services
      pointaient vers Wikimedia. Elles sont desormais servies depuis
      <code>frontoffice/images/services/</code> et remplacables une par une ci-dessous.</div>
    </div>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">6 services</h5>
        <span class="text-body-secondary fs-13">Glissez les lignes pour changer l'ordre d'affichage</span>
      </div>
      <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th style="width:36px"></th><th>Service</th><th>Visuel sur l'accueil</th><th>Ordre</th><th>Statut</th><th class="text-end">Actions</th></tr></thead>
        <tbody>%s</tbody></table></div>
      <div class="card-footer text-end"><button class="btn btn-primary waves-effect waves-light">%s Enregistrer l'ordre</button></div>
    </div>""" % (icon("help",18), trs, icon("check",16))
    return render("service-list.html", "Services", body, "contentTab")

# -------------------------------------------------------------- utilisateurs
def users_list():
    rows = [("Ilyas Kone","ilyas@sci4k.ci","Administrateur","danger","Actif","success","Il y a 5 min"),
            ("Emma Diallo","emma@sci4k.ci","Editeur","primary","Actif","success","Il y a 2 h"),
            ("Marc Toure","marc@sci4k.ci","Redacteur","info","Actif","success","Hier"),
            ("Awa Sylla","awa@sci4k.ci","Redacteur","info","Inactif","secondary","Il y a 3 semaines"),
            ("Jean Bamba","jean@sci4k.ci","Lecteur","secondary","Invitation envoyee","warning","Jamais")]
    trs = "".join("""
          <tr>
            <td><input class="form-check-input" type="checkbox"></td>
            <td><div class="d-flex align-items-center gap-3">
              <span class="avatar-initial rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px;font-size:13px;font-weight:600">{ini}</span>
              <div><span class="d-block fw-medium">{n}</span><span class="text-body-secondary fs-12">{e}</span></div>
            </div></td>
            <td>{r}</td><td>{s}</td><td class="text-body-secondary fs-13">{l}</td>
            <td class="text-end">{act}</td>
          </tr>""".format(ini="".join(p[0] for p in n.split()[:2]), n=n, e=e, r=badge(role, rt),
                          s=badge(st, stt), l=last, act=ROW_ACTIONS.format(edit="javascript:void(0);"))
        for n, e, role, rt, st, stt, last in rows)
    perms = [("Administrateur","Acces complet, y compris la configuration et les utilisateurs"),
             ("Editeur","Cree et publie tous les contenus, sans acces aux reglages"),
             ("Redacteur","Cree et modifie ses propres contenus, publication soumise a validation"),
             ("Lecteur","Consultation seule du tableau de bord et des statistiques")]
    ps = "".join("""
          <div class="d-flex gap-3 py-3 border-bottom">
            <span class="stat-ico bg-secondary-subtle text-body flex-shrink-0" style="width:36px;height:36px;border-radius:10px">{i}</span>
            <div><span class="d-block fw-medium fs-14">{r}</span><span class="text-body-secondary fs-12">{d}</span></div>
          </div>""".format(i=icon("users",16), r=r, d=d) for r, d in perms)
    body = page_head("Utilisateurs", "5 comptes, 3 actifs",
        [("Accueil","dashboard.html"),("Reglages",None),("Utilisateurs",None)],
        '<button class="btn btn-primary waves-effect waves-light">%s Inviter un utilisateur</button>' % icon("plus",16))
    body += """
    <div class="row g-3">
      <div class="col-xl-8">
        <div class="card">
          <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h5 class="card-title mb-0">Comptes</h5>
            <div class="d-flex gap-2">
              <input type="text" class="form-control form-control-sm" placeholder="Rechercher..." style="width:180px">
              <select class="form-select form-select-sm w-auto"><option>Tous les roles</option><option>Administrateur</option><option>Editeur</option><option>Redacteur</option><option>Lecteur</option></select>
            </div>
          </div>
          <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th style="width:36px"><input class="form-check-input" type="checkbox"></th>
            <th>Utilisateur</th><th>Role</th><th>Statut</th><th>Derniere connexion</th><th class="text-end">Actions</th></tr></thead>
            <tbody>%s</tbody></table></div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card h-100">
          <div class="card-header"><h5 class="card-title mb-0">Roles et permissions</h5></div>
          <div class="card-body pt-0">%s</div>
        </div>
      </div>
    </div>""" % (trs, ps)
    return render("users-list.html", "Utilisateurs", body, "settingsTab")

# -------------------------------------------------------------- mediatheque
def _scan_images():
    """Inventaire reel de frontoffice/images/ au moment de la generation."""
    import os
    from urllib.parse import quote
    base = os.path.join("..", "frontoffice", "images")
    exts = (".jpg", ".jpeg", ".png", ".webp", ".svg", ".gif")
    out = []
    for racine, _, noms in os.walk(base):
        dossier = os.path.relpath(racine, base).replace("\\", "/")
        dossier = "" if dossier == "." else dossier
        for n in sorted(noms):
            if not n.lower().endswith(exts):
                continue
            p = os.path.join(racine, n)
            try:
                taille = os.path.getsize(p)
            except OSError:
                continue
            rel = (dossier + "/" if dossier else "") + n
            # encodage URL : plusieurs fichiers portent des espaces et des parentheses
            out.append((n, dossier, taille, quote(rel)))
    return sorted(out, key=lambda x: (x[1], x[0]))

def _ko(n):
    return "%d Ko" % round(n / 1024) if n < 1024 * 1024 else "%.1f Mo" % (n / 1048576.0)

def media_gallery():
    files = _scan_images()
    total = sum(f[2] for f in files)
    dossiers = sorted({f[1] for f in files if f[1]})
    LIBELLES = {"": ("Divers", "Fichiers a la racine du dossier images"),
                "actualites": ("Actualites", "Couvertures des articles"),
                "hero": ("Bannieres de page", "Fonds des bandeaux de titre"),
                "partners": ("Partenaires", "Logos de l ecosysteme"),
                "presentation": ("Presentation", "Visuels de la page Presentation"),
                "sections": ("Sections", "Fonds des blocs de contenu"),
                "services": ("Services", "Visuels des cartes de prestation")}

    par_dossier = {}
    for n, dos, t, rel in files:
        par_dossier.setdefault(dos, []).append((n, t, rel))

    def vignette(n, t, rel):
        return """
        <div class="col-xl-2 col-lg-3 col-md-4 col-6">
          <div class="media-tile h-100">
            <div class="media-ph p-0"><img src="../frontoffice/images/{rel}" alt="{n}"
                 style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>
            <div class="p-2 border-top">
              <div class="fs-12 fw-medium text-truncate" title="{rel}">{n}</div>
              <div class="fs-12 text-body-secondary">{s}</div>
            </div>
          </div>
        </div>""".format(n=n, s=_ko(t), rel=rel)

    tiles = ""
    for dos in sorted(par_dossier, key=lambda d: (d == "", d)):
        titre, sous = LIBELLES.get(dos, (dos or "Divers", ""))
        poids = sum(t for _, t, _ in par_dossier[dos])
        tiles += """
    <div class="card mb-3">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
          <h5 class="card-title mb-0">{titre}</h5>
          <span class="text-body-secondary fs-12">{sous} &mdash; <code>images/{dos}</code></span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-secondary-subtle text-body">{n} fichiers</span>
          <span class="badge bg-secondary-subtle text-body">{p}</span>
        </div>
      </div>
      <div class="card-body"><div class="row g-3">{v}</div></div>
    </div>""".format(titre=titre, sous=sous, dos=dos or "", n=len(par_dossier[dos]), p=_ko(poids),
                     v="".join(vignette(*x) for x in par_dossier[dos]))
    body = page_head("Mediatheque", "%d fichiers &mdash; %s dans frontoffice/images/" % (len(files), _ko(total)),
        [("Accueil","dashboard.html"),("Contenu",None),("Mediatheque",None)],
        '<button class="btn btn-light waves-effect">%s Nouveau dossier</button>'
        '<button class="btn btn-primary waves-effect waves-light">%s Televerser</button>'
        % (icon("plus",16), icon("upload",16)))
    body += """
    <div class="card mb-3">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-lg-4 col-md-6"><label class="form-label">Recherche</label>
            <input type="text" class="form-control" placeholder="Nom du fichier..."></div>
          <div class="col-lg-2 col-md-6"><label class="form-label">Type</label>
            <select class="form-select"><option>Tous</option><option>Images</option><option>Documents</option><option>Videos</option></select></div>
          <div class="col-lg-2 col-md-6"><label class="form-label">Dossier</label>
            <select class="form-select"><option>Tous les dossiers</option><option>racine</option>""" + "".join('<option>%s</option>' % d for d in dossiers) + """</select></div>
          <div class="col-lg-2 col-md-6"><label class="form-label">Trier par</label>
            <select class="form-select"><option>Plus recent</option><option>Nom</option><option>Taille</option></select></div>
          <div class="col-lg-2">
            <div class="d-flex justify-content-end gap-1">
              <button class="btn btn-primary btn-icon waves-effect waves-light" title="Grille">%s</button>
              <button class="btn btn-light btn-icon waves-effect" title="Liste">%s</button>
            </div>
          </div>
        </div>
        <div class="fs-12 text-body-secondary mt-3">Inventaire lu directement dans
        <code>frontoffice/images/</code> &mdash; dossiers : racine, %s.</div>
      </div>
    </div>
    %s""" % (icon("layers",16), icon("pages",16), ", ".join(dossiers), tiles)
    return render("media-gallery.html", "Mediatheque", body, "contentTab")

# --------------------------------------------------------------- reglages
def settings():
    body = page_head("Configuration", "Parametres generaux du site SCI4K",
        [("Accueil","dashboard.html"),("Reglages",None),("Configuration",None)],
        '<button class="btn btn-primary waves-effect waves-light">%s Enregistrer</button>' % icon("check",16))
    body += """
    <div class="card">
      <div class="card-header p-0 border-bottom">
        <ul class="nav nav-tabs card-header-tabs mx-3 mt-3 border-0" role="tablist">
          <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabGeneral" type="button">General</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSeo" type="button">Referencement</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabContact" type="button">Contact</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSocial" type="button">Reseaux sociaux</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabMail" type="button">Messagerie</button></li>
        </ul>
      </div>
      <div class="card-body">
        <div class="tab-content">

          <div class="tab-pane fade show active" id="tabGeneral">
            <div class="row g-3" style="max-width:760px">
              <div class="col-md-6"><label class="form-label">Nom du site</label><input type="text" class="form-control" value="SCI4K"></div>
              <div class="col-md-6"><label class="form-label">Slogan</label><input type="text" class="form-control" value="L'immobilier autrement a Abidjan"></div>
              <div class="col-12"><label class="form-label">Description courte</label>
                <textarea class="form-control" rows="2">Agence immobiliere basee a Abidjan : vente, location, gestion locative et construction.</textarea></div>
              <div class="col-md-6"><label class="form-label">Logo</label>
                <div class="d-flex align-items-center gap-3">
                  <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white fw-bold" style="width:48px;height:48px">S4</span>
                  <button class="btn btn-light btn-sm waves-effect">%s Remplacer</button>
                </div></div>
              <div class="col-md-6"><label class="form-label">Favicon</label>
                <div class="d-flex align-items-center gap-3">
                  <span class="d-inline-flex align-items-center justify-content-center rounded-2 bg-secondary-subtle" style="width:32px;height:32px">%s</span>
                  <button class="btn btn-light btn-sm waves-effect">%s Remplacer</button>
                </div></div>
              <div class="col-md-4"><label class="form-label">Langue</label>
                <select class="form-select"><option selected>Francais</option><option>Anglais</option></select></div>
              <div class="col-md-4"><label class="form-label">Fuseau horaire</label>
                <select class="form-select"><option selected>Africa/Abidjan (UTC+0)</option><option>Europe/Paris (UTC+1)</option></select></div>
              <div class="col-md-4"><label class="form-label">Devise</label>
                <select class="form-select"><option selected>FCFA (XOF)</option><option>Euro</option><option>Dollar US</option></select></div>
              <div class="col-12"><hr>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="maint">
                  <label class="form-check-label" for="maint">Activer le mode maintenance</label>
                  <div class="form-text">Le site public affiche une page d'attente ; le backoffice reste accessible.</div></div></div>
            </div>
          </div>

          <div class="tab-pane fade" id="tabSeo">
            <div class="row g-3" style="max-width:760px">
              <div class="col-12"><label class="form-label">Titre meta par defaut</label>
                <input type="text" class="form-control" value="SCI4K &mdash; Immobilier a Abidjan"></div>
              <div class="col-12"><label class="form-label">Description meta par defaut</label>
                <textarea class="form-control" rows="3">Villas, appartements, terrains et bureaux a la vente et a la location a Abidjan.</textarea></div>
              <div class="col-md-6"><label class="form-label">Identifiant Google Analytics</label>
                <input type="text" class="form-control" placeholder="G-XXXXXXXXXX"></div>
              <div class="col-md-6"><label class="form-label">Identifiant Search Console</label>
                <input type="text" class="form-control" placeholder="google-site-verification=..."></div>
              <div class="col-12"><label class="form-label">Fichier robots.txt</label>
                <textarea class="form-control font-monospace fs-13" rows="4">User-agent: *
Allow: /
Sitemap: https://sci4k.ci/sitemap.xml</textarea></div>
              <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="idx" checked>
                <label class="form-check-label" for="idx">Autoriser l'indexation par les moteurs de recherche</label></div></div>
            </div>
          </div>

          <div class="tab-pane fade" id="tabContact">
            <div class="row g-3" style="max-width:760px">
              <div class="col-md-6"><label class="form-label">Telephone principal</label><input type="text" class="form-control" value="+225 07 00 00 00 00"></div>
              <div class="col-md-6"><label class="form-label">WhatsApp</label><input type="text" class="form-control" value="+225 07 00 00 00 00"></div>
              <div class="col-md-6"><label class="form-label">Adresse e-mail publique</label><input type="text" class="form-control" value="contact@sci4k.ci"></div>
              <div class="col-md-6"><label class="form-label">Destinataire du formulaire</label><input type="text" class="form-control" value="contact@sci4k.ci"></div>
              <div class="col-12"><label class="form-label">Adresse postale</label>
                <textarea class="form-control" rows="2">Cocody, Riviera Golf, Abidjan, Cote d'Ivoire</textarea></div>
              <div class="col-md-6"><label class="form-label">Horaires</label><input type="text" class="form-control" value="Lundi au vendredi, 8h &ndash; 18h"></div>
              <div class="col-md-6"><label class="form-label">Coordonnees de la carte</label><input type="text" class="form-control" value="5.3600, -3.9900"></div>
            </div>
          </div>

          <div class="tab-pane fade" id="tabSocial">
            <div class="row g-3" style="max-width:760px">
              <div class="col-md-6"><label class="form-label">Facebook</label><input type="text" class="form-control" placeholder="https://facebook.com/..."></div>
              <div class="col-md-6"><label class="form-label">Instagram</label><input type="text" class="form-control" placeholder="https://instagram.com/..."></div>
              <div class="col-md-6"><label class="form-label">LinkedIn</label><input type="text" class="form-control" placeholder="https://linkedin.com/company/..."></div>
              <div class="col-md-6"><label class="form-label">YouTube</label><input type="text" class="form-control" placeholder="https://youtube.com/@..."></div>
              <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="shr" checked>
                <label class="form-check-label" for="shr">Afficher les boutons de partage sur les articles</label></div></div>
            </div>
          </div>

          <div class="tab-pane fade" id="tabMail">
            <div class="row g-3" style="max-width:760px">
              <div class="col-md-6"><label class="form-label">Serveur SMTP</label><input type="text" class="form-control" placeholder="smtp.exemple.ci"></div>
              <div class="col-md-3"><label class="form-label">Port</label><input type="text" class="form-control" value="587"></div>
              <div class="col-md-3"><label class="form-label">Chiffrement</label>
                <select class="form-select"><option selected>TLS</option><option>SSL</option><option>Aucun</option></select></div>
              <div class="col-md-6"><label class="form-label">Identifiant</label><input type="text" class="form-control" placeholder="no-reply@sci4k.ci"></div>
              <div class="col-md-6"><label class="form-label">Mot de passe</label><input type="password" class="form-control" value="123456789"></div>
              <div class="col-md-6"><label class="form-label">Nom de l'expediteur</label><input type="text" class="form-control" value="SCI4K"></div>
              <div class="col-md-6"><label class="form-label">Adresse de l'expediteur</label><input type="text" class="form-control" value="no-reply@sci4k.ci"></div>
              <div class="col-12"><button class="btn btn-light waves-effect">%s Envoyer un e-mail de test</button></div>
            </div>
          </div>

        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <button class="btn btn-light waves-effect">Annuler</button>
        <button class="btn btn-primary waves-effect waves-light">%s Enregistrer les modifications</button>
      </div>
    </div>""" % (icon("upload",14), icon("image",16), icon("upload",14), icon("mail",16), icon("check",16))
    return render("settings.html", "Configuration", body, "settingsTab")

# ------------------------------------------------------------------- login
def login():
    html = HEAD.format(title="Connexion").replace('<div class="page-layout">', '<div class="auth-layout">')
    html += """
  <div class="d-flex align-items-center justify-content-center min-vh-100 p-3">
    <div class="w-100" style="max-width:420px">
      <div class="text-center mb-4">
        <span class="d-inline-flex align-items-center justify-content-center rounded-4 bg-primary text-white fw-bold mb-3" style="width:56px;height:56px;font-size:20px">S4</span>
        <h4 class="mb-1">Bon retour</h4>
        <p class="text-body-secondary mb-0 fs-14">Connectez-vous au panneau d'administration SCI4K</p>
      </div>
      <div class="card">
        <div class="card-body p-4">
          <form onsubmit="return false">
            <div class="mb-3">
              <label class="form-label">Adresse e-mail</label>
              <input type="email" class="form-control" placeholder="vous@sci4k.ci">
            </div>
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center">
                <label class="form-label mb-0">Mot de passe</label>
                <a href="javascript:void(0);" class="fs-13">Mot de passe oublie ?</a>
              </div>
              <input type="password" class="form-control mt-2" value="123456789">
            </div>
            <div class="form-check mb-4">
              <input class="form-check-input" type="checkbox" id="rm" checked>
              <label class="form-check-label fs-14" for="rm">Rester connecte sur cet appareil</label>
            </div>
            <a href="dashboard.html" class="btn btn-primary w-100 waves-effect waves-light">Se connecter</a>
          </form>
        </div>
      </div>
      <p class="text-center text-body-secondary fs-12 mt-4 mb-0">&copy; 2026 SCI4K &mdash; Acces reserve aux administrateurs.</p>
    </div>
  </div>
</div>
<script src="assets/libs/global/global.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
"""
    open("login.html", "w", encoding="utf-8").write(html)
    return "login.html"

def index():
    html = """<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow">
<meta http-equiv="refresh" content="0; url=login.html">
<title>SCI4K Admin</title>
</head>
<body>
<p>Redirection vers <a href="login.html">la page de connexion</a>&hellip;</p>
</body>
</html>
"""
    open("index.html", "w", encoding="utf-8").write(html)
    return "index.html"
