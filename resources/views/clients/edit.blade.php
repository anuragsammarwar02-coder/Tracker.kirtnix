@extends('layouts.app')

@section('title', 'Edit ' . $client->company_name)
@section('page_title', 'Edit Client Profile')

@section('content')
<div style="max-width: 720px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Edit Client Details</h2>
        <div class="card-subtitle">Update company information, KX code, and Meta Ads connection.</div>
      </div>
      <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">Back to Client</a>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('clients.update', $client) }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="kx_code">KX Client Code *</label>
          <input type="text" id="kx_code" name="kx_code" class="form-input" value="{{ old('kx_code', $client->kx_code ?? 'KX-00' . $client->id) }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="company_name">Company / Brand Name *</label>
          <input type="text" id="company_name" name="company_name" class="form-input" value="{{ old('company_name', $client->company_name) }}" required />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="client_name">Client Lead Name *</label>
          <input type="text" id="client_name" name="client_name" class="form-input" value="{{ old('client_name', $client->client_name) }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="industry">Industry / Niche *</label>
          <input type="text" id="industry" name="industry" class="form-input" value="{{ old('industry', $client->industry ?? 'Stock Market & Option Trading') }}" required />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input" value="{{ old('email', $client->email) }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="phone">Phone / WhatsApp</label>
          <input type="text" id="phone" name="phone" class="form-input" value="{{ old('phone', $client->phone) }}" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="status">Account Status *</label>
        <select id="status" name="status" class="form-select" required>
          <option value="active" {{ old('status', $client->status) === 'active' ? 'selected' : '' }}>Active</option>
          <option value="paused" {{ old('status', $client->status) === 'paused' ? 'selected' : '' }}>Paused</option>
          <option value="archived" {{ old('status', $client->status) === 'archived' ? 'selected' : '' }}>Archived</option>
        </select>
      </div>

      <div style="margin: 10px 0 16px;">
        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer;">
          <input type="checkbox" name="meta_ads_connected" value="1" {{ $client->meta_ads_connected ? 'checked' : '' }} style="accent-color: var(--brand-yellow); width: 16px; height: 16px;" />
          <span>Meta Ads Integration Connected</span>
        </label>
      </div>

      <div class="form-group">
        <label class="form-label" for="notes">Internal Notes & Strategy</label>
        <textarea id="notes" name="notes" class="form-textarea" rows="2">{{ old('notes', $client->notes) }}</textarea>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
        <button type="button" class="btn btn-danger" onclick="if(confirm('Archive this client?')) document.getElementById('delete-form').submit();">
          Archive Client
        </button>

        <div style="display: flex; gap: 10px;">
          <a href="{{ route('clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>

    <form id="delete-form" action="{{ route('clients.destroy', $client) }}" method="POST" style="display: none;">
      @csrf
      @method('DELETE')
    </form>
  </div>
</div>
@endsection
