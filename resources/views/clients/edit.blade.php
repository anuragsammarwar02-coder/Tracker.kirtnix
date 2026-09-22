@extends('layouts.app')

@section('title', 'Edit ' . $client->company_name)
@section('page_title', 'Edit Client Profile')

@section('content')
<div style="max-width: 720px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Edit Client Details</h2>
        <div class="card-subtitle">Update company information, KX code, and Meta Ads connection.</div>
      </div>
      <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">Back to Client</a>
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

    <form method="POST" action="{{ route('clients.update', $client) }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="kx_code">KX Client Code *</label>
          <input type="text" id="kx_code" name="kx_code" class="form-input" value="{{ old('kx_code', $client->kx_code ?? 'KX-00' . $client->id) }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="company_name">Company / Brand Name *</label>
          <input type="text" id="company_name" name="company_name" class="form-input" value="{{ old('company_name', $client->company_name) }}" required />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="client_name">Client Lead Name *</label>
          <input type="text" id="client_name" name="client_name" class="form-input" value="{{ old('client_name', $client->client_name) }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="category">Client Category / Niche *</label>
          <select id="category" name="category" class="form-select" required>
            @foreach($categories as $catKey => $catLabel)
              <option value="{{ $catKey }}" {{ old('category', old('industry', $client->category ?? $client->industry)) === $catKey ? 'selected' : '' }}>
                {{ $catLabel }}
              </option>
            @endforeach
          </select>
          <div class="form-hint">Meta Conversions API will optimize conversions for this category.</div>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input" value="{{ old('email', $client->email) }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Phone / WhatsApp</label>
          <input type="text" id="phone" name="phone" class="form-input" value="{{ old('phone', $client->phone) }}" />
        </div>
      </div>

      <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
          <label class="form-label" for="ad_account_id" style="margin-bottom: 0;">Meta Ad Account</label>
          <a href="{{ route('settings.index', ['tab' => 'meta']) }}" target="_blank" style="font-size: 11px; color: var(--accent-blue); text-decoration: none; font-weight: 600;">
            🔄 Deep Sync All
          </a>
        </div>
        <select id="ad_account_id" name="ad_account_id" class="form-select">
          <option value="">-- No Ad Account Assigned --</option>
          @foreach($availableAdAccounts as $acc)
            <option value="{{ $acc->id }}" {{ old('ad_account_id', $client->ad_account_id) == $acc->id ? 'selected' : '' }}>
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
          <div class="form-hint" style="margin-top: 4px;">Change or reassign the Meta Ad Account used to fetch marketing spend, reach, and conversion metrics.</div>
        @endif
      </div>

      <div class="form-group">
        <label class="form-label" for="status">Account Status *</label>
        <select id="status" name="status" class="form-select" required>
          <option value="active" {{ old('status', $client->status) === 'active' ? 'selected' : '' }}>Active</option>
          <option value="paused" {{ old('status', $client->status) === 'paused' ? 'selected' : '' }}>Paused</option>
          <option value="archived" {{ old('status', $client->status) === 'archived' ? 'selected' : '' }}>Archived</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="notes">Internal Notes & Strategy</label>
        <textarea id="notes" name="notes" class="form-textarea" rows="2">{{ old('notes', $client->notes) }}</textarea>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
        <button type="button" class="btn btn-danger" onclick="if(confirm('Archive this client?')) document.getElementById('delete-form').submit();">
          Archive Client
        </button>

        <div style="display: flex; gap: 10px;">
          <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>

    <form id="delete-form" action="{{ route('clients.destroy', $client) }}" method="POST" style="display: none;">
      @csrf
      @method('DELETE')
    </form>
  </div>
</div>
@endsection
