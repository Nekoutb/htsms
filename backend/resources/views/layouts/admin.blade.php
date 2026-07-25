<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · EA HTSMS</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="admin-page">
<header class="admin-header">
    <a class="brand brand-lockup" href="{{ route('admin.index') }}"><img src="{{ asset('brand/ea-mark.svg') }}" alt=""><span><b>ELITE ADVISORS</b><small>HTSMS</small></span></a>
    <div><b>{{ app()->isLocale('fr') ? 'Administration' : 'Platform operations' }}</b><a href="{{ route('portal.home') }}">{{ app()->isLocale('fr') ? 'Portail client' : 'Customer portal' }}</a><div class="lang-switch"><a class="{{ app()->isLocale('en') ? 'active' : '' }}" href="{{ route('locale.switch','en') }}">EN</a><a class="{{ app()->isLocale('fr') ? 'active' : '' }}" href="{{ route('locale.switch','fr') }}">FR</a></div><form method="POST" action="{{ route('logout') }}">@csrf<button>{{ __('ui.sign_out') }}</button></form></div>
</header>
<main class="admin-main">
    @if(session('status'))<div class="flash success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="flash error">{{ $errors->first() }}</div>@endif
    <header class="admin-title"><div><span class="eyebrow">EA HTSMS</span><h1>@yield('heading')</h1></div>@yield('actions')</header>
    @yield('content')
</main>
</body>
</html>
