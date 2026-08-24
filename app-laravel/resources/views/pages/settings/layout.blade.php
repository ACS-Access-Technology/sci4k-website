<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <nav aria-label="{{ __('Settings') }}" class="space-y-1">
            <a href="{{ route('profile.edit') }}" wire:navigate @class(['block rounded-lg px-3 py-2 text-sm font-medium', 'bg-zinc-100 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('profile.edit'), 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('profile.edit')])>{{ __('Profile') }}</a>
            <a href="{{ route('security.edit') }}" wire:navigate @class(['block rounded-lg px-3 py-2 text-sm font-medium', 'bg-zinc-100 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('security.edit'), 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('security.edit')])>{{ __('Security') }}</a>
            <a href="{{ route('appearance.edit') }}" wire:navigate @class(['block rounded-lg px-3 py-2 text-sm font-medium', 'bg-zinc-100 text-zinc-900 dark:bg-white/10 dark:text-white' => request()->routeIs('appearance.edit'), 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-white/5' => ! request()->routeIs('appearance.edit')])>{{ __('Appearance') }}</a>
        </nav>
    </div>

    <x-ui.separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <x-ui.heading>{{ $heading ?? '' }}</x-ui.heading>
        <x-ui.subheading>{{ $subheading ?? '' }}</x-ui.subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
