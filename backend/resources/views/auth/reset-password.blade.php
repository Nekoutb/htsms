@extends('layouts.auth')
@section('title','Choose a new password')
@section('content')
<div class="form-heading"><span>Account recovery</span><h2>Choose a new password</h2><p>This link works once and expires quickly for your security.</p></div>
<form method="POST" action="{{ route('password.update') }}" class="stack-form">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<label>Email address<input type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required></label>
<label>New password<input type="password" name="password" autocomplete="new-password" required autofocus><small>12+ characters with uppercase, lowercase, number, and symbol.</small></label>
<label>Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" required></label>
@if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
<button class="button full" type="submit">Save new password</button></form>
<p class="form-foot">Link expired? <a href="{{ route('password.request') }}">Request a new one</a></p>
@endsection
