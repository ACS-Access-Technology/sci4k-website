@props(['route', 'motif' => null, 'intitule', 'icone' => 'document'])

{{--
  Un lien de la barre laterale.

  Extrait parce que le lot 2b y ajoute neuf ecrans : repeter douze lignes deux
  fois par ecran — la barre existe en version bureau et en version mobile —
  aurait fait deux cents lignes ou seul le nom de la route change.

  `motif` sert a marquer le lien actif : « admin.temoignages.* » couvre la
  liste et ses deux formulaires. A defaut, le nom exact de la route.
--}}
@php($actif = request()->routeIs($motif ?? $route))

<a
    href="{{ route($route) }}"
    wire:navigate
    @class([
        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
        'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white' => $actif,
        'text-zinc-600 hover:bg-zinc-200/50 dark:text-zinc-300 dark:hover:bg-white/5' => ! $actif,
    ])
    @if ($actif) aria-current="page" @endif
>
    <x-admin.icone :nom="$icone" />
    {{ $intitule }}
</a>
