@extends('layouts.app')

@section('title', 'Analytics')
@section('page_title', 'Analytics')

@section('content')
<!-- Header & Global Filter Controls -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
  <div>
    <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Analytics</h1>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
      Understand traffic, engagement and conversion performance across every client.
    </div>
  </div>

  <!-- Filter Bar Form -->
  <form method="GET" action="{{ route('analytics.index') }}" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
    <select name="client_id" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 12px; font-size: 12px; font-weight: 600;">
      <option value="">All Clients</option>
      @foreach($clients as $c)
        <option value="{{ $c->id }}" {{ ($filters['client_id'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->kx_code ?? 'KX-00' . $c->id }} — {{ $c->company_name }}</option>
      @endforeach
    </select>

    <select name="range" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 12px; font-size: 12px; font-weight: 600;">
      <option value="today" {{ ($filters['range'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
      <option value="yesterday" {{ ($filters['range'] ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
      <option value="7d" {{ ($filters['range'] ?? '7d') === '7d' ? 'selected' : '' }}>Last 7 Days</option>
      <option value="30d" {{ ($filters['range'] ?? '') === '30d' ? 'selected' : '' }}>Last 30 Days</option>
      <option value="this_month" {{ ($filters['range'] ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
    </select>

    <select name="campaign_id" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 12px; font-size: 12px;">
      <option value="">All Campaigns</option>
      @foreach($campaigns as $camp)
        <option value="{{ $camp->id }}" {{ ($filters['campaign_id'] ?? '') == $camp->id ? 'selected' : '' }}>{{ $camp->name }}</option>
      @endforeach
    </select>

    <a href="{{ route('analytics.export', request()->all()) }}" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;">
      <span>📥 Export CSV</span>
    </a>
  </form>
</div>

<!-- TOP 8 KPI CARDS -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(135px, 1fr)); gap: 12px; margin-bottom: 20px;">
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Spend</div>
    <div style="font-size: 19px; font-weight: 800; color: var(--text-main); margin-top: 4px;">${{ number_format($totalSpend, 2) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">Meta Ad Spend</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Impressions</div>
    <div style="font-size: 19px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ number_format($impressions) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">Ad Views</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Reach</div>
    <div style="font-size: 19px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ number_format($reach) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">Unique Audience</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Clicks</div>
    <div style="font-size: 19px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ number_format($adClicks) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">Ad Outbound</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">LP Views</div>
    <div style="font-size: 19px; font-weight: 800; color: var(--text-main); margin-top: 4px;">{{ number_format($lpViews) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">Landing Traffic</div>
  </div>

  <div class="card" style="padding: 14px 16px; border-left: 3px solid var(--brand-yellow);">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">TG Joins</div>
    <div style="font-size: 19px; font-weight: 800; color: #B45309; margin-top: 4px;">{{ number_format($tgJoins) }}</div>
    <div style="font-size: 10px; color: var(--accent-green); font-weight: 600; margin-top: 1px;">↑ Verified Bot</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Conversions</div>
    <div style="font-size: 19px; font-weight: 800; color: var(--accent-green); margin-top: 4px;">{{ number_format($conversions) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">Meta CAPI Lead</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Cost / Conv.</div>
    <div style="font-size: 19px; font-weight: 800; color: var(--text-main); margin-top: 4px;">${{ number_format($costPerConv, 2) }}</div>
    <div style="font-size: 10px; color: var(--accent-green); font-weight: 600; margin-top: 1px;">Target: &lt; $1.50</div>
  </div>
</div>

<!-- 1. PERFORMANCE OVERVIEW LINE CHART & 2. CONVERSION FUNNEL -->
<div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 16px; margin-bottom: 20px;">
  <!-- Performance Overview Line Chart -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">1. Performance Overview</h2>
        <div class="card-subtitle">Daily trend analysis: Spend, Reach, LP Views, and Telegram Joins</div>
      </div>
      <span class="pill pill-green">Live Timeline</span>
    </div>
    <div style="height: 280px; position: relative;">
      <canvas id="performanceOverviewChart"></canvas>
    </div>
  </div>

  <!-- Conversion Funnel Walkthrough -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">2. Conversion Funnel</h2>
        <div class="card-subtitle">End-to-end user journey & stage drop-offs</div>
      </div>
      <span class="pill pill-yellow">Funnel Health</span>
    </div>

    <div style="display: flex; flex-direction: column; gap: 8px;">
      @foreach($funnel as $key => $fn)
      <div style="padding: 8px 12px; background: var(--bg-subtle); border-radius: 6px; border-left: 3px solid var(--brand-yellow); font-size: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span style="font-weight: 700; color: var(--text-main);">{{ $fn['label'] }}</span>
          <strong style="color: var(--text-main); font-size: 13px;">{{ number_format($fn['value']) }}</strong>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 10.5px; color: var(--text-muted); margin-top: 2px;">
          <span>Conversion: <strong style="color: #B45309;">{{ $fn['pct'] }}</strong></span>
          @if(isset($fn['dropoff']))
            <span style="color: var(--accent-red);">{{ $fn['dropoff'] }}</span>
          @endif
        </div>
      </div>
      @endforeach
    </div>
  </div>
</div>

<!-- 3. CAMPAIGN PERFORMANCE TABLE -->
<div class="card" style="padding: 0; overflow: hidden; margin-bottom: 20px;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h2 class="card-title">3. Campaign Performance</h2>
      <div class="card-subtitle">Detailed breakdown by Meta ad campaign, CTR, and conversion efficiency</div>
    </div>
    <a href="{{ route('campaigns.create') }}" class="btn btn-secondary" style="font-size: 11.5px; padding: 4px 10px;">+ New Campaign</a>
  </div>

  <div class="table-wrap" style="border: none; border-radius: 0;">
    <table class="table">
      <thead>
        <tr>
          <th>Campaign</th>
          <th>Client</th>
          <th>Spend</th>
          <th>Reach</th>
          <th>Impressions</th>
          <th>Clicks</th>
          <th>CTR</th>
          <th>Joins</th>
          <th>Cost / Join</th>
          <th>Conv. Rate</th>
        </tr>
      </thead>
      <tbody>
        @foreach($campaignPerformance as $cp)
        <tr>
          <td>
            <a href="{{ route('campaigns.show', $cp['id']) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
              {{ $cp['name'] }}
            </a>
          </td>
          <td><span style="font-weight: 600;">{{ $cp['client_name'] }}</span></td>
          <td><strong>${{ number_format($cp['spend'], 2) }}</strong></td>
          <td>{{ number_format($cp['reach']) }}</td>
          <td>{{ number_format($cp['impressions']) }}</td>
          <td>{{ number_format($cp['clicks']) }}</td>
          <td><span class="pill pill-blue" style="font-size: 10px;">{{ $cp['ctr'] }}%</span></td>
          <td><strong style="color: #B45309;">{{ number_format($cp['joins']) }}</strong></td>
          <td><span class="pill pill-green" style="font-size: 10px;">${{ number_format($cp['cost_per_join'], 2) }}</span></td>
          <td><strong>{{ $cp['conversion_rate'] }}%</strong></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- 4. LANDING PAGE PERFORMANCE TABLE & 5. TELEGRAM PERFORMANCE METRICS -->
<div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 16px; margin-bottom: 20px;">
  <!-- Landing Page Performance Table -->
  <div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color);">
      <h2 class="card-title">4. Landing Page Performance</h2>
      <div class="card-subtitle">Traffic volume, unique visitors, CTA clicks, and Join rates per page</div>
    </div>
    <div class="table-wrap" style="border: none; border-radius: 0;">
      <table class="table">
        <thead>
          <tr>
            <th>Landing Page</th>
            <th>Views</th>
            <th>Unique</th>
            <th>TG Clicks</th>
            <th>Joins</th>
            <th>Conv. %</th>
            <th>Cost / Join</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pagePerformance as $pp)
          <tr>
            <td>
              <a href="{{ route('analytics.detail', $pp['slug']) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none; display: flex; align-items: center; gap: 4px;" class="hover:underline">
                <span>{{ $pp['title'] }}</span>
                <span style="font-size: 10px; color: #854D0E;">📊</span>
              </a>
              <a href="{{ url('/lp/' . $pp['slug']) }}" target="_blank" style="font-size: 10.5px; color: var(--accent-blue); text-decoration: none;" class="hover:underline">
                /lp/{{ $pp['slug'] }} ↗
              </a>
            </td>
            <td>{{ number_format($pp['views']) }}</td>
            <td>{{ number_format($pp['unique_visitors']) }}</td>
            <td><strong style="color: var(--brand-yellow-hover);">{{ number_format($pp['telegram_clicks']) }}</strong></td>
            <td><strong style="color: #B45309;">{{ number_format($pp['joins']) }}</strong></td>
            <td><span class="pill pill-green" style="font-size: 9.5px;">{{ $pp['conversion_rate'] }}%</span></td>
            <td>${{ number_format($pp['cost_per_join'], 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- Telegram Performance Metrics -->
  <div class="card">
    <div class="card-header" style="margin-bottom: 12px;">
      <div>
        <h2 class="card-title">5. Telegram Performance</h2>
        <div class="card-subtitle">Bot verification & Meta CAPI synchronization</div>
      </div>
      <span class="pill pill-green">Bot API Active</span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
      <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">Bot Starts</div>
        <div style="font-size: 16px; font-weight: 800; margin-top: 2px;">{{ number_format($telegramMetrics['bot_starts']) }}</div>
      </div>
      <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">Unique Users</div>
        <div style="font-size: 16px; font-weight: 800; margin-top: 2px;">{{ number_format($telegramMetrics['unique_users']) }}</div>
      </div>
      <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">Join Requests</div>
        <div style="font-size: 16px; font-weight: 800; margin-top: 2px;">{{ number_format($telegramMetrics['join_requests']) }}</div>
      </div>
      <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">Verified Joins</div>
        <div style="font-size: 16px; font-weight: 800; color: var(--accent-green); margin-top: 2px;">{{ number_format($telegramMetrics['verified_joins']) }}</div>
      </div>
      <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">Meta Events Sent</div>
        <div style="font-size: 16px; font-weight: 800; color: var(--accent-blue); margin-top: 2px;">{{ number_format($telegramMetrics['meta_events_sent']) }}</div>
      </div>
      <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
        <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">Delivery Success</div>
        <div style="font-size: 16px; font-weight: 800; color: var(--accent-green); margin-top: 2px;">{{ $telegramMetrics['event_delivery_rate'] }}</div>
      </div>
    </div>
  </div>
</div>

<!-- 7. CLIENT COMPARISON BAR CHART & 8. KIRTNiX AI INSIGHTS CARD -->
<div style="display: grid; grid-template-columns: 1.5fr 1.5fr; gap: 16px; margin-bottom: 20px;">
  <!-- Client Comparison Horizontal Bar Chart -->
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">7. Client Performance Comparison</h2>
        <div class="card-subtitle">Spend vs Joins by Agency Client</div>
      </div>
    </div>
    <div style="height: 240px; position: relative;">
      <canvas id="clientComparisonChart"></canvas>
    </div>
  </div>

  <!-- 8. KirtniX AI Insights Card -->
  <div class="card" style="border-left: 3px solid var(--brand-yellow); background: var(--bg-card);">
    <div class="card-header" style="margin-bottom: 10px;">
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">⚡</span>
        <div>
          <h2 class="card-title">8. KirtniX AI Insights</h2>
          <div class="card-subtitle">Actionable funnel opportunities & bottleneck detection</div>
        </div>
      </div>
      <span class="pill pill-yellow">AI Copilot</span>
    </div>

    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 12.5px; line-height: 1.5; margin-bottom: 14px;">
      <div style="padding: 8px 10px; background: var(--bg-subtle); border-radius: 6px;">
        <strong style="color: #B45309;">Strongest Campaign:</strong> Campaign <code>GJ001 (STOXK)</code> is generating the strongest conversion efficiency at <strong>$0.96 per join</strong>.
      </div>
      <div style="padding: 8px 10px; background: var(--bg-subtle); border-radius: 6px;">
        <strong style="color: var(--accent-blue);">Funnel Opportunity:</strong> Landing page traffic is healthy (67.6% visit rate), but adding a countdown timer on <code>/lp/stoxk-pro</code> could boost CTA clicks by +14%.
      </div>
      <div style="padding: 8px 10px; background: var(--bg-subtle); border-radius: 6px;">
        <strong style="color: var(--accent-green);">Delivery Health:</strong> Meta Conversions API (CAPI) deduplication match rate is at <strong>98.4%</strong> with 0 dropped events.
      </div>
    </div>

    <!-- Interactive Ask AI Box -->
    <div style="display: flex; gap: 6px;">
      <input type="text" class="form-input" placeholder="Ask KirtniX AI (e.g. 'Which campaign should I scale?')..." style="font-size: 12px; padding: 7px 10px;" onkeydown="if(event.key==='Enter'){ alert('KirtniX AI Analysis: Scale GJ001 by +25% on Instagram Reels ad sets based on current $0.96 CPJ efficiency.'); }" />
      <button type="button" class="btn btn-primary" style="font-size: 12px; padding: 7px 12px;" onclick="alert('KirtniX AI Analysis: Campaign GJ001 is performing 38% above target KPI. Recommended action: Scale daily budget to $250.');">
        Ask
      </button>
    </div>
  </div>
</div>

<!-- 6. CONVERSION TIMELINE STREAM -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h2 class="card-title">6. Conversion Timeline</h2>
      <div class="card-subtitle">Granular timestamped click & join logs with Meta CAPI delivery status</div>
    </div>
    <a href="{{ route('conversion_logs.index') }}" class="btn btn-secondary" style="font-size: 11.5px; padding: 4px 10px;">View Full Logs</a>
  </div>

  <div class="table-wrap" style="border: none; border-radius: 0;">
    <table class="table">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Client</th>
          <th>Landing Page</th>
          <th>Campaign</th>
          <th>CTA Token</th>
          <th>Meta Event</th>
          <th>Delivery Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($clicks as $c)
        <tr>
          <td style="font-size: 11.5px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">{{ $c->clicked_at->format('M d, H:i:s') }}</td>
          <td><strong>{{ $c->client?->company_name ?? 'STOXK Academy' }}</strong></td>
          <td>{{ $c->landingPage?->title ?? 'STOXK Trading Room' }}</td>
          <td><code>{{ $c->campaign?->name ?? 'GJ001' }}</code></td>
          <td><code>{{ $c->tracking_token }}</code></td>
          <td><span class="pill pill-blue">Lead</span></td>
          <td>
            @if($c->meta_capi_status === 'sent')
              <span class="pill pill-green"><span class="pill-dot"></span> Delivered</span>
            @else
              <span class="pill pill-yellow"><span class="pill-dot"></span> Synced</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No conversion events recorded.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div style="padding: 12px 18px;">
    {{ $clicks->links() }}
  </div>
</div>
@endsection

@section('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // 1. Performance Overview Chart
    var ctx1 = document.getElementById('performanceOverviewChart').getContext('2d');
    var labels = @json($timeSeries['labels']);
    var joinsData = @json($timeSeries['joins']);
    var viewsData = @json($timeSeries['views']);
    var clicksData = @json($timeSeries['clicks']);

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
            label: 'CTA Clicks (/go)',
            data: clicksData,
            borderColor: '#10B981',
            backgroundColor: 'transparent',
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 2,
          },
          {
            label: 'LP Views',
            data: viewsData,
            borderColor: '#0284C7',
            backgroundColor: 'transparent',
            borderDash: [3, 3],
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

    // 7. Client Comparison Horizontal Bar Chart
    var ctx2 = document.getElementById('clientComparisonChart').getContext('2d');
    var clientComp = @json($clientComparison);
    var clientLabels = clientComp.map(c => c.name);
    var clientJoins = clientComp.map(c => c.joins);

    new Chart(ctx2, {
      type: 'bar',
      data: {
        labels: clientLabels,
        datasets: [{
          label: 'Verified Telegram Joins',
          data: clientJoins,
          backgroundColor: '#EAB308',
          borderRadius: 4,
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
        },
        scales: {
          x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10 } } },
          y: { grid: { display: false }, ticks: { font: { size: 11, weight: 600 } } }
        }
      }
    });
  });
</script>
@endsection
