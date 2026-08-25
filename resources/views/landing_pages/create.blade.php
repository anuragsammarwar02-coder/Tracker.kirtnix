@extends('layouts.app')

@section('title', 'Create Landing Page')
@section('page_title', 'Create Dynamic Landing Page')

@section('content')
<div style="max-width: 860px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title">Dynamic Landing Page Builder</h2>
        <div style="font-size: 12px; color: var(--text-muted);">Reuses high-converting design templates with dynamic copy & tracking configurations.</div>
      </div>
      <a href="{{ route('landing-pages.index') }}" class="btn btn-secondary">Cancel</a>
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

    <form method="POST" action="{{ route('landing-pages.store') }}">
      @csrf

      <!-- Basic Setup -->
      <h3 style="font-size: 14px; font-weight: 700; color: var(--brand-yellow); text-transform: uppercase; letter-spacing: 0.6px; margin: 16px 0 12px; padding-bottom: 6px; border-bottom: 1px solid var(--border-subtle);">
        1. Assignment, Deployment & Template
      </h3>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px;">
        <div class="form-group">
          <label class="form-label" for="client_id">Client *</label>
          <select id="client_id" name="client_id" class="form-select" required>
            <option value="">Select Client</option>
            @foreach($clients as $c)
              <option value="{{ $c->id }}" {{ old('client_id', $selectedClientId) == $c->id ? 'selected' : '' }}>
                {{ $c->company_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="campaign_id">Campaign (Optional)</label>
          <select id="campaign_id" name="campaign_id" class="form-select">
            <option value="">No Campaign Attached</option>
            @foreach($campaigns as $camp)
              <option value="{{ $camp->id }}" {{ old('campaign_id') == $camp->id ? 'selected' : '' }}>
                {{ $camp->name }} ({{ $camp->client?->company_name }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="hosting_source">Hosting Platform *</label>
          <select id="hosting_source" name="hosting_source" class="form-select">
            <option value="kirtnix" selected>⚡ Kirtnix Built-in (/lp/slug)</option>
            <option value="vercel">▲ Vercel Deployment</option>
            <option value="netlify">🌐 Netlify Deployment</option>
            <option value="custom">External Webflow / Custom Site</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="template_type">Template *</label>
          <select id="template_type" name="template_type" class="form-select" required>
            <option value="forex_focus" {{ old('template_type') === 'forex_focus' ? 'selected' : '' }}>Forex Focus Dark (Default)</option>
            <option value="gujarati_trader" {{ old('template_type') === 'gujarati_trader' ? 'selected' : '' }}>Gujarati Trader Dark</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="title">Landing Page Admin Title *</label>
          <input type="text" id="title" name="title" class="form-input" placeholder="Forex Focus | Free Market Education" value="{{ old('title') }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="slug">Public URL Slug * (/lp/slug)</label>
          <input type="text" id="slug" name="slug" class="form-input" placeholder="forex-focus-tg" value="{{ old('slug') }}" required />
        </div>
      </div>

      <!-- Branding & Copy -->
      <h3 style="font-size: 14px; font-weight: 700; color: var(--brand-yellow); text-transform: uppercase; letter-spacing: 0.6px; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid var(--border-subtle);">
        2. Branding & Content
      </h3>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="brand_name">Brand Display Name *</label>
          <input type="text" id="brand_name" name="brand_name" class="form-input" placeholder="FOREX FOCUS" value="{{ old('brand_name', 'FOREX FOCUS') }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="brand_tagline">Brand Tagline</label>
          <input type="text" id="brand_tagline" name="brand_tagline" class="form-input" placeholder="Free Market Education · Community Learning" value="{{ old('brand_tagline') }}" />
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="brand_logo_url">Brand Logo Image URL</label>
          <input type="text" id="brand_logo_url" name="brand_logo_url" class="form-input" placeholder="/assets/branding/kirtnix-logo-dark-icon.png" value="{{ old('brand_logo_url') }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="badge_text">Header Pill Badge</label>
          <input type="text" id="badge_text" name="badge_text" class="form-input" placeholder="Educational Channel · Daily Insights" value="{{ old('badge_text', 'Educational Channel · Daily Market Insights') }}" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="hero_heading">Hero Main Heading *</label>
        <input type="text" id="hero_heading" name="hero_heading" class="form-input" placeholder="Understand the Forex Markets with Clarity & Confidence" value="{{ old('hero_heading') }}" required />
      </div>

      <div class="form-group">
        <label class="form-label" for="hero_subheading">Hero Subtitle / Description</label>
        <textarea id="hero_subheading" name="hero_subheading" class="form-textarea" rows="2" placeholder="Learn how currency pairs move, read chart patterns, and follow structured market breakdowns.">{{ old('hero_subheading') }}</textarea>
      </div>

      <!-- Telegram & CTA -->
      <h3 style="font-size: 14px; font-weight: 700; color: var(--brand-yellow); text-transform: uppercase; letter-spacing: 0.6px; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid var(--border-subtle);">
        3. Telegram Channel & CTA Flow
      </h3>

      <div class="form-group">
        <label class="form-label" for="telegram_destination">Telegram Channel / Invite Destination URL *</label>
        <input type="text" id="telegram_destination" name="telegram_destination" class="form-input" placeholder="https://t.me/+sncMUjBZ9a41ZDll or https://t.me/channel_username" value="{{ old('telegram_destination') }}" required />
        <div class="form-hint">KirtniX automatically resolves invite hashes and creates high-speed deep links.</div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="primary_cta_text">Primary Button Text *</label>
          <input type="text" id="primary_cta_text" name="primary_cta_text" class="form-input" value="{{ old('primary_cta_text', 'Join Free Telegram Channel') }}" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="secondary_cta_text">Secondary Button Text</label>
          <input type="text" id="secondary_cta_text" name="secondary_cta_text" class="form-input" value="{{ old('secondary_cta_text', 'Open Telegram Channel') }}" />
        </div>
      </div>

      <!-- Tracking & Meta CAPI -->
      <h3 style="font-size: 14px; font-weight: 700; color: var(--brand-yellow); text-transform: uppercase; letter-spacing: 0.6px; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid var(--border-subtle);">
        4. Tracking: Meta Pixel, Conversions API & GTM
      </h3>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div class="form-group">
          <label class="form-label" for="meta_pixel_id">Facebook / Meta Pixel ID</label>
          <input type="text" id="meta_pixel_id" name="meta_pixel_id" class="form-input" placeholder="e.g. 1130260856232291" value="{{ old('meta_pixel_id') }}" />
        </div>

        <div class="form-group">
          <label class="form-label" for="meta_test_event_code">Meta Test Event Code (Optional)</label>
          <input type="text" id="meta_test_event_code" name="meta_test_event_code" class="form-input" placeholder="e.g. TEST48291" value="{{ old('meta_test_event_code') }}" />
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="meta_access_token">Meta Conversions API (CAPI) Access Token</label>
        <input type="password" id="meta_access_token" name="meta_access_token" class="form-input" placeholder="EAAGm0PX4ZCpsBO..." value="{{ old('meta_access_token') }}" />
        <div class="form-hint">Kept strictly server-side. Never exposed in HTML or frontend scripts.</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="gtm_id">Google Tag Manager ID</label>
        <input type="text" id="gtm_id" name="gtm_id" class="form-input" placeholder="GTM-XXXXXXX" value="{{ old('gtm_id') }}" />
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
        <a href="{{ route('landing-pages.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Publish Landing Page</button>
      </div>
    </form>
  </div>
</div>
@endsection
