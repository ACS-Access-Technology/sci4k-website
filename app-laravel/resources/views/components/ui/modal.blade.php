@props([
    'name',
])

{{--
    First-party, dependency-free modal built on the native <dialog> element
    (replaces the proprietary component library's modal). Addressable by id
    (`document.getElementById('modal-{name}')`) so a trigger — or an
    x-effect reacting to a `$wire` boolean — can open/close it from
    anywhere on the page, including a different Livewire component than
    the modal itself. State is intentionally never opened here via x-init:
    Livewire's DOM morph after any wire update on the surrounding component
    would silently reset a bare ".showModal()" call, so every caller in
    this app derives the open/closed state from a `$wire` property via
    x-effect instead (see the security/delete-user-modal/two-factor-setup
    views) — that re-runs after each morph and stays correct.

    Focus trapping, background scroll blocking and closing on Escape are
    provided natively by `<dialog>.showModal()`: the browser confines focus
    inside the dialog, makes the rest of the document inert, and fires a
    cancelable "cancel" event on Escape. Every caller hooks that event with
    `x-on:cancel.prevent="$wire.close...()"` so the Livewire state (and
    therefore this element's open/closed state) stays the single source of
    truth. `html:has(dialog[open])` in app.css backstops the scroll lock
    across browsers that don't fully inert background scrolling.
--}}
<dialog
    id="modal-{{ $name }}"
    role="dialog"
    aria-modal="true"
    {{ $attributes->class('m-auto w-full rounded-xl border border-zinc-200 bg-white p-6 text-zinc-900 shadow-xl backdrop:bg-black/50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white') }}
>
    {{ $slot }}
</dialog>
