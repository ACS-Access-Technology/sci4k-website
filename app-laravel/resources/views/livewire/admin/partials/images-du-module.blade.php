{{--
  Les images de fond d'un module.

  Un module en porte souvent UNE — la banniere de sa page — mais parfois
  plusieurs : les six tuiles de services ont chacune la leur. Le bloc etait
  ecrit sept fois, une par ecran de page, et ne savait en montrer qu'une ;
  celles des tuiles, du pied de page et des pages d'erreur n'etaient donc
  atteignables nulle part depuis le retrait de l'ecran « Images de fond ».

  Attend : $images (collection d'ImageDeFond), $module.
--}}
@if ($images->isNotEmpty())
    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="text-sm font-semibold">
            {{ trans_choice('Image de fond|Images de fond', $images->count()) }}
        </h3>

        <div class="mt-3 space-y-5">
            @foreach ($images as $image)
                {{-- wire:key porte le SLUG : sans lui, Livewire reutiliserait
                     l'instance d'une image a l'autre et afficherait le fichier
                     de la precedente. --}}
                <div class="flex items-start gap-4 {{ $loop->first ? '' : 'border-t border-zinc-200 pt-5 dark:border-zinc-700' }}">
                    @if ($image->fichier)
                        <img src="{{ asset($image->fichier) }}" alt=""
                             class="h-16 w-28 shrink-0 rounded object-cover">
                    @endif
                    <div class="min-w-0 flex-1">
                        @livewire('admin.image-de-fond-formulaire',
                            ['element' => $image, 'embarque' => true],
                            key('image-'.$module.'-'.$image->slug))
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
