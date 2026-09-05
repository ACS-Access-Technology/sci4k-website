@extends('public.layout')

@section('titre', $banniere?->texteBilingue('meta_titre', $langue) ?: __('Contact'))
@section('description', $banniere?->texteBilingue('meta_description', $langue) ?: __("Contactez SCI4K à Abidjan : achat, vente, location, construction et gestion de patrimoine immobilier."))
@section('classe-page', 'page-contact')

@section('contenu')

{{--
  Les INTITULES au-dessus de chaque coordonnee et les textes de la carte
  etaient ecrits en dur et traduits par __() : aucun ecran ne les exposait.
  Les VALEURS — adresse, telephone, horaires — venaient deja de la
  configuration ; les titres qui les coiffent, non.

  « Site web » n'est pas branche : c'est l'etiquette du champ piege, placee
  hors de l'ecran et jamais montree.
--}}
@php($tCoordonnees = fn (string $nom, string $defaut) => $sectionCoordonnees?->texteBilingue($nom, $langue) ?: $defaut)
@php($tCarte = fn (string $nom, string $defaut) => $enteteCarte?->texteBilingue($nom, $langue) ?: $defaut)

{{--
  Page de contact, portee depuis maquettes-frontoffice/contact.html.

  Le balisage est repris tel quel — memes classes, memes identifiants — parce
  que main.js s'y accroche : handleContactSubmit lit les champs par leur id, et
  le bloc de la page ne s'active que sur body.page-contact. Changer un id ici
  casserait l'envoi sans qu'aucun test ne le voie.

  Ce qui change, c'est l'ORIGINE des textes. Ils venaient du dictionnaire de
  main.js et de valeurs ecrites en dur ; ils viennent maintenant de la base :

  - les trois en-tetes par ReglageDeSection (contact.page, contact.form,
    contact.map), semes depuis le lot 2 mais que rien ne lisait faute de page
    portee ;
  - l'adresse, le telephone, l'email, les horaires et les coordonnees de la
    carte par Parametre, donc par l'onglet « Contact » de la configuration.
    « Horaires » et « Coordonnees de la carte » y etaient enregistres sans que
    rien ne les affiche nulle part.

  Chaque valeur se replie sur le texte d'origine si la base est muette : la
  page reste complete sur une installation neuve.
--}}

<section class="page-banner pb-contact">
  <div class="wrap">
    @if ($banniere?->etiquette($langue))
      <div class="tag reveal">{{ $banniere->etiquette($langue) }}</div>
    @endif
    <h1 class="reveal">{{ $banniere?->titre($langue) ?: __('Contactez SCI4K') }}</h1>
    @if ($banniere?->chapo($langue))
      <p class="reveal">{{ $banniere->chapo($langue) }}</p>
    @endif
  </div>
</section>

