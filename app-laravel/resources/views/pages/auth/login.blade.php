<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-ui.input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <x-ui.input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <x-ui.link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </x-ui.link>
                @endif
            </div>

            <!-- Remember Me -->
            <x-ui.checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <x-ui.button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </x-ui.button>
            </div>
        </form>

        {{-- Pas de lien « Créer un compte » : ce site n'a pas d'espace membre.
             Les comptes du backoffice sont crees par un administrateur depuis
             l'ecran des utilisateurs, qui envoie au titulaire un lien pour
             choisir son mot de passe. --}}
        <p class="text-sm text-center text-zinc-600 dark:text-zinc-400">
            {{ __("L'accès au backoffice est délivré par un administrateur.") }}
        </p>
    </div>
</x-layouts::auth>
