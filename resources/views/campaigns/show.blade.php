@extends('layouts.app')

@section('title', $campaign->name)
@section('page_title', $campaign->name)

@section('content')
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
  <div>
    <div style="display: flex; align-items: center; gap: 10px;">
      <h2 style="font-size: 22px; font-weight: 800;">{{ $campaign->name }}</h2>
      @if($campaign->status === 'active')
        <span class="badge badge-success">Active</span>
      @else
        <span class="badge badge-warning">{{ ucfirst($campaign->status) }}</span>
      @endif
    </div>
    <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
      Client: <a href="{{ route('clients.show', $campaign->client) }}" style="color: var(--brand-yellow); font-weight: 600; text-decoration: none;">{{ $campaign->client?->company_name }}</a>
      · Slug: <code>{{ $campaign->slug }}</code>
    </div>
  </div>

  <div style="display: flex; gap: 10px;">
    <a href="{{ route('landing-pages.create', ['client_id' => $campaign->client_id, 'campaign_id' => $campaign->id]) }}" class="btn btn-primary">
      <span>+ Add Landing Page</span>
    </a>
    <a href="{{ route('campaigns.edit', $campaign) }}" class="btn btn-secondary">
      <span>Edit Campaign</span>
    </a>
  </div>
</div>

<!-- Campaign KPI Summary -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px;">
  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Campaign Views</div>
    <div style="font-size: 24px; font-weight: 800; margin-top: 4px;">{{ number_format($viewsCount) }}</div>
  </div>

  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">CTA Clicks</div>
    <div style="font-size: 24px; font-weight: 800; margin-top: 4px; color: var(--brand-yellow);">{{ number_format($clicksCount) }}</div>
  </div>

  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Verified Joins</div>
    <div style="font-size: 24px; font-weight: 800; margin-top: 4px; color: var(--accent-green);">{{ number_format($joinsCount) }}</div>
  </div>

  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">CTR %</div>
    <div style="font-size: 24px; font-weight: 800; margin-top: 4px;">{{ $ctr }}%</div>
  </div>

  <div class="card" style="padding: 16px 20px;">
    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Join Rate %</div>
    <div style="font-size: 24px; font-weight: 800; margin-top: 4px; color: var(--accent-green);">{{ $joinRate }}%</div>
  </div>
</div>

<!-- Campaign Landing Pages -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Associated Landing Pages</h3>
    <a href="{{ route('landing-pages.create', ['client_id' => $campaign->client_id, 'campaign_id' => $campaign->id]) }}" class="btn btn-secondary" style="padding: 5px 12px; font-size: 12px;">+ Attach Landing Page</a>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Page Title</th>
          <th>Template</th>
          <th>Public Link with UTMs</th>
          <th>Traffic</th>
          <th>Status</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($campaign->landingPages as $lp)
        @php
          $taggedUrl = route('public.landing_page', $lp->slug) . '?' . http_build_query([
            'utm_source' => $campaign->utm_source ?? 'facebook',
            'utm_medium' => $campaign->utm_medium ?? 'cpc',
            'utm_campaign' => $campaign->utm_campaign ?? $campaign->slug,
          ]);
        @endphp
        <tr>
          <td>
            <a href="{{ route('landing-pages.show', $lp) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
              {{ $lp->title }}
            </a>
          </td>
          <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $lp->template_type)) }}</span></td>
          <td>
            <div style="display: flex; align-items: center; gap: 8px;">
              <input type="text" readonly value="{{ $taggedUrl }}" class="form-input" style="font-size: 11.5px; padding: 4px 8px; font-family: 'JetBrains Mono', monospace;" />
              <button type="button" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;" onclick="navigator.clipboard.writeText('{{ $taggedUrl }}'); alert('Copied ad link to clipboard!');">Copy</button>
            </div>
          </td>
          <td>
            <div>{{ $lp->views()->count() }} views</div>
            <div style="font-size: 11px; color: var(--brand-yellow);">{{ $lp->clicks()->count() }} clicks</div>
          </td>
          <td>
            @if($lp->is_active)
              <span class="badge badge-success">Live</span>
            @else
              <span class="badge" style="background: rgba(148, 163, 184, 0.1); color: var(--text-muted);">Draft</span>
            @endif
          </td>
          <td style="text-align: right;">
            <a href="{{ route('landing-pages.edit', $lp) }}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 11.5px;">Edit</a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No landing pages connected to this campaign yet.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
