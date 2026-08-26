{{--
  Bandeau de chiffres au-dessus d'une liste, comme sur la maquette.

  Attend $statistiques : une liste de ['intitule' => …, 'valeur' => …,
  'detail' => … (facultatif), 'ton' => 'neutre'|'alerte' (facultatif)].

  Le ton « alerte » sert aux chiffres qui appellent une action — des elements
  masques du site, par exemple. Un chiffre qui ne demande rien reste neutre :
  tout colorer revient a ne rien signaler.
--}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-{{ min(count($statistiques), 4) }}">
    @foreach ($statistiques as $statistique)
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ $statistique['intitule'] }}
            </p>
            <p @class([
                'mt-1 text-2xl font-semibold',
                'text-amber-600 dark:text-amber-400' => ($statistique['ton'] ?? 'neutre') === 'alerte',
                'text-zinc-900 dark:text-white' => ($statistique['ton'] ?? 'neutre') !== 'alerte',
            ])>{{ $statistique['valeur'] }}</p>

            @isset($statistique['detail'])
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $statistique['detail'] }}</p>
            @endisset
        </div>
    @endforeach
</div>
