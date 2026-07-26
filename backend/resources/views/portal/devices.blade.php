@extends('layouts.portal')
@section('title', __('Devices')) @section('heading', __('Android gateways'))
@section('actions')<form method="POST" action="{{ route('portal.devices.pair',$organization) }}">@csrf<button class="button small">{{ __('Connect a phone') }}</button></form>@endsection
@section('content')
<section class="panel android-download"><div><span class="eyebrow">{{ __('Android gateway · Beta') }}</span><h2>{{ __('Connect your phone in three steps') }}</h2><ol class="pairing-steps"><li><b>1</b><span><strong>{{ __('Install HTSMS Gateway') }}</strong><small>{{ __('Android 8.0 or newer. Android may ask you to approve installation from your browser.') }}</small></span></li><li><b>2</b><span><strong>{{ __('Open the app and tap “Scan QR code”') }}</strong><small>{{ __('The app explains each required Phone, SMS, Camera and Notification permission before requesting it.') }}</small></span></li><li><b>3</b><span><strong>{{ __('Scan the secure QR shown below') }}</strong><small>{{ __('Confirm the workspace and phone. Connection then completes automatically.') }}</small></span></li></ol><small>{{ __('Signed release · HTTPS only · credentials protected by Android Keystore') }}</small></div><div class="download-actions"><a class="button" href="{{ asset(config('htsms.apk.path')) }}" download>{{ __('Download Android APK') }} v{{ config('htsms.apk.version') }}</a><a href="{{ asset(config('htsms.apk.checksum_path')) }}" download>{{ __('Verify checksum') }}</a></div></section>
@if(session('pairing_uri'))<section class="pairing-card"><div class="pairing-qr"><canvas data-pairing-qr="{{ session('pairing_uri') }}" aria-label="{{ __('HTSMS secure pairing QR code') }}"></canvas></div><div><span class="eyebrow" style="color:var(--signal-bright)">{{ __('One use · Expires in 10 minutes') }}</span><h2>{{ __('Scan to connect this phone') }}</h2><p>{{ __('In the HTSMS Gateway app, tap Scan QR code and point the camera here. Do not share this QR: it authorizes one phone for :name.', ['name' => $organization->name]) }}</p><div class="fallback-code"><span>{{ __('Cannot scan? Enter this code') }}</span><strong id="pairing-secret">{{ session('pairing_code') }}</strong><button type="button" data-copy="#pairing-secret" data-copied-label="{{ __('Copied') }}">{{ __('Copy code') }}</button></div></div></section>@endif
<div class="device-grid">@forelse($devices as $device)
<article class="device-card">
<div class="device-head"><div class="device-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg></div><div><h2>{{ $device->name }}</h2><span>{{ $device->manufacturer }} {{ $device->model }}</span></div><span class="status {{ $device->isOnline() ? 'online' : ($device->revoked_at ? 'revoked' : 'offline') }}">{{ $device->isOnline() ? __('online') : ($device->revoked_at ? __('revoked') : __('offline')) }}</span></div>
<dl><div><dt>{{ __('Battery') }}</dt><dd>{{ $device->battery_percent === null ? '—' : $device->battery_percent.'%' }}</dd></div><div><dt>{{ __('Connection') }}</dt><dd>{{ $device->connection_type ?? __('Unknown') }}</dd></div><div><dt>{{ __('Last seen') }}</dt><dd>{{ $device->last_seen_at?->diffForHumans() ?? __('Never') }}</dd></div><div><dt>{{ __('App') }}</dt><dd>v{{ $device->app_version }}</dd></div></dl>
<div class="sims">@forelse($device->simSlots as $sim)
@php($num = $sim->phone_number)
@php($rid = 'sim-'.$device->id.'-'.$sim->slot_index)
<span><b>{{ __('SIM') }} {{ $sim->slot_index + 1 }}</b>{{ $sim->carrier_name ?? __('Unknown carrier') }} ·
@if($num)
<span class="sim-number"><code id="{{ $rid }}" data-value="{{ $num }}" data-mask="{{ \Illuminate\Support\Str::mask($num, '•', 3, max(mb_strlen($num) - 6, 0)) }}" data-shown="0">{{ \Illuminate\Support\Str::mask($num, '•', 3, max(mb_strlen($num) - 6, 0)) }}</code><button type="button" class="reveal-btn" data-reveal="#{{ $rid }}" data-show-label="{{ __('Reveal') }}" data-hide-label="{{ __('Hide') }}">{{ __('Reveal') }}</button></span>
@else
<span title="{{ __('This SIM/carrier did not expose its own number to Android, so HTSMS cannot display it.') }}">{{ __('Number not reported by SIM') }}</span>
@endif
</span>
@empty<span>{{ __('No SIM reported') }}</span>@endforelse</div>
<div class="device-actions">
@if(!$device->revoked_at)<form method="POST" action="{{ route('portal.devices.revoke',[$organization,$device]) }}" data-confirm="{{ __('Revoke this phone immediately? It stops sending right away.') }}">@csrf @method('DELETE')<button class="danger-link">{{ __('Revoke access') }}</button></form>@endif
<form method="POST" action="{{ route('portal.devices.delete',[$organization,$device]) }}" data-confirm="{{ __('Permanently delete this device record? This cannot be undone.') }}">@csrf @method('DELETE')<button class="danger-link">{{ __('Delete record') }}</button></form>
</div>
</article>
@empty
<section class="empty-state"><div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg></div><h2>{{ __('No phones connected') }}</h2><p>{{ __('Create a secure QR, then scan it with the HTSMS Gateway app. A short code is also provided if the camera is unavailable.') }}</p><form method="POST" action="{{ route('portal.devices.pair',$organization) }}">@csrf<button class="button">{{ __('Create secure QR code') }}</button></form></section>
@endforelse</div>
@endsection
