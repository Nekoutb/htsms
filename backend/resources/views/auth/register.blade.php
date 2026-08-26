@extends('layouts.auth')
@section('title','Create account')
@section('content')
<div class="onboarding-steps" aria-label="Onboarding progress"><span class="active">1. Account</span><span>2. Check email</span><span>3. Sign in</span></div>
<div class="form-heading"><span>Start building</span><h2>Create your account</h2><p>We’ll email you a secure sign-in link. There’s no password to choose or remember.</p></div>
<form method="POST" action="{{ route('register') }}" class="stack-form">@csrf
<label>Full name<input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus></label>
<label>Work email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
@if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
@include('partials.turnstile')
<button class="button full" type="submit">Create account</button></form>
<p class="form-foot">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
