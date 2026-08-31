/* SCI4K backoffice - maquettes statiques.
   Comportements purement visuels : menu lateral, theme clair/sombre, tooltips. */
(function () {
  var root = document.documentElement;

  // --- theme clair / sombre -------------------------------------------------
  var saved = null;
  try { saved = localStorage.getItem('sci4k-theme'); } catch (e) {}
  if (saved) { root.setAttribute('data-bs-theme', saved); }
  var themeBtn = document.querySelector('.theme-btn');
  if (themeBtn) {
    if (root.getAttribute('data-bs-theme') === 'dark') themeBtn.classList.add('active');
    themeBtn.addEventListener('click', function () {
      var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-bs-theme', next);
      themeBtn.classList.toggle('active', next === 'dark');
      try { localStorage.setItem('sci4k-theme', next); } catch (e) {}
    });
  }

  // --- menu lateral ---------------------------------------------------------
  var toggler = document.querySelector('.app-toggler');
  var menubar = document.getElementById('appMenubar');

  // En dessous de 1480px la feuille de style masque la colonne du menu et
  // n'attend que la classe « open » : c'est donc le seuil a utiliser, pas 1199px.
  function estHorsEcran() {
    return window.matchMedia('(max-width: 1480px)').matches;
  }
  function fermer() {
    if (!menubar) return;
    menubar.classList.remove('open');
    if (toggler) toggler.classList.remove('active');
  }

  if (toggler && menubar) {
    toggler.addEventListener('click', function (e) {
      e.preventDefault();
      if (estHorsEcran()) {
        var ouvert = menubar.classList.toggle('open');
        toggler.classList.toggle('active', ouvert);
      } else {
        var mini = root.getAttribute('data-app-sidebar') === 'mini';
        root.setAttribute('data-app-sidebar', mini ? 'default' : 'mini');
        toggler.classList.toggle('active', !mini);
      }
    });

    // refermer apres avoir choisi une entree, sur petit ecran
    menubar.addEventListener('click', function (e) {
      if (estHorsEcran() && e.target.closest('.side-menubar a[href]')) fermer();
    });

    // touche Echap
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') fermer();
    });

    // au retour sur grand ecran, on repart d'un etat propre
    var minuteur = null;
    window.addEventListener('resize', function () {
      clearTimeout(minuteur);
      minuteur = setTimeout(function () {
        if (!estHorsEcran()) fermer();
      }, 150);
    });
  }


  // --- rail d'icones : defilement vers le groupe correspondant ---------------
  var rail = document.querySelectorAll('#appMenubarTabs .menu-link[href^="#grp-"]');
  if (rail.length) {
    rail.forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var target = document.getElementById(a.getAttribute('href').slice(1));
        if (!target) return;
        // si la barre est rangee, on la deroule avant de defiler
        if (root.getAttribute('data-app-sidebar') === 'mini') {
          root.setAttribute('data-app-sidebar', 'default');
          var tg = document.querySelector('.app-toggler');
          if (tg) tg.classList.remove('active');
        }
        rail.forEach(function (x) { x.classList.remove('active'); });
        a.classList.add('active');
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  // --- tooltips Bootstrap ---------------------------------------------------
  if (window.bootstrap && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      new bootstrap.Tooltip(el);
    });
  }
})();
