<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

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
        var appearance = window.localStorage.getItem('appearance') || 'system';
        var isDark = appearance === 'dark'
            || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', isDark);
    })();
</script>
