{{-- Apparence du bandeau defilant. Trois reglages, ceux de la maquette. --}}
<label class="block">
    <span class="text-sm font-medium">{{ __('Fond') }}</span>
    <select wire:model="reglages.fond" @disabled(! $peutEcrire) class="{{ $champ }}">
        <option value="sombre">{{ __('Sombre') }}</option>
        <option value="clair">{{ __('Clair') }}</option>
    </select>
</label>

<label class="block">
    <span class="text-sm font-medium">{{ __('Séparateur') }}</span>
    <input type="text" wire:model="reglages.separateur" maxlength="3"
           @disabled(! $peutEcrire) class="{{ $champ }}">
    <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
        {{ __('Signe placé entre deux communes. Trois caractères au plus.') }}
    </span>
</label>

<label class="block">
    <span class="text-sm font-medium">{{ __('Casse du texte') }}</span>
    <select wire:model="reglages.casse" @disabled(! $peutEcrire) class="{{ $champ }}">
        <option value="majuscules">{{ __('Majuscules') }}</option>
        <option value="saisie">{{ __('Telle que saisie') }}</option>
    </select>
</label>
