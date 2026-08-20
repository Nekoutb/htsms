<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head', ['ogTitle' => trim($__env->yieldContent('title', 'EA HTSMS')).' · EA HTSMS', 'noindex' => true])
    <title>@yield('title', app()->isLocale('fr') ? 'Administration' : 'Platform operations') · EA HTSMS</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="admin-page">
<a class="skip-link" href="#admin-content">{{ __('ui.skip_to_content') }}</a>
<header class="admin-header">
    <a class="brand brand-lockup" href="{{ route('admin.index') }}"><img src="{{ asset('brand/ea-mark.svg') }}" alt="" width="27" height="22"><span><b>ELITE ADVISORS</b><small>HTSMS</small></span></a>
    <div><b>{{ app()->isLocale('fr') ? 'Administration' : 'Platform operations' }}</b><a href="{{ route('portal.home') }}">{{ app()->isLocale('fr') ? 'Portail client' : 'Customer portal' }}</a><div class="lang-switch" aria-label="{{ __('ui.language') }}"><a class="{{ app()->isLocale('en') ? 'active' : '' }}" href="{{ route('locale.switch','en') }}">EN</a><a class="{{ app()->isLocale('fr') ? 'active' : '' }}" href="{{ route('locale.switch','fr') }}">FR</a></div><form method="POST" action="{{ route('logout') }}">@csrf<button>{{ __('ui.sign_out') }}</button></form></div>
</header>
<main class="admin-main" id="admin-content">
    @if(session('status'))<div class="flash success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="flash error" role="alert">{{ $errors->first() }}</div>@endif
    <header class="admin-title"><div><span class="eyebrow">EA HTSMS</span><h1>@yield('heading')</h1></div>@yield('actions')</header>
    @yield('content')
    <p class="portal-foot"><a href="mailto:{{ config('app.support_email') }}">{{ __('ui.support') }}</a>@if(config('app.support_phone')) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) config('app.support_phone')) }}">{{ config('app.support_phone') }}</a>@endif · © {{ date('Y') }} Elite Advisors</p>
</main>
</body>
</html>
