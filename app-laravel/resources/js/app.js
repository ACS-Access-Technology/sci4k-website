import './ordre';
import './recadrage-image';

// Preference d'apparence — clair / sombre / systeme.
//
// La cle est celle du SITE PUBLIC, 'sci4k-theme', et non plus 'appearance'.
// Les deux surfaces avaient chacune la sienne : choisir le sombre dans
// l'administration ne disait rien au site, et l'inverse non plus. C'est la
// preference qui est commune, pas son rendu — le site public pose un attribut
// data-theme lu par sa feuille de style, ici c'est la classe « dark » que
// Tailwind attend.
//
// Le script de partials/head.blade.php pose la classe avant le premier rendu,
// pour eviter le clignotement ; ce fichier ne fait que la maintenir ensuite.
const CLE = 'sci4k-theme';
const ANCIENNE_CLE = 'appearance';

function preferenceGardee() {
    // Reprise silencieuse de l'ancienne cle : un editeur qui avait deja choisi
    // le sombre ne doit pas voir son reglage disparaitre a la mise a jour.
    const valeur = localStorage.getItem(CLE) ?? localStorage.getItem(ANCIENNE_CLE) ?? 'system';

    return ['light', 'dark', 'system'].includes(valeur) ? valeur : 'system';
}

document.addEventListener('alpine:init', () => {
    window.Alpine.store('appearance', {
        value: preferenceGardee(),

        set(value) {
            this.value = value;
            localStorage.setItem(CLE, value);
            // L'ancienne cle est retiree une fois reprise, pour qu'il ne reste
            // qu'une seule source de verite.
            localStorage.removeItem(ANCIENNE_CLE);
            this.apply();
        },

        apply() {
            const isDark = this.value === 'dark'
                || (this.value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            document.documentElement.classList.toggle('dark', isDark);
            // L'attribut sert au site public ; le poser ici garde les deux
            // surfaces d'accord meme si l'on passe de l'une a l'autre sans
            // rechargement complet.
            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
        },
    });
});

// Livewire remplace le corps de la page a chaque navigation. La classe vit sur
// <html> et devrait survivre, mais s'y fier laisse le theme dependre d'un
// detail d'implementation : on la repose apres chaque navigation.
document.addEventListener('livewire:navigated', () => {
    window.Alpine?.store('appearance')?.apply();
});

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const store = window.Alpine?.store('appearance');

    if (store && store.value === 'system') {
        store.apply();
    }
});
