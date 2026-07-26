@extends('layouts.auth')
@section('title','Sign in')
@section('content')
<div class="form-heading"><span>Welcome back</span><h2>Sign in to HTSMS</h2><p>Use your verified business account.</p></div>
<form method="POST" action="{{ route('login') }}" class="stack-form">@csrf
<label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label>
@if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
<button class="button full" type="submit">Sign in</button></form>
<p class="form-foot"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
<p class="form-foot">New to HTSMS? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
