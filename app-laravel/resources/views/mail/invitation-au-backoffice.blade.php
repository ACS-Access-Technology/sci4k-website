{{ __('Bonjour :nom,', ['nom' => $nom]) }}

{{ __(':auteur vous a ouvert un accès à l’administration de :site.', ['auteur' => $invitePar, 'site' => $nomDuSite]) }}

{{ __('Choisissez votre mot de passe en suivant ce lien :') }}
{{ $lien }}

{{ __('Ce lien expire dans 60 minutes. Passé ce délai, demandez un nouveau mot de passe depuis la page de connexion.') }}

{{ __("Si vous n'attendiez pas cette invitation, ignorez ce message : aucun compte ne sera utilisable sans que vous ayez choisi un mot de passe.") }}
