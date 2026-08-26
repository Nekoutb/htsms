@extends('layouts.auth')
@section('title','Check your email')
@section('content')
<div class="onboarding-steps" aria-label="Onboarding progress"><span class="complete">1. Account</span><span class="active">2. Check email</span><span>3. Sign in</span></div>
<div class="form-heading"><span>One step left</span><h2>Check your inbox</h2><p>We emailed a secure sign-in link to <b>{{ $email }}</b>. Click it to sign in. The link expires in 15 minutes and can be used once.</p></div>
@if(session('status'))<div class="flash success">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('verification.send') }}" class="stack-form">@csrf
<button class="button full" type="submit">Resend sign-in link</button></form>
<p class="form-foot">No email after a few minutes? Check your spam folder, or contact <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>.</p>
<p class="form-foot"><a href="{{ route('login') }}">← Back to sign in</a></p>
@endsection