<section class="contact-section">
  <div class="wrap">
    <div class="contact-grid">

      {{-- FORMULAIRE --}}
      <div class="contact-card reveal">
        <h3 id="formTitle">{{ $enteteFormulaire?->titre($langue) ?: __('Envoyez-nous un message') }}</h3>
        <p class="sub" id="formSub">{{ $enteteFormulaire?->chapo($langue) ?: __('Remplissez le formulaire ci-dessous et notre équipe vous recontactera sous 24 heures ouvrées.') }}</p>
        {{-- Les libelles du formulaire viennent du backoffice, comme le reste
             du bloc : « Pages du site → Contact → Formulaire » les edite. Ce
             qui suit chaque appel reste la VALEUR DE REPLI — une base muette
             affiche donc exactement ce qu'affichait la page statique, et les
             cles __() servent encore de version anglaise par defaut. --}}
        @php($texte = fn (string $nom, string $defaut) => $enteteFormulaire?->texteBilingue($nom, $langue) ?: $defaut)

        <div class="alert-success" id="successAlert">{{ $texte('confirmation', __("Votre message est prêt : la conversation WhatsApp s'ouvre dans un nouvel onglet. Appuyez sur Envoyer pour le transmettre à SCI4K.")) }}</div>

        <form id="contactForm" onsubmit="handleContactSubmit(event)">
          {{-- Champ piege : invisible et hors du parcours au clavier, un humain
               ne le remplit jamais. Un robot remplit tout ce qu'il trouve, et
               le serveur refuse alors l'envoi. --}}
          <div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">
            <label for="contactSiteWeb">{{ __('Site web') }}</label>
            <input type="text" id="contactSiteWeb" name="site_web" tabindex="-1" autocomplete="off">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="contactName">{{ $texte('libelle_nom', __('Nom complet *')) }}</label>
              <input type="text" id="contactName" name="nom" required autocomplete="name" maxlength="80" placeholder="{{ $texte('exemple_nom', __('Ex: Jean Kouassi')) }}">
            </div>
            <div class="form-group">
              <label for="contactPhone">{{ $texte('libelle_telephone', __('Téléphone *')) }}</label>
              <input type="tel" id="contactPhone" name="telephone" required autocomplete="tel" maxlength="30" placeholder="{{ $texte('exemple_telephone', '+225 07 00 00 00 00') }}">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="contactEmail">{{ $texte('libelle_email', __('Adresse Email *')) }}</label>
              <input type="email" id="contactEmail" name="email" required autocomplete="email" maxlength="100" placeholder="{{ $texte('exemple_email', 'j.kouassi@email.com') }}">
            </div>
            <div class="form-group">
              @php($libelleSujet = $texte('libelle_sujet', __('Sujet de votre demande')))
              <label for="contactSubject">{{ $libelleSujet }}</label>

              {{-- Les sujets viennent du backoffice, un par ligne. L'intitule
                   EST la valeur : main.js recopie ce qui est choisi dans le
                   message WhatsApp, et c'est aussi ce qui est enregistre comme
                   sujet du message. Une valeur technique distincte aurait fait
                   arriver « Gestion » dans la boite de reception la ou
                   l'editeur avait ecrit « Gestion locative ». --}}
              <select id="contactSubject" aria-label="{{ $libelleSujet }}">
                @foreach ($sujets as $sujet)
                  <option value="{{ $sujet }}">{{ $sujet }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="messageTextarea">{{ $texte('libelle_message', __('Votre message *')) }}</label>
            <textarea id="messageTextarea" rows="5" required maxlength="900" placeholder="{{ $texte('exemple_message', __('Précisez les détails de votre projet, le quartier souhaité, le budget approximatif...')) }}"></textarea>
          </div>

          <button type="submit" class="submit-btn">{{ $texte('libelle_bouton', __('Envoyer mon message')) }}</button>
        </form>
      </div>

      {{-- COORDONNÉES --}}
      <div class="info-sidebar reveal">
        <div class="info-box">
          <div class="info-item">
            <h4>{{ $tCoordonnees('titre_adresse', __('Siège Social')) }}</h4>
            <p>{!! nl2br(e($adressePostale)) !!}</p>
          </div>
          <div class="divider"></div>
          <div class="info-item">
            <h4>{{ $tCoordonnees('titre_telephone', __('Téléphone & WhatsApp')) }}</h4>
            <p>{{ $telephonePublic }}</p>
          </div>
          <div class="divider"></div>
          <div class="info-item">
            <h4>{{ $tCoordonnees('titre_email', __('Email')) }}</h4>
            <p>{{ $emailPublic }}</p>
          </div>
          <div class="divider"></div>
          <div class="info-item">
            <h4>{{ $tCoordonnees('titre_horaires', __("Horaires d'ouverture")) }}</h4>
            <p>{!! nl2br(e($horaires)) !!}</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

{{-- CARTE --}}
<section class="map-section">
  <div class="wrap">
    <h2 class="reveal" style="font-size:clamp(26px,3vw,38px); color:var(--texte-titre); margin-bottom:28px;">
      {{ $enteteCarte?->titre($langue) ?: __('Notre localisation à Cocody') }}
    </h2>
    <div class="map-container reveal">
      <div class="map-label-bar">
        <div class="map-info">
          <div>
            <div class="addr">{{ $nomDuSite }} — {{ $premiereLigneAdresse }}
              <span>{{ $resteDeLAdresse }}</span>
            </div>
          </div>
        </div>
        {{-- Le lien et la carte partent des MEMES coordonnees. Ils divergeaient
             dans la page d'origine — la carte pointait sur -3.9927, le lien sur
             « −4.0083 » ecrit avec un signe moins typographique, que Google ne
             sait pas lire. --}}
        <a class="map-open-link" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($coordonneesCarte) }}" target="_blank" rel="noopener noreferrer">
          <span>{{ $tCarte('libelle_lien', __('Ouvrir dans Google Maps')) }}</span>
        </a>
      </div>
      <iframe
        title="{{ str_replace(':nom', $nomDuSite, $tCarte('titre_cadre', __('Localisation de :nom', ['nom' => ':nom']))) }}"
        src="https://www.google.com/maps?q={{ urlencode($coordonneesCarte) }}&z=15&hl={{ $langue }}&output=embed"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
    </div>
  </div>
</section>

{{-- Le numero WhatsApp vient de la configuration. main.js le lisait en dur,
     alors que l'aide du reglage annonce « c'est celui vers lequel le
     formulaire de contact ouvre la conversation ». Pose AVANT main.js, qui est
     charge en fin de page avec defer. --}}
<script>window.SCI4K_WHATSAPP = @json($whatsappPublic);</script>

@endsection
