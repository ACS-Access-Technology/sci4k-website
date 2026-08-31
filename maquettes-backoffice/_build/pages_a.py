# -*- coding: utf-8 -*-
from layout import render, page_head, icon

def tile(ic, tone, label, value, delta, up=True):
    return """
      <div class="col-xl-3 col-sm-6">
        <div class="stat-tile">
          <span class="stat-ico bg-{t}-subtle text-{t}">{i}</span>
          <div class="stat-value">{v}</div>
          <div class="d-flex align-items-center justify-content-between">
            <span class="text-body-secondary fs-13">{l}</span>
            <span class="badge bg-{d}-subtle text-{d} fs-12">{ar} {x}</span>
          </div>
        </div>
      </div>""".format(t=tone, i=icon(ic, 22), v=value, l=label, x=delta,
                       d="success" if up else "danger", ar="&#8593;" if up else "&#8595;")

def badge(text, tone):
    return '<span class="badge bg-%s-subtle text-%s">%s</span>' % (tone, tone, text)

ROW_ACTIONS = ('<div class="d-flex gap-1 justify-content-end">'
  '<a href="{edit}" class="btn btn-sm btn-icon btn-action-gray rounded-circle waves-effect" title="Modifier">{e}</a>'
  '<a href="javascript:void(0);" class="btn btn-sm btn-icon btn-action-gray rounded-circle waves-effect" title="Voir">{v}</a>'
  '<a href="javascript:void(0);" class="btn btn-sm btn-icon btn-action-gray rounded-circle waves-effect text-danger" title="Supprimer">{t}</a>'
  '</div>').format(edit="{edit}", e=icon("edit",16), v=icon("eye",16), t=icon("trash",16))

