<!doctype html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Platform administration · EA HTSMS</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="admin-page">
<header class="admin-header"><a class="brand" href="{{ route('admin.index') }}"><img src="{{ asset('brand/ea-mark.svg') }}" alt=""><span>EA HTSMS</span></a><div><b>Platform operations</b><a href="{{ route('portal.home') }}">Customer portal</a><div class="lang-switch"><a class="{{ app()->isLocale('en') ? 'active' : '' }}" href="{{ route('locale.switch','en') }}">EN</a><a class="{{ app()->isLocale('fr') ? 'active' : '' }}" href="{{ route('locale.switch','fr') }}">FR</a></div><form method="POST" action="{{ route('logout') }}">@csrf<button>{{ __('ui.sign_out') }}</button></form></div></header>
<main class="admin-main">
@if(session('status'))<div class="flash success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="flash error">{{ $errors->first() }}</div>@endif
<h1>Platform administration</h1><p class="lead">Customer onboarding, subscriptions, messaging channels and account safety.</p>

<section class="panel"><div class="panel-head"><div><span>Customer management</span><h2>Onboard a customer</h2></div></div>
<form method="POST" action="{{ route('admin.users.store') }}" class="onboard-grid">@csrf
<label>Name<input name="name" value="{{ old('name') }}" required></label>
<label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
<label>Business name<input name="business_name" value="{{ old('business_name') }}" required></label>
<label>Language<select name="locale"><option value="en">English</option><option value="fr">Français</option></select></label>
<button class="button">Send invitation</button></form></section>

<section class="panel"><div class="panel-head"><div><span>Managed accounts</span><h2>Onboarded customers</h2></div><small>{{ $onboardedUsers->count() }}</small></div>
<div class="table-wrap"><table><thead><tr><th>Customer</th><th>Email</th><th>Workspaces</th><th>Action</th></tr></thead><tbody>
@forelse($onboardedUsers as $customer)<tr><td><strong>{{ $customer->name }}</strong></td><td>{{ $customer->email }}</td><td>{{ $customer->memberships_count }}</td><td>@if($customer->onboarded_by_user_id === auth()->id())<form method="POST" action="{{ route('admin.users.destroy',$customer) }}" data-confirm="Permanently delete this customer and any workspace they solely own?">@csrf @method('DELETE')<button class="danger-link">Delete account</button></form>@else<span class="status queued">Managed by another admin</span>@endif</td></tr>@empty<tr><td colspan="4" class="empty">No customers have been onboarded from the admin platform.</td></tr>@endforelse
</tbody></table></div></section>

<section class="panel"><div class="panel-head"><div><span>Action required</span><h2>Pending plan requests</h2></div><b>{{ $pendingRequests->count() }}</b></div><div class="admin-requests">@forelse($pendingRequests as $change)<article><div><strong>{{ $change->organization?->name }}</strong><span>requests {{ ucfirst($change->requested_plan) }} · {{ $change->created_at?->diffForHumans() }}</span></div><form method="POST" action="{{ route('admin.requests.approve',$change) }}">@csrf<button class="button small">Confirm payment & activate</button></form><form method="POST" action="{{ route('admin.requests.reject',$change) }}">@csrf<button class="danger-link">Reject</button></form></article>@empty<div class="empty">No subscription requests pending.</div>@endforelse</div></section>

<section class="panel"><div class="panel-head"><div><span>Tenants</span><h2>Business workspaces</h2></div><small>{{ $organizations->total() }} total</small></div><div class="table-wrap"><table><thead><tr><th>Workspace</th><th>Plan</th><th>Usage</th><th>Devices</th><th>Messaging</th><th>Safety</th></tr></thead><tbody>
@foreach($organizations as $org)<tr><td><strong>{{ $org->name }}</strong><small class="block">{{ $org->slug }}</small></td><td><span class="status {{ $org->subscription?->status === 'active' ? 'delivered' : 'queued' }}">{{ $org->subscription?->plan ?? 'none' }} / {{ $org->subscription?->status ?? 'unconfigured' }}</span></td><td>{{ number_format($org->messages_count) }} messages</td><td>{{ $org->devices_count }}</td><td><div class="channel-controls"><form method="POST" action="{{ route('admin.organizations.channels',[$org,'inbound']) }}">@csrf<button class="{{ $org->inbound_enabled ? 'enabled' : '' }}">Inbound {{ $org->inbound_enabled ? 'on' : 'off' }}</button></form><form method="POST" action="{{ route('admin.organizations.channels',[$org,'outbound']) }}">@csrf<button class="{{ $org->outbound_enabled ? 'enabled' : '' }}">Outbound {{ $org->outbound_enabled ? 'on' : 'off' }}</button></form></div></td><td><div class="admin-controls"><form method="POST" action="{{ route('admin.organizations.pause',$org) }}">@csrf<button>{{ $org->sending_paused_at ? 'Resume' : 'Pause sending' }}</button></form><form method="POST" action="{{ route('admin.organizations.suspend',$org) }}">@csrf<button class="danger-link">{{ $org->suspended_at ? 'Unsuspend' : 'Suspend' }}</button></form></div></td></tr>@endforeach
</tbody></table></div><div class="pagination">{{ $organizations->links() }}</div></section>
</main></body></html>
