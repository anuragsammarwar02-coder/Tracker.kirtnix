@extends('layouts.app')

@section('title', 'Agency Support')
@section('page_title', 'Support')

@section('content')
<div style="max-width: 840px; margin: 0 auto;">
  <!-- Header Area -->
  <div style="margin-bottom: 24px;">
    <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Priority Agency Support</h1>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
      Direct engineering support for Meta CAPI, Telegram Webhooks, and tracking configurations.
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
    <!-- Direct Telegram Channel -->
    <div class="card" style="border-left: 3px solid var(--accent-blue);">
      <div style="font-size: 24px; margin-bottom: 8px;">💬</div>
      <h2 style="font-size: 15px; font-weight: 800; margin-bottom: 4px;">Direct Telegram Engineering Support</h2>
      <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 14px;">Instant real-time support from our senior tracking specialists.</p>
      <a href="https://t.me/kirtnixsupport" target="_blank" class="btn btn-primary" style="font-size: 12px;">
        <span>Open @kirtnixsupport ↗</span>
      </a>
      <div style="font-size: 11px; color: var(--text-subtle); margin-top: 8px;">Working Hours: 10:00 AM – 7:00 PM IST</div>
    </div>

    <!-- Live Status & Diagnostics -->
    <div class="card" style="border-left: 3px solid var(--accent-green);">
      <div style="font-size: 24px; margin-bottom: 8px;">🛡</div>
      <h2 style="font-size: 15px; font-weight: 800; margin-bottom: 4px;">Hostinger & Server Diagnostics</h2>
      <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 14px;">Check environment health, permissions, and webhook connectivity.</p>
      <a href="{{ route('settings.index') }}" class="btn btn-secondary" style="font-size: 12px;">
        <span>Run System Diagnostics</span>
      </a>
      <div style="font-size: 11px; color: var(--accent-green); font-weight: 600; margin-top: 8px;">● All 5 services operational</div>
    </div>
  </div>

  <!-- Submit Support Ticket Form -->
  <div class="card">
    <h2 class="card-title" style="margin-bottom: 16px;">Submit an Agency Support Ticket</h2>

    <form method="POST" action="{{ route('support.ticket') }}">
      @csrf

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="subject">Ticket Subject *</label>
          <input type="text" id="subject" name="subject" class="form-input" placeholder="e.g. Telegram Webhook sync timeout on Client KX-003" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="category">Category *</label>
          <select id="category" name="category" class="form-select" required>
            <option value="tracking">Meta Pixel & Conversions API (CAPI)</option>
            <option value="telegram">Telegram Bot Webhook & Member Verification</option>
            <option value="landing">Dynamic Landing Page & Direct Redirect</option>
            <option value="deployment">Hostinger Deployment & Domain Setup</option>
            <option value="other">General Agency Inquiry</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="message">Detailed Description & Error Logs *</label>
        <textarea id="message" name="message" class="form-textarea" rows="4" placeholder="Please describe the issue or attach the relevant URL/token..." required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; margin-top: 16px;">
        <button type="submit" class="btn btn-primary">Submit Ticket</button>
      </div>
    </form>
  </div>
</div>
@endsection
