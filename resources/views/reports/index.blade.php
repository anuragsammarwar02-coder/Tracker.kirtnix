@extends('layouts.app')

@section('title', 'Reports')
@section('page_title', 'Client Reports')

@section('content')
<!-- Header Area -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
  <div>
    <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Reports</h1>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
      Client-wise ad performance, joins and funnel — with an AI that writes the report for you.
    </div>
  </div>

  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <form method="POST" action="{{ route('reports.generate_ai') }}">
      @csrf
      <input type="hidden" name="client_id" value="{{ $activeClient?->id }}" />
      <input type="hidden" name="date_range" value="{{ $dateRange }}" />
      <button type="submit" class="btn btn-primary">
        <span>⚡ Generate AI Report</span>
      </button>
    </form>

    <a href="{{ route('reports.export_csv') }}" class="btn btn-secondary">
      <span>📥 Export CSV</span>
    </a>
  </div>
</div>

<!-- Filters Bar -->
<div class="card" style="padding: 12px 16px; margin-bottom: 20px;">
  <form method="GET" action="{{ route('reports.index') }}" style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 10px;">
      <span style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Client:</span>
      <select name="client_id" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 12px; font-size: 12px; font-weight: 600;">
        @foreach($clients as $c)
          <option value="{{ $c->id }}" {{ $activeClient?->id == $c->id ? 'selected' : '' }}>
            {{ $c->kx_code ?? 'KX-00' . $c->id }} — {{ $c->company_name }}
          </option>
        @endforeach
      </select>

      <span style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-left: 8px;">Period:</span>
      <select name="date_range" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 12px; font-size: 12px; font-weight: 600;">
        <option value="Last 7 Days" {{ $dateRange === 'Last 7 Days' ? 'selected' : '' }}>Last 7 Days</option>
        <option value="Last 30 Days" {{ $dateRange === 'Last 30 Days' ? 'selected' : '' }}>Last 30 Days</option>
        <option value="This Month" {{ $dateRange === 'This Month' ? 'selected' : '' }}>This Month</option>
      </select>
    </div>

    <div style="font-size: 11.5px; color: var(--text-muted);">
      Viewing audit for: <strong>{{ $activeClient?->company_name }}</strong>
    </div>
  </form>
</div>

<!-- 4 Key Metrics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px;">
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Spend</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 2px;">{{ $currencySymbol }}{{ number_format($totalSpend, 2) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle);">Meta Ads Budget</div>
  </div>

  <div class="card" style="padding: 14px 16px; border-left: 3px solid var(--brand-yellow);">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Joins</div>
    <div style="font-size: 20px; font-weight: 800; color: #B45309; margin-top: 2px;">{{ number_format($totalJoins) }}</div>
    <div style="font-size: 10px; color: var(--accent-green); font-weight: 600;">↑ Verified Members</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Cost / Join</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--accent-green); margin-top: 2px;">{{ $currencySymbol }}{{ number_format($costPerJoin, 2) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle);">Target: {{ $currencySymbol }}1.20</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Reach</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 2px;">{{ number_format($totalReach) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle);">Unique Impressions</div>
  </div>
</div>

<!-- AI Generated Report Document Box -->
@if($latestReport)
<div class="card" style="border-left: 4px solid var(--brand-yellow); margin-bottom: 24px; padding: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
    <div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 18px;">⚡</span>
        <h2 style="font-size: 16px; font-weight: 800; color: var(--text-main);">{{ $latestReport->title }}</h2>
      </div>
      <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
        Period: {{ $latestReport->date_range }} · Generated {{ $latestReport->created_at?->diffForHumans() ?? 'today' }} by KirtniX AI Engine
      </div>
    </div>

    <div style="display: flex; gap: 6px;">
      <button type="button" class="btn btn-secondary" style="font-size: 11.5px; padding: 4px 10px;" onclick="window.print();">
        <span>🖨 Export PDF</span>
      </button>
      <form method="POST" action="{{ route('reports.generate_ai') }}" style="display: inline;">
        @csrf
        <input type="hidden" name="client_id" value="{{ $activeClient?->id }}" />
        <button type="submit" class="btn btn-secondary" style="font-size: 11.5px; padding: 4px 10px;">
          <span>🔄 Regenerate</span>
        </button>
      </form>
    </div>
  </div>

  <!-- AI Executive Summary -->
  <div style="background: var(--bg-subtle); border: 1px solid var(--border-subtle); border-radius: 8px; padding: 14px 18px; margin-bottom: 18px; font-size: 13px; line-height: 1.6;">
    <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">Executive Summary:</strong>
    <p>{{ $latestReport->ai_summary }}</p>
  </div>

  <!-- Detailed AI Sections Grid -->
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
    <!-- Observations -->
    <div style="padding: 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;">
      <strong style="font-size: 12px; color: var(--accent-blue); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Key Observations:</strong>
      <div style="font-size: 12.5px; line-height: 1.6; white-space: pre-line; color: var(--text-body);">{{ $latestReport->ai_observations }}</div>
    </div>

    <!-- Recommendations -->
    <div style="padding: 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;">
      <strong style="font-size: 12px; color: var(--accent-green); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">CRO & Scaling Recommendations:</strong>
      <div style="font-size: 12.5px; line-height: 1.6; white-space: pre-line; color: var(--text-body);">{{ $latestReport->ai_recommendations }}</div>
    </div>

    <!-- Issues -->
    <div style="padding: 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;">
      <strong style="font-size: 12px; color: var(--accent-red); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Bottlenecks & Warnings:</strong>
      <div style="font-size: 12.5px; line-height: 1.6; white-space: pre-line; color: var(--text-body);">{{ $latestReport->ai_issues }}</div>
    </div>

    <!-- Next Actions -->
    <div style="padding: 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;">
      <strong style="font-size: 12px; color: #B45309; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Next Action Checklist:</strong>
      <div style="font-size: 12.5px; line-height: 1.6; white-space: pre-line; color: var(--text-body);">{{ $latestReport->ai_next_actions }}</div>
    </div>
  </div>