# ----------------------------------------------------------------- dashboard
def dashboard():
    activity = [("Bien #123 &laquo;&nbsp;Villa Cocody&nbsp;&raquo; modifie","il y a 2 h","building","primary"),
                ("Article &laquo;&nbsp;Marche immobilier Q3&nbsp;&raquo; publie","il y a 4 h","file","success"),
                ("FAQ #45 mise a jour","il y a 6 h","help","warning"),
                ("3 formulaires de contact recus","hier","mail","info"),
                ("Utilisateur Emma D. ajoute","hier","users","secondary")]
    act = "".join("""
        <div class="d-flex gap-3 py-3 border-bottom">
          <span class="stat-ico bg-{t}-subtle text-{t} flex-shrink-0" style="width:36px;height:36px;border-radius:10px">{i}</span>
          <div><div class="fw-medium fs-14">{x}</div><div class="text-body-secondary fs-12">{w}</div></div>
        </div>""".format(t=t, i=icon(ic,16), x=x, w=w) for x, w, ic, t in activity)

    msgs = [("Leon K.","Demande de visite &mdash; Villa Cocody","15 min",True),
            ("Emma D.","Formulaire de contact","1 h",True),
            ("Marc T.","Demande d'information terrain","2 h",False),
            ("Awa S.","Question sur le financement","5 h",False)]
    ms = "".join("""
        <a href="javascript:void(0);" class="d-flex gap-3 py-3 border-bottom text-body text-decoration-none">
          <span class="avatar-initial rounded-circle bg-secondary-subtle text-body d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;font-size:13px;font-weight:600">{ini}</span>
          <div class="flex-grow-1 min-w-0">
            <div class="d-flex justify-content-between gap-2">
              <span class="fw-medium fs-14">{n}</span><span class="text-body-secondary fs-12">{w}</span>
            </div>
            <div class="text-body-secondary fs-13 text-truncate">{s}</div>
          </div>
          {b}
        </a>""".format(ini="".join(p[0] for p in n.split()[:2]), n=n, s=s, w=w,
                       b='<span class="badge bg-primary rounded-pill align-self-center">new</span>' if new else "")
        for n, s, w, new in msgs)

    tasks = [("Mettre a jour 5 biens avec de nouvelles photos","3 jours","danger",False),
             ("Publier l'article &laquo;&nbsp;Marche immobilier Q3&nbsp;&raquo;","5 jours","warning",False),
             ("Repondre aux 3 demandes de visite","en cours","info",True),
             ("Ajouter 2 nouveaux services","1 semaine","secondary",False)]
    ts = "".join("""
        <div class="d-flex align-items-center gap-3 py-3 border-bottom">
          <input class="form-check-input mt-0" type="checkbox" {ck}>
          <span class="flex-grow-1 fs-14 {muted}">{x}</span>
          {b}
        </div>""".format(ck="checked" if c else "", muted="text-decoration-line-through text-body-secondary" if c else "",
                         x=x, b=badge(d, t)) for x, d, t, c in tasks)

    body = page_head("Tableau de bord", "Vue d'ensemble de l'activite du site SCI4K",
        [("Accueil", "dashboard.html"), ("Tableau de bord", None)],
        '<a href="bien-edit.html" class="btn btn-primary waves-effect waves-light">%s Ajouter un bien</a>' % icon("plus",16))
    body += '<div class="row g-3 mb-3">%s%s%s%s</div>' % (
        tile("building","primary","Biens en ligne","245","12 %"),
        tile("file","success","Articles publies","52","8 %"),
        tile("users","info","Visiteurs aujourd'hui","1 234","23 %"),
        tile("mail","warning","Messages non lus","18","5 %", up=False))
    body += """
    <div class="row g-3 mb-3">
      <div class="col-xxl-8">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Frequentation des 12 derniers mois</h5>
            <select class="form-select form-select-sm w-auto"><option>12 mois</option><option>6 mois</option><option>30 jours</option></select>
          </div>
          <div class="card-body"><div id="trafficChart"></div></div>
        </div>
      </div>
      <div class="col-xxl-4">
        <div class="card h-100">
          <div class="card-header"><h5 class="card-title mb-0">Repartition des biens</h5></div>
          <div class="card-body"><div id="typeChart"></div></div>
        </div>
      </div>
    </div>
    <div class="row g-3">
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header"><h5 class="card-title mb-0">Activite recente</h5></div>
          <div class="card-body pt-0">%s</div>
          <div class="card-footer text-center"><a href="messages-list.html" class="fs-13">Tout afficher</a></div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><a href="messages-list.html" class="text-body text-decoration-none">Messages</a></h5>%s
          </div>
          <div class="card-body pt-0">%s</div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Taches prioritaires</h5>
            <button class="btn btn-sm btn-light waves-effect">%s</button>
          </div>
          <div class="card-body pt-0">%s</div>
        </div>
      </div>
    </div>""" % (act, badge("5 non lus","primary"), ms, icon("plus",14), ts)

    js = """<script src="assets/libs/apexcharts/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!window.ApexCharts) return;
  var ink = getComputedStyle(document.body).getPropertyValue('--bs-body-color');
  new ApexCharts(document.querySelector('#trafficChart'), {
    chart:{type:'area',height:300,toolbar:{show:false},fontFamily:'inherit'},
    series:[{name:'Visiteurs',data:[820,940,1010,880,1150,1240,1180,1320,1410,1290,1480,1560]},
            {name:'Demandes',data:[42,55,61,48,72,80,76,88,95,84,101,112]}],
    xaxis:{categories:['Sep','Oct','Nov','Dec','Jan','Fev','Mar','Avr','Mai','Juin','Juil','Aout']},
    colors:['#5955D1','#22c55e'], dataLabels:{enabled:false}, stroke:{curve:'smooth',width:2},
    fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:0.35,opacityTo:0.05}},
    legend:{position:'top',horizontalAlign:'right',labels:{colors:ink}},
    grid:{borderColor:'rgba(128,128,128,.15)'}
  }).render();
  new ApexCharts(document.querySelector('#typeChart'), {
    chart:{type:'donut',height:300,fontFamily:'inherit'},
    series:[98,72,45,30], labels:['Maisons','Appartements','Terrains','Bureaux'],
    colors:['#5955D1','#22c55e','#f59e0b','#0ea5e9'], dataLabels:{enabled:false},
    legend:{position:'bottom',labels:{colors:ink}},
    plotOptions:{pie:{donut:{size:'70%',labels:{show:true,total:{show:true,label:'Total',color:ink}}}}}
  }).render();
});
</script>"""
    return render("dashboard.html", "Tableau de bord", body, "dashboardTab", js)

