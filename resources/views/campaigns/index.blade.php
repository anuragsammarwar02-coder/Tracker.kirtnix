@extends('layouts.app')

@section('title', 'Campaigns')
@section('page_title', 'Marketing Campaigns')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
  <form method="GET" action="{{ route('campaigns.index') }}" style="display: flex; align-items: center; gap: 10px; max-width: 480px; width: 100%;">
    <input type="text" name="search" class="form-input" placeholder="Search campaigns by name, UTM..." value="{{ request('search') }}" />
    <select name="client_id" class="form-select" onchange="this.form.submit()" style="width: auto;">
      <option value="">All Clients</option>
      @foreach($clients as $c)
        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
  </form>

  <a href="{{ route('campaigns.create') }}" class="btn btn-primary">
    <span>+ New Campaign</span>
  </a>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
  <div class="table-wrap" style="border: none; border-radius: 0;">
    <table class="table">
      <thead>
        <tr>
          <th>Campaign Name</th>
          <th>Client</th>
          <th>UTM Parameters</th>
          <th>Pages</th>
          <th>Traffic & Clicks</th>
          <th>Budget</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($campaigns as $camp)
        <tr>
          <td>
            <a href="{{ route('campaigns.show', $camp) }}" style="font-weight: 700; color: var(--text-main); text-decoration: none;">
              {{ $camp->name }}
            </a>
            <div style="font-size: 11.5px; color: var(--text-muted);">Slug: {{ $camp->slug }}</div>
          </td>
          <td>
            @if($camp->client)
              <a href="{{ route('clients.show', $camp->client) }}" style="color: var(--text-main); font-weight: 600; text-decoration: none;">
                {{ $camp->client->company_name }}
              </a>
            @else
              <span style="color: var(--text-muted); font-style: italic;">Unassigned Client</span>
            @endif
          </td>
          <td>
            <div style="font-size: 12px; font-family: 'JetBrains Mono', monospace;">
              @if($camp->utm_source)<span style="color: var(--brand-yellow);">src:{{ $camp->utm_source }}</span>@endif
              @if($camp->utm_campaign)<span style="color: var(--accent-blue);"> · c:{{ $camp->utm_campaign }}</span>@endif
            </div>
          </td>
          <td>
            <span class="badge badge-info">{{ $camp->landing_pages_count }} Pages</span>
          </td>
          <td>
            <div>{{ number_format($camp->views_count) }} views</div>
            <div style="font-size: 11px; color: var(--brand-yellow);">{{ number_format($camp->clicks_count) }} clicks</div>
          </td>
          <td>
            {{ $camp->budget ? '$' . number_format($camp->budget, 2) : 'N/A' }}
          </td>
          <td>
            @if($camp->status === 'active')
              <span class="badge badge-success">Active</span>
            @elseif($camp->status === 'paused')
              <span class="badge badge-warning">Paused</span>
            @else
              <span class="badge" style="background: rgba(148, 163, 184, 0.1); color: var(--text-muted);">Completed</span>
            @endif
          </td>
          <td style="text-align: right;">
            <div style="display: inline-flex; gap: 6px;">
              <a href="{{ route('campaigns.show', $camp) }}" class="btn btn-secondary" style="padding: 5px 10px; font-size: 11.5px;">View</a>
              <a href="{{ route('campaigns.edit', $camp) }}" class="btn btn-secondary" style="padding: 5px 10px; font-size: 11.5px;">Edit</a>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-muted);">
            No campaigns found. <a href="{{ route('campaigns.create') }}" style="color: var(--brand-yellow);">Create your first campaign</a>.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div style="margin-top: 20px;">
  {{ $campaigns->links() }}
</div>
@endsection
