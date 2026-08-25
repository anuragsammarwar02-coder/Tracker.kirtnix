@extends('layouts.app')

@section('title', 'Login Requests')
@section('page_title', 'Login Requests & Security')

@section('content')
<!-- Header Area -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
  <div>
    <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Login Requests & Security Audit</h1>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
      Monitor authenticated agency sessions, suspicious IP logins, and approve new device access.
    </div>
  </div>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px;">
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Requests</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--text-main); margin-top: 2px;">{{ $stats['total'] }}</div>
  </div>
  <div class="card" style="padding: 14px 16px; border-left: 3px solid var(--accent-green);">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Approved</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--accent-green); margin-top: 2px;">{{ $stats['approved'] }}</div>
  </div>
  <div class="card" style="padding: 14px 16px; border-left: 3px solid var(--brand-yellow);">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pending Review</div>
    <div style="font-size: 20px; font-weight: 800; color: #B45309; margin-top: 2px;">{{ $stats['pending'] }}</div>
  </div>
  <div class="card" style="padding: 14px 16px;">
    <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Rejected</div>
    <div style="font-size: 20px; font-weight: 800; color: var(--accent-red); margin-top: 2px;">{{ $stats['rejected'] }}</div>
  </div>
</div>

<!-- Login Requests Table -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
    <h2 class="card-title">Session Audit Logs</h2>
    <span class="pill pill-green"><span class="pill-dot"></span> 2FA Guard Active</span>
  </div>

  <div class="table-wrap" style="border: none; border-radius: 0;">
    <table class="table">
      <thead>
        <tr>
          <th>Time</th>
          <th>Account Email</th>
          <th>Location</th>
          <th>IP Address</th>
          <th>Device</th>
          <th>Status</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($requests as $req)
        <tr>
          <td style="font-family: 'JetBrains Mono', monospace; font-size: 11.5px;">{{ $req->requested_at->format('M d, H:i') }}</td>
          <td><strong>{{ $req->email }}</strong></td>
          <td>{{ $req->location ?? 'Unknown' }}</td>
          <td><code style="font-size: 11px;">{{ $req->ip_address }}</code></td>
          <td>{{ $req->device ?? 'Desktop' }}</td>
          <td>
            @if($req->status === 'approved')
              <span class="pill pill-green"><span class="pill-dot"></span> Approved</span>
            @elseif($req->status === 'pending')
              <span class="pill pill-yellow"><span class="pill-dot"></span> Pending</span>
            @elseif($req->status === 'revoked')
              <span class="pill pill-red">Revoked</span>
            @else
              <span class="pill pill-red">Rejected</span>
            @endif
          </td>
          <td style="text-align: right;">
            @if($req->status === 'pending')
              <form method="POST" action="{{ route('login_requests.update_status', $req) }}" style="display: inline;">
                @csrf
                <input type="hidden" name="status" value="approved" />
                <button type="submit" class="btn btn-primary" style="padding: 3px 8px; font-size: 11px;">Approve</button>
              </form>
              <form method="POST" action="{{ route('login_requests.update_status', $req) }}" style="display: inline;">
                @csrf
                <input type="hidden" name="status" value="rejected" />
                <button type="submit" class="btn btn-danger" style="padding: 3px 8px; font-size: 11px;">Reject</button>
              </form>
            @elseif($req->status === 'approved')
              <form method="POST" action="{{ route('login_requests.revoke', $req) }}" style="display: inline;" onsubmit="return confirm('Immediately revoke access and invalidate active session for {{ addslashes($req->email) }}?');">
                @csrf
                <button type="submit" class="btn btn-secondary" style="padding: 3px 8px; font-size: 11px; color: var(--accent-red); font-weight: 700;">
                  Revoke Access
                </button>
              </form>
            @else
              <span style="color: var(--text-subtle); font-size: 11px;">Closed</span>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">No login requests recorded.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div style="padding: 12px 18px;">
    {{ $requests->links() }}
  </div>
</div>
@endsection
