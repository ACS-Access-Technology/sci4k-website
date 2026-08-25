@props(['intitule', 'pour' => null])

{{--
    Un champ de filtre : intitule au-dessus, champ en dessous.

    L'intitule est un vrai <label> lie au champ, et non un simple texte : sans
    lui, un lecteur d'ecran annonce « zone de saisie » sans dire laquelle. La
    revue d'accessibilite du frontoffice avait releve exactement ce manque sur
    le formulaire de contact.
--}}
<div class="flex flex-col gap-1.5">
    <label @if ($pour) for="{{ $pour }}" @endif class="text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">
        {{ $intitule }}
    </label>
    {{ $slot }}
</div>
