@extends('layouts.auth')
@section('title','Reset your password')
@section('content')
<div class="form-heading"><span>Account recovery</span><h2>Reset your password</h2><p>Enter your account email and we will send a secure reset link.</p></div>
<form method="POST" action="{{ route('password.email') }}" class="stack-form">@csrf
<label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
@if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
<button class="button full" type="submit">Email me a reset link</button></form>
<p class="form-foot"><a href="{{ route('login') }}">← Back to sign in</a></p>
@endsection
