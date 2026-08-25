@extends('layouts.app')

@section('title', 'Edit Landing Page')
@section('page_title', 'Edit ' . $landingPage->title)

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Edit Dynamic Landing Page</h2>
        <div style="font-size: 12px; color: var(--text-muted);">Update headlines, Telegram destination, Pixel IDs or CTA styling.</div>
      </div>
      <a href="{{ route('landing-pages.show', $landingPage) }}" class="btn btn-secondary">Back to Page</a>
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

    <form method="POST" action="{{ route('landing-pages.update', $landingPage) }}">
      @csrf
      @method('PUT')

      <!-- Basic Setup -->
      <h3 style="font-size: 14px; font-weight: 700; color: var(--brand-yellow); text-transform: uppercase; letter-spacing: 0.6px; margin: 16px 0 12px; padding-bottom: 6px; border-bottom: 1px solid var(--border-subtle);">
        1. Assignment & Template
      </h3>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="client_id">Client *</label>
          <select id="client_id" name="client_id" class="form-select" required>
            @foreach($clients as $c)
              <option value="{{ $c->id }}" {{ old('client_id', $landingPage->client_id) == $c->id ? 'selected' : '' }}>
                {{ $c->company_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="campaign_id">Campaign</label>
          <select id="campaign_id" name="campaign_id" class="form-select">
            <option value="">No Campaign Attached</option>
            @foreach($campaigns as $camp)
              <option value="{{ $camp->id }}" {{ old('campaign_id', $landingPage->campaign_id) == $camp->id ? 'selected' : '' }}>
                {{ $camp->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="template_type">Template *</label>
          <select id="template_type" name="template_type" class="form-select" required>
            <option value="forex_focus" {{ old('template_type', $landingPage->template_type) === 'forex_focus' ? 'selected' : '' }}>Forex Focus Dark (Default)</option>
            <option value="gujarati_trader" {{ old('template_type', $landingPage->template_type) === 'gujarati_trader' ? 'selected' : '' }}>Gujarati Trader Dark</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="title">Landing Page Admin Title *</label>
          <input type="text" id="title" name="title" class="form-input" value="{{ old('title', $landingPage->title) }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="slug">Public URL Slug * (/lp/slug)</label>
          <input type="text" id="slug" name="slug" class="form-input" value="{{ old('slug', $landingPage->slug) }}" required />
        </div>
      </div>

      <!-- Branding & Copy -->
      <h3 style="font-size: 14px; font-weight: 700; color: var(--brand-yellow); text-transform: uppercase; letter-spacing: 0.6px; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid var(--border-subtle);">
        2. Branding & Content
      </h3>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="brand_name">Brand Display Name *</label>
          <input type="text" id="brand_name" name="brand_name" class="form-input" value="{{ old('brand_name', $landingPage->brand_name) }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="brand_tagline">Brand Tagline</label>
          <input type="text" id="brand_tagline" name="brand_tagline" class="form-input" value="{{ old('brand_tagline', $landingPage->brand_tagline) }}" />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="brand_logo_url">Brand Logo Image URL</label>
          <input type="text" id="brand_logo_url" name="brand_logo_url" class="form-input" value="{{ old('brand_logo_url', $landingPage->brand_logo_url) }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="badge_text">Header Pill Badge</label>
          <input type="text" id="badge_text" name="badge_text" class="form-input" value="{{ old('badge_text', $landingPage->badge_text) }}" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="hero_heading">Hero Main Heading *</label>
        <input type="text" id="hero_heading" name="hero_heading" class="form-input" value="{{ old('hero_heading', $landingPage->hero_heading) }}" required />
      </div>

      <div class="form-group">
        <label class="form-label" for="hero_subheading">Hero Subtitle / Description</label>
        <textarea id="hero_subheading" name="hero_subheading" class="form-textarea" rows="2">{{ old('hero_subheading', $landingPage->hero_subheading) }}</textarea>
      </div>

      <!-- Telegram & CTA -->
      <h3 style="font-size: 14px; font-weight: 700; color: var(--brand-yellow); text-transform: uppercase; letter-spacing: 0.6px; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid var(--border-subtle);">
        3. Telegram Channel & CTA Flow
      </h3>

      <div class="form-group">
        <label class="form-label" for="telegram_destination">Telegram Channel / Invite Destination URL *</label>
        <input type="text" id="telegram_destination" name="telegram_destination" class="form-input" value="{{ old('telegram_destination', $landingPage->telegram_destination) }}" required />
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="primary_cta_text">Primary Button Text *</label>
          <input type="text" id="primary_cta_text" name="primary_cta_text" class="form-input" value="{{ old('primary_cta_text', $landingPage->primary_cta_text) }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="secondary_cta_text">Secondary Button Text</label>
          <input type="text" id="secondary_cta_text" name="secondary_cta_text" class="form-input" value="{{ old('secondary_cta_text', $landingPage->secondary_cta_text) }}" />
        </div>
      </div>

      <!-- Tracking & Meta CAPI -->
      <h3 style="font-size: 14px; font-weight: 700; color: var(--brand-yellow); text-transform: uppercase; letter-spacing: 0.6px; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid var(--border-subtle);">
        4. Tracking: Meta Pixel, Conversions API & GTM
      </h3>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="meta_pixel_id">Facebook / Meta Pixel ID</label>
          <input type="text" id="meta_pixel_id" name="meta_pixel_id" class="form-input" value="{{ old('meta_pixel_id', $landingPage->meta_pixel_id) }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="meta_test_event_code">Meta Test Event Code</label>
          <input type="text" id="meta_test_event_code" name="meta_test_event_code" class="form-input" value="{{ old('meta_test_event_code', $landingPage->meta_test_event_code) }}" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="meta_access_token">Meta Conversions API (CAPI) Access Token</label>
        <input type="password" id="meta_access_token" name="meta_access_token" class="form-input" value="{{ old('meta_access_token', $landingPage->meta_access_token) }}" />
      </div>

      <div class="form-group">
        <label class="form-label" for="gtm_id">Google Tag Manager ID</label>
        <input type="text" id="gtm_id" name="gtm_id" class="form-input" value="{{ old('gtm_id', $landingPage->gtm_id) }}" />
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px;">
        <button type="button" class="btn btn-danger" onclick="if(confirm('Are you sure you want to archive this landing page?')) document.getElementById('delete-form').submit();">
          Archive Page
        </button>

        <div style="display: flex; gap: 12px;">
          <a href="{{ route('landing-pages.show', $landingPage) }}" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Page</button>
        </div>
      </div>
    </form>

    <form id="delete-form" action="{{ route('landing-pages.destroy', $landingPage) }}" method="POST" style="display: none;">
      @csrf
      @method('DELETE')
    </form>
  </div>
</div>
@endsection