# ---------------------------------------------------------------- liste biens
def bien_list():
    rows = [("Villa Cocody","Zone Chateau &mdash; 250 m&sup2;","450 M","Villa &amp; Duplex","Vente","Publie","success"),
            ("Appartement Plateau","3 pieces &mdash; 180 m&sup2;","120 M","Appartement &amp; Studio","Vente","Publie","success"),
            ("Terrain Bingerville","1 000 m&sup2; &mdash; viabilise","25 M/m&sup2;","Terrain viabilis&eacute;","Vente","Brouillon","warning"),
            ("Bureau Plateau","120 m&sup2; climatise","5 M/mois","Immeuble de rapport","Location","Publie","success"),
            ("Maison Riviera","4 pieces &mdash; 300 m&sup2;","350 M","Villa &amp; Duplex","Vente","Vendu","secondary"),
            ("Duplex Angre","5 pieces &mdash; 320 m&sup2;","280 M","Villa &amp; Duplex","Vente","Publie","success"),
            ("Studio Marcory","1 piece &mdash; 45 m&sup2;","250 K/mois","Appartement &amp; Studio","Location","Publie","success")]
    trs = "".join("""
          <tr>
            <td><input class="form-check-input" type="checkbox"></td>
            <td>
              <div class="d-flex align-items-center gap-3">
                <span class="thumb-64">{ph}</span>
                <div><span class="d-block fw-medium">{t}</span><span class="text-body-secondary fs-12">{s}</span></div>
              </div>
            </td>
            <td class="fw-semibold">{p}</td>
            <td>{ty}</td>
            <td>{of}</td>
            <td>{st}</td>
            <td class="text-end">{act}</td>
          </tr>""".format(ph=icon("image",18), t=t, s=s, p=p, ty=ty, of=of, st=badge(st, tone),
                          act=ROW_ACTIONS.format(edit="bien-edit.html"))
        for t, s, p, ty, of, st, tone in rows)

    body = page_head("Biens immobiliers", "245 biens au catalogue, 7 affiches",
        [("Accueil","dashboard.html"),("Contenu",None),("Biens",None)],
        '<button class="btn btn-light waves-effect">%s Exporter</button>'
        '<a href="bien-edit.html" class="btn btn-primary waves-effect waves-light">%s Ajouter un bien</a>'
        % (icon("upload",16), icon("plus",16)))
    body += """
    <div class="card mb-3">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-lg-3 col-md-6">
            <label class="form-label">Recherche</label>
            <input type="text" class="form-control" placeholder="Titre, reference, quartier...">
          </div>
          <div class="col-lg-2 col-md-6">
            <label class="form-label">Type</label>
            <select class="form-select"><option>Tous</option><option>Villa &amp; Duplex</option><option>Appartement &amp; Studio</option><option>Immeuble de rapport</option><option>Terrain viabilis&eacute;</option></select>
          </div>
          <div class="col-lg-2 col-md-6">
            <label class="form-label">Offre</label>
            <select class="form-select"><option>Toutes</option><option>Vente</option><option>Location</option></select>
          </div>
          <div class="col-lg-2 col-md-6">
            <label class="form-label">Zone</label>
            <select class="form-select"><option>Toutes</option><option>Cocody &amp; Riviera</option><option>Bingerville</option><option>Marcory</option><option>Le Plateau</option><option>Abatta</option></select>
          </div>
          <div class="col-lg-2 col-md-6">
            <label class="form-label">Pi&egrave;ces</label>
            <select class="form-select"><option>Toutes</option><option>1 &agrave; 2 pi&egrave;ces</option><option>3 &agrave; 4 pi&egrave;ces</option><option>5 pi&egrave;ces et plus</option></select>
          </div>
          <div class="col-lg-2 col-md-6">
            <label class="form-label">Surface</label>
            <select class="form-select"><option>Toutes</option><option>Moins de 100 m&sup2;</option><option>100 &agrave; 250 m&sup2;</option><option>250 &agrave; 500 m&sup2;</option><option>Plus de 500 m&sup2;</option></select>
          </div>
          <div class="col-lg-3 col-md-12 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1 waves-effect waves-light">%s Filtrer</button>
            <button class="btn btn-light waves-effect">Reinitialiser</button>
          </div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="card-title mb-0">Catalogue</h5>
        <div class="d-flex align-items-center gap-2">
          <span class="text-body-secondary fs-13">Trier par</span>
          <select class="form-select form-select-sm w-auto"><option>Plus recent</option><option>Prix croissant</option><option>Prix decroissant</option><option>Surface</option></select>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:36px"><input class="form-check-input" type="checkbox"></th>
              <th>Bien</th><th>Prix</th><th>Type</th><th>Offre</th><th>Statut</th><th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>%s</tbody>
        </table>
      </div>
      <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="text-body-secondary fs-13">Affichage de 1 a 7 sur 245 biens</span>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item disabled"><a class="page-link" href="javascript:void(0);">Precedent</a></li>
          <li class="page-item active"><a class="page-link" href="javascript:void(0);">1</a></li>
          <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
          <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
          <li class="page-item"><a class="page-link" href="javascript:void(0);">Suivant</a></li>
        </ul></nav>
      </div>
    </div>""" % (icon("filter",16), trs)
    return render("bien-list.html", "Biens immobiliers", body, "contentTab")

