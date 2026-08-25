@extends('layouts.app')

@section('title', 'Connect Telegram Bot')
@section('page_title', 'Connect Telegram Bot')

@section('content')
<div style="max-width: 700px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Telegram Bot Credentials</h2>
      <a href="{{ route('telegram.index') }}" class="btn btn-secondary">Cancel</a>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('telegram.store') }}">
      @csrf

      <div class="form-group">
        <label class="form-label" for="client_id">Associate with Client *</label>
        <select id="client_id" name="client_id" class="form-select" required>
          <option value="">Select a Client</option>
          @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ old('client_id', request('client_id')) == $c->id ? 'selected' : '' }}>
              {{ $c->company_name }}
            </option>
          @endforeach
        </select>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="name">Bot Internal Name *</label>
          <input type="text" id="name" name="name" class="form-input" placeholder="e.g. Forex Focus Community Verifier" value="{{ old('name') }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="username">Bot Username (without @) *</label>
          <input type="text" id="username" name="username" class="form-input" placeholder="ForexFocusVerifyBot" value="{{ old('username') }}" required />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="bot_token">Bot API Token (from @BotFather) *</label>
        <input type="password" id="bot_token" name="bot_token" class="form-input" placeholder="7192847192:AAH9..." value="{{ old('bot_token') }}" required />
        <div class="form-hint">Stored securely on the server. Never exposed to browsers.</div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="channel_title">Channel / Group Title</label>
          <input type="text" id="channel_title" name="channel_title" class="form-input" placeholder="Forex Focus Global VIP" value="{{ old('channel_title') }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="channel_id">Channel ID (e.g. -100xxxxxxxxxx)</label>
          <input type="text" id="channel_id" name="channel_id" class="form-input" placeholder="-1002194829104" value="{{ old('channel_id') }}" />
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px;">
        <a href="{{ route('telegram.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Connect & Register Webhook</button>
      </div>
    </form>
  </div>
</div>
@endsection
