// Light / dark / system appearance switcher.
//
// This replaces Flux's built-in `$flux.appearance` store (removed along
// with the `livewire/flux` package). It is first-party code: it only
// reads/writes `localStorage` and toggles the `dark` class on <html>,
// mirroring the behaviour already primed by the inline script in
// resources/views/partials/head.blade.php (which avoids a flash of the
// wrong theme before Alpine boots).
document.addEventListener('alpine:init', () => {
    window.Alpine.store('appearance', {
        value: localStorage.getItem('appearance') ?? 'system',

        set(value) {
            this.value = value;
            localStorage.setItem('appearance', value);
            this.apply();
        },

        apply() {
            const isDark = this.value === 'dark'
                || (this.value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            document.documentElement.classList.toggle('dark', isDark);
        },
    });
});

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    const store = window.Alpine?.store('appearance');

    if (store && store.value === 'system') {
        store.apply();
    }
});
