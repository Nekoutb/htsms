<!doctype html>
<html lang="{{ app()->getLocale() }}">
@php($fr = app()->isLocale('fr'))
<head>
@include('partials.head', ['ogTitle' => 'EA HTSMS · ' . ($fr ? "Conditions d'utilisation" : 'Terms of Service'), 'description' => $fr ? "Les conditions régissant l'utilisation du service de passerelle SMS EA HTSMS." : 'The terms governing use of the EA HTSMS SMS gateway service.'])
<title>{{ $fr ? "Conditions d'utilisation" : 'Terms of Service' }} · EA HTSMS</title>
<link rel="canonical" href="{{ url()->current() }}">
@vite(['resources/css/app.css','resources/js/app.js'])
<style>.legal-content{max-width:760px;padding:60px 0 90px}.legal-content h1{font-family:Georgia,serif;font-size:44px;letter-spacing:-1.5px;margin:14px 0 6px}.legal-content h2{font-family:Georgia,serif;font-size:24px;margin:38px 0 10px}.legal-content p,.legal-content li{color:#3a4a44;line-height:1.7;font-size:15px}.legal-updated{color:var(--muted);font-size:13px}.legal-note{background:#fff7d6;border:1px solid #f0e3a8;color:#6b5c14;padding:12px 15px;border-radius:9px;font-size:13px;margin:22px 0}</style>
</head>
<body class="marketing legal-page">
<a class="skip-link" href="#main">{{ __('ui.skip_to_content') }}</a>
<header class="site-header wrap"><a class="brand" href="{{ route('home') }}" aria-label="EA HTSMS"><img src="{{ asset('brand/ea-mark.svg') }}" alt="" width="27" height="22"><span>EA HTSMS</span></a><nav aria-label="{{ $fr ? 'Navigation principale' : 'Primary' }}"><a href="{{ route('home') }}">{{ $fr ? 'Accueil' : 'Home' }}</a><a href="{{ route('privacy') }}">{{ $fr ? 'Confidentialité' : 'Privacy' }}</a><a href="{{ route('login') }}">{{ $fr ? 'Connexion' : 'Sign in' }}</a></nav></header>
<main id="main" class="wrap legal-content">
<span class="eyebrow">{{ $fr ? 'Juridique' : 'Legal' }}</span>
<h1>{{ $fr ? "Conditions d'utilisation" : 'Terms of Service' }}</h1>
<p class="legal-updated">{{ $fr ? 'Dernière mise à jour' : 'Last updated' }}: {{ config('htsms.legal.updated') }}</p>
<div class="legal-note">{{ $fr ? 'Modèle provisoire à faire réviser par un conseiller juridique avant publication définitive.' : 'Draft template. Have this reviewed by legal counsel before treating it as final.' }}</div>

<h2>1. {{ $fr ? 'Acceptation des conditions' : 'Acceptance of terms' }}</h2>
<p>{{ $fr ? "En créant un compte ou en utilisant EA HTSMS (le « Service »), exploité par Elite Advisors, vous acceptez ces conditions." : 'By creating an account or using EA HTSMS (the "Service"), operated by Elite Advisors, you agree to these terms.' }}</p>

<h2>2. {{ $fr ? 'Description du service' : 'The service' }}</h2>
<p>{{ $fr ? "EA HTSMS permet d'utiliser un téléphone Android et une carte SIM que vous possédez comme passerelle SMS programmable. Vous êtes responsable de l'appareil, de la SIM et du respect des conditions de votre opérateur." : 'EA HTSMS lets you use an Android phone and SIM card that you own as a programmable SMS gateway. You are responsible for the device, the SIM, and compliance with your mobile carrier terms.' }}</p>

<h2>3. {{ $fr ? 'Comptes' : 'Accounts' }}</h2>
<p>{{ $fr ? "Vous devez fournir des informations exactes, protéger vos identifiants et clés API, et nous informer de tout accès non autorisé." : 'You must provide accurate information, keep your credentials and API keys secure, and notify us of any unauthorised access.' }}</p>

<h2>4. {{ $fr ? 'Utilisation acceptable' : 'Acceptable use' }}</h2>
<p>{{ $fr ? "Vous vous engagez à ne pas envoyer de messages illégaux, frauduleux ou non sollicités (spam), ni à contourner le consentement des destinataires ou la réglementation applicable." : 'You agree not to send unlawful, fraudulent, or unsolicited messages (spam), and not to bypass recipient consent or applicable regulations.' }}</p>

<h2>5. {{ $fr ? 'Abonnements et paiements' : 'Subscriptions and payments' }}</h2>
<p>{{ $fr ? "Les fonctionnalités payantes sont facturées selon le forfait choisi. Les modalités détaillées de facturation seront précisées ici." : 'Paid features are billed according to your chosen plan. Detailed billing terms will be specified here.' }}</p>

<h2>6. {{ $fr ? 'Disponibilité et responsabilité' : 'Availability and liability' }}</h2>
<p>{{ $fr ? "Le Service est fourni « en l'état ». Dans les limites permises par la loi, Elite Advisors décline toute responsabilité pour les dommages indirects ou la perte de messages liés à la connectivité de l'appareil." : 'The Service is provided "as is". To the extent permitted by law, Elite Advisors is not liable for indirect damages or message loss arising from device connectivity.' }}</p>

<h2>7. {{ $fr ? 'Résiliation' : 'Termination' }}</h2>
<p>{{ $fr ? "Vous pouvez fermer votre compte à tout moment. Nous pouvons suspendre un compte en cas de violation de ces conditions." : 'You may close your account at any time. We may suspend accounts that violate these terms.' }}</p>

<h2>8. {{ $fr ? 'Contact' : 'Contact' }}</h2>
<p>{{ $fr ? 'Pour toute question' : 'Questions' }}: <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>.</p>
</main>
@include('partials.site-footer')
</body>
</html>
