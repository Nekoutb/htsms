{{-- Shared document head: icons, viewport, description and social cards.
     Pass $description to override the default; $noindex hides the page from crawlers. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@if(app()->bound('session') && app('session')->isStarted())<meta name="csrf-token" content="{{ csrf_token() }}">@endif
<meta name="theme-color" content="#e2382b">
<meta name="description" content="{{ $description ?? __('ui.meta_description') }}">
@if($noindex ?? false)<meta name="robots" content="noindex, nofollow">@endif
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}" sizes="16x16 32x32 48x48">
<link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">
<meta property="og:site_name" content="EA HTSMS">
<meta property="og:type" content="website">
<meta property="og:locale" content="{{ app()->isLocale('fr') ? 'fr_FR' : 'en_GB' }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="{{ $ogTitle ?? 'EA HTSMS' }}">
<meta property="og:description" content="{{ $description ?? __('ui.meta_description') }}">
<meta property="og:image" content="{{ asset('brand/og-image.svg') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle ?? 'EA HTSMS' }}">
<meta name="twitter:description" content="{{ $description ?? __('ui.meta_description') }}">
<meta name="twitter:image" content="{{ asset('brand/og-image.svg') }}">
@php($structuredData = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'EA HTSMS',
    'legalName' => 'Elite Advisors',
    'url' => url('/'),
    'logo' => asset('brand/apple-touch-icon.png'),
    'description' => __('ui.meta_description'),
    'email' => config('app.support_email'),
    'areaServed' => 'CM',
])
<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
