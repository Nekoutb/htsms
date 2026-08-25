<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head', ['ogTitle' => trim($__env->yieldContent('title', 'EA HTSMS')).' · EA HTSMS', 'noindex' => true])
    <title>@yield('title', app()->isLocale('fr') ? 'Compte' : 'Account') · EA HTSMS</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="auth-page">@php($fr = app()->isLocale('fr'))
<a class="skip-link" href="#auth-form">{{ __('ui.skip_to_content') }}</a>
<a class="brand auth-brand" href="{{ route('home') }}" aria-label="EA HTSMS · {{ __('ui.home') }}"><img src="{{ asset('brand/ea-mark.svg') }}" alt="" width="27" height="22"><span>EA HTSMS</span></a>
<div class="auth-lang lang-switch" aria-label="{{ __('ui.language') }}"><a class="{{ !$fr ? 'active' : '' }}" href="{{ route('locale.switch','en') }}">EN</a><a class="{{ $fr ? 'active' : '' }}" href="{{ route('locale.switch','fr') }}">FR</a></div>
<main class="auth-shell"><section class="auth-story"><span class="eyebrow">{{ $fr ? 'Votre SIM. Votre passerelle.' : 'Your SIM. Your gateway.' }}</span><h1>{{ $fr ? 'Communiquez avec vos clients grâce à une infrastructure que vous contrôlez.' : 'Message customers from infrastructure you control.' }}</h1><p>{{ $fr ? 'Associez un téléphone Android, créez une clé API sécurisée et suivez chaque message.' : 'Pair an Android phone, issue a secure API key, and follow every message through delivery.' }}</p><div class="auth-proof"><span>✓</span><div><b>{{ $fr ? 'Isolation par entreprise' : 'Tenant-isolated by default' }}</b><small>{{ $fr ? 'Chaque appareil, clé et message appartient à un seul espace.' : 'Every device, key, and message belongs to one workspace.' }}</small></div></div></section><section class="auth-card" id="auth-form">@if(request()->boolean('verified'))<div class="flash success">{{ $fr ? 'Adresse e-mail vérifiée. Vous pouvez vous connecter.' : 'Email address verified successfully. You can now sign in.' }}</div>@elseif(session('status'))<div class="flash success">{{ session('status') }}</div>@endif @yield('content')<p class="auth-help"><a href="mailto:{{ config('app.support_email') }}">{{ __('ui.support') }}</a>@if(config('app.support_phone')) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) config('app.support_phone')) }}">{{ config('app.support_phone') }}</a>@endif · © {{ date('Y') }} Elite Advisors</p></section></main>
</body>
</html>
