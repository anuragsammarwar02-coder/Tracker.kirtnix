@extends('layouts.app')

@section('title', 'Edit Campaign')
@section('page_title', 'Edit ' . $campaign->name)

@section('content')
<div style="max-width: 760px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Edit Campaign Details</h2>
      <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-secondary">Back to Campaign</a>
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

    <form method="POST" action="{{ route('campaigns.update', $campaign) }}">
      @csrf
      @method('PUT')

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="client_id">Assign Client *</label>
          <select id="client_id" name="client_id" class="form-select" required>
            @foreach($clients as $client)
              <option value="{{ $client->id }}" {{ old('client_id', $campaign->client_id) == $client->id ? 'selected' : '' }}>
                {{ $client->company_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="name">Campaign Name *</label>
          <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $campaign->name) }}" required />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="slug">Campaign Slug *</label>
          <input type="text" id="slug" name="slug" class="form-input" value="{{ old('slug', $campaign->slug) }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="utm_campaign">UTM Campaign Tag</label>
          <input type="text" id="utm_campaign" name="utm_campaign" class="form-input" value="{{ old('utm_campaign', $campaign->utm_campaign) }}" />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="utm_source">UTM Source</label>
          <input type="text" id="utm_source" name="utm_source" class="form-input" value="{{ old('utm_source', $campaign->utm_source) }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="utm_medium">UTM Medium</label>
          <input type="text" id="utm_medium" name="utm_medium" class="form-input" value="{{ old('utm_medium', $campaign->utm_medium) }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="status">Status *</label>
          <select id="status" name="status" class="form-select" required>
            <option value="active" {{ old('status', $campaign->status) === 'active' ? 'selected' : '' }}>Active</option>
            <option value="paused" {{ old('status', $campaign->status) === 'paused' ? 'selected' : '' }}>Paused</option>
            <option value="completed" {{ old('status', $campaign->status) === 'completed' ? 'selected' : '' }}>Completed</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="budget">Ad Budget ($)</label>
          <input type="number" step="0.01" id="budget" name="budget" class="form-input" value="{{ old('budget', $campaign->budget) }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="start_date">Start Date</label>
          <input type="date" id="start_date" name="start_date" class="form-input" value="{{ old('start_date', $campaign->start_date?->format('Y-m-d')) }}" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="description">Campaign Description & Ad Copy Notes</label>
        <textarea id="description" name="description" class="form-textarea" rows="3">{{ old('description', $campaign->description) }}</textarea>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px;">
        <button type="button" class="btn btn-danger" onclick="if(confirm('Are you sure you want to archive this campaign?')) document.getElementById('delete-form').submit();">
          Archive Campaign
        </button>

        <div style="display: flex; gap: 12px;">
          <a href="{{ route('campaigns.show', $campaign) }}" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Campaign</button>
        </div>
      </div>
    </form>

    <form id="delete-form" action="{{ route('campaigns.destroy', $campaign) }}" method="POST" style="display: none;">
      @csrf
      @method('DELETE')
    </form>
  </div>
</div>
@endsection
