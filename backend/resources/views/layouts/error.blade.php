<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head', ['description' => $description ?? null, 'noindex' => true, 'ogTitle' => $title.' · EA HTSMS'])
    <title>{{ $title }} · EA HTSMS</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="error-page">
<header class="site-header wrap">
    <a class="brand" href="{{ route('home') }}" aria-label="EA HTSMS · {{ __('ui.home') }}"><img src="{{ asset('brand/ea-mark.svg') }}" alt="" width="27" height="22"><span>EA HTSMS</span></a>
    <div class="lang-switch" aria-label="{{ __('ui.language') }}"><a class="{{ app()->isLocale('en') ? 'active' : '' }}" href="{{ route('locale.switch','en') }}">EN</a><a class="{{ app()->isLocale('fr') ? 'active' : '' }}" href="{{ route('locale.switch','fr') }}">FR</a></div>
</header>
<main class="wrap error-shell">
    <span class="error-code">{{ $code }}</span>
    <h1>{{ $title }}</h1>
    <p>{{ $body }}</p>
    <div class="error-actions">
        <a class="button" href="{{ route('home') }}">{{ __('ui.error_home') }}</a>
        <a class="text-link" href="{{ route('login') }}">{{ __('ui.error_signin') }} →</a>
    </div>
    <p class="error-support">{{ __('ui.error_contact') }}: <a class="support-link" href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>@if(config('app.support_phone')) · <a class="support-link" href="tel:{{ preg_replace('/[^0-9+]/', '', (string) config('app.support_phone')) }}">{{ config('app.support_phone') }}</a>@endif</p>
</main>
@include('partials.site-footer')
</body>
</html>
