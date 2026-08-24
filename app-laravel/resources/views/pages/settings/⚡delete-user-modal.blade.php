<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    public bool $open = false;

    /**
     * Open the account deletion confirmation modal.
     */
    #[On('open-account-deletion')]
    public function openModal(): void
    {
        $this->open = true;
    }

    /**
     * Close the account deletion confirmation modal.
     */
    public function closeModal(): void
    {
        $this->open = false;
        $this->reset('password');
        $this->resetErrorBag();
    }

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

{{--
    Open/closed state is driven from `$wire.open` via x-effect rather than
    opened imperatively on click. This modal's trigger button lives in a
    different Livewire component (delete-user-form), and re-rendering after
    a failed password validation morphs this component's own DOM — either
    of those would reset a plain ".showModal()" call. Re-deriving from
    server state on every update keeps it correctly open/closed regardless.
--}}
<div
    x-data
    x-effect="$wire.open ? document.getElementById('modal-confirm-user-deletion')?.showModal() : document.getElementById('modal-confirm-user-deletion')?.close()"
>
    <x-ui.modal
        name="confirm-user-deletion"
        class="max-w-lg"
        x-on:cancel.prevent="$wire.closeModal()"
        x-on:click.self="$wire.closeModal()"
    >
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <x-ui.heading size="lg">{{ __('Are you sure you want to delete your account?') }}</x-ui.heading>

                <x-ui.subheading>
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </x-ui.subheading>
            </div>

            <x-ui.input wire:model="password" name="password" :label="__('Password')" type="password" viewable />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <x-ui.button variant="filled" wire:click="closeModal">{{ __('Cancel') }}</x-ui.button>

                <x-ui.button variant="danger" type="submit" data-test="confirm-delete-user-button">
                    {{ __('Delete account') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
