{{--
  LE FORMULAIRE, OUVERT DANS LA LISTE.

  Embarquee dans un ecran de page, une liste n'envoie plus l'editeur sur une
  autre adresse : elle ouvre le formulaire ici meme.

  wire:key porte l'identifiant edite. Sans lui, Livewire reutiliserait
  l'instance d'une ligne a l'autre et afficherait les valeurs de la precedente.

  x-init amene le bloc sous les yeux. Le formulaire s'ouvre au-dessus du
  tableau : clique depuis une ligne du bas d'une longue liste, il s'affichait
  hors de l'ecran, et rien ne signalait qu'il etait la — on croyait le bouton
  sans effet. Le defilement n'est anime que pour qui l'accepte : la preference
  systeme « reduire les animations » vaut refus.

  Attend : $composant, $parametres, $cle.
--}}
<div x-data
     x-init="$nextTick(() => $el.scrollIntoView({
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        block: 'center',
     }))"
     class="rounded-xl border border-zinc-300 bg-white p-5 dark:border-zinc-600 dark:bg-zinc-900">
    @livewire($composant, $parametres, key('formulaire-'.$cle))
</div>
