@extends('layouts.auth')
@section('title','Check your email')
@section('content')
<div class="form-heading"><span>Almost there</span><h2>Check your inbox</h2><p>If an account exists for that email, we’ve sent a secure sign-in link. It expires in 15 minutes and can be used once.</p></div>
<p class="form-foot">No email? Check your spam folder, or <a href="{{ route('login') }}">try again</a>.</p>
<p class="form-foot"><a href="{{ route('home') }}">← Back to home</a></p>
@endsection
