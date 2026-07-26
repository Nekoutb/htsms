<!doctype html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0c1f18">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">
<title>@yield('title') · HTSMS</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal">
<div class="nav-backdrop" data-nav-close></div>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="{{ route('portal.home') }}">@include('partials.logo', ['tone' => 'light'])</a>
    <div class="workspace">
        <small>{{ __('Workspace') }}</small>
        <strong>{{ $organization->name }}</strong>
        <span>{{ $organization->slug }}</span>
    </div>
    <nav class="side-nav">
        <a class="{{ request()->routeIs('portal.overview') ? 'active' : '' }}" href="{{ route('portal.overview',$organization) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>{{ __('Overview') }}</a>
        <a class="{{ request()->routeIs('portal.messages*') ? 'active' : '' }}" href="{{ route('portal.messages',$organization) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l16 8-16 8 3-8-3-8Z"/><path d="M7 12h13"/></svg>{{ __('Messages') }}</a>
        <a class="{{ request()->routeIs('portal.devices*') ? 'active' : '' }}" href="{{ route('portal.devices',$organization) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg>{{ __('Devices') }}</a>
        <a class="{{ request()->routeIs('portal.developer*') ? 'active' : '' }}" href="{{ route('portal.developer',$organization) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 8l-4 4 4 4"/><path d="M16 8l4 4-4 4"/><path d="M13 6l-2 12"/></svg>{{ __('Developer') }}</a>
        @can('manageMarketing',$organization)
        <a class="{{ request()->routeIs('portal.marketing*') ? 'active' : '' }}" href="{{ route('portal.marketing',$organization) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v2a1 1 0 0 0 1 1h2l4 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M15 8a5 5 0 0 1 0 8"/></svg>{{ __('Marketing') }}</a>
        @endcan
        <a class="{{ request()->routeIs('portal.billing*') ? 'active' : '' }}" href="{{ route('portal.billing',$organization) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 9.5h19"/></svg>{{ __('Plan & billing') }}</a>
        <a class="{{ request()->routeIs('portal.settings*') ? 'active' : '' }}" href="{{ route('portal.settings',$organization) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a7 7 0 0 0-1.7-1L14.5 2h-4l-.3 2.6a7 7 0 0 0-1.7 1l-2.4-1-2 3.4L4 9.9a7 7 0 0 0 0 2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 1.7 1l.3 2.6h4l.3-2.6a7 7 0 0 0 1.7-1l2.4 1 2-3.4-2-1.6a7 7 0 0 0 .1-1Z"/></svg>{{ __('Settings') }}</a>
    </nav>
    <div class="side-user">
        <div class="avatar">{{ mb_strtoupper(mb_substr($user->name,0,1)) }}</div>
        <div>
            <b>{{ $user->name }}</b>
            <small>{{ $user->email }}</small>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button title="{{ __('Sign out') }}" aria-label="{{ __('Sign out') }}">
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/></svg>
            </button>
        </form>
    </div>
</aside>
<main class="portal-main">
    <header class="portal-top">
        <div>
            <span class="breadcrumb">HTSMS / {{ $organization->name }}</span>
            <h1>@yield('heading')</h1>
        </div>
        <div class="top-tools">
            @yield('actions')
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="{{ __('Toggle dark mode') }}">
                <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.5 6.5 0 0 0 9.8 9.8Z"/></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5.6 5.6 4.2 4.2M19.8 19.8l-1.4-1.4M18.4 5.6l1.4-1.4M4.2 19.8l1.4-1.4"/></svg>
            </button>
            <button class="nav-toggle" type="button" data-nav-toggle aria-label="{{ __('Open menu') }}" aria-expanded="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </header>
    @yield('content')
</main>
<div class="toast-stack" aria-live="polite">
    @if(session('status'))
    <div class="toast success" role="status">
        <svg class="toast-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        <p>{{ session('status') }}</p>
        <button type="button" data-toast-close aria-label="{{ __('Dismiss') }}">&times;</button>
    </div>
    @endif
    @if($errors->any())
    <div class="toast error" role="alert">
        <svg class="toast-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/></svg>
        <p>{{ $errors->first() }}</p>
        <button type="button" data-toast-close aria-label="{{ __('Dismiss') }}">&times;</button>
    </div>
    @endif
</div>
</body>
</html>
