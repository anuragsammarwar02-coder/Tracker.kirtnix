@extends('layouts.app')

@section('title', 'Import Landing Page')
@section('page_title', 'Import Landing Page')

@section('content')
<div x-data="{
    activeTab: '{{ $activeTab ?? 'vercel' }}',
    selectedProject: '{{ old('vercel_project_name', 'gujaratitrde') }}',
    projects: {{ json_encode($vercelProjects) }},
    title: '{{ old('title', 'gujaratitrde') }}',
    slug: '{{ old('slug', 'gujaratitrde') }}',
    domain: '{{ old('production_domain', 'gujaratitrde.vercel.app') }}',
    telegramDestination: '{{ old('telegram_destination', '') }}',
    metaPixelId: '{{ old('meta_pixel_id', '') }}',
    metaAccessToken: '{{ old('meta_access_token', '') }}',
    showTokenModal: false,
    copiedKx: false,
    
    updateFromProject(projectName) {
      let prj = this.projects.find(p => p.name === projectName);
      if (prj) {
        this.title = prj.name;
        this.slug = prj.name.toLowerCase().replace(/[^a-z0-9_-]/g, '-');
        this.domain = prj.domain;
      }
    },
    copyScript(text) {
      navigator.clipboard.writeText(text);
      this.copiedKx = true;
      setTimeout(() => this.copiedKx = false, 2500);
    }
}" class="max-w-4xl mx-auto space-y-6">

  <!-- Breadcrumb & Back Link -->
  <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
    <a href="{{ route('dashboard') }}" class="hover:text-slate-900 transition">Dashboard</a>
    <span>›</span>
    <a href="{{ route('landing-pages.index') }}" class="hover:text-slate-900 transition">Landing pages</a>
    <span>›</span>
    <span class="text-slate-900">import</span>
  </div>

  <div>
    <a href="{{ route('landing-pages.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 transition mb-2">
      ← Landing pages
    </a>
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Import landing page</h1>
    <p class="text-xs text-slate-500 mt-1 max-w-2xl leading-relaxed">
      Bring an external page into Kirtnix. The Kirtnix tracking script (<code class="font-mono text-amber-700 bg-slate-100 px-1 py-0.5 rounded">kx.js</code>) intercepts CTA clicks and your channel bot creates dynamic single-use invite links (Join Request for private channels, standard join for public channels) to fire Meta Conversions API events.
    </p>
  </div>

  <!-- Provider Selector Tabs -->
  <div class="flex items-center gap-2 text-xs font-bold">
    <button type="button" @click="activeTab = 'html_upload'" 
      :class="activeTab === 'html_upload' ? 'bg-yellow-400 text-slate-950 shadow-sm' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200'"
      class="px-4 py-2 rounded-lg transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      HTML upload
    </button>

    <button type="button" @click="activeTab = 'vercel'" 
      :class="activeTab === 'vercel' ? 'bg-yellow-400 text-slate-950 shadow-sm' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200'"
      class="px-4 py-2 rounded-lg transition flex items-center gap-2">
      <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M24 22.525H0l12-21.05 12 21.05z"/></svg>
      Vercel
    </button>

    <button type="button" @click="activeTab = 'netlify'" 
      :class="activeTab === 'netlify' ? 'bg-yellow-400 text-slate-950 shadow-sm' : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200'"
      class="px-4 py-2 rounded-lg transition flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      Netlify
    </button>
  </div>

  <!-- TAB 1: VERCEL IMPORT FORM -->
  <div x-show="activeTab === 'vercel'" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-6">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
      <div>
        <h2 class="text-base font-bold text-slate-900">Import From Vercel</h2>
        <p class="text-xs text-slate-500 mt-0.5">Select a deployed project from your connected Vercel account.</p>
      </div>

      <div class="flex items-center gap-2">
        <button type="button" @click="showTokenModal = true" class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 fill-current text-slate-900" viewBox="0 0 24 24"><path d="M24 22.525H0l12-21.05 12 21.05z"/></svg>
          {{ $hasVercelToken ? 'Vercel Connected ✓' : 'Connect Vercel API Token' }}
        </button>
      </div>
    </div>

    @if(isset($errors) && $errors->any())
      <div class="p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs">
        <ul class="list-disc pl-4 space-y-1">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('landing-pages.store_import') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="import_type" value="vercel">

      <!-- Project Selection -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Project</label>
        <select name="vercel_project_name" x-model="selectedProject" @change="updateFromProject($event.target.value)" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none">
          <option value="">Select a project...</option>
          <template x-for="p in projects" :key="p.id">
            <option :value="p.name" x-text="p.name + ' (' + p.domain + ')'"></option>
          </template>
        </select>
      </div>

      <!-- Production Domain -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Production domain</label>
        <input type="text" name="production_domain" x-model="domain" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="e.g. gujaratitrde.vercel.app" required>
      </div>

      <!-- Title & Slug -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Title</label>
          <input type="text" name="title" x-model="title" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="e.g. gujaratitrde" required>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Analytics slug</label>
          <input type="text" name="slug" x-model="slug" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="e.g. gujaratitrde" required>
        </div>
      </div>

      <!-- Client Selection -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Client (required for analytics, spend and Meta attribution)</label>
        <select name="client_id" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" required>
          <option value="">Select a client...</option>
          @foreach($clients as $c)
            <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>
              {{ $c->kx_code ?? 'KX-00' . $c->id }} · {{ $c->company_name }} ({{ $c->client_name }})
            </option>
          @endforeach
        </select>
      </div>

      <!-- Telegram Link (Optional) -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Telegram Destination Link <span class="text-slate-400 font-normal">(Optional — Channel bot generates dynamic invite links)</span></label>
        <input type="text" name="telegram_destination" x-model="telegramDestination" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="Optional fallback: https://t.me/+xyz or leave blank for bot links">
      </div>

      <!-- Meta Pixel ID & Meta Access Token Inputs -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-slate-100">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Pixel ID (Optional)</label>
          <input type="text" name="meta_pixel_id" x-model="metaPixelId" value="{{ old('meta_pixel_id') }}" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="e.g. 1018611380802707">
          <div class="text-[11px] text-slate-500 mt-1">Can also be added/updated directly after import.</div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Access Token / CAPI Token (Optional)</label>
          <input type="password" name="meta_access_token" x-model="metaAccessToken" value="{{ old('meta_access_token') }}" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="EAAB...">
          <div class="text-[11px] text-slate-500 mt-1">Conversions API token for server-side Telegram join events.</div>
        </div>
      </div>

      <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs text-slate-500">
        Landing page URL stays <span class="font-mono text-slate-800 font-bold" x-text="'https://' + domain.replace(/^https?:\/\//, '')">https://gujaratitrde.vercel.app</span> — Kirtnix only adds tracking and analytics.
      </div>

      <div class="flex justify-end pt-2">
        <button type="submit" class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
          Import site
        </button>
      </div>
    </form>
  </div>

  <!-- TAB 2: HTML UPLOAD FORM -->
  <div x-show="activeTab === 'html_upload'" style="display: none;" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-5">
    <div class="border-b border-slate-100 pb-3">
      <h2 class="text-base font-bold text-slate-900">Import From HTML File</h2>
      <p class="text-xs text-slate-500 mt-0.5">Upload an existing standalone HTML landing page template.</p>
    </div>

    <form method="POST" action="{{ route('landing-pages.store_import') }}" enctype="multipart/form-data" class="space-y-4">
      @csrf
      <input type="hidden" name="import_type" value="html_upload">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Page Title</label>
          <input type="text" name="title" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" placeholder="e.g. Forex Scalper Landing" required>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Slug</label>
          <input type="text" name="slug" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" placeholder="e.g. forex-scalper" required>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Client</label>
        <select name="client_id" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" required>
          <option value="">Select client...</option>
          @foreach($clients as $c)
            <option value="{{ $c->id }}">{{ $c->company_name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Upload HTML File (.html, .htm)</label>
        <input type="file" name="html_file" accept=".html,.htm,.txt" class="w-full text-xs border border-slate-300 rounded-lg p-2 bg-slate-50" required>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Telegram Destination Link <span class="text-slate-400 font-normal">(Optional)</span></label>
        <input type="text" name="telegram_destination" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" placeholder="Optional fallback: https://t.me/+xyz">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-slate-100">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Pixel ID (Optional)</label>
          <input type="text" name="meta_pixel_id" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="e.g. 1018611380802707">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Access Token / CAPI Token (Optional)</label>
          <input type="password" name="meta_access_token" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="EAAB...">
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <button type="submit" class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition">
          Upload & Generate Tracker
        </button>
      </div>
    </form>
  </div>

  <!-- TAB 3: NETLIFY IMPORT FORM -->
  <div x-show="activeTab === 'netlify'" style="display: none;" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-5">
    <div class="border-b border-slate-100 pb-3">
      <h2 class="text-base font-bold text-slate-900">Import From Netlify</h2>
      <p class="text-xs text-slate-500 mt-0.5">Attach tracking pixel to your Netlify deployed landing page.</p>
    </div>

    <form method="POST" action="{{ route('landing-pages.store_import') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="import_type" value="netlify">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Site Name / Title</label>
          <input type="text" name="title" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" placeholder="e.g. trading-pro" required>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Netlify Domain</label>
          <input type="text" name="external_url" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" placeholder="e.g. trading-pro.netlify.app" required>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Analytics Slug</label>
          <input type="text" name="slug" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" placeholder="e.g. trading-pro" required>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Client</label>
          <select name="client_id" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" required>
            <option value="">Select client...</option>
            @foreach($clients as $c)
              <option value="{{ $c->id }}">{{ $c->company_name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Telegram Destination Link <span class="text-slate-400 font-normal">(Optional)</span></label>
        <input type="text" name="telegram_destination" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" placeholder="Optional fallback: https://t.me/+xyz">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-slate-100">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Pixel ID (Optional)</label>
          <input type="text" name="meta_pixel_id" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="e.g. 1018611380802707">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Access Token / CAPI Token (Optional)</label>
          <input type="password" name="meta_access_token" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="EAAB...">
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <button type="submit" class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition">
          Import Netlify Site
        </button>
      </div>
    </form>
  </div>

  <!-- SCRIPT GENERATOR BOX & META PIXEL CONFIGURATION (DIRECT UNDER SCRIPT) -->
  @if(isset($importedPage) && $importedPage)
  @php
    $trackingScript = '<script src="' . url('/api/public/kx.js') . '?lp=' . ($importedPage->tracking_token ?? $importedPage->slug) . '" data-kx-lp="' . ($importedPage->tracking_token ?? $importedPage->slug) . '"></script>';
  @endphp
  <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-6 animate-in fade-in duration-300">
    
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div>
        <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wide">Tracking Script & Meta Configuration</h2>
        <p class="text-xs text-slate-500 mt-0.5">Paste this single script into your site's <code class="bg-slate-100 px-1.5 py-0.5 rounded text-amber-700 font-mono">&lt;head&gt;</code> tag.</p>
      </div>
      <div class="flex items-center gap-2">
        @if(!empty($importedPage->meta_pixel_id))
          <span class="px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold">
            ● Pixel ID: {{ $importedPage->meta_pixel_id }}
          </span>
        @endif
        @if(!empty($importedPage->meta_access_token))
          <span class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-bold">
            ● Server CAPI Token Active ✓
          </span>
        @endif
      </div>
    </div>

    <!-- 1. KIRTNIX UNIVERSAL SCRIPT (kx.js) -->
    <div class="space-y-2">
      <div class="flex items-center justify-between text-xs">
        <span class="font-mono text-xs uppercase tracking-wider text-slate-700 font-bold">KIRTNIX TRACKING SCRIPT (kx.js)</span>
        
        <button type="button" @click="copyScript(`{{ $trackingScript }}`)" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded text-xs font-semibold flex items-center gap-1.5 transition">
          <span x-text="copiedKx ? '✓ Copied!' : '📋 Copy Kirtnix Script'"></span>
        </button>
      </div>

      <div class="bg-slate-950 rounded-xl p-4 border border-slate-800 text-slate-200">
        <pre class="font-mono text-xs text-yellow-300 overflow-x-auto whitespace-pre-wrap leading-relaxed select-all"><code>{{ $trackingScript }}</code></pre>
      </div>
      <div class="text-[11px] text-slate-500">
        Loads asynchronously — intercepts Telegram CTA clicks, dynamically creates single-use bot invite links (Join Request for private channels), and fires Meta Conversions API events.
      </div>
    </div>

    <!-- 2. DIRECT META PIXEL ID & ACCESS TOKEN INPUT CONFIGURATION -->
    <div class="p-5 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wide">Meta Pixel ID & Access Token (Required for CAPI)</h3>
          <p class="text-[11px] text-slate-500 mt-0.5">Configure your Meta Pixel ID and Conversions API Access Token directly for <span class="font-semibold text-slate-800">{{ $importedPage->title }}</span>.</p>
        </div>
        @if(!empty($importedPage->meta_pixel_id) && !empty($importedPage->meta_access_token))
          <span class="text-[11px] font-bold text-emerald-700 bg-emerald-100/80 px-2.5 py-1 rounded-full border border-emerald-300">
            ✓ CAPI Ready
          </span>
        @else
          <span class="text-[11px] font-bold text-amber-700 bg-amber-100/80 px-2.5 py-1 rounded-full border border-amber-300">
            ! Token Required
          </span>
        @endif
      </div>

      <form method="POST" action="{{ route('landing-pages.update_meta_config', $importedPage) }}" class="space-y-3">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Meta Pixel ID <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="meta_pixel_id" value="{{ old('meta_pixel_id', $importedPage->meta_pixel_id) }}" placeholder="e.g. 1018611380802707" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" required>
            <div class="text-[11px] text-slate-400 mt-1">Your Meta Events Manager Dataset / Pixel ID.</div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Meta Access Token / CAPI Token <span class="text-rose-500 font-bold">* (Required)</span>
            </label>
            <input type="password" name="meta_access_token" value="{{ old('meta_access_token', $importedPage->meta_access_token) }}" placeholder="EAAB..." class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" required>
            <div class="text-[11px] text-slate-400 mt-1">Direct System User Access Token with <code>ads_management</code> permissions.</div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-2 border-t border-slate-200">
          <div class="text-[11px] text-slate-500">
            No Meta base code snippet needed on site. Server sends Subscribe / Lead events directly to Meta.
          </div>
          <button type="submit" class="px-5 py-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            Save Meta Pixel & Token
          </button>
        </div>
      </form>
    </div>

    <!-- Quick Navigation Links -->
    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs font-bold">
      <a href="{{ route('landing-pages.show', $importedPage) }}" class="text-amber-700 hover:underline flex items-center gap-1">
        <span>View Landing Page Settings</span> →
      </a>
      <a href="{{ route('analytics.detail', $importedPage->slug) }}" class="text-slate-700 hover:text-slate-900 hover:underline flex items-center gap-1">
        <span>Open Analytics Dashboard</span> →
      </a>
    </div>
  </div>
  @endif

  <!-- Vercel Token Config Modal -->
  <div x-show="showTokenModal" style="display: none;" class="modal-backdrop" @click.self="showTokenModal = false">
    <div class="modal-content" style="max-width: 440px; padding: 24px;">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 fill-current text-slate-900" viewBox="0 0 24 24"><path d="M24 22.525H0l12-21.05 12 21.05z"/></svg>
          <h3 class="text-sm font-bold text-slate-900">Vercel API Token</h3>
        </div>
        <button type="button" @click="showTokenModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
      </div>

      <p class="text-xs text-slate-500 mb-4">
        Enter your Vercel Personal Access Token from <a href="https://vercel.com/account/tokens" target="_blank" class="text-blue-600 underline font-semibold">vercel.com/account/tokens</a> to sync all your projects automatically.
      </p>

      <form method="POST" action="{{ route('landing-pages.vercel_token') }}" class="space-y-4">
        @csrf
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Vercel Token</label>
          <input type="password" name="vercel_token" value="{{ \App\Models\Setting::get('vercel_token') }}" placeholder="vercel_..." class="w-full text-xs font-mono border border-slate-300 rounded-lg px-3 py-2 text-slate-900 focus:outline-none focus:ring-2 focus:ring-yellow-400" required>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" @click="showTokenModal = false" class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg">Cancel</button>
          <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg">Save & Sync</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
