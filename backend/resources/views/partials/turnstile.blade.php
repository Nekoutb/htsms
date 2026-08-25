{{-- Cloudflare Turnstile widget. Renders only when a site key is configured. --}}
@if(config('services.turnstile.site_key'))
<div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="light"></div>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
