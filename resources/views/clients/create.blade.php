@extends('layouts.app')

@section('title', 'Add Client')
@section('page_title', 'Create New Client')

@section('content')
<div style="max-width: 720px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">New Client Profile</h2>
        <div class="card-subtitle">Generate a unique KX code and assign Meta Ads & Telegram funnels.</div>
      </div>
      <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancel</a>
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

    <form method="POST" action="{{ route('clients.store') }}" enctype="multipart/form-data">
      @csrf

      <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="kx_code">KX Client Code *</label>
          <input type="text" id="kx_code" name="kx_code" class="form-input" value="{{ old('kx_code', $suggestedKxCode) }}" required />
          <div class="form-hint">Agency identifier (e.g. KX-001)</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="company_name">Company / Channel Name *</label>
          <input type="text" id="company_name" name="company_name" class="form-input" placeholder="e.g. STOXK Academy" value="{{ old('company_name') }}" required />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="client_name">Client Lead / Influencer Name *</label>
          <input type="text" id="client_name" name="client_name" class="form-input" placeholder="e.g. Nandu Meena" value="{{ old('client_name') }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="industry">Industry / Niche *</label>
          <input type="text" id="industry" name="industry" class="form-input" placeholder="e.g. STOXK / Stock Market / Forex" value="{{ old('industry', 'Stock Market & Option Trading') }}" required />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input" placeholder="contact@client.com" value="{{ old('email') }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Phone / WhatsApp</label>
          <input type="text" id="phone" name="phone" class="form-input" placeholder="+91 98290 12345" value="{{ old('phone') }}" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="ad_account_id">Meta Ad Account (Optional)</label>
        <select id="ad_account_id" name="ad_account_id" class="form-select">
          <option value="">-- Select an ad account --</option>
          @foreach($availableAdAccounts as $acc)
            <option value="{{ $acc->id }}" {{ old('ad_account_id') == $acc->id ? 'selected' : '' }}>
              {{ $acc->name }} ({{ $acc->account_id }}) — {{ $acc->currency }} [{{ $acc->status }}]
            </option>
          @endforeach
        </select>
        @if(!$hasGlobalMetaConnection)
          <div class="form-hint" style="color: #b45309;">⚠️ Global Meta account not connected. Connect in <a href="{{ route('settings.index', ['tab' => 'meta']) }}" style="color: var(--accent-blue);">Settings ➔ Meta</a>.</div>
        @elseif($availableAdAccounts->isEmpty())
          <div class="form-hint" style="color: #b45309;">No ad accounts synced. Click "Sync accounts" in <a href="{{ route('settings.index', ['tab' => 'meta']) }}" style="color: var(--accent-blue);">Meta Settings</a>.</div>
        @else
          <div class="form-hint">Assign one connected Meta Ad Account to scope all live marketing metrics for this client.</div>
        @endif
      </div>

      <div class="form-group">
        <label class="form-label" for="status">Account Status *</label>
        <select id="status" name="status" class="form-select" required>
          <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Paused</option>
          <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="notes">Internal Notes & Strategy</label>
        <textarea id="notes" name="notes" class="form-textarea" rows="2" placeholder="Key target audiences, ad creatives, Telegram community goals...">{{ old('notes') }}</textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
        <a href="{{ route('clients.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save & Create Client Profile</button>
      </div>
    </form>
  </div>
</div>
@endsection
