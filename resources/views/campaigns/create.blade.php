@extends('layouts.app')

@section('title', 'Create Campaign')
@section('page_title', 'New Marketing Campaign')

@section('content')
<div style="max-width: 760px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Campaign Configuration</h2>
      <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">Cancel</a>
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

    <form method="POST" action="{{ route('campaigns.store') }}">
      @csrf

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="client_id">Assign Client *</label>
          <select id="client_id" name="client_id" class="form-select" required>
            <option value="">Select a Client</option>
            @foreach($clients as $client)
              <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                {{ $client->company_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="name">Campaign Name *</label>
          <input type="text" id="name" name="name" class="form-input" placeholder="e.g. Scalping Masterclass 2026" value="{{ old('name') }}" required />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="utm_source">UTM Source</label>
          <input type="text" id="utm_source" name="utm_source" class="form-input" placeholder="facebook / tiktok / google" value="{{ old('utm_source', 'facebook') }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="utm_medium">UTM Medium</label>
          <input type="text" id="utm_medium" name="utm_medium" class="form-input" placeholder="cpc / reel / story" value="{{ old('utm_medium', 'cpc') }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="utm_campaign">UTM Campaign Tag</label>
          <input type="text" id="utm_campaign" name="utm_campaign" class="form-input" placeholder="scalping_scale_aug" value="{{ old('utm_campaign') }}" />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="budget">Ad Budget ($)</label>
          <input type="number" step="0.01" id="budget" name="budget" class="form-input" placeholder="1000.00" value="{{ old('budget') }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="start_date">Start Date</label>
          <input type="date" id="start_date" name="start_date" class="form-input" value="{{ old('start_date', date('Y-m-d')) }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="status">Status *</label>
          <select id="status" name="status" class="form-select" required>
            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Paused</option>
            <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="description">Campaign Description & Ad Copy Notes</label>
        <textarea id="description" name="description" class="form-textarea" rows="3">{{ old('description') }}</textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
        <a href="{{ route('campaigns.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Create Campaign</button>
      </div>
    </form>
  </div>
</div>
@endsection