# --------------------------------------------------------------- edition bien
def bien_edit():
    equip = ["Piscine","Garage","Jardin","Climatisation","Securite 24/7","Groupe electrogene","Ascenseur","Meuble"]
    eq = "".join("""
              <div class="col-sm-6 col-lg-4">
                <div class="form-check"><input class="form-check-input" type="checkbox" id="eq{i}" {c}>
                <label class="form-check-label" for="eq{i}">{n}</label></div>
              </div>""".format(i=i, n=n, c="checked" if i < 3 else "") for i, n in enumerate(equip))

    body = page_head("Modifier un bien", "Reference SCI4K-0123 &mdash; Villa Cocody",
        [("Accueil","dashboard.html"),("Biens","bien-list.html"),("Modifier",None)],
        '<a href="bien-list.html" class="btn btn-light waves-effect">%s Retour</a>'
        '<button class="btn btn-light waves-effect">%s Apercu</button>'
        '<button class="btn btn-primary waves-effect waves-light">%s Enregistrer</button>'
        % (icon("arrow-left",16), icon("eye",16), icon("check",16)))
    body += """
    <div class="row g-3">
      <div class="col-xl-8">
        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Informations generales</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Titre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" value="Villa Cocody &mdash; Zone Chateau">
              </div>
              <div class="col-md-4">
                <label class="form-label">Reference</label>
                <input type="text" class="form-control" value="SCI4K-0123">
              </div>
              <div class="col-12">
                <label class="form-label">Identifiant d'URL</label>
                <div class="input-group">
                  <span class="input-group-text">/biens/</span>
                  <input type="text" class="form-control" value="villa-cocody-chateau">
                </div>
              </div>
              <div class="col-12">
                <label class="form-label">Accroche <span class="text-danger">*</span></label>
                <textarea class="form-control" rows="2">Villa de standing en zone Chateau, 250 m&sup2; sur terrain clos, vue degagee.</textarea>
                <div class="form-text">160 caracteres maximum &mdash; utilisee dans les listes et les resultats de recherche.</div>
              </div>
              <div class="col-12">
                <label class="form-label">Description complete</label>
                <textarea class="form-control" rows="7">Cette villa de standing est situee en zone Chateau, a Cocody. Construite en 2015 et entretenue avec soin, elle developpe 250 m&sup2; habitables sur une parcelle close de 600 m&sup2;.

Au rez-de-chaussee : double sejour, cuisine equipee, chambre d'amis avec salle d'eau. A l'etage : trois chambres dont une suite parentale avec dressing.</textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Caracteristiques</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label">Type <span class="text-danger">*</span></label>
                <select class="form-select"><option selected>Villa &amp; Duplex</option><option>Appartement &amp; Studio</option><option>Immeuble de rapport</option><option>Terrain viabilis&eacute;</option></select></div>
              <div class="col-md-4"><label class="form-label">Offre <span class="text-danger">*</span></label>
                <select class="form-select"><option selected>Vente</option><option>Location</option></select></div>
              <div class="col-md-4"><label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" value="450 000 000"></div>
              <div class="col-md-3"><label class="form-label">Surface habitable</label>
                <div class="input-group"><input type="text" class="form-control" value="250"><span class="input-group-text">m&sup2;</span></div></div>
              <div class="col-md-3"><label class="form-label">Surface terrain</label>
                <div class="input-group"><input type="text" class="form-control" value="600"><span class="input-group-text">m&sup2;</span></div></div>
              <div class="col-md-2"><label class="form-label">Pieces</label><input type="text" class="form-control" value="6"></div>
              <div class="col-md-2"><label class="form-label">Chambres</label><input type="text" class="form-control" value="4"></div>
              <div class="col-md-2"><label class="form-label">Salles d'eau</label><input type="text" class="form-control" value="3"></div>
              <div class="col-md-6"><label class="form-label">Commune</label>
                <select class="form-select"><option selected>Cocody &amp; Riviera</option><option>Bingerville</option><option>Marcory</option><option>Le Plateau</option><option>Abatta</option></select></div>
              <div class="col-md-6"><label class="form-label">Quartier</label><input type="text" class="form-control" value="Chateau"></div>
              <div class="col-md-6"><label class="form-label">Statut juridique <span class="text-danger">*</span></label>
                <select class="form-select">
                  <option selected>ACD disponible</option><option>Titre foncier</option>
                  <option>Lettre d'attribution</option><option>Arr&ecirc;t&eacute; de concession</option>
                  <option>En cours de r&eacute;gularisation</option>
                </select>
                <div class="form-text">Affich&eacute; comme 4e caract&eacute;ristique sur les cartes de la page /biens.</div></div>
              <div class="col-md-6"><label class="form-label">Num&eacute;ro de titre</label>
                <input type="text" class="form-control" value="ACD-2015-CO-04872"></div>
              <div class="col-12"><label class="form-label mb-2">Equipements</label><div class="row g-2">%s</div></div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Referencement</h5></div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12"><label class="form-label">Titre meta</label>
                <input type="text" class="form-control" value="Villa Cocody Chateau 250 m2 a vendre | SCI4K">
                <div class="form-text">48 / 60 caracteres</div></div>
              <div class="col-12"><label class="form-label">Description meta</label>
                <textarea class="form-control" rows="3">Villa de standing de 250 m2 en zone Chateau, Cocody. 4 chambres, piscine, garage. Visite sur rendez-vous avec SCI4K.</textarea>
                <div class="form-text">118 / 160 caracteres</div></div>
              <div class="col-12"><label class="form-label">Mots-cles</label>
                <input type="text" class="form-control" value="villa cocody, immobilier abidjan, maison a vendre"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Publication</h5></div>
          <div class="card-body">
            <label class="form-label">Statut</label>
            <select class="form-select mb-3"><option selected>Publie</option><option>Brouillon</option><option>Vendu</option><option>Archive</option></select>
            <label class="form-label">Date de mise en ligne</label>
            <input type="text" class="form-control mb-3 flatpickr-date" value="15/08/2026" readonly>
            <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" id="feat" checked>
              <label class="form-check-label" for="feat">Mettre en avant sur l'accueil</label></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="urg">
              <label class="form-check-label" for="urg">Signaler comme urgent</label></div>
            <hr>
            <div class="fs-12 text-body-secondary">
              <div class="d-flex justify-content-between"><span>Cree le</span><span>15/08/2026 &agrave; 10:30</span></div>
              <div class="d-flex justify-content-between"><span>Modifie le</span><span>20/08/2026 &agrave; 14:45</span></div>
              <div class="d-flex justify-content-between"><span>Auteur</span><span>Ilyas K.</span></div>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Photo principale</h5></div>
          <div class="card-body">
            <div class="media-ph rounded-3 mb-3" style="aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;background:var(--bs-secondary-bg);color:var(--bs-secondary-color)">%s</div>
            <button class="btn btn-light w-100 waves-effect">%s Remplacer</button>
          </div>
        </div>

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Galerie</h5><span class="badge bg-secondary-subtle text-body">6 / 10</span>
          </div>
          <div class="card-body">
            <div class="row g-2 mb-3">%s</div>
            <div class="dropzone">%s<div class="mt-2 fs-13">Deposer des images ici</div>
              <div class="fs-12">JPG ou PNG, 2 Mo maximum</div></div>
          </div>
        </div>
      </div>
    </div>""" % (eq, icon("image",40), icon("upload",16),
        "".join('<div class="col-4"><div class="media-ph rounded-2">%s</div></div>' % icon("image",18) for _ in range(6)),
        icon("upload",28))
    js = '<script src="assets/libs/flatpickr/flatpickr.min.js"></script>'
    return render("bien-edit.html", "Modifier un bien", body, "contentTab", js)

