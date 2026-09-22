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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
          <label class="form-label" for="ad_account_id" style="margin-bottom: 0;">Meta Ad Account (Optional)</label>
          <a href="{{ route('settings.index', ['tab' => 'meta']) }}" target="_blank" style="font-size: 11px; color: var(--accent-blue); text-decoration: none; font-weight: 600;">
            🔄 Deep Sync All
          </a>
        </div>
        <select id="ad_account_id" name="ad_account_id" class="form-select">
          <option value="">-- Select an ad account --</option>
          @foreach($availableAdAccounts as $acc)
            <option value="{{ $acc->id }}" {{ old('ad_account_id') == $acc->id ? 'selected' : '' }}>
              {{ $acc->name }} ({{ $acc->account_id }}) — {{ $acc->currency }} [{{ $acc->status }}]
            </option>
          @endforeach
        </select>

        <!-- Quick Custom ID Entry Accordion -->
        <div style="margin-top: 6px;" x-data="{ customOpen: false, inputId: '', fetching: false, fetchMsg: '', fetchErr: '' }">
          <button type="button" @click="customOpen = !customOpen" style="background: none; border: none; padding: 0; font-size: 11px; color: #b45309; font-weight: 700; cursor: pointer; text-decoration: underline;">
            <span x-text="customOpen ? '▲ Hide Custom Ad Account ID' : '➕ Can\'t find your account? Enter Ad Account ID directly'"></span>
          </button>

          <div x-show="customOpen" x-cloak style="margin-top: 8px; padding: 10px; background: var(--bg-subtle, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px;">
            <label style="font-size: 11px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">Enter Ad Account ID (e.g. 1049354661209537):</label>
            <div style="display: flex; gap: 8px;">
              <input type="text" name="custom_ad_account_id" x-model="inputId" placeholder="e.g. 1049354661209537 or act_..." class="form-input" style="font-size: 12px; font-family: monospace;" />
              <button type="button" class="btn btn-secondary" style="font-size: 11px; padding: 4px 10px; white-space: nowrap;" :disabled="fetching || !inputId" @click="
                if(!inputId) return;
                fetching = true;
                fetchMsg = '';
                fetchErr = '';
                fetch('{{ route('meta.ad_accounts.quick_fetch') }}', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                  },
                  body: JSON.stringify({ account_id: inputId })
                })
                .then(res => res.json())
                .then(data => {
                  fetching = false;
                  if(data.success && data.ad_account) {
                    fetchMsg = '✓ ' + data.ad_account.name + ' (' + data.ad_account.account_id + ') fetched & synced!';
                    const sel = document.getElementById('ad_account_id');
                    if(sel) {
                      let opt = Array.from(sel.options).find(o => o.value == data.ad_account.id);
                      if(!opt) {
                        opt = new Option(data.ad_account.name + ' (' + data.ad_account.account_id + ') — ' + data.ad_account.currency + ' [' + data.ad_account.status + ']', data.ad_account.id);
                        sel.add(opt);
                      }
                      sel.value = data.ad_account.id;
                    }
                  } else {
                    fetchErr = data.error || 'Failed to fetch account.';
                  }
                })
                .catch(e => {
                  fetching = false;
                  fetchErr = 'Connection error: ' + e.message;
                });
              ">
                <span x-show="!fetching">🔍 Fetch &amp; Select</span>
                <span x-show="fetching">Fetching...</span>
              </button>
            </div>
            <div x-show="fetchMsg" style="font-size: 11px; color: #16a34a; font-weight: 600; margin-top: 4px;" x-text="fetchMsg"></div>
            <div x-show="fetchErr" style="font-size: 11px; color: #dc2626; font-weight: 600; margin-top: 4px;" x-text="fetchErr"></div>
          </div>
        </div>

        @if(!$hasGlobalMetaConnection)
          <div class="form-hint" style="color: #b45309; margin-top: 4px;">⚠️ Global Meta account not connected. Connect in <a href="{{ route('settings.index', ['tab' => 'meta']) }}" style="color: var(--accent-blue);">Settings ➔ Meta</a>.</div>
        @else
          <div class="form-hint" style="margin-top: 4px;">Assign one connected Meta Ad Account to scope all live marketing metrics for this client.</div>
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
