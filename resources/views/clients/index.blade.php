@extends('layouts.app')

@section('title', 'Clients')
@section('page_title', 'Clients')

@section('content')
<div x-data="{ createModal: false, deleteModal: false, clientToDelete: { id: null, name: '', kx_code: '' } }">
  <!-- Header Area -->
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
    <div>
      <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Clients</h1>
      <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
        Every client gets a unique KX code, contact profile and Meta Ads spend view.
      </div>
    </div>

    <button type="button" @click="createModal = true" class="btn btn-primary">
      <span>+ New client</span>
    </button>
  </div>

  <!-- Search Bar & Filters -->
  <div class="card" style="padding: 12px 16px; margin-bottom: 20px;">
    <form method="GET" action="{{ route('clients.index') }}" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
      <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 260px;">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="search" class="form-input" placeholder="Search by KX code, name, industry, email..." value="{{ request('search') }}" style="border: none; padding: 6px 0; background: transparent;" />
      </div>

      <div style="display: flex; align-items: center; gap: 8px;">
        <select name="status" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 5px 10px; font-size: 12px;">
          <option value="">All Statuses</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
        </select>

        <button type="submit" class="btn btn-secondary" style="padding: 5px 12px; font-size: 12px;">Filter</button>
      </div>
    </form>
  </div>

  <!-- Client Grid Cards -->
  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; margin-bottom: 24px;">
    @forelse($clients as $client)
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.15s, box-shadow 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
      <div>
        <!-- Top Card Header: Avatar, Name & KX Code -->
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 38px; height: 38px; border-radius: 8px; background: #0F172A; color: var(--brand-yellow); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; border: 1px solid var(--border-color); flex-shrink: 0;">
              {{ substr($client->company_name, 0, 1) }}
            </div>
            <div>
              <a href="{{ route('clients.show', $client) }}" style="font-size: 14px; font-weight: 700; color: var(--text-main); text-decoration: none; display: block; line-height: 1.2;">
                {{ $client->company_name }}
              </a>
              <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">{{ $client->client_name }}</div>
            </div>
          </div>

          <span class="pill pill-yellow" style="font-family: 'JetBrains Mono', monospace; font-size: 10px;">
            {{ $client->kx_code ?? 'KX-00' . $client->id }}
          </span>
        </div>

        <!-- Industry & Meta Connection Status -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: var(--bg-subtle); border-radius: 6px; margin-bottom: 14px; font-size: 11.5px;">
          <div>
            <span style="color: var(--text-muted);">Industry:</span>
            <strong style="color: var(--text-main);">{{ $client->industry ?? 'Stock Trading' }}</strong>
          </div>
          <div>
            @if($client->adAccount)
              <span class="pill pill-green" style="font-size: 9.5px;" title="{{ $client->adAccount->account_id }}"><span class="pill-dot"></span> {{ $client->adAccount->name }}</span>
            @elseif($client->meta_ads_connected)
              <span class="pill pill-green" style="font-size: 9.5px;"><span class="pill-dot"></span> Meta Connected</span>
            @else
              <span class="pill pill-gray" style="font-size: 9.5px;">Meta Offline</span>
            @endif
          </div>
        </div>

        <!-- Quick Metrics Snapshot -->
        @php
          $cSpend = (float) $client->campaigns->sum('spend');
          if ($cSpend <= 0 && $client->adAccount && $client->adAccount->lifetime_spend > 0) {
              $cSpend = (float) $client->adAccount->lifetime_spend;
          }
          $cJoins = $client->telegramEvents->where('event_type', 'join')->count();
          $cCpj = $cJoins > 0 ? round($cSpend / $cJoins, 2) : 0.00;
        @endphp
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; text-align: center; border-top: 1px solid var(--border-subtle); padding-top: 12px; margin-bottom: 14px;">
          <div>
            <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Spend</div>
            <div style="font-size: 13.5px; font-weight: 800; color: var(--text-main); margin-top: 2px;">{{ $client->currency_symbol }}{{ number_format($cSpend, 0) }}</div>
          </div>
          <div>
            <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Joins</div>
            <div style="font-size: 13.5px; font-weight: 800; color: #B45309; margin-top: 2px;">{{ number_format($cJoins) }}</div>
          </div>
          <div>
            <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Cost / Join</div>
            <div style="font-size: 13.5px; font-weight: 800; color: var(--accent-green); margin-top: 2px;">{{ $client->currency_symbol }}{{ number_format($cCpj, 2) }}</div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border-subtle); padding-top: 10px;">
        <div style="font-size: 11px; color: var(--text-muted);">
          {{ $client->landing_pages_count ?? $client->landingPages->count() }} Landing Pages
        </div>
        <div style="display: flex; gap: 6px; align-items: center;">
          <a href="{{ route('clients.show', $client) }}" class="btn btn-primary" style="padding: 4px 10px; font-size: 11.5px;">Overview ↗</a>
          <a href="{{ route('clients.edit', $client) }}" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11.5px;">Edit</a>
          <button type="button" @click="clientToDelete = { id: {{ $client->id }}, name: '{{ addslashes($client->company_name) }}', kx_code: '{{ $client->kx_code ?? 'KX-00' . $client->id }}' }; deleteModal = true;" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11.5px; color: var(--accent-red);" title="Delete Client">
            🗑
          </button>
        </div>
      </div>
    </div>
    @empty
    <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 48px 20px;">
      <div style="font-size: 28px; margin-bottom: 8px;">👤</div>
      <div style="font-weight: 700; font-size: 14px; color: var(--text-main);">No clients found</div>
      <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px; margin-bottom: 16px;">Get started by onboarding your first agency client.</div>
      <button type="button" @click="createModal = true" class="btn btn-primary">+ Create Client</button>
    </div>
    @endforelse
  </div>

  <div style="margin-top: 16px;">
    {{ $clients->links() }}
  </div>

  <!-- New Client Modal (Matches Screenshot 1) -->
  <div x-show="createModal" style="display: none;" class="modal-backdrop" @click.self="createModal = false">
    <div class="modal-content" style="max-width: 540px; padding: 24px;">
      <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px;">
        <div>
          <h3 style="font-size: 16px; font-weight: 800; color: var(--text-main);">New Client Profile</h3>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Create client profile & assign Meta Ad Account.</div>
        </div>
        <button type="button" @click="createModal = false" class="btn-icon" style="color: var(--text-muted); cursor: pointer;">✕</button>
      </div>

      <form method="POST" action="{{ route('clients.store') }}">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="modal_kx_code">KX Code</label>
            <input type="text" id="modal_kx_code" name="kx_code" class="form-input" placeholder="KX-001" />
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="modal_company_name">Company / Channel *</label>
            <input type="text" id="modal_company_name" name="company_name" class="form-input" placeholder="e.g. STOXK Academy" required />
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="modal_client_name">Client Lead Name *</label>
            <input type="text" id="modal_client_name" name="client_name" class="form-input" placeholder="e.g. Nandu Meena" required />
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="modal_industry">Industry / Niche</label>
            <input type="text" id="modal_industry" name="industry" class="form-input" placeholder="Stock Trading" value="Stock Market & Options" />
          </div>
        </div>

        <!-- Meta Ad Account Selection Dropdown (Matches Screenshot 1) -->
        <div class="form-group" style="margin-bottom: 14px;">
          <label class="form-label" for="modal_create_ad_account_id">Meta Ad Account (Optional)</label>
          <select id="modal_create_ad_account_id" name="ad_account_id" class="form-select" style="width: 100%;">
            <option value="">-- Select an ad account --</option>
            @foreach($availableAdAccounts as $acc)
              <option value="{{ $acc->id }}">
                {{ $acc->name }} ({{ $acc->account_id }}) — {{ $acc->currency }} [{{ $acc->status }}]
              </option>
            @endforeach
          </select>
          @if(!$hasGlobalMetaConnection)
            <div class="form-hint" style="color: #b45309; font-size: 11px;">⚠️ Global Meta not connected. Connect in <a href="{{ route('settings.index', ['tab' => 'meta']) }}" style="color: var(--accent-blue);">Settings ➔ Meta</a>.</div>
          @elseif($availableAdAccounts->isEmpty())
            <div class="form-hint" style="color: #b45309; font-size: 11px;">No ad accounts found. Click "Sync accounts" in <a href="{{ route('settings.index', ['tab' => 'meta']) }}" style="color: var(--accent-blue);">Meta Settings</a>.</div>
          @else
            <div class="form-hint" style="font-size: 11px;">Marketing data will automatically sync from this specific Meta Ad Account.</div>
          @endif
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="modal_email">Email Address</label>
            <input type="email" id="modal_email" name="email" class="form-input" placeholder="client@domain.com" />
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label" for="modal_status">Status</label>
            <select id="modal_status" name="status" class="form-select">
              <option value="active" selected>Active</option>
              <option value="paused">Paused</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" @click="createModal = false" class="btn btn-secondary" style="font-size: 12px; padding: 7px 14px;">Cancel</button>
          <button type="submit" class="btn btn-primary" style="font-size: 12px; font-weight: 700; padding: 7px 16px;">
            Create Client Profile
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Client Confirmation Modal -->
  <div x-show="deleteModal" style="display: none;" class="modal-backdrop" @click.self="deleteModal = false">
    <div class="modal-content" style="max-width: 460px; padding: 24px;">
      <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239, 68, 68, 0.12); color: var(--accent-red); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
            ⚠️
          </div>
          <div>
            <h3 style="font-size: 16px; font-weight: 800; color: var(--text-main);">Delete Client Workspace</h3>
            <div style="font-size: 11.5px; color: var(--accent-red); font-weight: 600;">Permanent deletion & data purge</div>
          </div>
        </div>
        <button type="button" @click="deleteModal = false" class="btn-icon" style="color: var(--text-muted); font-size: 14px; cursor: pointer; padding: 4px 8px;">✕</button>
      </div>

      <div style="background: var(--bg-subtle); padding: 14px 16px; border-radius: 8px; border: 1px solid var(--border-subtle); margin-bottom: 20px; font-size: 12.5px;">
        <div>Client Name: <strong style="color: var(--text-main);" x-text="clientToDelete.name"></strong></div>
        <div style="margin-top: 5px;">KX Code: <span class="pill pill-yellow" style="font-family: 'JetBrains Mono', monospace; font-size: 10px;" x-text="clientToDelete.kx_code"></span></div>
        <div style="margin-top: 10px; padding: 8px 10px; background: rgba(239, 68, 68, 0.08); border-radius: 6px; border-left: 3px solid var(--accent-red); font-size: 11.5px; color: var(--text-body); line-height: 1.45;">
          <strong>Warning:</strong> Deleting this client will permanently purge all associated landing pages, campaigns, tracking sessions, joins and conversions from the dashboard.
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" @click="deleteModal = false" class="btn btn-secondary" style="font-size: 12px; padding: 7px 14px;">Cancel</button>
        <form :action="'/clients/' + clientToDelete.id" method="POST" style="display: inline;">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger" style="font-size: 12px; font-weight: 700; padding: 7px 14px;">
            Yes, Delete Client & All Data
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
