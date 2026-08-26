@props([
    'sidebar' => false,
])

{{--
  Le logo du backoffice est celui du site, et non le losange de Laravel herite
  du starter kit : l'administration d'un site doit ressembler au site qu'elle
  administre. Le fichier est celui que servent les pages publiques, depose dans
  public/images/ par tools/sync-frontoffice.sh.
--}}
<a {{ $attributes->class('flex items-center gap-2 font-medium text-zinc-900 dark:text-white') }}>
    <span class="flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-md">
        <img src="{{ asset('images/image (3).png') }}" alt="" class="size-full object-contain">
    </span>

    <span class="truncate text-sm leading-tight font-semibold">
        {{ config('app.name', 'SCI4K') }}
    </span>
</a>
