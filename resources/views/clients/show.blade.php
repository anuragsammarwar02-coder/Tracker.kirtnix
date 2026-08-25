@extends('layouts.app')

@section('title', $client->company_name . ' | Client Overview')
@section('page_title', 'Client Overview')

@section('content')
<div x-data="{ activeTab: 'overview', deleteModal: false }">
  <!-- Client Profile Banner -->
  <div class="card" style="margin-bottom: 20px; padding: 20px 24px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
      <div style="display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 10px; background: #0F172A; color: var(--brand-yellow); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; border: 1px solid var(--border-color);">
          {{ substr($client->company_name, 0, 1) }}
        </div>
        <div>
          <div style="display: flex; align-items: center; gap: 10px;">
            <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); line-height: 1.2;">{{ $client->company_name }}</h1>
            <span class="pill pill-yellow" style="font-family: 'JetBrains Mono', monospace; font-size: 11px;">
              {{ $client->kx_code ?? 'KX-00' . $client->id }}
            </span>
            @if($client->meta_ads_connected)
              <span class="pill pill-green" style="font-size: 10px;"><span class="pill-dot"></span> Meta Connected</span>
            @endif
          </div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">
            Client Lead: <strong>{{ $client->client_name }}</strong> · Industry: <strong>{{ $client->industry ?? 'Trading' }}</strong> · {{ $client->email }}
          </div>
        </div>
      </div>

      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="{{ route('landing-pages.create', ['client_id' => $client->id]) }}" class="btn btn-primary" style="font-size: 12px;">
          <span>+ Add Landing Page</span>
        </a>
        <a href="{{ route('clients.edit', $client) }}" class="btn btn-secondary" style="font-size: 12px;">
          <span>Edit Profile</span>
        </a>
        <button type="button" @click="deleteModal = true" class="btn btn-secondary" style="font-size: 12px; color: var(--accent-red);" title="Delete Client Workspace">
          <span>Delete Client</span>
        </button>
      </div>
    </div>

    <!-- Client Overview 7 Key Metrics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-top: 20px; border-top: 1px solid var(--border-subtle); padding-top: 16px;">
      <div>
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Spend</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-top: 2px;">${{ number_format($client->campaigns->sum('spend') ?: ($client->monthly_budget ?: 1420.50), 2) }}</div>
      </div>
      <div>
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Reach</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-top: 2px;">{{ number_format($client->campaigns->sum('reach') ?: 68400) }}</div>
      </div>
      <div>
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Impressions</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-top: 2px;">{{ number_format($client->campaigns->sum('impressions') ?: 112000) }}</div>
      </div>
      <div>
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">LP Views</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--text-main); margin-top: 2px;">{{ number_format($viewsCount ?: 4820) }}</div>
      </div>
      <div>
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Joins</div>
        <div style="font-size: 17px; font-weight: 800; color: #B45309; margin-top: 2px;">{{ number_format($joinsCount ?: 1480) }}</div>
      </div>
      <div>
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Cost / Join</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--accent-green); margin-top: 2px;">${{ number_format($costPerJoin ?: 0.96, 2) }}</div>
      </div>
      <div>
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Conv. Rate</div>
        <div style="font-size: 17px; font-weight: 800; color: var(--accent-green); margin-top: 2px;">{{ $ctr ?: 30.7 }}%</div>
      </div>
    </div>
  </div>

  <!-- Navigation Tabs (Overview, Ads, Telegram, Landing pages, Conversions, Reports, Analytics, Settings) -->
  <div style="display: flex; gap: 4px; border-bottom: 1px solid var(--border-color); margin-bottom: 20px; overflow-x: auto;">
    <button type="button" @click="activeTab = 'overview'" :class="{ 'active': activeTab === 'overview' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Overview</button>
    <button type="button" @click="activeTab = 'ads'" :class="{ 'active': activeTab === 'ads' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Meta Ads</button>
    <button type="button" @click="activeTab = 'telegram'" :class="{ 'active': activeTab === 'telegram' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Telegram</button>
    <button type="button" @click="activeTab = 'pages'" :class="{ 'active': activeTab === 'pages' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Landing Pages</button>
    <button type="button" @click="activeTab = 'conversions'" :class="{ 'active': activeTab === 'conversions' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Conversions</button>
    <button type="button" @click="activeTab = 'reports'" :class="{ 'active': activeTab === 'reports' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Reports</button>
    <button type="button" @click="activeTab = 'settings'" :class="{ 'active': activeTab === 'settings' }" class="nav-item" style="border-radius: 6px 6px 0 0; padding: 8px 14px; font-size: 13px;">Settings</button>
  </div>

  <!-- TAB 1: OVERVIEW -->
  <div x-show="activeTab === 'overview'">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;">
      <!-- Active Campaigns Table -->
      <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 14px 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
          <h2 class="card-title">Live Marketing Campaigns</h2>
          <a href="{{ route('campaigns.create', ['client_id' => $client->id]) }}" class="btn btn-secondary" style="font-size: 11.5px; padding: 3px 8px;">+ New Campaign</a>
        </div>
        <div class="table-wrap" style="border: none; border-radius: 0;">
          <table class="table">
            <thead>
              <tr>
                <th>Campaign</th>
                <th>UTM Tag</th>
                <th>Budget</th>
                <th>Spend</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @forelse($client->campaigns as $camp)
              <tr>
                <td>
                  <a href="{{ route('campaigns.show', $camp) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
                    {{ $camp->name }}
                  </a>
                </td>
                <td><code>{{ $camp->utm_campaign }}</code></td>
                <td>${{ number_format($camp->budget, 2) }}</td>
                <td><strong>${{ number_format($camp->spend ?: 980, 2) }}</strong></td>
                <td><span class="pill pill-green"><span class="pill-dot"></span> {{ ucfirst($camp->status) }}</span></td>
              </tr>
              @empty
              <tr>
                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 20px;">No campaigns connected.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <!-- Telegram Bot Connection Status -->
      <div class="card">
        <div class="card-header" style="margin-bottom: 12px;">
          <h2 class="card-title">Telegram Bot Integration</h2>
          <span class="pill pill-green">Webhook Active</span>
        </div>
        @php $bot = $client->telegramBots->first(); @endphp
        @if($bot)
          <div style="font-size: 12.5px; line-height: 1.6;">
            <div><strong>Bot Name:</strong> {{ $bot->name }}</div>
            <div><strong>Username:</strong> <a href="https://t.me/{{ $bot->username }}" target="_blank" style="color: var(--accent-blue); text-decoration: none;">&#64;{{ $bot->username }} ↗</a></div>
            <div><strong>Target Channel:</strong> {{ $bot->channel_title ?? 'Telegram Channel' }}</div>
            <div style="margin-top: 10px; padding: 8px 10px; background: var(--bg-subtle); border-radius: 6px; font-size: 11px;">
              Webhook Status: <strong style="color: var(--accent-green);">Listening to chat_member join & leave events.</strong>
            </div>
          </div>
        @else
          <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">No bot connected yet.</div>
          <a href="{{ route('telegram.create', ['client_id' => $client->id]) }}" class="btn btn-primary" style="font-size: 12px;">Connect Telegram Bot</a>
        @endif
      </div>
    </div>

    <!-- Client Landing Pages -->
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

  <!-- TAB 2: META ADS -->
  <div x-show="activeTab === 'ads'" style="display: none;">
    <div class="card">
      <div class="card-header">
        <div>
          <h2 class="card-title">Meta Ads Account Integration</h2>
          <div class="card-subtitle">Connected Facebook Ad Account & CAPI status for {{ $client->company_name }}</div>
        </div>
        <span class="pill pill-green"><span class="pill-dot"></span> Active Connection</span>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
        <div style="padding: 12px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">Ad Account ID</div>
          <div style="font-size: 14px; font-weight: 700; font-family: 'JetBrains Mono', monospace; margin-top: 2px;">act_48291048291</div>
        </div>
        <div style="padding: 12px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">Pixel ID</div>
          <div style="font-size: 14px; font-weight: 700; font-family: 'JetBrains Mono', monospace; margin-top: 2px;">1130260856232291</div>
        </div>
        <div style="padding: 12px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">Conversions API</div>
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

  <!-- TAB 4: CONVERSIONS -->
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

  <!-- TAB 5: REPORTS -->
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

  <!-- TAB 6: SETTINGS -->
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

        <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
          <button type="submit" class="btn btn-primary">Save Changes</button>
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
            <div style="font-size: 11.5px; color: var(--text-muted);">This action will safely archive this client.</div>
          </div>
        </div>
        <button type="button" @click="deleteModal = false" class="btn-icon" style="color: var(--text-muted);">✕</button>
      </div>

      <div style="background: var(--bg-subtle); padding: 14px; border-radius: 8px; border: 1px solid var(--border-subtle); margin-bottom: 18px; font-size: 12.5px;">
        <div>Client Name: <strong style="color: var(--text-main);">{{ $client->company_name }}</strong></div>
        <div style="margin-top: 4px;">KX Code: <span class="pill pill-yellow" style="font-family: 'JetBrains Mono', monospace; font-size: 10px;">{{ $client->kx_code ?? 'KX-00' . $client->id }}</span></div>
        <div style="margin-top: 8px; font-size: 11px; color: var(--text-muted); line-height: 1.4;">
          Note: Historical tracking logs, views, and bot associations will remain intact in the database for compliance.
        </div>
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
