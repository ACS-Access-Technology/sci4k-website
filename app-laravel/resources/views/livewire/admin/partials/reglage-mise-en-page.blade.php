{{--
  Choix de mise en page du bloc processus.

  L'ordre des etapes suit celui de l'ecran, et la numerotation sur le site en
  decoule : la frise horizontale numerote de gauche a droite, la liste
  verticale de haut en bas. Le choix ne change donc pas le contenu, seulement
  sa disposition.
--}}
<label class="block">
    <span class="text-sm font-medium">{{ __('Mise en page') }}</span>
    <select wire:model="reglages.mise_en_page" @disabled(! $peutEcrire) class="{{ $champ }}">
        <option value="frise">{{ __('Frise horizontale') }}</option>
        <option value="liste">{{ __('Liste verticale') }}</option>
    </select>
    <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
        {{ __("L'ordre des étapes détermine leur numérotation sur le site.") }}
    </span>
</label>
