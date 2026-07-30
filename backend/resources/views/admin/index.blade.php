@extends('layouts.admin')
@section('title','Platform administration')
@section('heading','Platform administration')
@section('content')
<p class="lead">Everything about your customers in four places: platform health, workspaces, customer accounts, and your own security.</p>

<div class="metrics">
    <article><span>Workspaces</span><strong>{{ number_format($stats['workspaces']) }}</strong><small>business tenants</small></article>
    <article><span>User accounts</span><strong>{{ number_format($stats['users']) }}</strong><small>registered logins</small></article>
    <article><span>Outbound processed</span><strong>{{ number_format($stats['outbound']) }}</strong><small>messages sent through the platform</small></article>
    <article><span>Inbound processed</span><strong>{{ number_format($stats['inbound']) }}</strong><small>replies received</small></article>
</div>

@if($pendingRequests->isNotEmpty())
<section class="panel"><div class="panel-head"><div><span>Action required</span><h2>Pending plan requests</h2></div><b>{{ $pendingRequests->count() }}</b></div><div class="admin-requests">@foreach($pendingRequests as $change)<article><div><strong>{{ $change->organization?->name }}</strong><span>requests {{ ucfirst($change->requested_plan) }} · {{ $change->created_at?->diffForHumans() }}</span></div><form method="POST" action="{{ route('admin.requests.approve',$change) }}">@csrf<button class="button small">Confirm payment & activate</button></form><form method="POST" action="{{ route('admin.requests.reject',$change) }}">@csrf<button class="danger-link">Reject</button></form></article>@endforeach</div></section>
@endif

<section class="panel"><div class="panel-head"><div><span>Tenants</span><h2>Business workspaces</h2></div><small>{{ $organizations->total() }} total</small></div>
<div class="table-wrap"><table><thead><tr><th>Workspace</th><th>Plan</th><th>Outbound</th><th>Inbound</th><th>Devices</th><th>Messaging</th><th>Safety</th></tr></thead><tbody>
@forelse($organizations as $org)<tr>
<td><strong>{{ $org->name }}</strong><small class="block">{{ $org->slug }}</small><a class="admin-marketing-link" href="{{ route('admin.marketing',$org) }}">Marketing</a></td>
<td><span class="status {{ $org->subscription?->status === 'active' ? 'delivered' : 'queued' }}">{{ $org->subscription?->plan ?? 'none' }} / {{ $org->subscription?->status ?? 'unconfigured' }}</span>
<form method="POST" action="{{ route('admin.organizations.plan',$org) }}" class="plan-inline">@csrf @method('PUT')<select name="plan"><option value="free" @selected(($org->subscription?->plan ?? 'free') === 'free')>Free</option><option value="starter" @selected($org->subscription?->plan === 'starter')>Starter</option><option value="business" @selected($org->subscription?->plan === 'business')>Business</option></select><button class="button small">Save</button></form></td>
<td><strong>{{ number_format($org->messages_count) }}</strong><small class="block">sent</small></td>
<td><strong>{{ number_format($org->inbound_messages_count) }}</strong><small class="block">received</small></td>
<td>{{ $org->devices_count }}</td>
<td><div class="channel-controls"><form method="POST" action="{{ route('admin.organizations.channels',[$org,'inbound']) }}">@csrf<button class="{{ $org->inbound_enabled ? 'enabled' : '' }}">Inbound {{ $org->inbound_enabled ? 'on' : 'off' }}</button></form><form method="POST" action="{{ route('admin.organizations.channels',[$org,'outbound']) }}">@csrf<button class="{{ $org->outbound_enabled ? 'enabled' : '' }}">Outbound {{ $org->outbound_enabled ? 'on' : 'off' }}</button></form></div></td>
<td><div class="admin-controls"><form method="POST" action="{{ route('admin.organizations.pause',$org) }}">@csrf<button>{{ $org->sending_paused_at ? 'Resume' : 'Pause sending' }}</button></form><form method="POST" action="{{ route('admin.organizations.suspend',$org) }}">@csrf<button class="danger-link">{{ $org->suspended_at ? 'Unsuspend' : 'Suspend' }}</button></form></div></td>
</tr>@empty<tr><td colspan="7" class="empty">No workspaces yet. Onboard your first customer below.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $organizations->links() }}</div></section>

<section class="panel"><div class="panel-head"><div><span>Customers</span><h2>Accounts &amp; onboarding</h2></div><small>{{ $accounts->count() }} accounts</small></div>
<form method="POST" action="{{ route('admin.users.store') }}" class="onboard-grid">@csrf
<label>Name<input name="name" value="{{ old('name') }}" required></label>
<label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
<label>Business name<input name="business_name" value="{{ old('business_name') }}" required></label>
<label>Language<select name="locale"><option value="en">English</option><option value="fr">Français</option></select></label>
<button class="button">Send invitation</button></form>
<div class="table-wrap accounts-table"><table><thead><tr><th>User</th><th>Email</th><th>Workspaces</th><th>Type</th><th>Action</th></tr></thead><tbody>
@forelse($accounts as $account)<tr><td><strong>{{ $account->name }}</strong></td><td>{{ $account->email }}</td><td>{{ $account->memberships_count }}</td><td>@if($account->is_platform_admin)<span class="status delivered">Administrator</span>@elseif($account->onboarded_by_user_id)<span class="status queued">Onboarded</span>@else<span class="status disabled">Self-registered</span>@endif</td><td>@if($account->id === auth()->id())<span class="status delivered">You</span>@elseif($account->is_platform_admin)<span>—</span>@else<form method="POST" action="{{ route('admin.users.destroy',$account) }}" data-confirm="Permanently delete this user? Workspaces they solely own are deleted with them; shared workspaces are kept.">@csrf @method('DELETE')<button class="danger-link">Delete account</button></form>@endif</td></tr>@empty<tr><td colspan="5" class="empty">No user accounts yet.</td></tr>@endforelse
</tbody></table></div></section>

<section class="panel"><div class="panel-head"><div><span>Security</span><h2>Change administrator password</h2></div></div><form method="POST" action="{{ route('admin.password.update') }}" class="stack-form compact password-form">@csrf @method('PUT')<label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label><label>New password<input type="password" name="password" autocomplete="new-password" minlength="12" required><small>Use at least 12 characters with uppercase, lowercase, a number and a symbol.</small></label><label>Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" required></label><button class="button">Change password</button></form></section>
@endsection
