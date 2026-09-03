<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;
    use WithFileUploads;

    public string $name = '';
    public string $email = '';

    /** Fichier choisi dans le navigateur, pas encore enregistre. */
    public $photo = null;

    /** L'utilisateur a demande le retrait de sa photo. */
    public bool $photoARetirer = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id) + ['photo' => $this->reglesDeLaPhoto()]);

        // La photo n'est pas une colonne remplie par fill() : elle passe par
        // televerserLaPhoto(), qui gere aussi l'effacement de l'ancienne.
        unset($validated['photo']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $this->televerserLaPhoto($user);

        $user->save();

        $this->dispatch('toast', message: __('Profile updated.'), variant: 'success');
    }

    /**
     * Regles de la photo.
     *
     * 2 Mo au plus : une photo de profil s'affiche en 32 pixels de cote, et
     * rien ne justifie d'en televerser davantage. `image` refuse les fichiers
     * qui n'en sont pas, quel que soit leur nom.
     *
     * @return list<string>
     */
    protected function reglesDeLaPhoto(): array
    {
        return ['nullable', 'image', 'max:2048'];
    }

    /** Valide le fichier des son choix, sans attendre l'enregistrement. */
    public function updatedPhoto(): void
    {
        $this->validate(['photo' => $this->reglesDeLaPhoto()]);
        $this->photoARetirer = false;
    }

    /**
     * Retire la photo. Le fichier n'est efface qu'a l'enregistrement : tant
     * que rien n'est valide, on peut encore changer d'avis.
     */
    public function retirerLaPhoto(): void
    {
        $this->photo = null;
        $this->photoARetirer = true;
    }

    /**
     * Enregistre la photo choisie, ou efface celle qui existait.
     *
     * L'ancien fichier n'est efface que s'il vient bien du dossier des photos
     * de profil : un chemin forge designant une couverture d'article ne doit
     * pas pouvoir la detruire au passage.
     */
    protected function televerserLaPhoto(\App\Models\User $user): void
    {
        if (! $this->photo && ! $this->photoARetirer) {
            return;
        }

        $ancienne = $user->photo;

        $user->photo = $this->photo
            ? 'storage/'.$this->photo->store('comptes', 'public')
            : null;

        if ($ancienne && str_starts_with($ancienne, \App\Models\User::DOSSIER_PHOTOS.'/')) {
            \Illuminate\Support\Facades\Storage::disk('public')
                ->delete(mb_substr($ancienne, mb_strlen('storage/')));
        }

        $this->photo = null;
        $this->photoARetirer = false;
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-ui.heading level="2" class="sr-only">{{ __('Profile settings') }}</x-ui.heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">

            {{-- LA PHOTO DE PROFIL
                 L'apercu montre le fichier CHOISI tant qu'il n'est pas
                 enregistre, et la photo en place sinon : c'est le seul moment
                 ou l'on peut encore se raviser. --}}
            <div class="flex items-center gap-4">
                {{-- isPreviewable() garde l'apercu : temporaryUrl() leve une
                     exception sur un type qu'elle ne sait pas montrer, et le
                     depot d'un PDF faisait planter le rendu entier au lieu
                     d'afficher l'erreur de validation. --}}
                @if ($photo && $photo->isPreviewable())
                    <img src="{{ $photo->temporaryUrl() }}" alt=""
                         class="size-16 shrink-0 rounded-full object-cover ring-2 ring-zinc-300 dark:ring-zinc-600">
                @elseif ($photoARetirer)
                    <span class="flex size-16 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                        {{ auth()->user()->initials() }}
                    </span>
                @else
                    <x-admin.vignette-compte :compte="auth()->user()" taille="size-16" class="!text-sm" />
                @endif

                <div class="min-w-0 flex-1 space-y-2">
                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Photo de profil') }}</span>
                        <input type="file" wire:model="photo" accept="image/*"
                               class="mt-1 block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white dark:text-zinc-300 dark:file:bg-white dark:file:text-zinc-900">
                    </label>

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Image carrée de préférence, 2 Mo au plus. Sans photo, vos initiales sont affichées.') }}
                    </p>

                    <div wire:loading wire:target="photo" class="text-xs text-zinc-500">{{ __('Envoi en cours…') }}</div>
                    @error('photo') <span class="block text-sm text-red-600">{{ $message }}</span> @enderror

                    @if (auth()->user()->photo && ! $photoARetirer)
                        <button type="button" wire:click="retirerLaPhoto"
                                class="text-xs text-red-600 hover:underline">{{ __('Retirer la photo') }}</button>
                    @endif

                    @if ($photoARetirer)
                        <p class="text-xs text-amber-700 dark:text-amber-400">
                            {{ __('La photo sera retirée à l’enregistrement.') }}
                        </p>
                    @endif
                </div>
            </div>

            <x-ui.input wire:model="name" name="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <x-ui.input wire:model="email" name="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <x-ui.text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <x-ui.link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </x-ui.link>
                        </x-ui.text>

                        @if (session('status') === 'verification-link-sent')
                            <x-ui.text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </x-ui.text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <x-ui.button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </x-ui.button>
                </div>

            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
