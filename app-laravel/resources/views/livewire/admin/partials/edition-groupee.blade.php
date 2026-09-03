{{--
  Corps commun aux trois ecrans d'edition groupee.

  Tous les elements cote a cote, un seul bouton d'enregistrement, et — quand
  l'ecran le permet — l'ajout et le retrait. Le panneau « Reglages du bloc »
  n'apparait que pour les ensembles qui reglent l'en-tete d'une section.

  Attend : $titre, $fil, $sousTitre, $champs, $intituleRang, et
           $champsSimples / $options / $apercu, facultatifs.
--}}
@php($champ = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')
@php($champsSimples = $champsSimples ?? [])
@php($sousTitre = $sousTitre ?? null)

{{-- Ce bloc porte-t-il du contenu bilingue ? Les champs a suffixe _fr/_en,
     et l'en-tete de section quand elle est proposee. La banderole des communes
     n'a ni l'un ni l'autre : ses noms de communes sont des noms propres. --}}
@php($aDuBilingue = count($champs) > 0 || ($enteteAffichee ?? true))

<form wire:submit="enregistrer" class="space-y-6">

    {{-- Embarque dans un ecran de page, ce bloc n'a pas de titre a lui : la
         page qui l'accueille porte deja le sien. Les boutons « Ajouter » et
         « Enregistrer » restent, sans quoi la liste ne servirait qu'a lire. --}}
    @if ($embarque ?? false)
        <div class="flex flex-wrap justify-end gap-2">
            @if ($peutEcrire && $ajoutPermis)
                <button type="button" wire:click="ajouter"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    <x-admin.icone nom="plus" />
                    {{ $libelleAjout ?? __('Ajouter') }}
                </button>
            @endif
            @if ($peutEcrire)
                <button type="submit"
                        class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    {{ __('Enregistrer') }}
                </button>
            @endif
        </div>
    @else
    <x-admin.entete-page :titre="$titre" :fil="$fil" :resume="$sousTitre">
        <x-slot:actions>
            <x-bascule-langue />
            @if ($peutEcrire && $ajoutPermis)
                <button type="button" wire:click="ajouter"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    <x-admin.icone nom="plus" />
                    {{ $libelleAjout ?? __('Ajouter') }}
                </button>
            @endif
            @if ($peutEcrire)
                <button type="submit"
                        class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    {{ __('Enregistrer') }}
                </button>
            @endif
        </x-slot:actions>
    </x-admin.entete-page>
    @endif

    @if (session('message'))
        <div role="status" class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    @isset($apercu)
        {{-- L'apercu montre ce que le visiteur verra, avec les valeurs en cours
             de saisie : c'est le seul endroit ou l'on voit qu'un suffixe manque
             ou qu'un libelle deborde. --}}
        {{-- La classe de grille est ECRITE EN TOUTES LETTRES, pas construite.
             Tailwind ne compile que les classes qu'il trouve dans les sources :
             « sm:grid-cols-{{ $n }} » n'existe dans aucune feuille produite, et
             les cartes retombaient donc les unes sous les autres. --}}
        @php($colonnesApercu = match (min(max(count($lignes), 1), 4)) {
            1 => 'sm:grid-cols-1',
            2 => 'sm:grid-cols-2',
            3 => 'sm:grid-cols-3',
            default => 'sm:grid-cols-2 lg:grid-cols-4',
        })

        <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Aperçu sur le site') }}</p>

            {{-- Tous les apercus ne sont pas des grilles de cartes. Celui de la
                 banderole est UNE bande qui prend toute la largeur : la glisser
                 dans une grille a quatre colonnes la reduisait au quart de
                 l'ecran, et son texte s'y coupait. Le bloc dit donc lui-meme la
                 forme de son apercu. --}}
            @if ($apercuPleineLargeur ?? false)
                <div class="mt-4">{!! $apercu !!}</div>
            @else
                <div class="mt-4 grid gap-4 {{ $colonnesApercu }}">
                    {!! $apercu !!}
                </div>
            @endif
        </div>
    @endisset

    {{-- Promettre une traduction automatique sur un bloc qui n'a aucun champ
         bilingue serait mentir a l'editeur : il chercherait ce que l'autre
         langue va remplir, et ne trouverait rien. --}}
    @if ($traductionActive && $aDuBilingue)
        <p class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
    @endif

    {{-- Onglets de la langue du CONTENU. « Français » et « English » restent
         ecrits dans leur propre langue : ce sont des endonymes.

         Ils disparaissent quand rien n'est bilingue : deux onglets qui
         n'echangent aucun champ font croire a un ecran casse. --}}
    @if ($aDuBilingue)
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-4" aria-label="{{ __('Langue du contenu') }}">
            @foreach (['fr' => 'Français', 'en' => 'English'] as $code => $intitule)
                <button type="button" wire:click="$set('langueActive', '{{ $code }}')"
                        @class([
                            'border-b-2 px-1 py-2 text-sm',
                            'border-zinc-900 font-medium dark:border-white' => $langueActive === $code,
                            'border-transparent text-zinc-600 dark:text-zinc-400' => $langueActive !== $code,
                        ])>{{ $intitule }}</button>
            @endforeach
        </nav>
    </div>
    @endif

    {{-- La colonne de reglages disparait quand l'editeur est embarque : la
         grille repasse alors sur une seule colonne, sinon la liste occuperait
         deux tiers de la largeur et laisserait un vide a droite. --}}
    <div class="grid gap-6 {{ $sectionReglee && ! ($embarque ?? false) ? 'lg:grid-cols-3' : '' }}">
        <div class="{{ $sectionReglee ? 'lg:col-span-2' : '' }} grid gap-4 {{ ($colonnes ?? 1) > 1 ? 'sm:grid-cols-2' : '' }}">
            @forelse ($lignes as $cle => $ligne)
                <fieldset wire:key="ligne-{{ $cle }}"
                          class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-start justify-between gap-3">
                        <legend class="sr-only">{{ $intituleRang }} {{ $loop->iteration }}</legend>

                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                            {{ $loop->iteration }}
                        </span>

                        {{-- Masquee quand le modele n'a pas de colonne
                             « visible » : les categories d'articles sont
                             toutes proposees par le filtre public, et une case
                             a decocher sans effet aurait menti. --}}
                        @if ($visibiliteAffichee ?? true)
                            <label class="ms-auto flex items-center gap-2">
                                <input type="checkbox" wire:model="lignes.{{ $cle }}.visible"
                                       @disabled(! $peutEcrire) class="rounded border-zinc-300">
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('Visible') }}</span>
                            </label>
                        @endif

                        @if ($peutEcrire && $ajoutPermis)
                            @unless ($visibiliteAffichee ?? true) <span class="ms-auto"></span> @endunless
                            <button type="button" wire:click="retirer('{{ $cle }}')"
                                    wire:confirm="{{ __('Retirer cet élément ? Il sera supprimé à l’enregistrement.') }}"
                                    title="{{ __('Retirer') }}"
                                    class="rounded-md p-1.5 text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 dark:hover:text-red-400">
                                <x-admin.icone nom="corbeille" />
                            </button>
                        @endif
                    </div>

                    <div class="mt-3 grid gap-4">
                        @foreach ($champsSimples as $nom => $description)
                            <label class="block">
                                <span class="text-sm font-medium">{{ $description['intitule'] }}</span>
                                <input type="{{ $description['type'] ?? 'text' }}"
                                       wire:model="lignes.{{ $cle }}.{{ $nom }}"
                                       @disabled(! $peutEcrire) class="{{ $champ }}">
                                @isset($description['aide'])
                                    <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $description['aide'] }}</span>
                                @endisset
                                @error('lignes.'.$cle.'.'.$nom) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>
                        @endforeach

                        @foreach ($champs as $prefixe => $intitule)
                            {{-- Les deux langues sont rendues, celle qui n'est
                                 pas active etant masquee : la basculer ne doit
                                 pas coûter un aller-retour au serveur ni perdre
                                 une saisie en cours. --}}
                            @foreach (['fr', 'en'] as $code)
                                <label class="block {{ $langueActive === $code ? '' : 'hidden' }}">
                                    <span class="text-sm font-medium">
                                        {{ $intitule }} ({{ $code === 'fr' ? __('français') : __('anglais') }})
                                    </span>
                                    <textarea wire:model="lignes.{{ $cle }}.{{ $prefixe }}_{{ $code }}"
                                              rows="2" @disabled(! $peutEcrire)
                                              class="{{ $champ }}"></textarea>
                                    @error('lignes.'.$cle.'.'.$prefixe.'_'.$code)
                                        <span class="text-sm text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>
                            @endforeach
                        @endforeach
                    </div>
                </fieldset>
            @empty
                <p class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-600 dark:border-zinc-600 dark:text-zinc-400">
                    {{ __('Aucun élément. Utilisez le bouton d’ajout pour en créer un.') }}
                </p>
            @endforelse
        </div>

        {{-- L'EN-TETE de la section est masquee : l'ecran de page qui accueille
             cet editeur l'edite DEJA, juste au-dessus. Deux formulaires pour la
             meme donnee, c'est la porte ouverte a une saisie qui en ecrase une
             autre selon l'ordre des clics.

             L'APPARENCE, elle, reste : la casse et le separateur de la
             banderole, la duree d'animation des chiffres cles ne sont edites
             nulle part ailleurs. Les masquer avec le reste les aurait rendus
             inatteignables — c'est le piege deja rencontre sur la mise en page
             du processus, que l'ecran de page a du reprendre a son compte. --}}
        @php($enteteEditee = ($enteteAffichee ?? true) && ! ($embarque ?? false))
        @php($panneauUtile = $enteteEditee || isset($reglagesSupplementaires))

        @if ($sectionReglee && $panneauUtile)
            <aside class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Réglages du bloc') }}</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $enteteEditee
                        ? __("En-tête de la section sur le site.")
                        : __("Apparence du bloc sur le site.") }}
                </p>

                <div class="mt-4 space-y-4">
                    {{-- Certains blocs n'affichent AUCUN en-tete sur le site —
                         la banderole en est un. Leur proposer un titre et un
                         chapo aurait fait saisir un texte que rien ne rend :
                         c'est le defaut d'ecran menteur releve cinq fois sur ce
                         projet. Ils reglent alors leur apparence, et rien
                         d'autre. --}}
                    @foreach ($enteteEditee ? ['titre' => __('Titre de la section'), 'chapo' => __('Chapô')] : [] as $nom => $intitule)
                        @foreach (['fr', 'en'] as $code)
                            <label class="block {{ $langueActive === $code ? '' : 'hidden' }}">
                                <span class="text-sm font-medium">
                                    {{ $intitule }} ({{ $code === 'fr' ? __('français') : __('anglais') }})
                                </span>
                                <textarea wire:model="reglages.{{ $nom }}_{{ $code }}" rows="{{ $nom === 'chapo' ? 3 : 2 }}"
                                          @disabled(! $peutEcrire) class="{{ $champ }}"></textarea>
                            </label>
                        @endforeach
                    @endforeach

                    @isset($reglagesSupplementaires)
                        {!! $reglagesSupplementaires !!}
                    @endisset
                </div>
            </aside>
        @endif
    </div>
</form>
