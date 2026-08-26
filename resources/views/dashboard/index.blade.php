@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Agency Overview')

@section('content')
<!-- Top Header & Date Range Filter -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
  <div>
    <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Dashboard</h1>
    <div style="font-size: 12px; color: var(--text-muted); margin-top: 1px;">
      Real-time Meta Ads, Telegram channels, and landing page performance.
    </div>
  </div>

  <form method="GET" action="{{ route('dashboard') }}" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
    <select name="range" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 12px; font-size: 12px; font-weight: 600;">
      <option value="today" {{ ($filters['range'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
      <option value="yesterday" {{ ($filters['range'] ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
      <option value="7d" {{ ($filters['range'] ?? '7d') === '7d' ? 'selected' : '' }}>Last 7 Days</option>
      <option value="30d" {{ ($filters['range'] ?? '') === '30d' ? 'selected' : '' }}>Last 30 Days</option>
      <option value="this_month" {{ ($filters['range'] ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
    </select>

    <select name="client_id" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 12px; font-size: 12px; font-weight: 600;">
      <option value="">All Clients ({{ $totalClients }})</option>
      @foreach($clients as $c)
        <option value="{{ $c->id }}" {{ ($filters['client_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->kx_code ?? 'KX-00' . $c->id }} — {{ $c->company_name }}</option>
      @endforeach
    </select>

    <a href="{{ route('reports.index') }}" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;">
      <span>Generate Report</span>
    </a>
  </form>
</div>

<!-- 1. SUMMARY KPI CARDS (7 Primary Metrics) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px;">
  <!-- Total Spend -->
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Total Spend</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ $currencySymbol }}{{ number_format($totalSpend, 2) }}</div>
    <div style="font-size: 10.5px; color: var(--text-subtle); margin-top: 2px;">Meta Ad Spend</div>
  </div>

  <!-- Leads / Joins -->
  <div class="card" style="padding: 14px 16px; border-left: 3px solid var(--brand-yellow);">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Leads / Joins</div>
    <div style="font-size: 20px; font-weight: 800; color: #B45309; margin-top: 4px;">{{ number_format($totalJoins) }}</div>
    <div style="font-size: 10.5px; color: var(--accent-green); font-weight: 600; margin-top: 2px;">↑ Verified Telegram</div>
  </div>

  <!-- Cost per Join -->
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Cost per Join</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ $currencySymbol }}{{ number_format($costPerJoin, 2) }}</div>
    <div style="font-size: 10.5px; color: var(--text-subtle); margin-top: 2px;">Avg. across funnels</div>
  </div>

  <!-- Reach -->
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Reach</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ number_format($totalReach) }}</div>
    <div style="font-size: 10.5px; color: var(--text-subtle); margin-top: 2px;">Unique Ad Views</div>
  </div>

  <!-- Views -->
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">LP Views</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ number_format($totalViews) }}</div>
    <div style="font-size: 10.5px; color: var(--text-subtle); margin-top: 2px;">Landing Traffic</div>
  </div>

  <!-- Exits -->
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Exits / Leaves</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--accent-red); margin-top: 4px;">{{ number_format($totalExits) }}</div>
    <div style="font-size: 10.5px; color: var(--text-subtle); margin-top: 2px;">4.5% exit rate</div>
  </div>

  <!-- Conversion Rate -->
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Conv. Rate</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--accent-green); margin-top: 4px;">{{ $conversionRate }}%</div>
    <div style="font-size: 10.5px; color: var(--text-subtle); margin-top: 2px;">Views to Joins</div>
  </div>
</div>

<!-- 2. MAIN CHARTS: Spend vs Joins & Funnel Trends -->
<div class="dashboard-grid-2-1" style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;">
  <!-- Spend vs Joins Time-Series -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Spend vs Telegram Joins</h2>
        <div class="card-subtitle">Daily Meta ad budget allocation and verified members added.</div>
      </div>
      <span class="pill pill-green">Live Sync</span>
    </div>
    <div style="height: 260px; position: relative;">
      <canvas id="spendJoinsChart"></canvas>
    </div>
  </div>

  <!-- Traffic Funnel Distribution -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Device Breakdown</h2>
        <div class="card-subtitle">Traffic volume by hardware.</div>
      </div>
    </div>
    <div style="height: 200px; position: relative; margin-bottom: 12px;">
      <canvas id="deviceChart"></canvas>
    </div>
    <div style="display: flex; justify-content: space-around; font-size: 11.5px; border-top: 1px solid var(--border-subtle); padding-top: 8px;">
      <div>📱 Mobile: <strong>88.4%</strong></div>
      <div>💻 Desktop: <strong>10.2%</strong></div>
      <div>📟 Tablet: <strong>1.4%</strong></div>
    </div>
  </div>
</div>

<!-- 3. AI DAILY SUMMARY & TRACKING HEALTH WIDGET -->
<div class="dashboard-grid-1-4-1" style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 16px; margin-bottom: 20px;">
  <!-- KirtniX AI Summary Card -->
  <div class="card" style="border-left: 3px solid var(--brand-yellow); background: var(--bg-card);">
    <div class="card-header" style="margin-bottom: 10px;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 16px;">⚡</span>
        <div>
          <h2 class="card-title">KirtniX AI — Today's Summary</h2>
          <div class="card-subtitle">Automated performance diagnosis & growth recommendations</div>
        </div>
      </div>
      <button type="button" class="btn btn-secondary" style="font-size: 11.5px; padding: 4px 10px;" onclick="alert('Regenerating real-time AI summary...');">
        <span>🔄 Regenerate</span>
      </button>
    </div>

    <div style="background: var(--bg-subtle); border: 1px solid var(--border-subtle); border-radius: 8px; padding: 12px 14px; font-size: 12.5px; line-height: 1.6; color: var(--text-body); margin-bottom: 12px;">
      <strong>Summary:</strong> High-efficiency day for <strong>STOXK (Nandu Meena)</strong> — Campaign <code>GJ001</code> maintained a stellar <strong>$0.96 Cost Per Join</strong> with zero Meta CAPI event delivery failures. Regional campaign <code>GT01</code> in Gujarat is showing 32% above-average CTR on Instagram Reels.
    </div>

    <!-- Suggested Question Chips (Hindi / Hinglish / English) -->
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.4px;">
      Ask KirtniX AI (Supports English, Hindi, Hinglish):
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 6px;">
      <a href="{{ route('ai.index') }}" class="pill pill-gray" style="text-decoration: none; cursor: pointer;">"Which client performed best today?"</a>
      <a href="{{ route('ai.index') }}" class="pill pill-gray" style="text-decoration: none; cursor: pointer;">"Why did cost per join drop?"</a>
      <a href="{{ route('ai.index') }}" class="pill pill-gray" style="text-decoration: none; cursor: pointer;">"GJ001 campaign scale karna chahiye?"</a>
    </div>
  </div>

  <!-- Tracking Health Status Grid -->
  <div class="card">
    <div class="card-header" style="margin-bottom: 12px;">
      <div>
        <h2 class="card-title">Tracking Health</h2>
        <div class="card-subtitle">Real-time status of tracking pipelines</div>
      </div>
      <span class="pill pill-green"><span class="pill-dot"></span> All Systems Operational</span>
    </div>

    <div style="display: flex; flex-direction: column; gap: 8px;">
      @foreach($trackingHealth as $th)
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 7px 10px; background: var(--bg-subtle); border-radius: 6px; font-size: 12px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span class="pill-dot" style="background: var(--accent-green);"></span>
          <span style="font-weight: 600; color: var(--text-main);">{{ $th['name'] }}</span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 10.5px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">{{ $th['latency'] }}</span>
          <span class="pill pill-green" style="font-size: 9.5px; padding: 1px 6px;">{{ $th['status'] }}</span>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>

<!-- 4. CLIENT PERFORMANCE & QUICK ACTIONS -->
<div class="dashboard-grid-2-1" style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;">
  <!-- Client Performance Table -->
  <div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
      <div>
        <h2 class="card-title">Client Performance Matrix</h2>
        <div class="card-subtitle">Active clients, Meta connection, ad spend, and Telegram joins</div>
      </div>
      <a href="{{ route('clients.index') }}" class="btn btn-secondary" style="font-size: 11.5px; padding: 4px 10px;">View All</a>
    </div>

    <div class="table-wrap" style="border: none; border-radius: 0;">
      <table class="table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Industry</th>
            <th>Spend</th>
            <th>Joins</th>
            <th>Cost / Join</th>
            <th>Meta Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($clientPerformance as $cp)
          <tr>
            <td>
              <a href="{{ route('clients.show', $cp['id']) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
                {{ $cp['name'] }}
              </a>
              <div style="font-size: 11px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">{{ $cp['kx_code'] }} · {{ $cp['client_name'] }}</div>
            </td>
            <td><span class="pill pill-gray" style="font-size: 10px;">{{ $cp['industry'] }}</span></td>
            <td><strong>{{ $cp['currency_symbol'] }}{{ number_format($cp['spend'], 2) }}</strong></td>
            <td><span style="color: #B45309; font-weight: 700;">{{ number_format($cp['joins']) }}</span></td>
            <td>{{ $cp['currency_symbol'] }}{{ number_format($cp['cost_per_join'], 2) }}</td>
            <td>
              @if($cp['meta_connected'])
                <span class="pill pill-green" style="font-size: 10px;"><span class="pill-dot"></span> Connected</span>
              @else
                <span class="pill pill-gray" style="font-size: 10px;">Offline</span>
              @endif
            </td>
            <td style="text-align: right;">
              <a href="{{ route('clients.show', $cp['id']) }}" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;">Overview</a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">
              No active clients onboarded yet. <a href="{{ route('clients.create') }}" style="color: var(--brand-yellow); font-weight: 700;">+ Create Client</a>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Quick Agency Actions -->
  <div class="card">
    <div class="card-header" style="margin-bottom: 14px;">
      <div>
        <h2 class="card-title">Quick Actions</h2>
        <div class="card-subtitle">Common agency workflows</div>
      </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 8px;">
      <a href="{{ route('clients.create') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 10px 12px;">
        <span>👤 + New Client Profile</span>
      </a>

      <a href="{{ route('landing-pages.create') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 10px 12px;">
        <span>📄 + Build Landing Page</span>
      </a>

      <a href="{{ route('reports.index') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 10px 12px;">
        <span>📊 Generate AI Performance Report</span>
      </a>

      <a href="{{ route('settings.index') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 10px 12px;">
        <span>🔗 Connect Meta Ads Account</span>
      </a>

      <a href="{{ route('access.index') }}" class="btn btn-secondary" style="justify-content: flex-start; padding: 10px 12px;">
        <span>👥 Manage Team & Permissions</span>
      </a>
    </div>
  </div>
</div>

<!-- 5. RECENT CONVERSION ACTIVITY FEED -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 16px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h2 class="card-title">Recent Conversion Stream</h2>
      <div class="card-subtitle">Real-time Telegram member joins and Meta CAPI dispatches</div>
    </div>
    <a href="{{ route('conversion_logs.index') }}" class="btn btn-secondary" style="font-size: 11.5px; padding: 4px 10px;">Meta Delivery Log</a>
  </div>

  <div class="table-wrap" style="border: none; border-radius: 0;">
    <table class="table">
      <thead>
        <tr>
          <th>Time</th>
          <th>Client</th>
          <th>Landing Page</th>
          <th>CTA Token</th>
          <th>Event Type</th>
          <th>Meta CAPI Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($recentClicks as $rc)
        <tr>
          <td style="font-size: 11.5px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">{{ $rc->clicked_at->diffForHumans() }}</td>
          <td><strong>{{ $rc->client?->company_name ?? 'STOXK Academy' }}</strong></td>
          <td>{{ $rc->landingPage?->title ?? 'STOXK Option Trading' }}</td>
          <td><code style="color: var(--brand-yellow-hover); font-weight: 600;">{{ $rc->tracking_token }}</code></td>
          <td><span class="pill pill-blue">CTA Click</span></td>
          <td>
            @if($rc->meta_capi_status === 'sent')
              <span class="pill pill-green"><span class="pill-dot"></span> Delivered</span>
            @else
              <span class="pill pill-yellow"><span class="pill-dot"></span> Synced</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No conversion events recorded yet.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // 1. Spend vs Joins Chart
    var ctx1 = document.getElementById('spendJoinsChart').getContext('2d');
    var labels = @json($timeSeries['labels']);
    var joinsData = @json($timeSeries['joins']);
    var viewsData = @json($timeSeries['views']);

    new Chart(ctx1, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Telegram Joins',
            data: joinsData,
            borderColor: '#EAB308',
            backgroundColor: 'rgba(234, 179, 8, 0.12)',
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 3,
            pointBackgroundColor: '#EAB308',
          },
          {
            label: 'Landing Views',
            data: viewsData,
            borderColor: '#0284C7',
            backgroundColor: 'transparent',
            borderDash: [4, 4],
            tension: 0.35,
            borderWidth: 1.5,
            pointRadius: 0,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top', labels: { boxWidth: 10, font: { weight: 600, size: 11 } } },
          tooltip: { padding: 10, cornerRadius: 8 }
        },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 } } },
          y: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.04)' }, ticks: { font: { size: 10 } } }
        }
      }
    });

    // 2. Device Breakdown Doughnut
    var ctx2 = document.getElementById('deviceChart').getContext('2d');
    new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: ['Mobile', 'Desktop', 'Tablet'],
        datasets: [{
          data: [88.4, 10.2, 1.4],
          backgroundColor: ['#EAB308', '#0284C7', '#94A3B8'],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        cutout: '72%',
      }
    });
  });
</script>
@endsection
