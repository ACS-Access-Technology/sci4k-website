{{--
    First-party toast notification, dependency-free (replaces the
    proprietary component library's toast group). Livewire components
    trigger it with `$this->dispatch('toast', message: '...', variant: 'success')`.
--}}
<div
    x-data="{ show: false, message: '', variant: 'success', timer: null }"
    x-on:toast.window="
        message = $event.detail.message;
        variant = $event.detail.variant ?? 'success';
        show = true;
        clearTimeout(timer);
        timer = setTimeout(() => show = false, 4000);
    "
    x-show="show"
    x-cloak
    x-transition
    role="status"
    aria-live="polite"
    class="fixed top-4 end-4 z-50"
>
    <div
        :class="variant === 'success' ? 'bg-zinc-900 dark:bg-white dark:text-zinc-900' : 'bg-red-600'"
        class="rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg"
        x-text="message"
    ></div>
</div>
