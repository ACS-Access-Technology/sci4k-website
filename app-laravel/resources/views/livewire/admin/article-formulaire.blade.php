@php($champ = 'mt-1 w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900')

<form wire:submit="enregistrer" class="max-w-3xl space-y-6">

    {{-- Le formulaire n'a pas de titre de page : il est ouvert DANS la liste
         des articles, rendue depuis « Pages du site → Actualités », qui porte
         le sien. « Annuler » referme le bloc au lieu de renvoyer ailleurs. --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 pb-3 dark:border-zinc-700">
        <h4 class="text-sm font-semibold">
            {{ $article ? __('Modifier l’article') : __('Nouvel article') }}
        </h4>
        <div class="flex items-center gap-2">
            <x-bascule-langue />
            <button type="button" wire:click="$dispatch('bloc-annule')"
                    class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium dark:border-zinc-600">
                {{ __('Annuler') }}
            </button>
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                {{ __('Enregistrer') }}
            </button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-medium">{{ __("Identifiant d'adresse") }}</span>
            <input type="text" wire:model="slug" class="{{ $champ }}">
            <span class="mt-1 block text-xs text-zinc-500">
                {{ __('Apparaîtra dans le lien de l\'article.') }}
                <code>/actualites/{{ $slug ?: 'mon-article' }}</code>
            </span>
            @error('slug') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">{{ __('Catégorie') }}</span>
            <select wire:model="categorieId" class="{{ $champ }}">
                <option value="">{{ __('Choisir…') }}</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->nom($langue) }}</option>
                @endforeach
            </select>
            @error('categorieId') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">{{ __('Date de publication') }}</span>
            <input type="date" wire:model="datePublication" class="{{ $champ }}">
            @error('datePublication') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        {{-- Un redacteur ne choisit pas le statut : il ne publie pas. On le lui
             DIT plutot que de lui presenter un choix sans effet — le composant
             ramene de toute facon la valeur a « brouillon », le navigateur
             pouvant fixer une propriete publique meme masquee. --}}
        @if (auth()->user()?->peutPublier())
            <label class="block">
                <span class="text-sm font-medium">{{ __('Statut') }}</span>
                <select wire:model="statut" class="{{ $champ }}">
                    <option value="brouillon">{{ __('Brouillon') }}</option>
                    <option value="publie">{{ __('Publié') }}</option>
                </select>
                <span class="mt-1 block text-xs text-zinc-500">
                    {{ __("Un brouillon n'apparaît pas sur le site public.") }}
                </span>
                @error('statut') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
        @else
            <div class="rounded-lg border border-dashed border-zinc-300 p-3 text-sm text-zinc-600 dark:border-zinc-600 dark:text-zinc-300">
                {{ __('Cet article restera un brouillon : un éditeur le relira avant publication.') }}
            </div>
        @endif

        {{-- L'interrupteur des commentaires, article par article : un sujet
             sensible peut se passer d'une discussion publique. Ferme, le
             formulaire disparait de la page ET le depot est refuse cote
             serveur — un formulaire absent n'empeche personne de poster a la
             main. --}}
        <label class="flex items-start gap-2 sm:col-span-2">
            <input type="checkbox" wire:model="commentairesOuverts" class="mt-0.5 rounded border-zinc-300">
            <span>
                <span class="text-sm font-medium">{{ __('Autoriser les commentaires') }}</span>
                <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Décoché, les commentaires déjà publiés restent visibles mais personne ne peut en ajouter.') }}
                </span>
            </span>
        </label>
    </div>

    {{--
      Onglets de la langue du CONTENU saisi. Ils ne suivent pas la langue de
      l'interface : « Français » et « English » restent ecrits dans leur propre
      langue, comme sur le bouton FR/EN du site public, sans quoi on ne saurait
      plus quelle version de l'article on est en train de rediger.
    --}}
    @if ($traductionActive)
        <p class="rounded border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
    @endif

    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-4" aria-label="{{ __('Langue du contenu') }}">
            @foreach (['fr' => 'Français', 'en' => 'English'] as $code => $intitule)
                <button type="button" wire:click="$set('langueActive', '{{ $code }}')"
                        @class([
                            'border-b-2 px-1 py-2 text-sm',
                            'border-zinc-900 font-medium dark:border-white' => $langueActive === $code,
                            'border-transparent text-zinc-500' => $langueActive !== $code,
                        ])>
                    {{ $intitule }}
                </button>
            @endforeach
        </nav>
    </div>

    {{--
      Les deux blocs restent dans le DOM, masques par `hidden` : une erreur de
      validation sur la langue inactive est ainsi conservee, et visible des
      qu'on revient sur son onglet. Les retirer du DOM la ferait disparaitre
      sans que l'editeur comprenne pourquoi l'enregistrement echoue.
    --}}
    @foreach (['fr' => 'Fr', 'en' => 'En'] as $code => $suffixe)
        <div class="space-y-4" @if ($langueActive !== $code) hidden @endif>
            <label class="block">
                <span class="text-sm font-medium">{{ __('Titre') }}</span>
                <input type="text" wire:model="titre{{ $suffixe }}" class="{{ $champ }}">
                @error('titre'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Résumé') }}</span>
                <textarea wire:model="resume{{ $suffixe }}" rows="3" class="{{ $champ }}"></textarea>
                <span class="mt-1 block text-xs text-zinc-500">{{ __("Affiché sur la carte de l'article.") }}</span>
                @error('resume'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Contenu') }}</span>
                <textarea wire:model="contenu{{ $suffixe }}" rows="12" class="{{ $champ }}"></textarea>
                <span class="mt-1 block text-xs text-zinc-500">
                    {{ __('Séparez les paragraphes par une ligne vide.') }}
                </span>
                @error('contenu'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Description pour les moteurs (160 signes)') }}</span>
                <input type="text" wire:model="metaDescription{{ $suffixe }}" maxlength="160" class="{{ $champ }}">
                <span class="mt-1 block text-xs text-zinc-500">
                    {{ __('Laissée vide, le résumé est utilisé.') }}
                </span>
                @error('metaDescription'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>
    @endforeach

    <div class="space-y-3 rounded border border-zinc-200 p-4 dark:border-zinc-700">
        <span class="block text-sm font-medium">{{ __('Image de couverture') }}</span>

        {{-- L'apercu n'est demande que si le fichier est valide : temporaryUrl()
             leve une exception sur un type non previsualisable, et la page
             tomberait au lieu d'afficher le message d'erreur. --}}
        @if ($couverture && ! $errors->has('couverture'))
            <img src="{{ $couverture->temporaryUrl() }}" alt="" class="h-40 rounded object-cover">
            <p class="text-xs text-zinc-500">{{ __("Nouvelle image, enregistrée à la validation du formulaire.") }}</p>
        @elseif ($couvertureActuelle && ! $couvertureARetirer)
            <img src="{{ asset($couvertureActuelle) }}" alt="" class="h-40 rounded object-cover">
            <button type="button" wire:click="supprimerCouverture" class="text-sm text-red-600 hover:underline">
                {{ __("Retirer l'image") }}
            </button>
        @elseif ($couvertureARetirer)
            <p class="text-sm text-amber-700 dark:text-amber-300">
                {{ __("L'image sera retirée à l'enregistrement.") }}
            </p>
        @else
            <p class="text-sm text-zinc-500">{{ __('Aucune image.') }}</p>
        @endif

        <input type="file" wire:model="couverture" accept="image/*" class="block w-full text-sm">
        <span class="block text-xs text-zinc-500">{{ __('JPEG, PNG ou WebP, 4 Mo au maximum.') }}</span>
        <div wire:loading wire:target="couverture" class="text-xs text-zinc-500">{{ __('Envoi en cours…') }}</div>
        @error('couverture') <span class="block text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
            {{ __('Enregistrer') }}
        </button>
        <button type="button" wire:click="$dispatch('bloc-annule')" class="text-sm text-zinc-500 hover:underline">
            {{ __('Annuler') }}
        </button>
    </div>
</form>