</div>
@endif

<!-- Charts: Spend vs Joins & Spend by Client -->
<div style="display: grid; grid-template-columns: 1.5fr 1.5fr; gap: 16px; margin-bottom: 20px;">
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Spend vs Joins Trend</h2>
      <span class="pill pill-green">Real Timeline</span>
    </div>
    <div style="height: 240px; position: relative;">
      <canvas id="reportSpendChart"></canvas>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Spend by Client</h2>
      <span class="pill pill-yellow">Agency Distribution</span>
    </div>
    <div style="height: 240px; position: relative;">
      <canvas id="reportClientSpendChart"></canvas>
    </div>
  </div>
</div>

<!-- Client Table: Client, Spend, Reach, Views, Joins, Exits, Cost / Join -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color);">
    <h2 class="card-title">Agency Client Performance Audit Table</h2>
    <div class="card-subtitle">Full funnel overview across all accounts</div>
  </div>

  <div class="table-wrap" style="border: none; border-radius: 0;">
    <table class="table">
      <thead>
        <tr>
          <th>Client</th>
          <th>Lead Name</th>
          <th>Spend</th>
          <th>Reach</th>
          <th>Views</th>
          <th>Joins</th>
          <th>Exits</th>
          <th>Cost / Join</th>
        </tr>
      </thead>
      <tbody>
        @forelse($clientBreakdown as $cb)
        <tr>
          <td>
            <a href="{{ route('clients.show', $cb['id']) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
              {{ $cb['name'] }}
            </a>
            <div style="font-size: 10.5px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">{{ $cb['kx_code'] }}</div>
          </td>
          <td>{{ $cb['client_name'] }}</td>
          <td><strong>{{ $cb['currency_symbol'] }}{{ number_format($cb['spend'], 2) }}</strong></td>
          <td>{{ number_format($cb['reach']) }}</td>
          <td>{{ number_format($cb['views']) }}</td>
          <td><strong style="color: #B45309;">{{ number_format($cb['joins']) }}</strong></td>
          <td style="color: var(--accent-red);">{{ number_format($cb['exits']) }}</td>
          <td><span class="pill pill-green">{{ $cb['currency_symbol'] }}{{ number_format($cb['cost_per_join'], 2) }}</span></td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 32px;">No client report data available yet.</td>
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
    var ctx1 = document.getElementById('reportSpendChart').getContext('2d');
    var labels = @json($chartLabels);
    var spend = @json($spendSeries);
    var joins = @json($joinSeries);

    new Chart(ctx1, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Telegram Joins',
            data: joins,
            borderColor: '#EAB308',
            backgroundColor: 'rgba(234, 179, 8, 0.12)',
            fill: true,
            borderWidth: 2,
            tension: 0.35,
          },
          {
            label: 'Meta Spend ($)',
            data: spend,
            borderColor: '#0284C7',
            backgroundColor: 'transparent',
            borderWidth: 2,
            tension: 0.35,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { boxWidth: 10, font: { size: 11, weight: 600 } } } },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }
        }
      }
    });

    var ctx2 = document.getElementById('reportClientSpendChart').getContext('2d');
    var clients = @json($clientBreakdown);
    new Chart(ctx2, {
      type: 'doughnut',
      data: {
        labels: clients.map(c => c.name),
        datasets: [{
          data: clients.map(c => c.spend),
          backgroundColor: ['#EAB308', '#0284C7', '#10B981', '#8B5CF6', '#F97316'],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'right', labels: { boxWidth: 10, font: { size: 10, weight: 600 } } } },
        cutout: '68%',
      }
    });
  });
</script>
@endsection
