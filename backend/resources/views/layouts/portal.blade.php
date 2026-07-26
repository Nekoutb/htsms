<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0c1f18">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <title>@yield('title') · EA HTSMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal">
<aside class="sidebar">
    <a class="brand brand-lockup" href="{{ route('portal.home') }}"><img src="{{ asset('brand/ea-mark.svg') }}" alt=""><span><b>ELITE ADVISORS</b><small>HTSMS</small></span></a>
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
<main class="portal-main">
    <header class="portal-top"><div><span class="breadcrumb">EA HTSMS / {{ $organization->name }}</span><h1>@yield('heading')</h1></div><div class="channel-controls"><div class="lang-switch" aria-label="{{ __('ui.language') }}"><a class="{{ app()->isLocale('en') ? 'active' : '' }}" href="{{ route('locale.switch','en') }}">EN</a><a class="{{ app()->isLocale('fr') ? 'active' : '' }}" href="{{ route('locale.switch','fr') }}">FR</a></div>@yield('actions')</div></header>
    @if(session('status'))<div class="flash success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="flash error">{{ $errors->first() }}</div>@endif
    @yield('content')
</main>
</body></html>
