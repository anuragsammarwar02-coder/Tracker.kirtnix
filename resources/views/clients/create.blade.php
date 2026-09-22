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
          <label class="form-label" for="category">Client Category / Niche *</label>
          <select id="category" name="category" class="form-select" required>
            @foreach($categories as $catKey => $catLabel)
              <option value="{{ $catKey }}" {{ old('category', old('industry', 'Stock Market & Options Trading')) === $catKey ? 'selected' : '' }}>
                {{ $catLabel }}
              </option>
            @endforeach
          </select>
          <div class="form-hint">Meta Conversions API (CAPI) will optimize for this specific niche audience.</div>
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
          <label class="form-label" style="margin-bottom: 0;">Meta Ad Account (Optional)</label>
          <span style="font-size: 11px; color: var(--text-muted);">Instant Search &amp; Filter</span>
        </div>

        @include('clients._ad_account_picker', [
          'availableAdAccounts' => $availableAdAccounts,
          'selectedId' => old('ad_account_id', ''),
          'selectId' => 'ad_account_id',
          'selectName' => 'ad_account_id',
          'hasGlobalMetaConnection' => $hasGlobalMetaConnection
        ])

        @if(!$hasGlobalMetaConnection)
          <div class="form-hint" style="color: #b45309; margin-top: 6px;">⚠️ Global Meta account not connected. Connect in <a href="{{ route('settings.index', ['tab' => 'meta']) }}" style="color: var(--accent-blue);">Settings ➔ Meta</a>.</div>
        @else
          <div class="form-hint" style="margin-top: 6px;">Assign one connected Meta Ad Account to scope all live marketing metrics for this client.</div>
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
