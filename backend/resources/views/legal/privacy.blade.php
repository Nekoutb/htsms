<!doctype html>
<html lang="{{ app()->getLocale() }}">
@php($fr = app()->isLocale('fr'))
<head>
@include('partials.head', ['ogTitle' => 'EA HTSMS · ' . ($fr ? 'Politique de confidentialité' : 'Privacy Policy'), 'description' => $fr ? 'Comment EA HTSMS collecte, utilise et protège les données.' : 'How EA HTSMS collects, uses, and protects data.'])
<title>{{ $fr ? 'Politique de confidentialité' : 'Privacy Policy' }} · EA HTSMS</title>
<link rel="canonical" href="{{ url()->current() }}">
@vite(['resources/css/app.css','resources/js/app.js'])
<style>.legal-content{max-width:760px;padding:60px 0 90px}.legal-content h1{font-family:Georgia,serif;font-size:44px;letter-spacing:-1.5px;margin:14px 0 6px}.legal-content h2{font-family:Georgia,serif;font-size:24px;margin:38px 0 10px}.legal-content p,.legal-content li{color:#3a4a44;line-height:1.7;font-size:15px}.legal-updated{color:var(--muted);font-size:13px}.legal-note{background:#fff7d6;border:1px solid #f0e3a8;color:#6b5c14;padding:12px 15px;border-radius:9px;font-size:13px;margin:22px 0}</style>
</head>
<body class="marketing legal-page">
<a class="skip-link" href="#main">{{ __('ui.skip_to_content') }}</a>
<header class="site-header wrap"><a class="brand" href="{{ route('home') }}" aria-label="EA HTSMS"><img src="{{ asset('brand/ea-mark.svg') }}" alt="" width="27" height="22"><span>EA HTSMS</span></a><nav aria-label="{{ $fr ? 'Navigation principale' : 'Primary' }}"><a href="{{ route('home') }}">{{ $fr ? 'Accueil' : 'Home' }}</a><a href="{{ route('terms') }}">{{ $fr ? 'Conditions' : 'Terms' }}</a><a href="{{ route('login') }}">{{ $fr ? 'Connexion' : 'Sign in' }}</a></nav></header>
<main id="main" class="wrap legal-content">
<span class="eyebrow">{{ $fr ? 'Juridique' : 'Legal' }}</span>
<h1>{{ $fr ? 'Politique de confidentialité' : 'Privacy Policy' }}</h1>
<p class="legal-updated">{{ $fr ? 'Dernière mise à jour' : 'Last updated' }}: {{ config('htsms.legal.updated') }}</p>
<div class="legal-note">{{ $fr ? 'Modèle provisoire à faire réviser par un conseiller juridique avant publication définitive.' : 'Draft template. Have this reviewed by legal counsel before treating it as final.' }}</div>

<h2>1. {{ $fr ? 'Données que nous collectons' : 'Data we collect' }}</h2>
<p>{{ $fr ? "Informations de compte (nom, e-mail), métadonnées des messages (destinataire, statut de livraison, horodatage), et informations techniques de l'appareil apparié (modèle, opérateur, état de connexion)." : 'Account information (name, email), message metadata (recipient, delivery status, timestamps), and technical information about the paired device (model, carrier, connection status).' }}</p>

<h2>2. {{ $fr ? 'Utilisation des données' : 'How we use data' }}</h2>
<p>{{ $fr ? 'Pour fournir le Service, acheminer et suivre les messages, sécuriser les comptes, et respecter nos obligations légales.' : 'To provide the Service, route and track messages, secure accounts, and meet our legal obligations.' }}</p>

<h2>3. {{ $fr ? 'Conservation' : 'Retention' }}</h2>
<p>{{ $fr ? "Les données sont conservées le temps nécessaire à la fourniture du Service et aux obligations légales, puis supprimées ou anonymisées." : 'Data is kept for as long as needed to provide the Service and meet legal obligations, then deleted or anonymised.' }}</p>

<h2>4. {{ $fr ? 'Partage' : 'Sharing' }}</h2>
<p>{{ $fr ? "Nous ne vendons pas vos données. Nous les partageons uniquement avec les prestataires nécessaires au fonctionnement du Service (par exemple l'envoi d'e-mails transactionnels) et lorsque la loi l'exige." : 'We do not sell your data. We share it only with providers necessary to run the Service (for example, transactional email delivery) and where required by law.' }}</p>

<h2>5. {{ $fr ? 'Sécurité' : 'Security' }}</h2>
<p>{{ $fr ? 'Chiffrement en transit (HTTPS), mots de passe hachés, clés API à portée limitée et contrôles d\'accès par espace de travail.' : 'Encryption in transit (HTTPS), hashed passwords, scoped API keys, and per-workspace access controls.' }}</p>

<h2>6. {{ $fr ? 'Vos droits' : 'Your rights' }}</h2>
<p>{{ $fr ? "Vous pouvez consulter, corriger ou supprimer vos données depuis votre espace, ou en nous contactant." : 'You can access, correct, or delete your data from your workspace, or by contacting us.' }}</p>

<h2>7. {{ $fr ? 'Contact' : 'Contact' }}</h2>
<p>{{ $fr ? 'Pour toute question relative à la confidentialité' : 'Privacy questions' }}: <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>.</p>
</main>
@include('partials.site-footer')
</body>
</html>
