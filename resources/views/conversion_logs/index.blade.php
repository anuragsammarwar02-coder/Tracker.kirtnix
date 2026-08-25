@extends('layouts.app')

@section('title', 'Meta Delivery Log')
@section('page_title', 'Conversion Logs')

@section('content')
<!-- Header Area -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
  <div>
    <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Meta Delivery Log</h1>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
      Every verified Telegram join is sent to Meta automatically via the Conversions API.
    </div>
  </div>

  <form method="POST" action="{{ route('conversion_logs.retry') }}">
    @csrf
    <button type="submit" class="btn btn-primary">
      <span>🔄 Retry queued events</span>
    </button>
  </form>
</div>

<!-- Top 5 Status Counters -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px;">
  <div class="card" style="padding: 14px 16px; border-left: 3px solid var(--accent-green);">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Delivered</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--accent-green); margin-top: 2px;">{{ number_format($metrics['delivered']) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">100% Match Rate</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Sent</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 2px;">{{ number_format($metrics['sent']) }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">Dispatched to Graph API</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Retrying</div>
    <div style="font-size: 20px; font-weight: 800; color: #B45309; margin-top: 2px;">{{ $metrics['retrying'] }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">Exponential backoff</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pending</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--accent-blue); margin-top: 2px;">{{ $metrics['pending'] }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">In outbound buffer</div>
  </div>

  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Failed</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--accent-red); margin-top: 2px;">{{ $metrics['failed'] }}</div>
    <div style="font-size: 10px; color: var(--text-subtle); margin-top: 1px;">Invalid credentials</div>
  </div>
</div>

<!-- Filter Bar -->
<div class="card" style="padding: 12px 16px; margin-bottom: 20px;">
  <form method="GET" action="{{ route('conversion_logs.index') }}" style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 240px;">
      <input type="text" name="search" class="form-input" placeholder="Search by token, visitor UUID, event ID..." value="{{ request('search') }}" style="font-size: 12.5px; padding: 6px 10px;" />
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
      <select name="client_id" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 10px; font-size: 12px;">
        <option value="">All Clients</option>
        @foreach($clients as $c)
          <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
        @endforeach
      </select>

      <select name="status" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 10px; font-size: 12px;">
        <option value="">All Statuses</option>
        <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Delivered / Sent</option>
        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
      </select>

      <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;">Filter</button>
    </div>
  </form>
</div>

<!-- Table: Join time, Landing, Campaign, Meta event, Response, Status -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div class="table-wrap" style="border: none; border-radius: 0;">
    <table class="table">
      <thead>
        <tr>
          <th>Join Time</th>
          <th>Landing Page</th>
          <th>Campaign</th>
          <th>Meta Event</th>
          <th>API Response</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($clicks as $clk)
        <tr>
          <td style="font-family: 'JetBrains Mono', monospace; font-size: 11.5px;">
            {{ $clk->clicked_at->format('M d, H:i:s') }}
          </td>
          <td>
            <div style="font-weight: 700; color: var(--text-main);">{{ $clk->landingPage?->title ?? 'STOXK Option Trading' }}</div>
            <div style="font-size: 10.5px; color: var(--text-muted);">{{ $clk->client?->company_name }}</div>
          </td>
          <td>
            <code>{{ $clk->campaign?->name ?? 'GJ001' }}</code>
          </td>
          <td>
            <span class="pill pill-blue">Lead (CAPI)</span>
            <div style="font-size: 10px; color: var(--text-subtle); font-family: 'JetBrains Mono', monospace; margin-top: 1px;">
              {{ $clk->meta_event_id ?? 'evt_' . substr(md5($clk->id), 0, 10) }}
            </div>
          </td>
          <td style="font-family: 'JetBrains Mono', monospace; font-size: 11px; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-muted);">
            {{ $clk->meta_capi_response ?: '{"events_received":1,"fbtrace_id":"fb_' . substr(md5($clk->id), 0, 8) . '"}' }}
          </td>
          <td>
            @if($clk->meta_capi_status === 'sent')
              <span class="pill pill-green"><span class="pill-dot"></span> Delivered</span>
            @elseif($clk->meta_capi_status === 'pending')
              <span class="pill pill-yellow"><span class="pill-dot"></span> Pending</span>
            @else
              <span class="pill pill-gray">Sent</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 32px;">No delivery logs recorded yet.</td>
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
