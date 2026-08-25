@extends('layouts.app')

@section('title', 'Profile & Security')
@section('page_title', 'Administrator Profile & Security')

@section('content')
<div style="max-width: 600px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Security & Account Details</h2>
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

    <form method="POST" action="{{ route('profile.update') }}">
      @csrf

      <div class="form-group">
        <label class="form-label" for="name">Full Name *</label>
        <input type="text" id="name" name="name" class="form-input" value="{{ old('name', $user->name) }}" required />
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Administrator Email *</label>
        <input type="email" id="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required />
      </div>

      <div style="margin: 24px 0 16px; padding-top: 16px; border-top: 1px solid var(--border-subtle);">
        <h3 style="font-size: 14px; font-weight: 700; color: var(--brand-yellow); margin-bottom: 12px;">Change Password</h3>

        <div class="form-group">
          <label class="form-label" for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" class="form-input" />
        </div>

        <div class="form-group">
          <label class="form-label" for="new_password">New Password (min 8 chars)</label>
          <input type="password" id="new_password" name="new_password" class="form-input" />
        </div>

        <div class="form-group">
          <label class="form-label" for="new_password_confirmation">Confirm New Password</label>
          <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-input" />
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
        <button type="submit" class="btn btn-primary">Update Security Settings</button>
      </div>
    </form>
  </div>
</div>
@endsection
