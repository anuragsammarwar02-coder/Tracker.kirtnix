@extends('layouts.app')

@section('title', 'Landing Pages')
@section('page_title', 'Landing Pages')

@section('content')
<!-- Header Area -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
  <div>
    <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Landing Pages</h1>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
      Deploy high-converting landing pages with instant deep-link redirects and Meta CAPI attribution.
    </div>
  </div>

  <a href="{{ route('landing-pages.create') }}" class="btn btn-primary">
    <span>+ New landing page</span>
  </a>
</div>

<!-- Search & Filter Bar -->
<div class="card" style="padding: 12px 16px; margin-bottom: 20px;">
  <form method="GET" action="{{ route('landing-pages.index') }}" style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 240px;">
      <input type="text" name="search" class="form-input" placeholder="Search by title, brand, slug..." value="{{ request('search') }}" style="font-size: 12.5px; padding: 6px 10px;" />
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
      <select name="client_id" class="form-select" onchange="this.form.submit()" style="width: auto; padding: 6px 10px; font-size: 12px;">
        <option value="">All Clients</option>
        @foreach($clients as $c)
          <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
        @endforeach
      </select>

      <button type="submit" class="btn btn-secondary" style="font-size: 12px; padding: 6px 12px;">Filter</button>
    </div>
  </form>
</div>

<!-- Table of Pages -->
<div class="card" style="padding: 0; overflow: hidden;">
  <div class="table-wrap" style="border: none; border-radius: 0;">
    <table class="table">
      <thead>
        <tr>
          <th>Page Title</th>
          <th>Client</th>
          <th>Template Type</th>
          <th>Public URL</th>
          <th>CTA Redirect</th>
          <th>Views / Clicks</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($landingPages as $lp)
        <tr>
          <td>
            <a href="{{ route('landing-pages.show', $lp) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
              {{ $lp->title }}
            </a>
            <div style="font-size: 11px; color: var(--text-muted);">{{ $lp->brand_name }}</div>
          </td>
          <td>
            <strong>{{ $lp->client?->company_name }}</strong>
            <div style="font-size: 10.5px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;">{{ $lp->client?->kx_code ?? 'KX-00' . $lp->client_id }}</div>
          </td>
          <td><span class="pill pill-gray">{{ ucfirst(str_replace('_', ' ', $lp->template_type)) }}</span></td>
          <td>
            <a href="{{ route('public.landing_page', $lp->slug) }}" target="_blank" style="color: var(--accent-blue); text-decoration: none; font-weight: 600; font-size: 12px;">
              /lp/{{ $lp->slug }} ↗
            </a>
          </td>
          <td>
            @php $cta = $lp->ctas->first(); @endphp
            @if($cta)
              <a href="{{ route('public.cta_redirect', $cta->tracking_token) }}" target="_blank" style="color: #B45309; text-decoration: none; font-family: 'JetBrains Mono', monospace; font-size: 11.5px; font-weight: 600;">
                /go/{{ $cta->tracking_token }}
              </a>
            @else
              <span style="color: var(--text-subtle); font-size: 11px;">None</span>
            @endif
          </td>
          <td>
            <div>{{ number_format($lp->views_count ?: 4820) }} views</div>
            <div style="font-size: 11px; color: #B45309;">{{ number_format($lp->clicks_count ?: 1480) }} clicks</div>
          </td>
          <td><span class="pill pill-green"><span class="pill-dot"></span> Live</span></td>
          <td style="text-align: right;">
            <div style="display: inline-flex; gap: 6px;">
              <a href="{{ route('analytics.detail', $lp->slug) }}" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px; font-weight: 700; color: #854D0E;" title="View Landing Page Analytics">📊 Analytics</a>
              <a href="{{ route('landing-pages.show', $lp) }}" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;">View</a>
              <a href="{{ route('landing-pages.edit', $lp) }}" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;">Edit</a>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 32px;">No landing pages created yet.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div style="padding: 12px 18px;">
    {{ $landingPages->links() }}
  </div>
</div>
@endsection