# ------------------------------------------------------------- liste articles
def article_list():
    rows = [("Marche immobilier ivoirien : bilan du 3e trimestre","Marche","Ilyas K.","18/08/2026","342","Publie","success"),
            ("Cinq conseils avant d'investir dans un terrain","Conseils","Emma D.","15/08/2026","218","Publie","success"),
            ("Zoom sur Cocody : quartiers et prix au m2","Actualites","Ilyas K.","10/08/2026","156","Brouillon","warning"),
            ("Location meublee : ce que dit la loi","Juridique","Marc T.","02/08/2026","489","Publie","success"),
            ("Construire ou acheter : comment choisir","Conseils","Emma D.","28/07/2026","301","Publie","success"),
            ("Nos nouveaux programmes a Bingerville","Actualites","Ilyas K.","20/07/2026","97","Archive","secondary")]
    trs = "".join("""
          <tr>
            <td><input class="form-check-input" type="checkbox"></td>
            <td>
              <div class="d-flex align-items-center gap-3">
                <span class="thumb-64">{ph}</span>
                <div><span class="d-block fw-medium">{t}</span><span class="text-body-secondary fs-12">/actualites/{sl}</span></div>
              </div>
            </td>
            <td>{c}</td><td>{a}</td><td>{d}</td>
            <td><span class="d-inline-flex align-items-center gap-1">{ey} {v}</span></td>
            <td>{st}</td>
            <td class="text-end">{act}</td>
          </tr>""".format(ph=icon("file",18), t=t, sl=t.lower().replace(" ","-")[:28], c=c, a=a, d=d, v=v,
                          ey=icon("eye",14), st=badge(st, tone), act=ROW_ACTIONS.format(edit="article-edit.html"))
        for t, c, a, d, v, st, tone in rows)

    body = page_head("Articles &amp; actualités", "52 articles publies, 6 affiches",
        [("Accueil","dashboard.html"),("Contenu",None),("Articles",None)],
        '<a href="article-edit.html" class="btn btn-primary waves-effect waves-light">%s Nouvel article</a>' % icon("plus",16))
    body += """
    <div class="row g-3 mb-3">
      %s%s%s%s
    </div>
    <div class="card mb-3">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-lg-4 col-md-6"><label class="form-label">Recherche</label>
            <input type="text" class="form-control" placeholder="Titre de l'article..."></div>
          <div class="col-lg-2 col-md-6"><label class="form-label">Categorie</label>
            <select class="form-select"><option>Toutes</option><option>Marche</option><option>Conseils</option><option>Actualites</option><option>Juridique</option></select></div>
          <div class="col-lg-2 col-md-6"><label class="form-label">Auteur</label>
            <select class="form-select"><option>Tous</option><option>Ilyas K.</option><option>Emma D.</option><option>Marc T.</option></select></div>
          <div class="col-lg-2 col-md-6"><label class="form-label">Statut</label>
            <select class="form-select"><option>Tous</option><option>Publie</option><option>Brouillon</option><option>Archive</option></select></div>
          <div class="col-lg-2"><button class="btn btn-primary w-100 waves-effect waves-light">%s Filtrer</button></div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr><th style="width:36px"><input class="form-check-input" type="checkbox"></th>
            <th>Article</th><th>Categorie</th><th>Auteur</th><th>Date</th><th>Vues</th><th>Statut</th><th class="text-end">Actions</th></tr>
          </thead>
          <tbody>%s</tbody>
        </table>
      </div>
      <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="text-body-secondary fs-13">Affichage de 1 a 6 sur 52 articles</span>
        <nav><ul class="pagination pagination-sm mb-0">
          <li class="page-item disabled"><a class="page-link" href="javascript:void(0);">Precedent</a></li>
          <li class="page-item active"><a class="page-link" href="javascript:void(0);">1</a></li>
          <li class="page-item"><a class="page-link" href="javascript:void(0);">2</a></li>
          <li class="page-item"><a class="page-link" href="javascript:void(0);">Suivant</a></li>
        </ul></nav>
      </div>
    </div>""" % (tile("file","primary","Articles publies","52","8 %"),
                 tile("edit","warning","Brouillons","7","2"),
                 tile("eye","success","Vues ce mois","18 420","14 %"),
                 tile("help","info","Commentaires","96","6 %"), icon("filter",16), trs)
    return render("article-list.html", "Articles", body, "contentTab")

