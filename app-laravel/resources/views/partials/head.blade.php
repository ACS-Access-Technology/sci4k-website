<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{-- Le titre passe par __() : les composants Volt le posent via l'attribut
         PHP #[Title('…')], qui n'accepte qu'une expression constante et ne peut
         donc pas appeler __() lui-meme. Le traduire ici laisse l'onglet suivre
         la langue. L'appel est sans effet sur un titre deja traduit, la cle
         etant alors absente du dictionnaire et rendue telle quelle. --}}
    {{ filled($title ?? null) ? __($title).' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="{{ asset('images/image (3).png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('images/image (3).png') }}">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles

{{--
    Applies the stored light/dark/system appearance before first paint, so
    there is no flash of the wrong theme while Alpine boots. First-party
    replacement for Flux's `@fluxAppearance` directive (removed with the
    `livewire/flux` package) — only touches localStorage and the <html>
    class, same behaviour as resources/js/app.js's Alpine store.
--}}
<style>
    :root.dark {
        color-scheme: dark;
    }
</style>
<script>
    (function () {
        // Meme cle que le site public : la preference d'apparence est celle du
        // produit entier. « appearance » est l'ancienne cle du backoffice, lue
        // une derniere fois pour ne pas perdre un reglage deja fait.
        var garde = window.localStorage.getItem('sci4k-theme')
            || window.localStorage.getItem('appearance')
            || 'system';

        var isDark = garde === 'dark'
            || (garde === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
    })();
</script>
