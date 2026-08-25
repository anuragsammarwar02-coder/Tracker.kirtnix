@extends('layouts.app')

@section('title', $landingPage->title)
@section('page_title', $landingPage->title)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
  <div>
    <div style="display: flex; align-items: center; gap: 10px;">
      <h2 style="font-size: 22px; font-weight: 800;">{{ $landingPage->title }}</h2>
      @if($landingPage->is_active)
        <span class="badge badge-success">Live</span>
      @else
        <span class="badge" style="background: rgba(148, 163, 184, 0.1); color: var(--text-muted);">Draft</span>
      @endif
    </div>
    <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
      Client: <a href="{{ route('clients.show', $landingPage->client) }}" style="color: var(--brand-yellow); font-weight: 600; text-decoration: none;">{{ $landingPage->client?->company_name }}</a>
      · Template: <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $landingPage->template_type)) }}</span>
    </div>
  </div>

  <div style="display: flex; gap: 10px; flex-wrap: wrap;">
    <a href="{{ route('analytics.detail', $landingPage->slug) }}" class="btn btn-secondary" style="font-weight: 700; color: #854D0E;">
      <span>📊 View Analytics</span>
    </a>
    <a href="{{ route('public.landing_page', $landingPage->slug) }}" target="_blank" class="btn btn-primary">
      <span>Open Live Page ↗</span>
    </a>
    <a href="{{ route('landing-pages.edit', $landingPage) }}" class="btn btn-secondary">
      <span>Edit Content</span>
    </a>
  </div>
</div>

<!-- Quick Link Copy Banner -->
<div class="card" style="margin-bottom: 24px; background: rgba(245, 197, 24, 0.04); border-color: rgba(245, 197, 24, 0.25);">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
    <div>
      <div style="font-size: 12px; font-weight: 700; color: var(--brand-yellow); text-transform: uppercase; letter-spacing: 0.5px;">Public Landing Page URL:</div>
      <div style="font-size: 15px; font-weight: 700; font-family: 'JetBrains Mono', monospace; margin-top: 2px;">
        {{ route('public.landing_page', $landingPage->slug) }}
      </div>
    </div>

    <button type="button" class="btn btn-primary" onclick="navigator.clipboard.writeText('{{ route('public.landing_page', $landingPage->slug) }}'); alert('Landing page URL copied!');">
      <span>📋 Copy URL</span>
    </button>
  </div>
</div>

<!-- KPI Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px;">
  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Views</div>
    <div style="font-size: 24px; font-weight: 800; margin-top: 4px;">{{ number_format($viewsCount) }}</div>
    <div style="font-size: 11px; color: var(--text-muted);">{{ number_format($uniqueVisitors) }} Unique</div>
  </div>

  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">CTA Clicks</div>
    <div style="font-size: 24px; font-weight: 800; margin-top: 4px; color: var(--brand-yellow);">{{ number_format($clicksCount) }}</div>
    <div style="font-size: 11px; color: var(--text-muted);">{{ number_format($uniqueClicks) }} Unique</div>
  </div>

  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Click-Through Rate</div>
    <div style="font-size: 24px; font-weight: 800; margin-top: 4px;">{{ $ctr }}%</div>
  </div>

  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Meta Pixel</div>
    <div style="font-size: 14px; font-weight: 700; margin-top: 8px;">
      @if($landingPage->meta_pixel_id)
        <span class="badge badge-success">ID: {{ $landingPage->meta_pixel_id }}</span>
      @else
        <span class="badge" style="background: rgba(148, 163, 184, 0.1); color: var(--text-muted);">Not Configured</span>
      @endif
    </div>
  </div>

  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Meta CAPI Status</div>
    <div style="font-size: 14px; font-weight: 700; margin-top: 8px;">
      @if($landingPage->meta_access_token)
        <span class="badge badge-success">Server-Side Enabled</span>
      @else
        <span class="badge" style="background: rgba(148, 163, 184, 0.1); color: var(--text-muted);">Browser Only</span>
      @endif
    </div>
  </div>