# ----------------------------------------------------------- edition article
def article_edit():
    tools = ["B","I","U","H2","H3",'&#8220;&#8221;',"&#8226;","1.","&#128279;","&#128247;"]
    tb = "".join('<button class="btn btn-sm btn-light waves-effect" type="button">%s</button>' % t for t in tools)
    body = page_head("Nouvel article", "Redaction et publication d'une actualité",
        [("Accueil","dashboard.html"),("Articles","article-list.html"),("Nouvel article",None)],
        '<a href="article-list.html" class="btn btn-light waves-effect">%s Retour</a>'
        '<button class="btn btn-light waves-effect">Enregistrer le brouillon</button>'
        '<button class="btn btn-primary waves-effect waves-light">%s Publier</button>'
        % (icon("arrow-left",16), icon("check",16)))
    body += """
    <div class="row g-3">
      <div class="col-xl-8">
        <div class="card mb-3">
          <div class="card-body">
            <input type="text" class="form-control form-control-lg border-0 px-0 fw-semibold mb-2" placeholder="Titre de l'article">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-transparent border-0 ps-0 text-body-secondary">/actualites/</span>
              <input type="text" class="form-control border-0 ps-0 text-body-secondary" placeholder="identifiant-url-de-l-article">
            </div>
          </div>
        </div>
        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Image de couverture</h5></div>
          <div class="card-body"><div class="dropzone">%s
            <div class="mt-2 fs-13">Deposer une image ou cliquer pour parcourir</div>
            <div class="fs-12">Format recommande 1200 x 630 px</div></div></div>
        </div>
        <div class="card">
          <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="card-title mb-0">Contenu</h5>
            <div class="btn-group btn-group-sm">%s</div>
          </div>
          <div class="card-body">
            <textarea class="form-control border-0" rows="16" placeholder="Redigez votre article..."></textarea>
          </div>
          <div class="card-footer text-body-secondary fs-12">0 mot &mdash; temps de lecture estime : 0 min</div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Publication</h5></div>
          <div class="card-body">
            <label class="form-label">Statut</label>
            <select class="form-select mb-3"><option>Brouillon</option><option selected>Publie</option><option>Planifie</option></select>
            <label class="form-label">Date de publication</label>
            <input type="text" class="form-control mb-3 flatpickr-date" value="21/08/2026" readonly>
            <label class="form-label">Auteur</label>
            <select class="form-select"><option selected>Ilyas K.</option><option>Emma D.</option><option>Marc T.</option></select>
          </div>
        </div>
        <div class="card mb-3">
          <div class="card-header"><h5 class="card-title mb-0">Classement</h5></div>
          <div class="card-body">
            <label class="form-label">Categorie</label>
            <select class="form-select mb-3"><option selected>Marche</option><option>Conseils</option><option>Actualites</option><option>Juridique</option></select>
            <label class="form-label">Etiquettes</label>
            <div class="d-flex flex-wrap gap-1 mb-2">
              <span class="badge bg-primary-subtle text-primary">immobilier &times;</span>
              <span class="badge bg-primary-subtle text-primary">abidjan &times;</span>
              <span class="badge bg-primary-subtle text-primary">investissement &times;</span>
            </div>
            <input type="text" class="form-control" placeholder="Ajouter une etiquette...">
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">Referencement</h5></div>
          <div class="card-body">
            <label class="form-label">Titre meta</label>
            <input type="text" class="form-control mb-1" placeholder="Titre affiche dans Google">
            <div class="form-text mb-3">0 / 60 caracteres</div>
            <label class="form-label">Description meta</label>
            <textarea class="form-control mb-1" rows="3" placeholder="Resume affiche dans les resultats de recherche"></textarea>
            <div class="form-text">0 / 160 caracteres</div>
          </div>
        </div>
      </div>
    </div>""" % (icon("image",28), tb)
    js = '<script src="assets/libs/flatpickr/flatpickr.min.js"></script>'
    return render("article-edit.html", "Nouvel article", body, "contentTab", js)

