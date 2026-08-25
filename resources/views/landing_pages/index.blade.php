@extends('layouts.app')

@section('title', 'Landing Pages')
@section('page_title', 'Landing Pages')

@section('content')
<div x-data="{
  modalOpen: false,
  modalToken: '',
  modalTitle: '',
  modalUrl: '',
  copied: false,
  openSnippet(token, title, url) {
    this.modalToken = token;
    this.modalTitle = title;
    this.modalUrl = url;
    this.modalOpen = true;
    this.copied = false;
  },
  copyScript() {
    let script = `<script src=\"{{ url('/api/public/kx.js') }}?lp=${this.modalToken}\" data-kx-lp=\"${this.modalToken}\"><\/script>`;
    navigator.clipboard.writeText(script);
    this.copied = true;
    setTimeout(() => this.copied = false, 2500);
  }
}" class="space-y-6">

  <!-- Header Area (Matches Screenshot 1) -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Landing pages</h1>
      <p class="text-xs text-slate-500 mt-0.5">High-converting pages with built-in tracking.</p>
    </div>

    <div class="flex items-center gap-2.5">
      <a href="{{ route('landing-pages.import') }}" class="px-3.5 py-2 text-xs font-bold text-slate-800 bg-white hover:bg-slate-50 border border-slate-200 rounded-lg shadow-sm transition flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        <span>Import page</span>
      </a>

      <a href="{{ route('landing-pages.create') }}" class="px-4 py-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition flex items-center gap-1.5">
        <span>+ New page</span>
      </a>
    </div>
  </div>

  <!-- Source Tabs & Filters Bar -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
    <!-- Source Filter Pills -->
    <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
      <a href="{{ route('landing-pages.index', array_merge(request()->query(), ['source' => 'all'])) }}"
         class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ ($currentSource ?? 'all') === 'all' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
        All Pages <span class="ml-1 opacity-70">({{ $counts['all'] ?? 0 }})</span>
      </a>

      <a href="{{ route('landing-pages.index', array_merge(request()->query(), ['source' => 'native'])) }}"
         class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ ($currentSource ?? '') === 'native' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
        Software Builder <span class="ml-1 opacity-70">({{ $counts['native'] ?? 0 }})</span>
      </a>

      <a href="{{ route('landing-pages.index', array_merge(request()->query(), ['source' => 'vercel'])) }}"
         class="px-3 py-1.5 rounded-lg transition whitespace-nowrap flex items-center gap-1 {{ ($currentSource ?? '') === 'vercel' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
        <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 24 24"><path d="M24 22.525H0l12-21.05 12 21.05z"/></svg>
        Vercel Pages <span class="ml-1 opacity-70">({{ $counts['vercel'] ?? 0 }})</span>
      </a>

      <a href="{{ route('landing-pages.index', array_merge(request()->query(), ['source' => 'netlify'])) }}"
         class="px-3 py-1.5 rounded-lg transition whitespace-nowrap {{ ($currentSource ?? '') === 'netlify' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
        Netlify <span class="ml-1 opacity-70">({{ $counts['netlify'] ?? 0 }})</span>
      </a>
    </div>

    <!-- Search & Client Dropdown -->
    <form method="GET" action="{{ route('landing-pages.index') }}" class="flex items-center gap-2">
      <input type="hidden" name="source" value="{{ request('source', 'all') }}">
      
      <input type="text" name="search" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-yellow-400" placeholder="Search pages..." value="{{ request('search') }}">

      <select name="client_id" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2.5 py-1.5 bg-white text-slate-800 focus:outline-none">
        <option value="">All Clients</option>
        @foreach($clients as $c)
          <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->company_name }}</option>
        @endforeach
      </select>

      @if(request('search') || request('client_id'))
        <a href="{{ route('landing-pages.index', ['source' => request('source', 'all')]) }}" class="text-xs text-slate-500 hover:text-slate-800">Clear</a>
      @endif
    </form>
  </div>

  <!-- CARD GRID VIEW (MATCHES SCREENSHOT 1) -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($landingPages as $lp)
    @php
      $isVercel = ($lp->page_source === 'vercel');
      $isNetlify = ($lp->page_source === 'netlify');
      $isNative = ($lp->page_source === 'native' || empty($lp->page_source));
      $liveUrl = $lp->external_url ? $lp->external_url : route('public.landing_page', $lp->slug);
      $displayUrl = $lp->external_url ? str_replace(['https://', 'http://'], '', $lp->external_url) : $lp->slug . '.kirtnix.agency';
      $token = $lp->tracking_token ?? $lp->slug;
    @endphp

    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between group">
      <div>
        <!-- Top Status & External Link Row -->
        <div class="flex items-center justify-between mb-3">
          <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold tracking-wider bg-emerald-50 text-emerald-700 uppercase border border-emerald-200">
            PUBLISHED
          </span>

          <div class="flex items-center gap-1.5 text-slate-400">
            <a href="{{ $liveUrl }}" target="_blank" class="p-1 hover:text-slate-900 transition" title="Open live URL">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
          </div>
        </div>

        <!-- Title & Domain Link -->
        <h3 class="text-base font-extrabold text-slate-900 group-hover:text-yellow-600 transition">
          <a href="{{ route('landing-pages.show', $lp) }}">{{ $lp->title }}</a>
        </h3>

        <div class="text-xs text-slate-400 font-mono mt-0.5 truncate">
          {{ $displayUrl }}
        </div>

        <!-- Tags / Badges -->
        <div class="flex items-center gap-2 mt-4">
          @if($isVercel)
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase tracking-wide">
              VERCEL
            </span>
          @elseif($isNetlify)
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-sky-50 text-sky-700 uppercase tracking-wide">
              NETLIFY
            </span>
          @else
            <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase tracking-wide">
              SOFTWARE BUILDER
            </span>
          @endif

          <span class="px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wide">
            TRACKING ON
          </span>
        </div>

        <!-- Performance Mini-Metrics -->
        <div class="grid grid-cols-3 gap-2 mt-4 pt-3 border-t border-slate-100 text-center">
          <div>
            <div class="text-xs font-bold text-slate-900">{{ number_format($lp->views_count) }}</div>
            <div class="text-[10px] text-slate-400 font-medium">Views</div>
          </div>
          <div>
            <div class="text-xs font-bold text-amber-600">{{ number_format($lp->clicks_count) }}</div>
            <div class="text-[10px] text-slate-400 font-medium">Clicks</div>
          </div>
          <div>
            <div class="text-xs font-bold text-emerald-600">
              {{ $lp->views_count > 0 ? round(($lp->clicks_count / $lp->views_count) * 100, 1) : 0 }}%
            </div>
            <div class="text-[10px] text-slate-400 font-medium">CR</div>
          </div>
        </div>
      </div>

      <!-- Action Footer Buttons -->
      <div class="flex items-center justify-between pt-4 mt-4 border-t border-slate-100 text-xs">
        <div class="flex items-center gap-1.5">
          <button type="button" @click="openSnippet('{{ $token }}', '{{ addslashes($lp->title) }}', '{{ $displayUrl }}')" class="px-2.5 py-1 text-xs font-bold text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg transition" title="Get Kirtnix Tracking Script Pixel">
            📋 Pixel Script
          </button>
          
          <a href="{{ route('analytics.detail', $lp->slug) }}" class="px-2.5 py-1 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition">
            Analytics
          </a>
        </div>

        <div class="flex items-center gap-1">
          <a href="{{ route('landing-pages.edit', $lp) }}" class="p-1 text-slate-400 hover:text-slate-700 transition" title="Edit">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </a>

          <form action="{{ route('landing-pages.destroy', $lp) }}" method="POST" onsubmit="return confirm('Permanently delete landing page \'{{ addslashes($lp->title) }}\'?');" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="Delete">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </form>
        </div>
      </div>
    </div>
    @empty
    <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center">
      <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto mb-3">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      </div>
      <h3 class="text-base font-bold text-slate-900">No landing pages found</h3>
      <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Create a native high-converting page or import your existing Vercel landing page with instant tracking.</p>
      
      <div class="flex items-center justify-center gap-3 mt-4">
        <a href="{{ route('landing-pages.import') }}" class="px-4 py-2 text-xs font-bold text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
          📥 Import Vercel Site
        </a>
        <a href="{{ route('landing-pages.create') }}" class="px-4 py-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition">
          + Create In Software
        </a>
      </div>
    </div>
    @endforelse
  </div>

  <!-- Pagination -->
  @if($landingPages->hasPages())
  <div class="p-4 bg-white rounded-xl border border-slate-200">
    {{ $landingPages->links() }}
  </div>
  @endif

  <!-- Tracking Pixel Snippet Modal -->
  <div x-show="modalOpen" style="display: none;" class="modal-backdrop" @click.self="modalOpen = false">
    <div class="modal-content" style="max-width: 580px; padding: 24px;">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="text-sm font-extrabold text-slate-900" x-text="'Tracking Pixel: ' + modalTitle"></h3>
          <p class="text-xs text-slate-500" x-text="modalUrl"></p>
        </div>
        <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 text-base">✕</button>
      </div>

      <div class="text-xs text-slate-700 mb-2 font-medium">
        Paste this script once into your website's <code class="bg-slate-100 px-1 py-0.5 rounded text-amber-700 font-mono">&lt;head&gt;</code>:
      </div>

      <div class="bg-slate-950 rounded-xl p-3.5 border border-slate-800 text-slate-200 mb-3">
        <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-800 text-[11px]">
          <span class="font-mono uppercase tracking-wider text-slate-400 font-bold">KIRTNIX TRACKING SCRIPT</span>
          <button type="button" @click="copyScript()" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded text-xs font-semibold flex items-center gap-1 transition">
            <span x-text="copied ? '✓ Copied!' : '📋 Copy Script'"></span>
          </button>
        </div>
        <pre class="font-mono text-xs text-yellow-300 overflow-x-auto whitespace-pre-wrap select-all"><code x-text="`<script src=\"{{ url('/api/public/kx.js') }}?lp=${modalToken}\" data-kx-lp=\"${modalToken}\"><\/script>`"></code></pre>
      </div>

      <div class="text-[11px] text-slate-500 mb-4">
        Loads asynchronously with zero layout shift. Tracks page views, UTM parameters, and dynamically attaches unique Telegram invite links.
      </div>

      <div class="flex justify-end gap-2">
        <button type="button" @click="modalOpen = false" class="px-4 py-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg">Done</button>
      </div>
    </div>
  </div>

</div>
@endsection
