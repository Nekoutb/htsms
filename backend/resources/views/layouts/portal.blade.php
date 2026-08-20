<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head', ['ogTitle' => trim($__env->yieldContent('title', 'EA HTSMS')).' · EA HTSMS', 'noindex' => true])
    <title>@yield('title', __('ui.workspace')) · EA HTSMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal">
<a class="skip-link" href="#portal-content">{{ __('ui.skip_to_content') }}</a>
<header class="portal-topbar">
    <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="portal-sidebar" aria-label="{{ __('ui.menu') }}"><span></span></button>
    <a class="brand brand-lockup" href="{{ route('portal.overview',$organization) }}"><img src="{{ asset('brand/ea-mark.svg') }}" alt="" width="27" height="22"><span><b>ELITE ADVISORS</b><small>HTSMS</small></span></a>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="topbar-signout" title="{{ __('ui.sign_out') }}" aria-label="{{ __('ui.sign_out') }}">↪</button></form>
</header>
<div class="nav-scrim" data-nav-close hidden></div>
<aside class="sidebar" id="portal-sidebar">
    <a class="brand brand-lockup" href="{{ route('portal.home') }}"><img src="{{ asset('brand/ea-mark.svg') }}" alt="" width="27" height="22"><span><b>ELITE ADVISORS</b><small>HTSMS</small></span></a>
    <button class="nav-close" type="button" data-nav-close aria-label="{{ __('ui.close_menu') }}">✕</button>
    <div class="workspace"><small>{{ __('ui.workspace') }}</small><strong>{{ $organization->name }}</strong><span>{{ $organization->slug }}</span></div>
    <nav class="side-nav" aria-label="Primary">
        <a class="{{ request()->routeIs('portal.overview') ? 'active' : '' }}" href="{{ route('portal.overview',$organization) }}"><i>⌂</i>{{ __('ui.overview') }}</a>
        <a class="{{ request()->routeIs('portal.messages*') ? 'active' : '' }}" href="{{ route('portal.messages',$organization) }}"><i>↗</i>{{ __('ui.messages') }}</a>
        <a class="{{ request()->routeIs('portal.devices*') ? 'active' : '' }}" href="{{ route('portal.devices',$organization) }}"><i>▣</i>{{ __('ui.devices') }}</a>
        <a class="{{ request()->routeIs('portal.developer*') ? 'active' : '' }}" href="{{ route('portal.developer',$organization) }}"><i>⌘</i>{{ __('ui.developer') }}</a>
        <a class="{{ request()->routeIs('portal.billing*') ? 'active' : '' }}" href="{{ route('portal.billing',$organization) }}"><i>◇</i>{{ __('ui.billing') }}</a>
        <a class="{{ request()->routeIs('portal.settings*') ? 'active' : '' }}" href="{{ route('portal.settings',$organization) }}"><i>⚙</i>{{ __('ui.settings') }}</a>
    </nav>
    <div class="side-user">
        <div class="avatar">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</div>
        <div><b>{{ $user->name }}</b><small>{{ $user->email }}</small></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button title="{{ __('ui.sign_out') }}" aria-label="{{ __('ui.sign_out') }}">↪</button></form>
    </div>
</aside>
<main class="portal-main" id="portal-content">
    <header class="portal-top"><div><span class="breadcrumb">EA HTSMS / {{ $organization->name }}</span><h1>@yield('heading')</h1></div><div class="channel-controls"><div class="lang-switch" aria-label="{{ __('ui.language') }}"><a class="{{ app()->isLocale('en') ? 'active' : '' }}" href="{{ route('locale.switch','en') }}">EN</a><a class="{{ app()->isLocale('fr') ? 'active' : '' }}" href="{{ route('locale.switch','fr') }}">FR</a></div>@yield('actions')</div></header>
    @if(session('status'))<div class="flash success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="flash error" role="alert">{{ $errors->first() }}</div>@endif
    @yield('content')
    <p class="portal-foot"><a href="mailto:{{ config('app.support_email') }}">{{ __('ui.support') }}</a>@if(config('app.support_phone')) · <a href="tel:{{ preg_replace('/[^0-9+]/', '', (string) config('app.support_phone')) }}">{{ config('app.support_phone') }}</a>@endif · © {{ date('Y') }} Elite Advisors</p>
</main>
</body>
</html>
