@props(['colonnes' => [], 'vide' => null])

{{--
    Tableau d'administration, commun a tous les ecrans de liste.

    Le tableau deborde sur mobile : il defile dans son propre conteneur, pour
    que la page elle-meme ne defile jamais horizontalement — un defaut releve
    sur le frontoffice et qu'on ne refait pas ici.
--}}
<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 bg-zinc-50 text-left dark:border-zinc-700 dark:bg-zinc-800/60">
                    @foreach ($colonnes as $colonne)
                        {{-- zinc-600 et non zinc-500 : en petites capitales,
                             zinc-500 plafonne a 4,4 de contraste, sous le seuil
                             de 4,5 exige pour du texte de cette taille. --}}
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-400">
                            {{ $colonne }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if (isset($pied))
        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">{{ $pied }}</div>
    @endif
</div>
