@extends('layouts.auth')
@section('title','Create account')
@section('content')
<div class="onboarding-steps" aria-label="Onboarding progress"><span class="active">1. Account</span><span>2. Confirm email</span><span>3. Sign in</span></div>
<div class="form-heading"><span>Start building</span><h2>Create your account</h2><p>You’ll verify your email before accessing the dashboard.</p></div>
<form method="POST" action="{{ route('register') }}" class="stack-form">@csrf
<label>Full name<input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus></label>
<label>Work email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
<label>Password<input type="password" name="password" autocomplete="new-password" required><small>12+ characters with uppercase, lowercase, number, and symbol.</small></label>
<label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" required></label>
@if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
<button class="button full" type="submit">Create account and continue</button></form>
<p class="form-foot">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
