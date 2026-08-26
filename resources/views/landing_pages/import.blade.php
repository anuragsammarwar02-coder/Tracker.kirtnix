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
    telegramDestination: '{{ old('telegram_destination', 'https://t.me/+sncMUjBZ9a41ZDll') }}',
    metaPixelId: '{{ old('meta_pixel_id', '') }}',
    metaAccessToken: '{{ old('meta_access_token', '') }}',
    showTokenModal: false,
    copiedKx: false,
    copiedPixel: false,
    
    updateFromProject(projectName) {
      let prj = this.projects.find(p => p.name === projectName);
      if (prj) {
        this.title = prj.name;
        this.slug = prj.name.toLowerCase().replace(/[^a-z0-9_-]/g, '-');
        this.domain = prj.domain;
      }
    },
    copyText(text, type) {
      navigator.clipboard.writeText(text);
      if (type === 'kx') {
        this.copiedKx = true;
        setTimeout(() => this.copiedKx = false, 2500);
      } else {
        this.copiedPixel = true;
        setTimeout(() => this.copiedPixel = false, 2500);
      }
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
      Bring an external page into Kirtnix. The Kirtnix tracking engine stays the single source of truth — views, CTA clicks, Telegram joins, Meta conversions and attribution work exactly like native pages.
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

      <!-- Telegram Destination Link -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Telegram link (CTA fallback)</label>
        <input type="text" name="telegram_destination" x-model="telegramDestination" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="https://t.me/+xyz or @yourchannel" required>
      </div>

      <!-- Meta Pixel ID & Meta Access Token Inputs (Direct per-page configuration) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-slate-100">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Pixel ID (Optional)</label>
          <input type="text" name="meta_pixel_id" x-model="metaPixelId" value="{{ old('meta_pixel_id') }}" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="e.g. 1130260856232291">
          <div class="text-[11px] text-slate-500 mt-1">Leave empty to use client or global Meta Pixel.</div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Access Token / CAPI Token (Optional)</label>
          <input type="password" name="meta_access_token" x-model="metaAccessToken" value="{{ old('meta_access_token') }}" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="EAAB...">
          <div class="text-[11px] text-slate-500 mt-1">Conversions API token for server-side Meta delivery.</div>
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
        <label class="block text-xs font-bold text-slate-700 mb-1">Telegram Destination Link</label>
        <input type="text" name="telegram_destination" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" placeholder="https://t.me/+xyz" required>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-slate-100">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Pixel ID (Optional)</label>
          <input type="text" name="meta_pixel_id" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="e.g. 1130260856232291">
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
        <label class="block text-xs font-bold text-slate-700 mb-1">Telegram Destination Link</label>
        <input type="text" name="telegram_destination" class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900" placeholder="https://t.me/+xyz" required>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 border-t border-slate-100">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Meta Pixel ID (Optional)</label>
          <input type="text" name="meta_pixel_id" class="w-full text-xs font-mono font-medium border border-slate-300 rounded-lg px-3 py-2.5 bg-white text-slate-900 focus:ring-2 focus:ring-yellow-400 focus:outline-none" placeholder="e.g. 1130260856232291">
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

  <!-- SCRIPT GENERATOR BOX & META PIXEL SNIPPETS -->
  @if(isset($importedPage) && $importedPage)
  @php
    $pixelId = $importedPage->meta_pixel_id ?: '1130260856232291';
    $trackingScript = '<script src="' . url('/api/public/kx.js') . '?lp=' . ($importedPage->tracking_token ?? $importedPage->slug) . '" data-kx-lp="' . ($importedPage->tracking_token ?? $importedPage->slug) . '"></script>';
    $metaPixelScript = "<!-- Meta Pixel Code -->\n<script>\n!function(f,b,e,v,n,t,s)\n{if(f.fbq)return;n=f.fbq=function(){n.callMethod?\nn.callMethod.apply(n,arguments):n.queue.push(arguments)};\nif(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\nn.queue=[];t=b.createElement(e);t.async=!0;\nt.src=v;s=b.getElementsByTagName(e)[0];\ns.parentNode.insertBefore(t,s)}(window, document,'script',\n'https://connect.facebook.net/en_US/fbevents.js');\nfbq('init', '{$pixelId}');\nfbq('track', 'PageView');\n</script>\n<noscript><img height=\"1\" width=\"1\" style=\"display:none\"\nsrc=\"https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1\"\n/></noscript>\n<!-- End Meta Pixel Code -->";
  @endphp
  <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-6 animate-in fade-in duration-300">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div>
        <h2 class="text-sm font-extrabold text-slate-900 uppercase tracking-wide">Tracking & Meta Pixel Code</h2>
        <p class="text-xs text-slate-500 mt-0.5">Paste these code snippets into your site's <code class="bg-slate-100 px-1.5 py-0.5 rounded text-amber-700 font-mono">&lt;head&gt;</code> tag.</p>
      </div>
      <div class="flex items-center gap-2">
        <span class="px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold">
          ● Pixel ID: {{ $pixelId }}
        </span>
        @if($importedPage->meta_access_token)
          <span class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-bold">
            ● CAPI Active
          </span>
        @endif
      </div>
    </div>

    <!-- 1. Kirtnix Universal Tracking Script -->
    <div class="space-y-2">
      <div class="flex items-center justify-between text-xs">
        <span class="font-mono text-xs uppercase tracking-wider text-slate-700 font-bold">1. KIRTNIX TRACKING SCRIPT (kx.js)</span>
        
        <button type="button" @click="copyText(`{{ $trackingScript }}`, 'kx')" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded text-xs font-semibold flex items-center gap-1.5 transition">
          <span x-text="copiedKx ? '✓ Copied!' : '📋 Copy Kirtnix Script'"></span>
        </button>
      </div>

      <div class="bg-slate-950 rounded-xl p-4 border border-slate-800 text-slate-200">
        <pre class="font-mono text-xs text-yellow-300 overflow-x-auto whitespace-pre-wrap leading-relaxed select-all"><code>{{ $trackingScript }}</code></pre>
      </div>
      <div class="text-[11px] text-slate-500">
        Loads asynchronously — records unique page views, UTM parameters, and intercepts Telegram clicks deterministically.
      </div>
    </div>

    <!-- 2. Meta Pixel Base Code -->
    <div class="space-y-2 pt-2 border-t border-slate-100">
      <div class="flex items-center justify-between text-xs">
        <span class="font-mono text-xs uppercase tracking-wider text-slate-700 font-bold">2. META PIXEL BASE CODE</span>
        
        <button type="button" @click="copyText(`{{ $metaPixelScript }}`, 'pixel')" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold flex items-center gap-1.5 transition">
          <span x-text="copiedPixel ? '✓ Copied!' : '📋 Copy Meta Pixel Code'"></span>
        </button>
      </div>

      <div class="bg-slate-950 rounded-xl p-4 border border-slate-800 text-slate-200">
        <pre class="font-mono text-xs text-emerald-300 overflow-x-auto whitespace-pre-wrap leading-relaxed select-all"><code>{{ $metaPixelScript }}</code></pre>
      </div>
      <div class="text-[11px] text-slate-500">
        Standard Meta Pixel base tag configured with Pixel ID <code class="font-mono font-bold text-slate-700">{{ $pixelId }}</code>.
      </div>
    </div>

    <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs font-bold">
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
