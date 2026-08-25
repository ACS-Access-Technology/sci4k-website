@php($courante = app()->getLocale())

<a href="{{ route('langue.basculer', $courante === 'fr' ? 'en' : 'fr') }}"
   class="inline-flex h-9 min-w-9 items-center justify-center rounded-full border border-zinc-300 px-3 text-xs font-semibold text-zinc-700 hover:bg-zinc-100"
   aria-label="{{ $courante === 'fr' ? __('Passer en anglais') : __('Passer en français') }}">
    {{ $courante === 'fr' ? 'EN' : 'FR' }}
</a>
