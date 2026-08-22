@extends('layouts.auth')
@section('title','Admin verification')
@section('content')
<div class="form-heading"><span>Protected area</span><h2>Verify admin access</h2><p>Request a single-use code, then enter it below. Access remains verified for eight hours.</p></div>
@if(session('status'))<div class="form-success">{{ session('status') }}</div>@endif
@error('mfa')<div class="form-error">{{ $message }}</div>@enderror
<form method="POST" action="{{ route('admin.mfa.send') }}" class="stack-form">@csrf
<button class="button secondary full" type="submit">Email me a code</button></form>
<form method="POST" action="{{ route('admin.mfa.verify') }}" class="stack-form">@csrf
<label>Six-digit code<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus></label>
@error('code')<div class="form-error">{{ $message }}</div>@enderror
<button class="button full" type="submit">Verify and continue</button></form>
@endsection
