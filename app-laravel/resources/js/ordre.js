/*
 * Reordonnancement des tableaux d'administration par glisser-deposer.
 *
 * Ecrit a la main plutot qu'avec une bibliotheque : l'API HTML5 de
 * glisser-deposer suffit pour des lignes de tableau, et une dependance de plus
 * pour quarante lignes ne se justifie pas.
 *
 * Le tableau declare `data-ordonnable` et chaque ligne son `data-id`. A la
 * depose, l'ordre des identifiants part vers la methode `reordonner` du
 * composant Livewire.
 */
function activerOrdre(tbody) {
    if (tbody.dataset.ordreActive === '1') return;
    tbody.dataset.ordreActive = '1';

    let ligneTiree = null;

    tbody.addEventListener('pointerdown', (e) => {
        const poignee = e.target.closest('.poignee');
        if (!poignee) return;
        const ligne = poignee.closest('tr');
        if (ligne) ligne.draggable = true;
    });

    tbody.addEventListener('dragstart', (e) => {
        ligneTiree = e.target.closest('tr');
        if (ligneTiree) ligneTiree.classList.add('opacity-50');
    });

    tbody.addEventListener('dragover', (e) => {
        e.preventDefault();
        const cible = e.target.closest('tr');
        if (!cible || !ligneTiree || cible === ligneTiree) return;

        const cadre = cible.getBoundingClientRect();
        const apres = e.clientY > cadre.top + cadre.height / 2;
        cible.parentNode.insertBefore(ligneTiree, apres ? cible.nextSibling : cible);
    });

    tbody.addEventListener('dragend', () => {
        if (!ligneTiree) return;
        ligneTiree.classList.remove('opacity-50');
        ligneTiree.draggable = false;
        ligneTiree = null;

        const ids = [...tbody.querySelectorAll('tr[data-id]')].map((tr) => Number(tr.dataset.id));
        const composant = tbody.closest('[wire\\:id]');
        if (composant && window.Livewire) {
            window.Livewire.find(composant.getAttribute('wire:id')).call('reordonner', ids);
        }
    });
}

function activerTout() {
    document.querySelectorAll('tbody[data-ordonnable]').forEach(activerOrdre);
}

document.addEventListener('DOMContentLoaded', activerTout);
// Livewire remplace le tableau apres chaque mise a jour : il faut reactiver.
document.addEventListener('livewire:navigated', activerTout);
document.addEventListener('livewire:update', activerTout);

export { activerTout };
