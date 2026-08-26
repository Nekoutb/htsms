@extends('layouts.auth')
@section('title','Sign in')
@section('content')
<div class="form-heading"><span>Welcome back</span><h2>Sign in to HTSMS</h2><p>Enter your email and we’ll send you a secure sign-in link. No password needed.</p></div>
<form method="POST" action="{{ route('login') }}" class="stack-form">@csrf
<label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
@if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
@include('partials.turnstile')
<button class="button full" type="submit">Email me a sign-in link</button></form>
<p class="form-foot">New to HTSMS? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
