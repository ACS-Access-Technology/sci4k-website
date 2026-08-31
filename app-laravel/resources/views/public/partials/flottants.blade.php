<div class="fab-stack" id="fabStack">
  <a class="fab-action fab-wa" href="https://wa.me/{{ $whatsappPublic }}?text={{ urlencode($whatsappMessage) }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Discuter sur WhatsApp') }}" title="WhatsApp">
    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.12-2.9-6.99A9.82 9.82 0 0 0 12.04 2z"/></svg>
  </a>
  @if ($chatActif)
    <button class="fab-action fab-chat" type="button" id="fabChat" aria-label="{{ __('Ouvrir le chat en ligne') }}" title="{{ __('Chat en ligne') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </button>
  @endif
  <button class="fab-trigger" type="button" id="fabTrigger" aria-expanded="false" aria-controls="fabStack" aria-label="{{ __('Nous joindre') }}">
    <svg class="fab-ico-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    <svg class="fab-ico-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
  </button>
</div>
