@extends('layouts.app')

@section('title', 'Notifications')
@section('page_title', 'Notifications Center')

@section('content')
<!-- Header Area -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
  <div>
    <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Notifications Center</h1>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
      Real-time alerts on Meta API, tracking pipelines, budget utilization, and AI insights.
    </div>
  </div>

  @if($unreadCount > 0)
  <form method="POST" action="{{ route('notifications.mark_all_read') }}">
    @csrf
    <button type="submit" class="btn btn-secondary">
      <span>✔ Mark all as read ({{ $unreadCount }})</span>
    </button>
  </form>
  @endif
</div>

<!-- Severity Filters -->
<div class="card" style="padding: 10px 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
  <div style="display: flex; gap: 8px; align-items: center;">
    <span style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Severity:</span>
    <a href="{{ route('notifications.index') }}" class="pill {{ !request('severity') ? 'pill-yellow' : 'pill-gray' }}" style="text-decoration: none;">All</a>
    <a href="{{ route('notifications.index', ['severity' => 'critical']) }}" class="pill {{ request('severity') === 'critical' ? 'pill-red' : 'pill-gray' }}" style="text-decoration: none;">Critical</a>
    <a href="{{ route('notifications.index', ['severity' => 'warning']) }}" class="pill {{ request('severity') === 'warning' ? 'pill-yellow' : 'pill-gray' }}" style="text-decoration: none;">Warning</a>
    <a href="{{ route('notifications.index', ['severity' => 'info']) }}" class="pill {{ request('severity') === 'info' ? 'pill-blue' : 'pill-gray' }}" style="text-decoration: none;">Info</a>
  </div>

  <div style="font-size: 11.5px; color: var(--text-muted);">
    Showing {{ $notifications->total() }} alerts
  </div>
</div>

<!-- Notifications List -->
<div style="display: flex; flex-direction: column; gap: 12px;">
  @forelse($notifications as $n)
  <div class="card" style="padding: 16px 20px; border-left: 4px solid {{ $n->severity === 'critical' ? 'var(--accent-red)' : ($n->severity === 'warning' ? 'var(--brand-yellow)' : 'var(--accent-blue)') }}; {{ $n->is_read ? 'opacity: 0.85;' : '' }}">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
      <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
          <strong style="font-size: 14px; color: var(--text-main);">{{ $n->title }}</strong>
          @if($n->severity === 'critical')
            <span class="pill pill-red" style="font-size: 9.5px;">Critical</span>
          @elseif($n->severity === 'warning')
            <span class="pill pill-yellow" style="font-size: 9.5px;">Warning</span>
          @else
            <span class="pill pill-blue" style="font-size: 9.5px;">Info</span>
          @endif
          @if(!$n->is_read)
            <span class="pill pill-green" style="font-size: 9px; padding: 1px 5px;">New</span>
          @endif
        </div>
        <p style="font-size: 13px; color: var(--text-body); line-height: 1.5; margin-bottom: 6px;">{{ $n->message }}</p>
        <div style="font-size: 11px; color: var(--text-muted); display: flex; gap: 12px;">
          <span>{{ $n->created_at->diffForHumans() }}</span>
          @if($n->client)
            <span>· Client: <strong>{{ $n->client->company_name }}</strong></span>
          @endif
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
        @if($n->link)
          <a href="{{ $n->link }}" class="btn btn-secondary" style="font-size: 11.5px; padding: 4px 8px;">View details</a>
        @endif
        @if(!$n->is_read)
          <form method="POST" action="{{ route('notifications.mark_read', $n) }}">
            @csrf
            <button type="submit" class="btn btn-secondary" style="font-size: 11.5px; padding: 4px 8px;" title="Mark as read">✔</button>
          </form>
        @endif
      </div>
    </div>
  </div>
  @empty
  <div class="card" style="text-align: center; padding: 48px 20px;">
    <div style="font-size: 28px; margin-bottom: 8px;">🔔</div>
    <div style="font-weight: 700; font-size: 14px; color: var(--text-main);">All caught up!</div>
    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">No unread alerts or tracking issues.</div>
  </div>
  @endforelse
</div>

<div style="margin-top: 16px;">
  {{ $notifications->links() }}
</div>
@endsection