</div>

<!-- Active CTAs Table -->
<div class="card" style="margin-bottom: 28px;">
  <div class="card-header">
    <h3 class="card-title">Trackable CTA Buttons</h3>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>CTA Name & Text</th>
          <th>Type</th>
          <th>Tracking URL (/go/token)</th>
          <th>Telegram Destination</th>
          <th>Total Clicks</th>
          <th style="text-align: right;">Test Link</th>
        </tr>
      </thead>
      <tbody>
        @forelse($landingPage->ctas as $cta)
        <tr>
          <td>
            <div style="font-weight: 700;">{{ $cta->name }}</div>
            <div style="font-size: 12px; color: var(--text-muted);">"{{ $cta->button_text }}"</div>
          </td>
          <td>
            <span class="badge {{ $cta->button_type === 'primary' ? 'badge-warning' : 'badge-info' }}">
              {{ ucfirst($cta->button_type) }}
            </span>
          </td>
          <td>
            <code style="color: var(--brand-yellow);">/go/{{ $cta->tracking_token }}</code>
          </td>
          <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            {{ $cta->telegram_destination }}
          </td>
          <td>
            <strong>{{ number_format($cta->click_count) }}</strong> clicks
          </td>
          <td style="text-align: right;">
            <a href="{{ route('public.cta_redirect', $cta->tracking_token) }}" target="_blank" class="btn btn-secondary" style="padding: 4px 10px; font-size: 11px;">
              Launch ↗
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 20px;">No CTAs registered.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
<!-- Vercel, Netlify & External Embed Code -->
<div class="card" style="margin-bottom: 28px; background: var(--bg-subtle);">
  <div class="card-header" style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 12px; margin-bottom: 14px;">
    <div>
      <h3 class="card-title" style="display: flex; align-items: center; gap: 8px;">
        <span>▲</span>
        <span>Vercel / Netlify / External Site Embed Script</span>
      </h3>
      <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
        Deploying this landing page externally on Vercel, Netlify, or Webflow? Paste this lightweight script into your page's <code>&lt;head&gt;</code> or before <code>&lt;/body&gt;</code>.
      </div>
    </div>
    <button type="button" class="btn btn-secondary" onclick="navigator.clipboard.writeText(document.getElementById('external-embed-snippet').innerText); alert('Vercel & Netlify tracking code copied to clipboard!');">
      <span>📋 Copy Embed Script</span>
    </button>
  </div>

  <pre id="external-embed-snippet" style="background: #0F172A; color: #E2E8F0; padding: 14px 18px; border-radius: 8px; font-family: 'JetBrains Mono', monospace; font-size: 11.5px; overflow-x: auto; line-height: 1.6; border: 1px solid var(--border-color);"><code>&lt;!-- Kirtnix Performance Tracker --&gt;
&lt;script 
  src="{{ url('/assets/tracker.js') }}" 
  data-lp="{{ $landingPage->slug }}" 
  data-endpoint="{{ url('/') }}" 
  data-client="{{ $landingPage->client?->kx_code ?? 'KX-001' }}" 
  async&gt;
&lt;/script&gt;</code></pre>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; font-size: 11.5px; color: var(--text-muted);">
    <div style="background: var(--bg-card); padding: 10px 14px; border-radius: 6px; border: 1px solid var(--border-subtle);">
      <strong style="color: var(--text-main); display: block; margin-bottom: 2px;">▲ Vercel (Next.js / React)</strong>
      Add to <code>app/layout.tsx</code> or <code>pages/_document.js</code> using <code>next/script</code> with <code>strategy="afterInteractive"</code>.
    </div>
    <div style="background: var(--bg-card); padding: 10px 14px; border-radius: 6px; border: 1px solid var(--border-subtle);">
      <strong style="color: var(--text-main); display: block; margin-bottom: 2px;">🌐 Netlify / Static HTML</strong>
      Paste directly into Netlify Snippet Injection (Site configuration → Build & deploy → Post-processing → Snippet injection).
    </div>
  </div>
</div>
@endsection