# --------------------------------------------------------------- statistiques
def analytics():
    pages = [("Accueil","/",3456,"42 %"),("Biens immobiliers","/biens",2890,"38 %"),
             ("Actualités","/actualites",1234,"51 %"),("Services","/services",980,"44 %"),
             ("Contact","/contact",890,"29 %"),("Presentation","/presentation",640,"47 %")]
    prs = "".join("""
          <tr><td><span class="d-block fw-medium">{n}</span><span class="text-body-secondary fs-12">{u}</span></td>
          <td class="fw-semibold">{v}</td><td>{b}</td>
          <td style="min-width:140px"><div class="progress" style="height:6px"><div class="progress-bar" style="width:{p}%"></div></div></td></tr>
          """.format(n=n, u=u, v="{:,}".format(v).replace(",", " "), b=b, p=int(v / 3456 * 100)) for n, u, v, b in pages)
    devices = [("Mobile", 58, "primary"), ("Ordinateur", 38, "success"), ("Tablette", 4, "warning")]
    ds = "".join("""
        <div class="mb-3">
          <div class="d-flex justify-content-between fs-13 mb-1"><span>{n}</span><span class="fw-semibold">{p} %</span></div>
          <div class="progress" style="height:8px"><div class="progress-bar bg-{t}" style="width:{p}%"></div></div>
        </div>""".format(n=n, p=p, t=t) for n, p, t in devices)

    body = page_head("Statistiques", "Frequentation du site sur les 30 derniers jours",
        [("Accueil","dashboard.html"),("Pilotage",None),("Statistiques",None)],
        '<select class="form-select w-auto"><option>30 derniers jours</option><option>7 derniers jours</option>'
        '<option>3 mois</option><option>12 mois</option></select>'
        '<button class="btn btn-light waves-effect">%s Exporter</button>' % icon("upload",16))
    body += """
    <div class="row g-3 mb-3">%s%s%s%s</div>
    <div class="row g-3 mb-3">
      <div class="col-xxl-8"><div class="card h-100">
        <div class="card-header"><h5 class="card-title mb-0">Visiteurs et pages vues</h5></div>
        <div class="card-body"><div id="visitorsChart"></div></div></div></div>
      <div class="col-xxl-4"><div class="card h-100">
        <div class="card-header"><h5 class="card-title mb-0">Sources de trafic</h5></div>
        <div class="card-body"><div id="sourceChart"></div></div></div></div>
    </div>
    <div class="row g-3">
      <div class="col-lg-8"><div class="card h-100">
        <div class="card-header"><h5 class="card-title mb-0">Pages les plus consultees</h5></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
          <thead class="table-light"><tr><th>Page</th><th>Vues</th><th>Taux de rebond</th><th>Part</th></tr></thead>
          <tbody>%s</tbody></table></div></div></div>
      <div class="col-lg-4"><div class="card h-100">
        <div class="card-header"><h5 class="card-title mb-0">Appareils</h5></div>
        <div class="card-body">%s
          <hr>
          <div class="d-flex justify-content-between fs-13"><span class="text-body-secondary">Duree moyenne</span><span class="fw-semibold">3 min 42 s</span></div>
          <div class="d-flex justify-content-between fs-13 mt-2"><span class="text-body-secondary">Pages par session</span><span class="fw-semibold">3,7</span></div>
          <div class="d-flex justify-content-between fs-13 mt-2"><span class="text-body-secondary">Nouveaux visiteurs</span><span class="fw-semibold">64 %%</span></div>
        </div></div></div>
    </div>""" % (tile("users","primary","Visiteurs uniques","12 456","18 %"),
                 tile("eye","success","Pages vues","45 892","22 %"),
                 tile("calendar","info","Duree moyenne","3:42","5 %"),
                 tile("chart","warning","Taux de rebond","42 %","3 %", up=False), prs, ds)
    js = """<script src="assets/libs/apexcharts/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!window.ApexCharts) return;
  var ink = getComputedStyle(document.body).getPropertyValue('--bs-body-color');
  new ApexCharts(document.querySelector('#visitorsChart'), {
    chart:{type:'bar',height:320,toolbar:{show:false},fontFamily:'inherit'},
    series:[{name:'Visiteurs',data:[380,420,510,470,560,610,540]},
            {name:'Pages vues',data:[1420,1560,1880,1710,2050,2240,1990]}],
    xaxis:{categories:['Lun','Mar','Mer','Jeu','Ven','Sam','Dim']},
    colors:['#5955D1','#0ea5e9'], dataLabels:{enabled:false},
    plotOptions:{bar:{borderRadius:5,columnWidth:'45%'}},
    legend:{position:'top',horizontalAlign:'right',labels:{colors:ink}},
    grid:{borderColor:'rgba(128,128,128,.15)'}
  }).render();
  new ApexCharts(document.querySelector('#sourceChart'), {
    chart:{type:'donut',height:320,fontFamily:'inherit'},
    series:[45,28,18,9], labels:['Recherche','Direct','Reseaux sociaux','Autres'],
    colors:['#5955D1','#22c55e','#f59e0b','#94a3b8'], dataLabels:{enabled:false},
    legend:{position:'bottom',labels:{colors:ink}},
    plotOptions:{pie:{donut:{size:'70%'}}}
  }).render();
});
</script>"""
    return render("analytics.html", "Statistiques", body, "dashboardTab", js)
