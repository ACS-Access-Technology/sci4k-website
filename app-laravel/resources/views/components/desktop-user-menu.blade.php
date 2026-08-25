@props([
    'showName' => true,
])

{{-- Menu déroulant en Alpine.js pur (remplace le dropdown/menu de la bibliothèque propriétaire). --}}
<div
    x-data="{ open: false }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    class="relative"
    data-test="sidebar-menu-button"
>
    <button
        type="button"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open"
        aria-haspopup="true"
        class="flex min-h-11 w-full cursor-pointer items-center gap-2 rounded-lg p-2 text-sm hover:bg-zinc-200/50 dark:hover:bg-white/5"
    >
        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent-content text-xs font-medium text-accent-foreground">
            {{ auth()->user()->initials() }}
        </span>

        @if ($showName)
            <span class="hidden text-start leading-tight sm:grid">
                <span class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</span>
            </span>
        @endif

        <x-icons.chevrons-up-down class="ms-auto text-zinc-400" />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        x-on:click="open = false"
        role="menu"
        class="absolute end-0 z-20 mt-2 w-56 rounded-lg border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
    >
        <div class="flex items-center gap-2 px-2 py-1.5 text-start text-sm">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent-content text-xs font-medium text-accent-foreground">
                {{ auth()->user()->initials() }}
            </span>
            <span class="grid flex-1 text-start leading-tight">
                <span class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</span>
                <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</span>
            </span>
        </div>

        <x-ui.separator class="my-1" />

        <a
            href="{{ route('profile.edit') }}"
            wire:navigate
            role="menuitem"
            class="flex min-h-11 items-center gap-2 rounded-md px-2 py-1.5 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/5"
        >
            <x-icons.cog />
            {{ __('Settings') }}
        </a>

        <x-ui.separator class="my-1" />

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button
                type="submit"
                data-test="logout-button"
                role="menuitem"
                class="flex min-h-11 w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-start text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-white/5"
            >
                <x-icons.logout />
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</div>
