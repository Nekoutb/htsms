@extends('layouts.portal')
@section('title', __('Settings')) @section('heading', __('Settings'))
@section('content')
<div class="dashboard-grid">
<section class="panel"><div class="panel-head"><div><span>{{ __('Your profile') }}</span><h2>{{ __('Account') }}</h2></div></div>
<form method="POST" action="{{ route('portal.settings.profile',$organization) }}" class="stack-form compact">@csrf
<label>{{ __('Full name') }}<input name="name" value="{{ old('name',$user->name) }}" required></label>
<label>{{ __('Email') }}<input value="{{ $user->email }}" disabled></label>
<label>{{ __('Role in this workspace') }}<input value="{{ ucfirst(str_replace('_',' ', $role?->value ?? 'member')) }}" disabled></label>
<button class="button">{{ __('Save profile') }}</button></form></section>

<section class="panel span-2"><div class="panel-head"><div><span>{{ __('Workspace') }}</span><h2>{{ $organization->name }}</h2></div></div>
@if($canManageWorkspace)
<form method="POST" action="{{ route('portal.settings.workspace',$organization) }}" class="stack-form compact">@csrf
<div class="form-grid"><label>{{ __('Business name') }}<input name="name" value="{{ old('name',$organization->name) }}" required></label>
<label>{{ __('Language') }}<select name="locale"><option value="en" @selected($organization->locale==='en')>English</option><option value="fr" @selected($organization->locale==='fr')>Français</option></select></label></div>
<label>{{ __('Workspace ID') }}<input value="{{ $organization->slug }}" disabled><small>{{ __('Used in dashboard links and cannot be changed after creation.') }}</small></label>
<button class="button">{{ __('Save workspace settings') }}</button></form>
@else
<p style="color:var(--muted);font-size:var(--fs-sm)">{{ __('Only workspace owners and administrators can change these settings.') }}</p>
<dl class="device-card" style="border:0;padding:0;margin:0;display:grid;grid-template-columns:repeat(2,1fr);gap:14px"><div><dt>{{ __('Language') }}</dt><dd>{{ $organization->locale === 'fr' ? 'Français' : 'English' }}</dd></div><div><dt>{{ __('Workspace ID') }}</dt><dd>{{ $organization->slug }}</dd></div></dl>
@endif
</section>
</div>

<section class="panel" style="border-color:color-mix(in srgb, var(--err) 35%, var(--line))">
<div class="panel-head"><div><span style="color:var(--err)">{{ __('Danger zone') }}</span><h2>{{ __('Delete data') }}</h2></div></div>
<div class="key-list">
@if($canDeleteWorkspace)
<article><div><strong>{{ __('Delete this workspace') }}</strong><small>{{ __('Permanently removes :name and all its devices, messages, keys, contacts, and campaigns. This cannot be undone.', ['name' => $organization->name]) }}</small></div>
<details class="danger-details"><summary class="danger-link">{{ __('Delete workspace') }}</summary>
<form method="POST" action="{{ route('portal.settings.workspace.delete',$organization) }}" class="stack-form compact" data-confirm="{{ __('Delete this workspace and all its data permanently?') }}" style="margin-top:12px">@csrf @method('DELETE')
<label>{{ __('Type the workspace ID to confirm') }}<input name="confirm" placeholder="{{ $organization->slug }}" autocomplete="off" required></label>
<button class="button" style="background:var(--err)">{{ __('Permanently delete workspace') }}</button></form></details></article>
@endif
<article><div><strong>{{ __('Delete my account') }}</strong><small>{{ __('Removes your login and personal data. Workspaces you solely own are deleted with it; you must first transfer or delete workspaces shared with others.') }}</small></div>
<details class="danger-details"><summary class="danger-link">{{ __('Delete account') }}</summary>
<form method="POST" action="{{ route('portal.settings.account.delete',$organization) }}" class="stack-form compact" data-confirm="{{ __('Delete your account permanently?') }}" style="margin-top:12px">@csrf @method('DELETE')
<label>{{ __('Confirm your password') }}<input type="password" name="password" autocomplete="current-password" required></label>
<button class="button" style="background:var(--err)">{{ __('Permanently delete my account') }}</button></form></details></article>
</div>
</section>
@endsection
