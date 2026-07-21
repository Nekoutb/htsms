@extends('layouts.portal')
@section('title','Messages') @section('heading','Messages')
@section('actions')<a class="button small" href="#compose">Compose message</a>@endsection
@section('content')
<section id="compose" class="panel compose"><div class="panel-head"><div><span>Transactional SMS</span><h2>Compose a message</h2></div><small>Use E.164 numbers such as +237670000000</small></div><form method="POST" action="{{ route('portal.messages.send',$organization) }}">@csrf<div class="compose-grid"><label>Recipient<input name="to" value="{{ old('to') }}" placeholder="+237 6•• ••• •••" required></label><label>Schedule (optional)<input type="datetime-local" name="send_at" value="{{ old('send_at') }}"></label></div><label>Message<textarea name="content" maxlength="1600" rows="4" placeholder="Write a clear transactional message…" required>{{ old('content') }}</textarea><small>Messages longer than one SMS may use multiple segments.</small></label><div class="form-actions"><button class="button">Queue message</button></div></form></section>
<section class="panel"><div class="panel-head"><div><span>Delivery log</span><h2>All messages</h2></div><small>{{ $messages->total() }} records</small></div>@include('portal.partials.message-table',['messages'=>$messages])<div class="pagination">{{ $messages->links() }}</div></section>
@endsection
