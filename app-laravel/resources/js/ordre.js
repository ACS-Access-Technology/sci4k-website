/*
 * Reordonnancement des tableaux d'administration par glisser-deposer.
 *
 * Ecrit a la main plutot qu'avec une bibliotheque : l'API HTML5 de
 * glisser-deposer suffit pour des lignes de tableau, et une dependance de plus
 * pour quarante lignes ne se justifie pas.
 *
 * Le tableau declare `data-ordonnable` et chaque ligne son `data-id`. A la
 * depose, l'ordre des identifiants part vers la methode `reordonner` du
 * composant Livewire qui contient le tableau.
 *
 * TOUT EST DELEGUE AU DOCUMENT, et rien n'est attache aux tableaux eux-memes.
 *
 * La version precedente accrochait ses ecouteurs sur chaque tbody, puis
 * comptait sur trois evenements pour recommencer apres un rendu :
 * DOMContentLoaded, livewire:navigated et « livewire:update ». Ce dernier
 * N'EXISTE PAS — Livewire n'emet rien de ce nom. Un tableau apparu apres une
 * mise a jour du composant n'etait donc jamais active, et le glisser-deposer
 * restait inerte : c'est ce qui se passait pour les services, les avis et les
 * partenaires ouverts depuis un ecran de page, ou la liste n'apparait qu'apres
 * un clic sur le module.
 *
 * La delegation supprime la question : il n'y a plus rien a reactiver, et un
 * tableau rendu dans une heure fonctionne comme celui rendu au chargement.
 */
let ligneTiree = null;

/** Le tbody ordonnable qui contient cet element, s'il y en a un. */
function corpsOrdonnable(element) {
    return element?.closest?.('tbody[data-ordonnable]') ?? null;
}

// La poignee arme le glissement. Sans cela, la ligne entiere serait
// deplacable, y compris depuis un lien ou un bouton d'action.
document.addEventListener('pointerdown', (e) => {
    const poignee = e.target.closest?.('.poignee');
    if (!poignee || !corpsOrdonnable(poignee)) return;

    const ligne = poignee.closest('tr');
    if (ligne) ligne.draggable = true;
});

document.addEventListener('dragstart', (e) => {
    const ligne = e.target.closest?.('tr');
    if (!ligne || !corpsOrdonnable(ligne)) return;

    ligneTiree = ligne;
    ligne.classList.add('opacity-50');
});

document.addEventListener('dragover', (e) => {
    if (!ligneTiree) return;

    const cible = e.target.closest?.('tr');
    if (!cible || cible === ligneTiree) return;

    // Une ligne ne se depose que dans SON tableau : deux listes ouvertes cote
    // a cote melangeraient sinon leurs identifiants.
    if (corpsOrdonnable(cible) !== corpsOrdonnable(ligneTiree)) return;

    e.preventDefault();

    const cadre = cible.getBoundingClientRect();
    const apres = e.clientY > cadre.top + cadre.height / 2;
    cible.parentNode.insertBefore(ligneTiree, apres ? cible.nextSibling : cible);
});

document.addEventListener('dragend', () => {
    if (!ligneTiree) return;

    const tbody = corpsOrdonnable(ligneTiree);
    ligneTiree.classList.remove('opacity-50');
    ligneTiree.draggable = false;
    ligneTiree = null;

    if (!tbody) return;

    const ids = [...tbody.querySelectorAll('tr[data-id]')].map((tr) => Number(tr.dataset.id));

    // Le composant le PLUS PROCHE, et non le premier trouve : embarquee dans
    // un ecran de page, la liste est un composant imbrique dans un autre, et
    // c'est elle qui porte reordonner().
    const composant = tbody.closest('[wire\\:id]');

    if (composant && window.Livewire) {
        window.Livewire.find(composant.getAttribute('wire:id'))?.call('reordonner', ids);
    }
});
