{{--
  Le logo du site, et non le losange de Laravel herite du starter kit. La page
  de connexion est le premier ecran que voit un editeur : elle doit dire chez
  qui il entre.

  Un <img> plutot qu'un SVG : le logo est un fichier fourni par le client,
  deposé dans public/images/ par tools/sync-frontoffice.sh, le meme que servent
  les pages publiques et la barre laterale. Les classes fill-current et
  text-* que passent encore les gabarits d'authentification n'ont plus d'effet
  sur une image ; elles sont sans danger et evitent de toucher aux trois
  gabarits pour un attribut mort.
--}}
<span {{ $attributes->class('inline-flex items-center justify-center overflow-hidden') }}>
    <img src="{{ asset('images/image (3).png') }}" alt="" class="size-full object-contain">
</span>
