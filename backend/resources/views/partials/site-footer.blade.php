{{-- Public site footer: clickable brand, reachable contacts, live copyright year. --}}
@php($supportPhone = config('app.support_phone'))
<footer class="site-footer">
    <div class="wrap site-footer-inner">
        <div class="site-footer-brand">
            <a class="brand" href="{{ route('home') }}" aria-label="EA HTSMS · {{ __('ui.home') }}"><img src="{{ asset('brand/ea-mark.svg') }}" alt="" width="27" height="22"><span>EA HTSMS</span></a>
            <p>© {{ date('Y') }} Elite Advisors. {{ app()->isLocale('fr') ? 'Logiciel propriétaire.' : 'Proprietary software.' }}</p>
        </div>
        <nav class="site-footer-nav" aria-label="{{ app()->isLocale('fr') ? 'Pied de page' : 'Footer' }}">
            <a href="{{ route('home') }}#how">{{ app()->isLocale('fr') ? 'Fonctionnement' : 'How it works' }}</a>
            <a href="{{ route('home') }}#pricing">{{ app()->isLocale('fr') ? 'Tarifs' : 'Pricing' }}</a>
            <a href="{{ asset(config('htsms.apk.path')) }}" download>{{ app()->isLocale('fr') ? 'Application Android' : 'Android app' }}</a>
            <a href="{{ route('terms') }}">{{ app()->isLocale('fr') ? "Conditions" : 'Terms' }}</a>
            <a href="{{ route('privacy') }}">{{ app()->isLocale('fr') ? 'Confidentialité' : 'Privacy' }}</a>
            <a href="{{ route('login') }}">{{ app()->isLocale('fr') ? 'Connexion' : 'Sign in' }}</a>
        </nav>
        <div class="site-footer-contact">
            <a class="support-link" href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>
            @if($supportPhone)<a class="support-link" href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}">{{ $supportPhone }}</a>@endif
        </div>
    </div>
</footer>
