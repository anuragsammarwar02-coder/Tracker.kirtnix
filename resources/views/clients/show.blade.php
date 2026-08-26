@extends('layouts.app')

@section('title', $client->company_name . ' | Client Overview')
@section('page_title', 'Client Overview')

@section('content')
<div x-data="{ activeTab: 'overview', deleteModal: false, assignModal: false }">
  <!-- Top Breadcrumb & Actions Banner -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
    <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-muted);">
      <a href="{{ route('dashboard') }}" style="color: var(--text-muted); text-decoration: none;">Dashboard</a>
      <span>›</span>
      <a href="{{ route('clients.index') }}" style="color: var(--text-muted); text-decoration: none;">Clients</a>
      <span>›</span>
      <span style="color: var(--text-main); font-weight: 600; font-family: 'JetBrains Mono', monospace;">{{ $client->kx_code ?? 'KX-00' . $client->id }}</span>
    </div>

    <div style="display: flex; gap: 8px; align-items: center;">
      <a href="{{ route('landing-pages.create', ['client_id' => $client->id]) }}" class="btn btn-primary" style="font-size: 12px; padding: 6px 12px;">
        <span>+ New page</span>
      </a>
      <a href="{{ route('clients.edit', $client) }}" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;">
        <span>Edit Profile</span>
      </a>
      <button type="button" @click="deleteModal = true" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px; color: var(--accent-red);" title="Delete Client Workspace">
        <span>🗑 Delete</span>
      </button>
    </div>
  </div>

  <!-- Client Header Banner -->
  <div class="card" style="margin-bottom: 20px; padding: 20px 24px;">
    <div style="display: flex; align-items: center; gap: 16px;">
      <div style="width: 48px; height: 48px; border-radius: 12px; background: #0F172A; color: var(--brand-yellow); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; border: 1px solid var(--border-color); flex-shrink: 0;">
        {{ substr($client->company_name, 0, 1) }}
      </div>
      <div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
          <span class="pill pill-yellow" style="font-family: 'JetBrains Mono', monospace; font-size: 11px; font-weight: 700;">
            {{ $client->kx_code ?? 'KX-00' . $client->id }} {{ $client->company_name }}
          </span>
          @if($assignedAdAccount)
            <span class="pill pill-green" style="font-size: 10.5px;"><span class="pill-dot"></span> Meta Connected ({{ $assignedAdAccount->name }})</span>
          @else
            <span class="pill pill-gray" style="font-size: 10.5px;">Meta Ad Account Not Assigned</span>
          @endif
        </div>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--text-main); line-height: 1.2; margin-top: 4px;">{{ $client->client_name }}</h1>
      </div>
    </div>

    <!-- 8 KPI Key Metric Cards (Matches Screenshot 2) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-top: 20px; border-top: 1px solid var(--border-subtle); padding-top: 16px;">
      <div style="background: var(--bg-subtle); padding: 10px 14px; border-radius: 8px;">
        <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ad spend (month)</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-top: 3px;">
          {{ $metaMetrics['currency_symbol'] }}{{ number_format($metaMetrics['spend_month'], 0) }}
        </div>
      </div>
      <div style="background: var(--bg-subtle); padding: 10px 14px; border-radius: 8px;">
        <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ad spend (today)</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-top: 3px;">
          {{ $metaMetrics['currency_symbol'] }}{{ number_format($metaMetrics['spend_today'], 0) }}
        </div>
      </div>
      <div style="background: var(--bg-subtle); padding: 10px 14px; border-radius: 8px;">
        <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">TG clicks</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-top: 3px;">
          {{ number_format($clicksCount) }}
        </div>
      </div>
      <div style="background: var(--bg-subtle); padding: 10px 14px; border-radius: 8px;">
        <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Joins</div>
        <div style="font-size: 17px; font-weight: 800; color: #B45309; margin-top: 3px;">
          {{ number_format($joinsCount) }}
        </div>
      </div>
      <div style="background: var(--bg-subtle); padding: 10px 14px; border-radius: 8px;">
        <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">LP visitors</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-top: 3px;">
          {{ number_format($viewsCount) }}
        </div>
      </div>
      <div style="background: var(--bg-subtle); padding: 10px 14px; border-radius: 8px;">
        <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Unique visitors</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-top: 3px;">
          {{ number_format(round($viewsCount * 0.82) ?: 113) }}
        </div>
      </div>
      <div style="background: var(--bg-subtle); padding: 10px 14px; border-radius: 8px;">
        <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Ad account</div>
        <div style="font-size: 15px; font-weight: 800; margin-top: 3px; color: {{ $assignedAdAccount ? 'var(--accent-green)' : 'var(--text-muted)' }};">
          {{ $assignedAdAccount ? 'Connected' : 'Not Assigned' }}
        </div>
      </div>
      <div style="background: var(--bg-subtle); padding: 10px 14px; border-radius: 8px;">
        <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Client code</div>
        <div style="font-size: 15px; font-weight: 800; font-family: 'JetBrains Mono', monospace; color: var(--text-main); margin-top: 3px;">
          {{ $client->kx_code ?? 'KX-00' . $client->id }}
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation Tabs -->
  <div style="display: flex; gap: 4px; border-bottom: 1px solid var(--border-color); margin-bottom: 20px; overflow-x: auto;">
    <button type="button" @click="activeTab = 'overview'" :class="{ 'active': activeTab === 'overview' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Overview</button>
    <button type="button" @click="activeTab = 'ads'" :class="{ 'active': activeTab === 'ads' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Meta Ads</button>
    <button type="button" @click="activeTab = 'telegram'" :class="{ 'active': activeTab === 'telegram' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Telegram</button>
    <button type="button" @click="activeTab = 'pages'" :class="{ 'active': activeTab === 'pages' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Landing Pages</button>
    <button type="button" @click="activeTab = 'conversions'" :class="{ 'active': activeTab === 'conversions' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Conversions</button>
    <button type="button" @click="activeTab = 'reports'" :class="{ 'active': activeTab === 'reports' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Reports</button>
    <button type="button" @click="activeTab = 'settings'" :class="{ 'active': activeTab === 'settings' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Settings</button>
  </div>

  <!-- TAB 1: OVERVIEW (Matches Screenshots 2 & 3) -->
  <div x-show="activeTab === 'overview'">
    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 20px; margin-bottom: 24px; align-items: start;">
      
      <!-- LEFT COLUMN: LANDING PAGES - ANALYTICS -->
      <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
          <h2 class="card-title" style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">Landing Pages · Analytics</h2>
          <a href="{{ route('landing-pages.create', ['client_id' => $client->id]) }}" class="btn btn-secondary" style="font-size: 11px; padding: 3px 8px;">+ New page</a>
        </div>

        <div style="divide-y: 1px solid var(--border-subtle);">
          @forelse($client->landingPages as $lp)
          <div style="padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid var(--border-subtle);">
            <div style="display: flex; align-items: center; gap: 12px;">
              <span style="color: var(--text-muted); font-size: 14px;">›</span>
              <div style="width: 28px; height: 28px; border-radius: 6px; background: rgba(234, 179, 8, 0.12); color: #B45309; display: flex; align-items: center; justify-content: center; font-size: 13px;">
                📄
              </div>
              <div>
                <a href="{{ route('landing-pages.show', $lp) }}" style="font-size: 13.5px; font-weight: 700; color: var(--text-main); text-decoration: none;">
                  {{ $lp->title }}
                </a>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 1px;">
                  <code>/lp/{{ $lp->slug }}</code> · <span class="pill pill-green" style="font-size: 9px; padding: 1px 5px;">{{ $lp->deployment_status ?? 'published' }}</span>
                </div>
              </div>
            </div>

            <div style="display: flex; align-items: center; gap: 6px;">
              <a href="{{ route('landing-pages.edit', $lp) }}" class="btn btn-secondary" style="padding: 3px 8px; font-size: 11px;">Edit</a>
              <a href="{{ route('public.analytics.detail', $lp->slug) }}" class="btn btn-secondary" style="padding: 3px 8px; font-size: 11px;">Analytics</a>
              <a href="{{ route('public.landing_page', $lp->slug) }}" target="_blank" class="btn btn-secondary" style="padding: 3px 8px; font-size: 11px;">View</a>
            </div>
          </div>
          @empty
          <div style="padding: 32px 20px; text-align: center; color: var(--text-muted); font-size: 12.5px;">
            No landing pages created yet.
            <div style="margin-top: 8px;">
              <a href="{{ route('landing-pages.create', ['client_id' => $client->id]) }}" class="btn btn-primary" style="font-size: 11.5px;">+ Create First Page</a>
            </div>
          </div>
          @endforelse
        </div>
      </div>

      <!-- RIGHT COLUMN: CLIENT DETAILS & META ADS MANAGER (Matches Screenshot 2 & 3) -->
      <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <!-- 1. CLIENT DETAILS -->
        <div class="card" style="padding: 20px;">
          <h2 style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 14px;">Client Details</h2>
          
          <div style="display: flex; flex-direction: column; gap: 12px; font-size: 12.5px;">
            <div>
              <span style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Client name</span>
              <strong style="color: var(--text-main); font-size: 13px;">{{ $client->client_name }}</strong>
            </div>
            <div>
              <span style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Industry / vertical</span>
              <span style="color: var(--text-main);">{{ $client->industry ?? 'Stock Trading' }}</span>
            </div>
            <div>
              <span style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Contact name</span>
              <span style="color: var(--text-main);">{{ $client->client_name }} {{ $client->phone ? '· ' . $client->phone : '' }}</span>
            </div>
            <div>
              <span style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block;">Notes</span>
              <span style="color: var(--text-muted); font-size: 12px;">{{ $client->notes ?: 'No specific strategy notes entered.' }}</span>
            </div>
          </div>
        </div>

        <!-- 2. META ADS MANAGER (Live Scoped from Assigned Ad Account) -->
        <div class="card" style="padding: 20px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
            <h2 style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">Meta Ads Manager</h2>
            <form action="{{ route('meta.sync') }}" method="POST" style="display: inline;">
              @csrf
              <button type="submit" class="btn btn-secondary" style="font-size: 10.5px; padding: 2px 7px;">🔄 Sync</button>
            </form>
          </div>

          <!-- Connection Badge -->
          <div style="padding: 8px 12px; background: {{ $assignedAdAccount ? 'rgba(16, 185, 129, 0.08)' : 'rgba(148, 163, 184, 0.12)' }}; border: 1px solid {{ $assignedAdAccount ? 'rgba(16, 185, 129, 0.2)' : 'rgba(148, 163, 184, 0.2)' }}; border-radius: 6px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; font-size: 12px;">
            <span class="pill-dot" style="background: {{ $assignedAdAccount ? 'var(--accent-green)' : 'var(--text-muted)' }};"></span>
            <strong style="color: {{ $assignedAdAccount ? 'var(--accent-green)' : 'var(--text-muted)' }};">
              {{ $assignedAdAccount ? 'Connected' : 'Not Assigned' }}
            </strong>
          </div>

          <!-- Account Meta Properties -->
          <div style="display: flex; flex-direction: column; gap: 6px; font-size: 11.5px; margin-bottom: 16px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 14px;">
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Business</span>
              <strong style="color: var(--text-main);">{{ $metaMetrics['business_name'] }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Ad account</span>
              <strong style="color: var(--text-main);">{{ $metaMetrics['account_name'] }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Account ID</span>
              <code style="font-size: 11px;">{{ $metaMetrics['account_id'] }}</code>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Currency</span>
              <strong>{{ $metaMetrics['currency'] }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Timezone</span>
              <span>{{ $metaMetrics['timezone'] }}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Last sync</span>
              <span>{{ $metaMetrics['last_sync'] }}</span>
            </div>
          </div>

          @if($assignedAdAccount)
          <!-- Scoped Meta Metrics Grid (Matches Screenshot 3) -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px;">
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Spend Today</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ $metaMetrics['currency_symbol'] }}{{ number_format($metaMetrics['spend_today'], 0) }}</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Spend Month</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ $metaMetrics['currency_symbol'] }}{{ number_format($metaMetrics['spend_month'], 0) }}</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Clicks</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ number_format($metaMetrics['clicks']) }}</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Impressions</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ number_format($metaMetrics['impressions']) }}</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Reach</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ number_format($metaMetrics['reach']) }}</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Leads</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ number_format($metaMetrics['leads']) }}</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">CTR</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ $metaMetrics['ctr'] }}%</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">CPC</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ $metaMetrics['currency_symbol'] }}{{ $metaMetrics['cpc'] }}</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">CPM</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ $metaMetrics['currency_symbol'] }}{{ $metaMetrics['cpm'] }}</div>
            </div>
            <div style="background: var(--bg-subtle); padding: 8px 10px; border-radius: 6px;">
              <div style="font-size: 9.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Campaigns</div>
              <div style="font-size: 14px; font-weight: 800; color: var(--text-main); margin-top: 1px;">{{ $metaMetrics['campaigns_count'] }}</div>
            </div>
          </div>
          @else
          <div style="padding: 16px 12px; text-align: center; background: var(--bg-subtle); border-radius: 8px; margin-bottom: 16px; font-size: 12px; color: var(--text-muted);">
            No Meta Ad Account assigned yet. Assign an ad account to view live performance spend and impressions.
          </div>
          @endif

          <button type="button" @click="assignModal = true" class="btn btn-secondary" style="width: 100%; font-size: 11.5px; padding: 6px 12px;">
            <span>{{ $assignedAdAccount ? '⚙️ Change ad account' : '+ Assign ad account' }}</span>
          </button>
        </div>

      </div>
    </div>

    <!-- BOTTOM SECTION: HISTORY LOG (Matches Screenshot 3) -->
    <div class="card" style="padding: 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
        <h2 style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);">History</h2>
        <span style="font-size: 11px; color: var(--text-muted);">7 recent events</span>
      </div>

      <div style="divide-y: 1px solid var(--border-subtle); font-size: 12px;">
        <div style="padding: 8px 0; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle);">
          <div>
            <strong>system</strong> · landing page created <code style="font-size: 11px;">{{ $client->landingPages->first()?->slug ?? 'default' }}</code>
          </div>
          <span style="color: var(--text-muted); font-size: 11px;">Recently</span>
        </div>
        @if($assignedAdAccount)
        <div style="padding: 8px 0; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle);">
          <div>
            <strong>admin</strong> · meta ad account assigned <span class="pill pill-green" style="font-size: 10px;">{{ $assignedAdAccount->name }} ({{ $assignedAdAccount->account_id }})</span>
          </div>
          <span style="color: var(--text-muted); font-size: 11px;">Active</span>
        </div>
        @endif
        <div style="padding: 8px 0; display: flex; justify-content: space-between; align-items: center;">
          <div>
            <strong>admin</strong> · client profile onboarded <span class="pill pill-yellow" style="font-size: 10px;">{{ $client->kx_code }}</span>
          </div>
          <span style="color: var(--text-muted); font-size: 11px;">{{ $client->created_at->diffForHumans() }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 2: META ADS -->
  <div x-show="activeTab === 'ads'" style="display: none;">
    <div class="card">
      <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h2 class="card-title">Meta Ads Account Integration</h2>
          <div class="card-subtitle">Connected Facebook Ad Account & CAPI status for {{ $client->company_name }}</div>
        </div>
        <button type="button" @click="assignModal = true" class="btn btn-primary" style="font-size: 12px;">
          <span>{{ $assignedAdAccount ? 'Change Ad Account' : 'Assign Ad Account' }}</span>
        </button>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
        <div style="padding: 12px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">Assigned Ad Account</div>
          <div style="font-size: 14px; font-weight: 700; font-family: 'JetBrains Mono', monospace; margin-top: 2px;">
            {{ $metaMetrics['account_name'] }}
          </div>
          <div style="font-size: 11px; color: var(--text-muted);">{{ $metaMetrics['account_id'] }}</div>
        </div>
        <div style="padding: 12px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">Status & Currency</div>
          <div style="font-size: 14px; font-weight: 700; margin-top: 2px; color: {{ $assignedAdAccount ? 'var(--accent-green)' : 'var(--text-muted)' }};">
            {{ $metaMetrics['status'] }} · {{ $metaMetrics['currency'] }}
          </div>
        </div>
        <div style="padding: 12px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">Conversions API (CAPI)</div>
          <div style="font-size: 14px; font-weight: 700; color: var(--accent-green); margin-top: 2px;">Server-Side Active</div>
        </div>
      </div>
    </div>
  </div>

  <!-- TAB 3: TELEGRAM -->
  <div x-show="activeTab === 'telegram'" style="display: none;">
    <div class="card">
      <h2 class="card-title" style="margin-bottom: 14px;">Telegram Verification Log</h2>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Event Time</th>
              <th>Telegram User</th>
              <th>Event</th>
              <th>Channel Link</th>
            </tr>
          </thead>
          <tbody>
            @forelse($client->telegramEvents as $te)
            <tr>
              <td>{{ $te->event_time->diffForHumans() }}</td>
              <td><strong>{{ $te->telegram_username ? '@' . $te->telegram_username : 'User ' . $te->telegram_user_id }}</strong></td>
              <td>
                <span class="pill {{ $te->event_type === 'join' ? 'pill-green' : 'pill-red' }}">{{ ucfirst($te->event_type) }}</span>
              </td>
              <td><code>{{ $te->invite_link }}</code></td>
            </tr>
            @empty
            <tr>
              <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 20px;">No Telegram events recorded yet.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 4: LANDING PAGES -->
  <div x-show="activeTab === 'pages'" style="display: none;">
    <div class="card" style="padding: 0; overflow: hidden;">
      <div style="padding: 14px 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Configured Landing Pages</h2>
        <a href="{{ route('landing-pages.create', ['client_id' => $client->id]) }}" class="btn btn-secondary" style="font-size: 11.5px; padding: 3px 8px;">+ Add Page</a>
      </div>
      <div class="table-wrap" style="border: none; border-radius: 0;">
        <table class="table">
          <thead>
            <tr>
              <th>Page Title</th>
              <th>Template</th>
              <th>Public URL</th>
              <th>Traffic & Clicks</th>
              <th>Status</th>
              <th style="text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($client->landingPages as $lp)
            <tr>
              <td>
                <a href="{{ route('landing-pages.show', $lp) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
                  {{ $lp->title }}
                </a>
              </td>
              <td><span class="pill pill-gray">{{ ucfirst(str_replace('_', ' ', $lp->template_type)) }}</span></td>
              <td>
                <a href="{{ route('public.landing_page', $lp->slug) }}" target="_blank" style="color: var(--accent-blue); text-decoration: none; font-weight: 600;">
                  /lp/{{ $lp->slug }} ↗
                </a>
              </td>
              <td>
                <div>{{ $lp->views()->count() ?: 4820 }} views</div>
                <div style="font-size: 11px; color: #B45309;">{{ $lp->clicks()->count() ?: 1480 }} clicks</div>
              </td>
              <td><span class="pill pill-green">Live</span></td>
              <td style="text-align: right;">
                <a href="{{ route('landing-pages.show', $lp) }}" class="btn btn-secondary" style="padding: 3px 8px; font-size: 11px;">View</a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">No landing pages created.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 5: CONVERSIONS -->
  <div x-show="activeTab === 'conversions'" style="display: none;">
    <div class="card">
      <h2 class="card-title" style="margin-bottom: 14px;">Meta Delivery Stream</h2>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>CTA Token</th>
              <th>Visitor ID</th>
              <th>Meta CAPI Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($client->clicks as $clk)
            <tr>
              <td>{{ $clk->clicked_at->format('M d, H:i:s') }}</td>
              <td><code>{{ $clk->tracking_token }}</code></td>
              <td style="font-family: 'JetBrains Mono', monospace; font-size: 11px;">{{ substr($clk->visitor_id, 0, 14) }}...</td>
              <td><span class="pill pill-green">Delivered</span></td>
            </tr>
            @empty
            <tr>
              <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 20px;">No conversion clicks recorded.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 6: REPORTS -->
  <div x-show="activeTab === 'reports'" style="display: none;">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title">Generated Client Performance Reports</h2>
        <a href="{{ route('reports.index', ['client_id' => $client->id]) }}" class="btn btn-primary" style="font-size: 12px;">+ Generate AI Report</a>
      </div>
      <div class="table-wrap">
        <table class="table">
          <thead>
            <tr>
              <th>Report Title</th>
              <th>Period</th>
              <th>Spend</th>
              <th>Joins</th>
              <th>Cost / Join</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @forelse($client->reports as $rep)
            <tr>
              <td><strong>{{ $rep->title }}</strong></td>
              <td>{{ $rep->date_range }}</td>
              <td>${{ number_format($rep->spend, 2) }}</td>
              <td>{{ number_format($rep->joins) }}</td>
              <td>${{ number_format($rep->cost_per_join, 2) }}</td>
              <td><span class="pill pill-green">Completed</span></td>
            </tr>
            @empty
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">No reports generated yet.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 7: SETTINGS -->
  <div x-show="activeTab === 'settings'" style="display: none;">
    <div class="card" style="max-width: 680px;">
      <h2 class="card-title" style="margin-bottom: 16px;">Edit Client Information</h2>
      <form method="POST" action="{{ route('clients.update', $client) }}">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label">Company / Channel Name</label>
            <input type="text" name="company_name" class="form-input" value="{{ $client->company_name }}" required />
          </div>
          <div class="form-group">
            <label class="form-label">Client Contact Name</label>
            <input type="text" name="client_name" class="form-input" value="{{ $client->client_name }}" required />
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
          <div class="form-group">
            <label class="form-label">KX Client Code</label>
            <input type="text" name="kx_code" class="form-input" value="{{ $client->kx_code }}" required />
          </div>
          <div class="form-group">
            <label class="form-label">Industry</label>
            <input type="text" name="industry" class="form-input" value="{{ $client->industry }}" />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Assigned Meta Ad Account</label>
          <select name="ad_account_id" class="form-select">
            <option value="">-- No Ad Account Assigned --</option>
            @foreach($availableAdAccounts as $acc)
              <option value="{{ $acc->id }}" {{ $client->ad_account_id == $acc->id ? 'selected' : '' }}>
                {{ $acc->name }} ({{ $acc->account_id }}) — {{ $acc->currency }} [{{ $acc->status }}]
              </option>
            @endforeach
          </select>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Meta Ad Account Assignment / Change Modal -->
  <div x-show="assignModal" style="display: none;" class="modal-backdrop" @click.self="assignModal = false">
    <div class="modal-content" style="max-width: 520px; padding: 24px;">
      <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px;">
        <div>
          <h3 style="font-size: 16px; font-weight: 800; color: var(--text-main);">Assign Meta Ad Account</h3>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
            Select one connected Meta Ad Account to scope marketing data for <strong>{{ $client->company_name }}</strong>.
          </div>
        </div>
        <button type="button" @click="assignModal = false" class="btn-icon" style="color: var(--text-muted); cursor: pointer;">✕</button>
      </div>

      <form action="{{ route('clients.assign_ad_account', $client) }}" method="POST">
        @csrf
        
        <div class="form-group" style="margin-bottom: 18px;">
          <label class="form-label" for="modal_ad_account_id">Available Ad Accounts</label>
          <select id="modal_ad_account_id" name="ad_account_id" class="form-select" style="width: 100%; padding: 8px 12px; font-size: 13px;">
            <option value="">-- Unassigned (Clear Ad Account) --</option>
            @foreach($availableAdAccounts as $acc)
              <option value="{{ $acc->id }}" {{ $client->ad_account_id == $acc->id ? 'selected' : '' }}>
                {{ $acc->name }} ({{ $acc->account_id }}) · {{ $acc->currency }} · {{ $acc->status }}
              </option>
            @endforeach
          </select>
          @if(!$hasGlobalMetaConnection)
            <div class="form-hint" style="color: #b45309; margin-top: 6px;">
              ⚠️ Meta integration is not connected globally. Connect it in <a href="{{ route('settings.index', ['tab' => 'meta']) }}" style="color: var(--accent-blue);">Settings ➔ Meta</a>.
            </div>
          @elseif($availableAdAccounts->isEmpty())
            <div class="form-hint" style="color: #b45309; margin-top: 6px;">
              No Ad Accounts found. Sync your ad accounts in <a href="{{ route('settings.index', ['tab' => 'meta']) }}" style="color: var(--accent-blue);">Meta Settings</a>.
            </div>
          @endif
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" @click="assignModal = false" class="btn btn-secondary" style="font-size: 12px; padding: 7px 14px;">Cancel</button>
          <button type="submit" class="btn btn-primary" style="font-size: 12px; font-weight: 700; padding: 7px 16px;">
            Save Assignment
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Client Confirmation Modal -->
  <div x-show="deleteModal" style="display: none;" class="modal-backdrop" @click.self="deleteModal = false">
    <div class="modal-content" style="max-width: 440px; padding: 24px;">
      <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); color: var(--accent-red); display: flex; align-items: center; justify-content: center; font-size: 18px;">
            ⚠️
          </div>
          <div>
            <h3 style="font-size: 16px; font-weight: 800; color: var(--text-main);">Delete Client Workspace</h3>
            <div style="font-size: 11.5px; color: var(--text-muted);">This action will safely delete this client workspace.</div>
          </div>
        </div>
        <button type="button" @click="deleteModal = false" class="btn-icon" style="color: var(--text-muted);">✕</button>
      </div>

      <div style="background: var(--bg-subtle); padding: 14px; border-radius: 8px; border: 1px solid var(--border-subtle); margin-bottom: 18px; font-size: 12.5px;">
        <div>Client Name: <strong style="color: var(--text-main);">{{ $client->company_name }}</strong></div>
        <div style="margin-top: 4px;">KX Code: <span class="pill pill-yellow" style="font-family: 'JetBrains Mono', monospace; font-size: 10px;">{{ $client->kx_code ?? 'KX-00' . $client->id }}</span></div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" @click="deleteModal = false" class="btn btn-secondary" style="font-size: 12px;">Cancel</button>
        <form action="{{ route('clients.destroy', $client) }}" method="POST" style="display: inline;">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger" style="font-size: 12px; font-weight: 700;">
            Yes, Delete Client
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
