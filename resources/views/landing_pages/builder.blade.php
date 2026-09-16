@extends('layouts.app')

@section('title', isset($landingPage) ? 'Edit: ' . $landingPage->title : 'Visual Landing Page Builder')
@section('page_title', 'Software Landing Page Builder')

@section('styles')
<style>
  /* Custom scrollbar for builder canvas and inspector */
  .custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
  }
  .custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(15, 23, 42, 0.6);
  }
  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #475569;
  }
  .cta-preview-glow {
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.35);
  }
</style>
@endsection

@section('content')
@php
  $isEdit = isset($landingPage);
  $formAction = $isEdit ? route('landing-pages.update', $landingPage) : route('landing-pages.store');
  $defaultBlocks = $isEdit && !empty($landingPage->blocks_json) 
      ? $landingPage->blocks_json 
      : \App\Models\LandingPage::getDefaultBlocks($landingPage->brand_name ?? 'VIP TRADING', $landingPage->telegram_destination ?? 'https://t.me/kirtnix');
  $initialTheme = $isEdit ? ($landingPage->theme ?? 'premium_dark') : 'premium_dark';
@endphp

<div 
  x-data="visualBuilder({
    blocks: {{ json_encode($defaultBlocks) }},
    title: '{{ addslashes(old('title', $landingPage->title ?? 'New Landing Page')) }}',
    slug: '{{ addslashes(old('slug', $landingPage->slug ?? 'vip-channel-' . substr(md5(uniqid()), 0, 5))) }}',
    theme: '{{ addslashes(old('theme', $initialTheme)) }}',
    brandName: '{{ addslashes(old('brand_name', $landingPage->brand_name ?? 'VIP TRADING')) }}',
    brandTagline: '{{ addslashes(old('brand_tagline', $landingPage->brand_tagline ?? 'Official Stock Market & VIP Trading Channel')) }}',
    brandLogoUrl: '{{ addslashes(old('brand_logo_url', $landingPage->brand_logo_url ?? '')) }}',
    telegramDestination: '{{ addslashes(old('telegram_destination', $landingPage->telegram_destination ?? 'https://t.me/kirtnix')) }}',
    metaPixelId: '{{ addslashes(old('meta_pixel_id', $landingPage->meta_pixel_id ?? '')) }}',
    metaAccessToken: '{{ addslashes(old('meta_access_token', $landingPage->meta_access_token ?? '')) }}',
    metaTestEventCode: '{{ addslashes(old('meta_test_event_code', $landingPage->meta_test_event_code ?? '')) }}',
    gtmId: '{{ addslashes(old('gtm_id', $landingPage->gtm_id ?? '')) }}',
    clientId: '{{ old('client_id', $landingPage->client_id ?? ($selectedClientId ?? ($clients->first()?->id ?? ''))) }}',
    campaignId: '{{ old('campaign_id', $landingPage->campaign_id ?? '') }}',
    customCss: '{{ addslashes(old('custom_css', $landingPage->custom_css ?? '')) }}',
    isActive: {{ old('is_active', $landingPage->is_active ?? true) ? 'true' : 'false' }},
  })"
  class="-mt-6 -mx-4 sm:-mx-6 lg:-mx-8 flex flex-col h-[calc(100vh-58px)] overflow-hidden bg-slate-950 text-slate-100 font-sans select-none"
>

  <!-- ================= TOP TOOLBAR ================= -->
  <header class="h-14 bg-slate-900 border-b border-slate-800 px-3 sm:px-6 flex items-center justify-between shrink-0 z-30">
    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
      <a href="{{ route('landing-pages.index') }}" class="px-2.5 py-1 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800/80 hover:bg-slate-800 rounded-lg border border-slate-700/60 transition flex items-center gap-1.5 shrink-0">
        <span>←</span>
        <span class="hidden sm:inline">Back</span>
      </a>
      <div class="h-4 w-px bg-slate-800 hidden md:block"></div>
      <div class="flex items-center gap-2 min-w-0">
        <span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse shrink-0"></span>
        <span class="text-xs font-bold text-white tracking-wide truncate max-w-[140px] sm:max-w-[220px]" x-text="title || 'Untitled Page'"></span>
        <span class="text-[11px] font-mono text-slate-400 bg-slate-800 px-2 py-0.5 rounded hidden lg:inline truncate max-w-[180px]" x-text="'/lp/' + slug"></span>
      </div>
    </div>

    <!-- Viewport Switcher & Theme Selector (Center) -->
    <div class="flex items-center gap-2">
      <!-- Responsive Viewport Switcher -->
      <div class="flex items-center bg-slate-950 p-0.5 sm:p-1 rounded-lg border border-slate-800">
        <button 
          type="button" 
          @click="viewport = 'desktop'" 
          :class="viewport === 'desktop' ? 'bg-slate-800 text-yellow-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
          class="px-2 sm:px-2.5 py-1 rounded text-xs font-medium transition flex items-center gap-1"
          title="Desktop View (Full Width)"
        >
          <span>🖥️</span>
          <span class="hidden md:inline text-[11px] font-bold">Desktop</span>
        </button>
        <button 
          type="button" 
          @click="viewport = 'tablet'" 
          :class="viewport === 'tablet' ? 'bg-slate-800 text-yellow-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
          class="px-2 sm:px-2.5 py-1 rounded text-xs font-medium transition flex items-center gap-1"
          title="Tablet View (540px)"
        >
          <span>📱</span>
          <span class="hidden md:inline text-[11px] font-bold">Tablet</span>
        </button>
        <button 
          type="button" 
          @click="viewport = 'mobile'" 
          :class="viewport === 'mobile' ? 'bg-slate-800 text-yellow-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
          class="px-2 sm:px-2.5 py-1 rounded text-xs font-medium transition flex items-center gap-1"
          title="Mobile View (390px)"
        >
          <span>📱</span>
          <span class="hidden md:inline text-[11px] font-bold">Mobile</span>
        </button>
      </div>

      <!-- Quick Theme Switcher Pill -->
      <div class="hidden xl:flex items-center bg-slate-950 p-1 rounded-lg border border-slate-800">
        <button 
          type="button" 
          @click="theme = 'premium_dark'" 
          :class="theme === 'premium_dark' ? 'bg-slate-800 text-yellow-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
          class="px-2.5 py-1 rounded text-[11px] transition flex items-center gap-1"
          title="Switch to Premium Dark Theme"
        >
          <span>🌙</span>
          <span>Dark</span>
        </button>
        <button 
          type="button" 
          @click="theme = 'minimal_light'" 
          :class="theme === 'minimal_light' ? 'bg-slate-800 text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
          class="px-2.5 py-1 rounded text-[11px] transition flex items-center gap-1"
          title="Switch to Minimal Light (Trading Community) Theme"
        >
          <span>☀️</span>
          <span>Clean Light</span>
        </button>
      </div>
    </div>

    <!-- Actions: AI, Preview in New Tab, Save Draft, Publish -->
    <div class="flex items-center gap-1.5 sm:gap-2">
      <button 
        type="button" 
        @click="openAiModal('new')" 
        class="hidden sm:flex px-3 py-1.5 text-xs font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 rounded-lg shadow-md border border-purple-400/30 transition items-center gap-1.5"
        title="Generate or Refine Page with AI"
      >
        <span>✨</span>
        <span class="font-extrabold tracking-wide">AI</span>
      </button>

      <!-- Open Preview in New Tab -->
      <a 
        :href="'/lp/' + (slug || 'preview')" 
        target="_blank" 
        class="px-2.5 sm:px-3 py-1.5 text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white rounded-lg border border-slate-700 transition flex items-center gap-1"
        title="Open Real Public Page in New Tab"
      >
        <span>👁️</span>
        <span class="hidden sm:inline">Preview</span>
      </a>

      <button 
        type="button" 
        @click="submitForm(false)" 
        class="px-3 py-1.5 text-xs font-bold text-slate-300 bg-slate-800 hover:bg-slate-700 rounded-lg border border-slate-700 transition"
      >
        Save Draft
      </button>

      <button 
        type="button" 
        @click="submitForm(true)" 
        class="px-3.5 sm:px-4 py-1.5 text-xs font-extrabold text-slate-950 bg-gradient-to-r from-yellow-400 to-amber-400 hover:from-yellow-300 hover:to-amber-300 rounded-lg shadow-sm transition flex items-center gap-1.5"
      >
        <span>🚀</span>
        <span>Publish</span>
      </button>
    </div>
  </header>

  <!-- ================= WORKSPACE (MAIN CANVAS + INSPECTOR SIDEBAR) ================= -->
  <div class="flex-1 flex overflow-hidden min-h-0">

    <!-- ================= LEFT/CENTER SCROLLABLE CANVAS ================= -->
    <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-8 bg-slate-950/95 flex flex-col items-center custom-scrollbar">
      
      <!-- Inner Device Canvas (Responsive width + Natural unbounded height) -->
      <div 
        class="w-full transition-all duration-300 mx-auto rounded-2xl border shadow-2xl flex flex-col"
        :class="{
          'max-w-[640px]': viewport === 'desktop',
          'max-w-[540px]': viewport === 'tablet',
          'max-w-[390px]': viewport === 'mobile',
          'bg-[#F4F6F9] text-slate-900 border-slate-700/60': theme === 'minimal_light',
          'bg-[#0A0B0D] text-slate-100 border-slate-800/80': theme === 'premium_dark'
        }"
      >
        <!-- Canvas Blocks Container -->
        <div class="p-4 sm:p-6 space-y-4 flex-1">

          <template x-for="(block, index) in blocks" :key="block.id || index">
            <div 
              @click="selectBlock(index)"
              class="relative group rounded-2xl transition-all duration-150 cursor-pointer"
              :class="selectedBlockIndex === index 
                ? (theme === 'minimal_light' ? 'ring-2 ring-blue-600 ring-offset-2 ring-offset-[#F4F6F9] bg-blue-50/50 p-2 -m-2' : 'ring-2 ring-yellow-400 ring-offset-2 ring-offset-[#0A0B0D] bg-slate-900/60 p-2 -m-2')
                : (theme === 'minimal_light' ? 'hover:ring-1 hover:ring-slate-300 p-1 -m-1' : 'hover:ring-1 hover:ring-slate-700/80 p-1 -m-1')"
            >
              <!-- Mini Block Action Toolbar (Hover/Selected) -->
              <div 
                class="absolute -top-3.5 right-2 z-20 flex items-center gap-1 bg-slate-900 border border-slate-700 px-2 py-0.5 rounded-md shadow-lg"
                :class="selectedBlockIndex === index ? 'opacity-100' : 'opacity-0 group-hover:opacity-100 transition-opacity'"
                @click.stop
              >
                <span class="text-[10px] font-mono uppercase text-yellow-400 font-bold mr-1" x-text="block.type"></span>
                <button type="button" @click="moveUp(index)" class="p-1 text-slate-400 hover:text-white text-xs" title="Move Up">▲</button>
                <button type="button" @click="moveDown(index)" class="p-1 text-slate-400 hover:text-white text-xs" title="Move Down">▼</button>
                <button type="button" @click="duplicateBlock(index)" class="p-1 text-slate-400 hover:text-white text-xs" title="Duplicate">📄</button>
                <button type="button" @click="deleteBlock(index)" class="p-1 text-red-400 hover:text-red-300 text-xs" title="Delete">🗑️</button>
              </div>

              <!-- ================= 1. HERO BLOCK CANVAS RENDER ================= -->
              <template x-if="block.type === 'hero'">
                <div class="text-center space-y-3 pt-2 pb-1">
                  
                  <!-- Live Traders Online Badge -->
                  <template x-if="block.live_traders_badge || theme === 'minimal_light'">
                    <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold"
                         :class="theme === 'minimal_light' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'">
                      <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                      <span x-text="block.live_traders_badge || '6,547 Traders Online Now'"></span>
                    </div>
                  </template>

                  <!-- Logo Avatar -->
                  <template x-if="brandLogoUrl || block.logo_url">
                    <div class="flex justify-center my-1.5">
                      <img 
                        :src="block.logo_url || brandLogoUrl" 
                        :alt="brandName" 
                        class="w-20 h-20 rounded-full object-cover shadow-md"
                        :class="theme === 'minimal_light' ? 'border-4 border-white ring-1 ring-slate-200 bg-white' : 'border-2 border-yellow-400/40 shadow-yellow-500/10'"
                      >
                    </div>
                  </template>

                  <!-- Brand Heading & Tagline -->
                  <div>
                    <h2 class="text-lg font-black tracking-tight" :class="theme === 'minimal_light' ? 'text-slate-900' : 'text-white'" x-text="brandName || 'VIP TRADING'"></h2>
                    <template x-if="brandTagline || block.tagline">
                      <p class="text-xs font-semibold" :class="theme === 'minimal_light' ? 'text-blue-600' : 'text-yellow-400'" x-text="block.tagline || brandTagline"></p>
                    </template>
                  </div>

                  <!-- Rating Stars -->
                  <template x-if="block.rating_text || theme === 'minimal_light'">
                    <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-amber-500">
                      <span>⭐⭐⭐⭐⭐</span>
                      <span class="text-[11px] font-semibold" :class="theme === 'minimal_light' ? 'text-slate-600' : 'text-slate-400'" x-text="block.rating_text || '4.9/5 (2,340 Reviews)'"></span>
                    </div>
                  </template>

                  <!-- Status / Redirect Pill -->
                  <template x-if="block.badge">
                    <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-xl text-xs font-semibold"
                         :class="theme === 'minimal_light' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-yellow-400/10 text-yellow-400 border border-yellow-400/30'">
                      <span>⚡</span>
                      <span x-text="block.badge"></span>
                    </div>
                  </template>

                  <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight" :class="theme === 'minimal_light' ? 'text-slate-900' : 'text-white'" x-text="block.heading || 'Join VIP Community'"></h1>
                  <p class="text-xs sm:text-sm max-w-md mx-auto leading-relaxed" :class="theme === 'minimal_light' ? 'text-slate-600' : 'text-slate-400'" x-text="block.subheading"></p>

                  <!-- Primary CTA Button Preview -->
                  <div class="pt-2">
                    <div class="w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-bold text-sm sm:text-base text-white shadow-md"
                         :class="theme === 'minimal_light' ? 'bg-blue-600' : 'bg-gradient-to-r from-blue-500 to-blue-600 shadow-blue-500/25'">
                      <span>➤</span>
                      <span x-text="block.button_text || 'Join Free Telegram Channel →'"></span>
                    </div>
                    <p class="text-[11px] mt-1.5" :class="theme === 'minimal_light' ? 'text-slate-500' : 'text-slate-500'" x-text="block.button_subtitle || 'Free instant access • No payment required'"></p>
                  </div>
                </div>
              </template>

              <!-- ================= 2. STATS BLOCK CANVAS RENDER ================= -->
              <template x-if="block.type === 'stats'">
                <div class="grid grid-cols-3 gap-2 py-1">
                  <template x-for="(st, stIdx) in (block.stats || [{value:'50K+', label:'Members'}, {value:'5+ Years', label:'Experience'}, {value:'95%', label:'Accuracy'}])" :key="stIdx">
                    <div class="text-center p-2.5 rounded-xl" :class="theme === 'minimal_light' ? 'bg-white border border-slate-200/90 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]'">
                      <div class="text-sm sm:text-base font-black" :class="theme === 'minimal_light' ? 'text-blue-600' : 'text-yellow-400'" x-text="st.value"></div>
                      <div class="text-[10px] sm:text-xs font-semibold" :class="theme === 'minimal_light' ? 'text-slate-600' : 'text-slate-400'" x-text="st.label"></div>
                    </div>
                  </template>
                </div>
              </template>

              <!-- ================= 3. FEATURES GRID CANVAS RENDER ================= -->
              <template x-if="block.type === 'features_grid'">
                <div class="space-y-2 pt-1">
                  <template x-if="block.title">
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-center" :class="theme === 'minimal_light' ? 'text-slate-700' : 'text-slate-400'" x-text="block.title"></h2>
                  </template>
                  <div class="space-y-2">
                    <template x-for="(card, cardIdx) in (block.cards || [])" :key="cardIdx">
                      <div class="flex items-center gap-3 p-3 rounded-xl transition"
                           :class="theme === 'minimal_light' ? 'bg-white border border-slate-200/80 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]'">
                        <div class="text-xl p-2 rounded-lg shrink-0" :class="theme === 'minimal_light' ? 'bg-blue-50 text-blue-600' : 'bg-[#1A1D26] border border-[#2A2E3A]'" x-text="card.icon || '📊'"></div>
                        <div class="space-y-0.5 min-w-0 flex-1">
                          <h3 class="text-xs sm:text-sm font-bold truncate" :class="theme === 'minimal_light' ? 'text-slate-900' : 'text-white'" x-text="card.title"></h3>
                          <p class="text-[11px] leading-relaxed" :class="theme === 'minimal_light' ? 'text-slate-500' : 'text-slate-400'" x-text="card.desc"></p>
                        </div>
                      </div>
                    </template>
                  </div>
                </div>
              </template>

              <!-- ================= 4. HEADING + TEXT CANVAS RENDER ================= -->
              <template x-if="block.type === 'heading_text' || block.type === 'text'">
                <div class="p-4 rounded-xl space-y-1.5" :class="theme === 'minimal_light' ? 'bg-white border border-slate-200/80 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]'">
                  <template x-if="block.heading">
                    <h2 class="text-sm font-extrabold" :class="theme === 'minimal_light' ? 'text-slate-900' : 'text-yellow-400'" x-text="block.heading"></h2>
                  </template>
                  <p class="text-xs whitespace-pre-wrap leading-relaxed" :class="theme === 'minimal_light' ? 'text-slate-600' : 'text-slate-300'" x-text="block.text || block.content"></p>
                </div>
              </template>

              <!-- ================= 5. IMAGE CANVAS RENDER ================= -->
              <template x-if="block.type === 'image'">
                <div class="rounded-xl overflow-hidden" :class="theme === 'minimal_light' ? 'border border-slate-200 shadow-sm bg-white' : 'border border-[#2A2E3A] bg-[#12141A]'">
                  <template x-if="block.url || block.image_url">
                    <img :src="block.url || block.image_url" :alt="block.alt || 'Image'" class="w-full h-auto object-cover max-h-72">
                  </template>
                  <template x-if="!block.url && !block.image_url">
                    <div class="p-8 text-center text-slate-500 text-xs font-mono">
                      🖼️ Image placeholder (Add image URL in sidebar)
                    </div>
                  </template>
                  <template x-if="block.caption">
                    <p class="text-[11px] text-center py-1.5" :class="theme === 'minimal_light' ? 'text-slate-500 bg-slate-50' : 'text-slate-400 bg-[#12141A]'" x-text="block.caption"></p>
                  </template>
                </div>
              </template>

              <!-- ================= 6. STANDALONE CTA BUTTON CANVAS RENDER ================= -->
              <template x-if="block.type === 'cta_button'">
                <div class="text-center p-4 rounded-xl space-y-2" :class="theme === 'minimal_light' ? 'bg-white border border-slate-200/80 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]'">
                  <template x-if="block.heading">
                    <h2 class="text-sm sm:text-base font-extrabold" :class="theme === 'minimal_light' ? 'text-slate-900' : 'text-white'" x-text="block.heading"></h2>
                  </template>
                  <template x-if="block.subheading">
                    <p class="text-xs" :class="theme === 'minimal_light' ? 'text-slate-600' : 'text-slate-400'" x-text="block.subheading"></p>
                  </template>
                  <div class="pt-1">
                    <div class="w-full flex items-center justify-center gap-2 px-5 py-3 rounded-xl font-bold text-xs sm:text-sm text-white"
                         :class="theme === 'minimal_light' ? 'bg-blue-600' : 'bg-gradient-to-r from-yellow-400 to-amber-500 text-slate-950 font-black'">
                      <span x-text="block.button_text || 'Join Free Telegram Channel →'"></span>
                    </div>
                    <template x-if="block.button_subtitle">
                      <p class="text-[10px] mt-1" :class="theme === 'minimal_light' ? 'text-slate-500' : 'text-slate-500'" x-text="block.button_subtitle"></p>
                    </template>
                  </div>
                </div>
              </template>

              <!-- ================= 7. TWO-COLUMN CANVAS RENDER ================= -->
              <template x-if="block.type === 'two_column'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-xl" :class="theme === 'minimal_light' ? 'bg-white border border-slate-200/80 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]'">
                  <div class="space-y-1">
                    <h3 class="text-xs font-bold" :class="theme === 'minimal_light' ? 'text-blue-600' : 'text-yellow-400'" x-text="block.col1_heading || 'Column 1'"></h3>
                    <p class="text-[11px] leading-relaxed whitespace-pre-wrap" :class="theme === 'minimal_light' ? 'text-slate-600' : 'text-slate-300'" x-text="block.col1_text || 'Content 1'"></p>
                  </div>
                  <div class="space-y-1">
                    <h3 class="text-xs font-bold" :class="theme === 'minimal_light' ? 'text-blue-600' : 'text-yellow-400'" x-text="block.col2_heading || 'Column 2'"></h3>
                    <p class="text-[11px] leading-relaxed whitespace-pre-wrap" :class="theme === 'minimal_light' ? 'text-slate-600' : 'text-slate-300'" x-text="block.col2_text || 'Content 2'"></p>
                  </div>
                </div>
              </template>

              <!-- ================= 8. FAQ CANVAS RENDER ================= -->
              <template x-if="block.type === 'faq'">
                <div class="space-y-2 pt-1">
                  <template x-if="block.title">
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-center" :class="theme === 'minimal_light' ? 'text-slate-700' : 'text-slate-400'" x-text="block.title"></h2>
                  </template>
                  <div class="space-y-1.5">
                    <template x-for="(faq, faqIdx) in (block.faqs || [])" :key="faqIdx">
                      <div class="p-3 rounded-xl text-xs" :class="theme === 'minimal_light' ? 'bg-white border border-slate-200/80 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]'">
                        <div class="font-bold flex justify-between items-center" :class="theme === 'minimal_light' ? 'text-slate-800' : 'text-slate-200'">
                          <span x-text="faq.q"></span>
                          <span class="text-slate-400">▼</span>
                        </div>
                        <div class="mt-1.5 pt-1.5 text-[11px]" :class="theme === 'minimal_light' ? 'border-t border-slate-100 text-slate-600' : 'border-t border-slate-800 text-slate-400'" x-text="faq.a"></div>
                      </div>
                    </template>
                  </div>
                </div>
              </template>

              <!-- ================= 9. DISCLAIMER CANVAS RENDER ================= -->
              <template x-if="block.type === 'disclaimer'">
                <div class="p-3.5 rounded-xl text-[10px] space-y-1" :class="theme === 'minimal_light' ? 'bg-slate-100 border border-slate-200 text-slate-500' : 'bg-[#12141A]/60 border border-amber-500/20 text-slate-400'">
                  <strong class="uppercase tracking-wider block font-bold text-[9px]" :class="theme === 'minimal_light' ? 'text-slate-700' : 'text-amber-400'" x-text="block.title || 'Risk Disclaimer'"></strong>
                  <p class="leading-relaxed whitespace-pre-wrap" x-text="block.text"></p>
                </div>
              </template>

              <!-- ================= 10. FOOTER CANVAS RENDER ================= -->
              <template x-if="block.type === 'footer'">
                <div class="pt-3 pb-1 text-center text-[11px] space-y-1" :class="theme === 'minimal_light' ? 'text-slate-500 border-t border-slate-200' : 'text-slate-500 border-t border-slate-800/80'">
                  <p x-text="block.copyright || ('© ' + (brandName || 'VIP Trading') + '. All rights reserved.')"></p>
                  <p class="text-[10px] font-medium" :class="theme === 'minimal_light' ? 'text-slate-400' : 'text-slate-500'" x-text="block.managed_by || '⚡ Ads Managed by Kirtnix Media'"></p>
                  <div class="flex justify-center gap-3 text-[10px] pt-0.5" :class="theme === 'minimal_light' ? 'text-slate-500' : 'text-slate-400'">
                    <span>Telegram</span>
                    <span>•</span>
                    <span>Disclaimer</span>
                  </div>
                </div>
              </template>

            </div>
          </template>

        </div>

        <!-- Add Section & AI Buttons at bottom of Canvas -->
        <div class="p-4 border-t flex flex-wrap items-center justify-center gap-2.5 rounded-b-2xl"
             :class="theme === 'minimal_light' ? 'border-slate-200 bg-slate-100/70' : 'border-slate-800/80 bg-slate-900/40'">
          <button 
            type="button" 
            @click="libraryOpen = true" 
            class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-yellow-400 font-bold text-xs border border-slate-700/80 shadow-md transition flex items-center gap-2 group"
          >
            <span class="text-base leading-none group-hover:scale-125 transition-transform">+</span>
            <span>Add Section</span>
          </button>

          <button 
            type="button" 
            @click="openAiModal(blocks.length > 0 ? 'refine' : 'new')" 
            class="px-4 py-2 rounded-xl bg-purple-950/60 hover:bg-purple-900/70 text-purple-200 font-bold text-xs border border-purple-700/60 shadow-md transition flex items-center gap-2 group"
          >
            <span class="text-sm">✨</span>
            <span x-text="blocks.length > 0 ? 'Refine with AI' : 'Generate with AI'"></span>
          </button>
        </div>
      </div>
    </main>

    <!-- ================= RIGHT SIDEBAR (INSPECTOR PANEL) ================= -->
    <aside class="w-80 sm:w-96 bg-slate-900 border-l border-slate-800 flex flex-col shrink-0 z-20 min-h-0">

      <!-- Sidebar Tabs -->
      <div class="flex border-b border-slate-800 bg-slate-950/60 p-1 shrink-0">
        <button 
          type="button" 
          @click="activeTab = 'settings'" 
          :class="activeTab === 'settings' ? 'bg-slate-800 text-yellow-400 border-slate-700' : 'text-slate-400 hover:text-slate-200 border-transparent'"
          class="flex-1 py-2 text-xs font-bold rounded-lg border transition flex items-center justify-center gap-1.5"
        >
          <span>⚙️</span>
          <span>Settings</span>
        </button>

        <button 
          type="button" 
          @click="activeTab = 'theme'" 
          :class="activeTab === 'theme' ? 'bg-slate-800 text-yellow-400 border-slate-700' : 'text-slate-400 hover:text-slate-200 border-transparent'"
          class="flex-1 py-2 text-xs font-bold rounded-lg border transition flex items-center justify-center gap-1.5"
        >
          <span>🎨</span>
          <span>Theme</span>
        </button>

        <button 
          type="button" 
          @click="activeTab = 'block'" 
          :class="activeTab === 'block' ? 'bg-slate-800 text-yellow-400 border-slate-700' : 'text-slate-400 hover:text-slate-200 border-transparent'"
          class="flex-1 py-2 text-xs font-bold rounded-lg border transition flex items-center justify-center gap-1.5"
        >
          <span>✏️</span>
          <span>Block</span>
          <template x-if="selectedBlockIndex !== null">
            <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
          </template>
        </button>
      </div>

      <!-- Tab Content Area (Scrolls independently) -->
      <div class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-5 space-y-4 custom-scrollbar">

        <!-- ================= TAB 1: PAGE & TRACKING SETTINGS ================= -->
        <div x-show="activeTab === 'settings'" class="space-y-4">
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-yellow-400/90 mb-3 pb-1 border-b border-slate-800">
              1. General & Client
            </h3>
            
            <div class="space-y-3 text-xs">
              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Assigned Client *</label>
                <select x-model="clientId" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
                  <option value="">Select Client</option>
                  @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                  @endforeach
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Attached Campaign (Optional)</label>
                <select x-model="campaignId" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
                  <option value="">No Campaign Attached</option>
                  @foreach($campaigns as $camp)
                    <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                  @endforeach
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Page Title *</label>
                <input type="text" x-model="title" placeholder="VIP Forex & Trading Channel" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Public URL Slug * (/lp/slug)</label>
                <input 
                  type="text" 
                  x-model="slug" 
                  @input="slug = slug.toLowerCase().replace(/[^a-z0-9-_]/g, '-').replace(/-+/g, '-')"
                  placeholder="forex-vip" 
                  class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none"
                >
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Brand Name *</label>
                <input type="text" x-model="brandName" placeholder="VIP TRADING" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Brand Tagline</label>
                <input type="text" x-model="brandTagline" placeholder="Official Stock Market & VIP Trading Channel" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 text-[11px] focus:border-yellow-400 outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Brand Logo URL</label>
                <input type="text" x-model="brandLogoUrl" placeholder="https://example.com/logo.png" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 text-[11px] focus:border-yellow-400 outline-none">
              </div>
            </div>
          </div>

          <!-- Telegram Funnel Config -->
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-yellow-400/90 mb-3 pb-1 border-b border-slate-800">
              2. Telegram Destination
            </h3>
            <div class="space-y-3 text-xs">
              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Default Telegram Link *</label>
                <input type="text" x-model="telegramDestination" placeholder="https://t.me/your_channel" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
                <p class="text-[10px] text-slate-500 mt-1">Supports channel username, private invite link, or request-to-join links.</p>
              </div>
            </div>
          </div>

          <!-- Meta Pixel & CAPI Settings -->
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-yellow-400/90 mb-3 pb-1 border-b border-slate-800 flex items-center justify-between">
              <span>3. Meta Pixel & CAPI</span>
              <span class="text-[10px] font-normal text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">Automated</span>
            </h3>
            <div class="space-y-3 text-xs">
              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Meta Pixel ID</label>
                <input type="text" x-model="metaPixelId" placeholder="123456789012345" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Meta CAPI Access Token</label>
                <input type="password" x-model="metaAccessToken" placeholder="EAA..." class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Test Event Code (Optional)</label>
                <input type="text" x-model="metaTestEventCode" placeholder="TEST12345" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Google Tag Manager ID</label>
                <input type="text" x-model="gtmId" placeholder="GTM-XXXXXX" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
              </div>
            </div>
          </div>
        </div>

        <!-- ================= TAB 2: THEME & DESIGN ================= -->
        <div x-show="activeTab === 'theme'" class="space-y-4">
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-yellow-400/90 mb-3 pb-1 border-b border-slate-800">
              Theme Selection
            </h3>

            <div class="space-y-3">
              <!-- Theme Option 1: Premium Dark -->
              <div 
                @click="theme = 'premium_dark'" 
                class="p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3"
                :class="theme === 'premium_dark' ? 'border-yellow-400 bg-slate-800/90 ring-1 ring-yellow-400' : 'border-slate-800 bg-slate-950 hover:border-slate-700'"
              >
                <div class="w-8 h-8 rounded-lg bg-[#0A0B0D] border border-slate-700 flex items-center justify-center text-lg shrink-0">
                  🌙
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-white">Premium Dark</span>
                    <template x-if="theme === 'premium_dark'">
                      <span class="text-[10px] font-bold text-yellow-400">ACTIVE</span>
                    </template>
                  </div>
                  <p class="text-[11px] text-slate-400 mt-0.5">High-contrast dark luxury theme with gold & blue glowing accents.</p>
                </div>
              </div>

              <!-- Theme Option 2: Minimal Light (Trading Community) -->
              <div 
                @click="theme = 'minimal_light'" 
                class="p-3.5 rounded-xl border cursor-pointer transition flex items-start gap-3"
                :class="theme === 'minimal_light' ? 'border-blue-500 bg-slate-800/90 ring-1 ring-blue-500' : 'border-slate-800 bg-slate-950 hover:border-slate-700'"
              >
                <div class="w-8 h-8 rounded-lg bg-white border border-slate-300 flex items-center justify-center text-lg shrink-0">
                  ☀️
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-white">Minimal Light (Trading)</span>
                    <template x-if="theme === 'minimal_light'">
                      <span class="text-[10px] font-bold text-blue-400">ACTIVE</span>
                    </template>
                  </div>
                  <p class="text-[11px] text-slate-400 mt-0.5">Clean light theme with rating stars, live counter pill, stat boxes, and blue CTA buttons.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Custom CSS -->
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-yellow-400/90 mb-3 pb-1 border-b border-slate-800">
              Custom CSS
            </h3>
            <textarea 
              x-model="customCss" 
              rows="4" 
              placeholder=".custom-class { ... }" 
              class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none"
            ></textarea>
          </div>
        </div>

        <!-- ================= TAB 3: BLOCK INSPECTOR ================= -->
        <div x-show="activeTab === 'block'" class="space-y-4">
          <template x-if="selectedBlockIndex === null">
            <div class="p-6 text-center text-slate-500 text-xs space-y-2">
              <div class="text-2xl">👆</div>
              <p class="font-semibold text-slate-400">No Block Selected</p>
              <p class="text-[11px]">Click on any section on the left canvas to edit its properties, text, and styles.</p>
            </div>
          </template>

          <template x-if="selectedBlockIndex !== null && blocks[selectedBlockIndex]">
            <div class="space-y-4 text-xs">
              <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <span class="font-mono text-yellow-400 uppercase font-bold text-xs" x-text="'Editing: ' + blocks[selectedBlockIndex].type"></span>
                <button type="button" @click="selectedBlockIndex = null" class="text-slate-400 hover:text-white text-xs">✕ Close</button>
              </div>

              <!-- HERO INSPECTOR -->
              <template x-if="blocks[selectedBlockIndex].type === 'hero'">
                <div class="space-y-3">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Live Traders Online Badge</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].live_traders_badge" placeholder="🟢 6,547 Traders Online Now" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Star Rating Text</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].rating_text" placeholder="4.9/5 (2,340 Reviews)" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Badge / Status Pill</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].badge" placeholder="⚡ 100% FREE VIP ACCESS" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Main Heading *</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].heading" placeholder="Join VIP Trading On Telegram" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Subheading</label>
                    <textarea x-model="blocks[selectedBlockIndex].subheading" rows="2" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none"></textarea>
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Button Text *</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].button_text" placeholder="Join Free Telegram Channel →" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Button Subtitle</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].button_subtitle" placeholder="Free instant access • No payment required" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
                  </div>
                </div>
              </template>

              <!-- STATS INSPECTOR -->
              <template x-if="blocks[selectedBlockIndex].type === 'stats'">
                <div class="space-y-3">
                  <label class="block text-[11px] font-semibold text-slate-300">Statistic Counters</label>
                  <template x-for="(st, stIdx) in (blocks[selectedBlockIndex].stats || [])" :key="stIdx">
                    <div class="p-2.5 rounded-lg bg-slate-950 border border-slate-800 space-y-1.5">
                      <div class="flex gap-2">
                        <input type="text" x-model="st.value" placeholder="50K+" class="w-1/2 bg-slate-900 border border-slate-700 rounded p-1.5 text-slate-100 outline-none">
                        <input type="text" x-model="st.label" placeholder="Members" class="w-1/2 bg-slate-900 border border-slate-700 rounded p-1.5 text-slate-100 outline-none">
                      </div>
                    </div>
                  </template>
                </div>
              </template>

              <!-- FEATURES GRID INSPECTOR -->
              <template x-if="blocks[selectedBlockIndex].type === 'features_grid'">
                <div class="space-y-3">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Section Title</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].title" placeholder="Why Join Our Community?" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Section Subtitle</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].subtitle" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div class="space-y-2 pt-1">
                    <label class="block text-[11px] font-semibold text-slate-300">Feature Items</label>
                    <template x-for="(card, cardIdx) in (blocks[selectedBlockIndex].cards || [])" :key="cardIdx">
                      <div class="p-2.5 rounded-lg bg-slate-950 border border-slate-800 space-y-1.5 relative group">
                        <button type="button" @click="blocks[selectedBlockIndex].cards.splice(cardIdx, 1)" class="absolute top-2 right-2 text-red-400 hover:text-red-300 text-xs">✕</button>
                        <div class="flex gap-2">
                          <input type="text" x-model="card.icon" placeholder="📊" class="w-12 bg-slate-900 border border-slate-700 rounded p-1.5 text-slate-100 text-center outline-none">
                          <input type="text" x-model="card.title" placeholder="Daily Market Setups" class="flex-1 bg-slate-900 border border-slate-700 rounded p-1.5 text-slate-100 outline-none">
                        </div>
                        <textarea x-model="card.desc" rows="2" placeholder="Description..." class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-slate-100 text-[11px] outline-none"></textarea>
                      </div>
                    </template>
                    <button type="button" @click="addCardToFeatures(blocks[selectedBlockIndex])" class="w-full py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-yellow-400 text-xs font-bold border border-slate-700 transition">
                      + Add Feature Item
                    </button>
                  </div>
                </div>
              </template>

              <!-- HEADING + TEXT INSPECTOR -->
              <template x-if="blocks[selectedBlockIndex].type === 'heading_text' || blocks[selectedBlockIndex].type === 'text'">
                <div class="space-y-3">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Heading</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].heading" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Text Content</label>
                    <textarea x-model="blocks[selectedBlockIndex].text" rows="4" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none"></textarea>
                  </div>
                </div>
              </template>

              <!-- IMAGE INSPECTOR -->
              <template x-if="blocks[selectedBlockIndex].type === 'image'">
                <div class="space-y-3">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Image URL</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].url" placeholder="https://..." class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Alt Text</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].alt" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Caption</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].caption" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                </div>
              </template>

              <!-- CTA BUTTON INSPECTOR -->
              <template x-if="blocks[selectedBlockIndex].type === 'cta_button'">
                <div class="space-y-3">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Heading</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].heading" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Subheading</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].subheading" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Button Text *</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].button_text" placeholder="Join Free Telegram Channel →" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Button Subtitle</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].button_subtitle" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                </div>
              </template>

              <!-- FAQ INSPECTOR -->
              <template x-if="blocks[selectedBlockIndex].type === 'faq'">
                <div class="space-y-3">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">FAQ Section Title</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].title" placeholder="Frequently Asked Questions" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div class="space-y-2 pt-1">
                    <label class="block text-[11px] font-semibold text-slate-300">Questions & Answers</label>
                    <template x-for="(faq, faqIdx) in (blocks[selectedBlockIndex].faqs || [])" :key="faqIdx">
                      <div class="p-2.5 rounded-lg bg-slate-950 border border-slate-800 space-y-1.5 relative group">
                        <button type="button" @click="blocks[selectedBlockIndex].faqs.splice(faqIdx, 1)" class="absolute top-2 right-2 text-red-400 hover:text-red-300 text-xs">✕</button>
                        <input type="text" x-model="faq.q" placeholder="Question..." class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-slate-100 outline-none">
                        <textarea x-model="faq.a" rows="2" placeholder="Answer..." class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-slate-100 text-[11px] outline-none"></textarea>
                      </div>
                    </template>
                    <button type="button" @click="addFaqItem(blocks[selectedBlockIndex])" class="w-full py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-yellow-400 text-xs font-bold border border-slate-700 transition">
                      + Add Question
                    </button>
                  </div>
                </div>
              </template>

              <!-- DISCLAIMER INSPECTOR -->
              <template x-if="blocks[selectedBlockIndex].type === 'disclaimer'">
                <div class="space-y-3">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Disclaimer Title</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].title" placeholder="Important Risk Disclaimer" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Disclaimer Text</label>
                    <textarea x-model="blocks[selectedBlockIndex].text" rows="4" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none"></textarea>
                  </div>
                </div>
              </template>

              <!-- FOOTER INSPECTOR -->
              <template x-if="blocks[selectedBlockIndex].type === 'footer'">
                <div class="space-y-3">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Copyright Text</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].copyright" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Managed By Branding</label>
                    <input type="text" x-model="blocks[selectedBlockIndex].managed_by" placeholder="⚡ Ads Managed by Kirtnix Media" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 outline-none">
                  </div>
                </div>
              </template>
            </div>
          </template>
        </div>

      </div>
    </aside>

  </div>

  <!-- ================= SECTION LIBRARY MODAL (+ Add Section) ================= -->
  <div 
    x-show="libraryOpen" 
    class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
    style="display: none;"
    @keydown.escape.window="libraryOpen = false"
  >
    <div 
      @click.outside="libraryOpen = false" 
      class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150"
    >
      <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-lg">🧱</span>
          <h2 class="text-sm font-extrabold text-white">Add New Block Section</h2>
        </div>
        <button type="button" @click="libraryOpen = false" class="text-slate-400 hover:text-white text-sm">✕</button>
      </div>

      <div class="p-6 grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[70vh] overflow-y-auto custom-scrollbar">
        <button type="button" @click="addBlock('hero')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">⚡</div>
          <div class="font-bold text-xs text-white">Hero Header</div>
          <p class="text-[10px] text-slate-400 mt-1">Logo, rating, live badge, headline, and primary CTA.</p>
        </button>

        <button type="button" @click="addBlock('stats')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🔢</div>
          <div class="font-bold text-xs text-white">Stats Counters</div>
          <p class="text-[10px] text-slate-400 mt-1">3-column counters for members, accuracy, and years.</p>
        </button>

        <button type="button" @click="addBlock('features_grid')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">📊</div>
          <div class="font-bold text-xs text-white">Feature Cards</div>
          <p class="text-[10px] text-slate-400 mt-1">Benefit cards with icons, titles, and descriptions.</p>
        </button>

        <button type="button" @click="addBlock('cta_button')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🎯</div>
          <div class="font-bold text-xs text-white">CTA Button</div>
          <p class="text-[10px] text-slate-400 mt-1">High-converting Telegram channel call to action.</p>
        </button>

        <button type="button" @click="addBlock('heading_text')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">📝</div>
          <div class="font-bold text-xs text-white">Heading & Text</div>
          <p class="text-[10px] text-slate-400 mt-1">About Us, bio, story, or description section.</p>
        </button>

        <button type="button" @click="addBlock('image')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🖼️</div>
          <div class="font-bold text-xs text-white">Image Proof</div>
          <p class="text-[10px] text-slate-400 mt-1">Trading chart screenshots, profits, or proof images.</p>
        </button>

        <button type="button" @click="addBlock('two_column')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">⚖️</div>
          <div class="font-bold text-xs text-white">Two Columns</div>
          <p class="text-[10px] text-slate-400 mt-1">What You Get vs Who It Is For breakdown.</p>
        </button>

        <button type="button" @click="addBlock('faq')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">❓</div>
          <div class="font-bold text-xs text-white">FAQ Accordion</div>
          <p class="text-[10px] text-slate-400 mt-1">Collapsible questions and answers to overcome doubts.</p>
        </button>

        <button type="button" @click="addBlock('disclaimer')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">⚠️</div>
          <div class="font-bold text-xs text-white">Risk Disclaimer</div>
          <p class="text-[10px] text-slate-400 mt-1">Financial markets regulatory disclaimer.</p>
        </button>

        <button type="button" @click="addBlock('footer')" class="p-4 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 hover:border-yellow-400/50 text-left transition group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">📄</div>
          <div class="font-bold text-xs text-white">Footer</div>
          <p class="text-[10px] text-slate-400 mt-1">Copyright, links, and agency branding.</p>
        </button>
      </div>
    </div>
  </div>

  <!-- ================= AI GENERATOR / REFINE MODAL ================= -->
  <div 
    x-show="aiModalOpen" 
    class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
    style="display: none;"
    @keydown.escape.window="aiModalOpen = false"
  >
    <div 
      @click.outside="aiModalOpen = false" 
      class="w-full max-w-lg bg-slate-900 border border-purple-500/30 rounded-2xl shadow-2xl overflow-hidden"
    >
      <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-gradient-to-r from-purple-950/40 to-slate-900">
        <div class="flex items-center gap-2">
          <span class="text-lg">✨</span>
          <h2 class="text-sm font-extrabold text-white" x-text="aiMode === 'refine' ? 'Refine Landing Page with AI' : 'Generate Landing Page with AI'"></h2>
        </div>
        <button type="button" @click="aiModalOpen = false" class="text-slate-400 hover:text-white text-sm">✕</button>
      </div>

      <div class="p-6 space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Prompt / Requirements *</label>
          <textarea 
            x-model="aiPrompt" 
            rows="3" 
            placeholder="e.g., Create a high-converting landing page for a BankNifty signals Telegram channel with 95% accuracy..." 
            class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-slate-100 text-xs focus:border-purple-400 outline-none"
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Tone</label>
            <select x-model="aiTone" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 text-xs outline-none">
              <option value="Urgent & High-Converting">Urgent & High-Converting</option>
              <option value="Professional & Analytical">Professional & Analytical</option>
              <option value="Exclusive VIP">Exclusive VIP</option>
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Language</label>
            <select x-model="aiLanguage" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 text-xs outline-none">
              <option value="English">English</option>
              <option value="Hindi / Hinglish">Hindi / Hinglish</option>
              <option value="Gujarati">Gujarati</option>
            </select>
          </div>
        </div>

        <template x-if="aiError">
          <div class="p-2.5 rounded-lg bg-red-950/60 border border-red-800 text-red-300 text-xs" x-text="aiError"></div>
        </template>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="aiModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white">Cancel</button>
          <button 
            type="button" 
            @click="generateWithAi()" 
            :disabled="aiLoading" 
            class="px-5 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold text-xs shadow-md transition disabled:opacity-50 flex items-center gap-2"
          >
            <span x-show="aiLoading" class="animate-spin text-sm">⏳</span>
            <span x-text="aiLoading ? 'Generating...' : 'Generate with AI'"></span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden Form for standard Laravel POST / PUT submission -->
  <form id="builder-form" method="POST" action="{{ $formAction }}" class="hidden">
    @csrf
    @if($isEdit)
      @method('PUT')
    @endif
    <input type="hidden" name="client_id" :value="clientId">
    <input type="hidden" name="campaign_id" :value="campaignId">
    <input type="hidden" name="title" :value="title">
    <input type="hidden" name="slug" :value="slug">
    <input type="hidden" name="template_type" value="visual_builder">
    <input type="hidden" name="theme" :value="theme">
    <input type="hidden" name="brand_name" :value="brandName">
    <input type="hidden" name="brand_tagline" :value="brandTagline">
    <input type="hidden" name="brand_logo_url" :value="brandLogoUrl">
    <input type="hidden" name="telegram_destination" :value="telegramDestination">
    <input type="hidden" name="meta_pixel_id" :value="metaPixelId">
    <input type="hidden" name="meta_access_token" :value="metaAccessToken">
    <input type="hidden" name="meta_test_event_code" :value="metaTestEventCode">
    <input type="hidden" name="gtm_id" :value="gtmId">
    <input type="hidden" name="custom_css" :value="customCss">
    <input type="hidden" name="is_active" :value="isActive ? 1 : 0">
    <input type="hidden" name="blocks_json" :value="JSON.stringify(blocks)">
  </form>

</div>

<script>
function visualBuilder(config) {
  return {
    blocks: config.blocks || [],
    title: config.title || 'New Landing Page',
    slug: config.slug || '',
    theme: config.theme || 'premium_dark',
    brandName: config.brandName || 'VIP TRADING',
    brandTagline: config.brandTagline || 'Official Stock Market & VIP Trading Channel',
    brandLogoUrl: config.brandLogoUrl || '',
    telegramDestination: config.telegramDestination || 'https://t.me/kirtnix',
    metaPixelId: config.metaPixelId || '',
    metaAccessToken: config.metaAccessToken || '',
    metaTestEventCode: config.metaTestEventCode || '',
    gtmId: config.gtmId || '',
    clientId: config.clientId || '',
    campaignId: config.campaignId || '',
    customCss: config.customCss || '',
    isActive: config.isActive !== undefined ? config.isActive : true,

    viewport: 'desktop', // desktop, tablet, mobile
    activeTab: 'settings', // settings, theme, block
    selectedBlockIndex: 0,
    libraryOpen: false,
    aiModalOpen: false,
    aiMode: 'new',
    aiPrompt: '',
    aiTone: 'Urgent & High-Converting',
    aiLanguage: 'English',
    aiLoading: false,
    aiError: '',

    selectBlock(index) {
      this.selectedBlockIndex = index;
      this.activeTab = 'block';
    },

    moveUp(index) {
      if (index > 0) {
        const item = this.blocks.splice(index, 1)[0];
        this.blocks.splice(index - 1, 0, item);
        this.selectedBlockIndex = index - 1;
      }
    },

    moveDown(index) {
      if (index < this.blocks.length - 1) {
        const item = this.blocks.splice(index, 1)[0];
        this.blocks.splice(index + 1, 0, item);
        this.selectedBlockIndex = index + 1;
      }
    },

    duplicateBlock(index) {
      const cloned = JSON.parse(JSON.stringify(this.blocks[index]));
      cloned.id = 'block_' + Math.random().toString(36).substr(2, 8);
      this.blocks.splice(index + 1, 0, cloned);
      this.selectedBlockIndex = index + 1;
    },

    deleteBlock(index) {
      if (confirm('Are you sure you want to delete this block section?')) {
        this.blocks.splice(index, 1);
        if (this.selectedBlockIndex >= this.blocks.length) {
          this.selectedBlockIndex = this.blocks.length - 1;
        }
        if (this.blocks.length === 0) {
          this.selectedBlockIndex = null;
          this.activeTab = 'settings';
        }
      }
    },

    addBlock(type) {
      const newId = 'block_' + Math.random().toString(36).substr(2, 8);
      let newBlock = { id: newId, type: type };

      switch (type) {
        case 'hero':
          newBlock = {
            id: newId,
            type: 'hero',
            live_traders_badge: '🟢 6,547 Traders Online Now',
            rating_text: '4.9/5 (2,340 Reviews)',
            badge: '⚡ 100% FREE VIP ACCESS',
            heading: 'Join ' + (this.brandName || 'VIP Trading') + ' On Telegram',
            subheading: 'Get daily high-accuracy trading signals, educational market setups, and real-time community updates.',
            button_text: 'Join Free Telegram Channel →',
            button_subtitle: 'Free instant access • No payment required',
          };
          break;
        case 'stats':
          newBlock = {
            id: newId,
            type: 'stats',
            stats: [
              { value: '50K+', label: 'Members' },
              { value: '5+ Years', label: 'Experience' },
              { value: '95%', label: 'Accuracy' }
            ]
          };
          break;
        case 'features_grid':
          newBlock = {
            id: newId,
            type: 'features_grid',
            title: 'Why Join Our Community?',
            subtitle: 'Professional advantages of our community',
            cards: [
              { icon: '📊', title: 'Daily Market Setups', desc: 'High-probability setups analyzed with strict risk management.' },
              { icon: '🎯', title: 'Clear Entry & Targets', desc: 'Every signal includes exact entry point, stop loss, and multiple targets.' },
              { icon: '⚡', title: 'Real-Time Telegram Alerts', desc: 'Instant push notifications directly to your phone so you never miss a move.' }
            ]
          };
          break;
        case 'heading_text':
          newBlock = {
            id: newId,
            type: 'heading_text',
            heading: 'About Our Community',
            text: 'We are a dedicated community of traders sharing real-time market setups, macro analysis, and educational resources.'
          };
          break;
        case 'text':
          newBlock = {
            id: newId,
            type: 'text',
            content: 'Get access to detailed daily market commentary and live trade execution.'
          };
          break;
        case 'image':
          newBlock = {
            id: newId,
            type: 'image',
            url: 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?auto=format&fit=crop&w=800&q=80',
            alt: 'Trading Chart Proof',
            caption: 'Verified Signal Performance Overview'
          };
          break;
        case 'cta_button':
          newBlock = {
            id: newId,
            type: 'cta_button',
            heading: 'Ready to Level Up Your Trading?',
            subheading: 'Tap below to gain instant access to our official Telegram channel.',
            button_text: 'Join Free Telegram Channel →',
            button_subtitle: 'Available on Telegram App & Web'
          };
          break;
        case 'two_column':
          newBlock = {
            id: newId,
            type: 'two_column',
            col1_heading: 'What You Get',
            col1_text: '• Daily trading signals\n• Live trade management\n• Risk calculator guidance',
            col2_heading: 'Who It Is For',
            col2_text: '• Full-time & part-time traders\n• Anyone looking for disciplined trade plans'
          };
          break;
        case 'faq':
          newBlock = {
            id: newId,
            type: 'faq',
            title: 'Frequently Asked Questions',
            faqs: [
              { q: 'Is this Telegram channel really free?', a: 'Yes! The main educational setups and community discussions are completely free to join.' },
              { q: 'How do I join after clicking the button?', a: 'Clicking the button will open the Telegram app directly to our official channel where you tap "Join".' },
              { q: 'Do I need prior trading experience?', a: 'Not at all. We provide comprehensive breakdowns suitable for beginners.' }
            ]
          };
          break;
        case 'disclaimer':
          newBlock = {
            id: newId,
            type: 'disclaimer',
            title: 'Important Risk Disclaimer',
            text: 'Trading financial markets involves substantial risk of loss and is not suitable for all investors. All setups, analysis, and information shared are for educational purposes only and do not constitute financial advice.'
          };
          break;
        case 'footer':
          newBlock = {
            id: newId,
            type: 'footer',
            copyright: '© ' + new Date().getFullYear() + ' ' + (this.brandName || 'VIP Trading') + '. All rights reserved.',
            managed_by: '⚡ Ads Managed by Kirtnix Media'
          };
          break;
      }

      this.blocks.push(newBlock);
      this.selectedBlockIndex = this.blocks.length - 1;
      this.activeTab = 'block';
      this.libraryOpen = false;
    },

    addCardToFeatures(block) {
      if (!block.cards) block.cards = [];
      block.cards.push({ icon: '⭐', title: 'New Benefit', desc: 'Description of the feature benefit.' });
    },

    addFaqItem(block) {
      if (!block.faqs) block.faqs = [];
      block.faqs.push({ q: 'New Question?', a: 'Answer to the question goes here.' });
    },

    openAiModal(mode = 'new') {
      this.aiMode = mode;
      this.aiError = '';
      if (mode === 'refine' && !this.aiPrompt) {
        this.aiPrompt = 'Make headlines more high-converting and optimize CTA copy.';
      }
      this.aiModalOpen = true;
    },

    async generateWithAi() {
      if (!this.aiPrompt.trim()) {
        this.aiError = 'Please enter a prompt describing your landing page.';
        return;
      }

      this.aiLoading = true;
      this.aiError = '';

      const payload = {
        prompt: this.aiPrompt.trim(),
        brand_name: this.brandName || null,
        tone: this.aiTone || null,
        language: this.aiLanguage || 'English',
        telegram_destination: this.telegramDestination || null,
        current_blocks: this.aiMode === 'refine' ? this.blocks : null
      };

      try {
        const response = await fetch('{{ route('landing-pages.generate_ai') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify(payload)
        });

        const res = await response.json();
        if (!response.ok || !res.success) {
          throw new Error(res.message || 'AI generation failed. Please try again.');
        }

        const data = res.data;
        if (data && Array.isArray(data.blocks) && data.blocks.length > 0) {
          this.blocks = data.blocks;
          if (data.title && (this.aiMode === 'new' || !this.title)) {
            this.title = data.title;
          }
          if (data.brand_name && (this.aiMode === 'new' || !this.brandName)) {
            this.brandName = data.brand_name;
          }
          if (data.slug && this.aiMode === 'new') {
            this.slug = data.slug;
          }
          this.aiModalOpen = false;
          this.selectedBlockIndex = 0;
          this.activeTab = 'block';
        } else {
          throw new Error('AI returned an empty block list.');
        }
      } catch (err) {
        console.error('AI Generation Error:', err);
        this.aiError = err.message || 'An unexpected error occurred.';
      } finally {
        this.aiLoading = false;
      }
    },

    submitForm(publish) {
      if (!this.clientId) {
        alert('Please select an Assigned Client in Settings.');
        this.activeTab = 'settings';
        return;
      }
      if (!this.title.trim()) {
        alert('Please enter a Page Title in Settings.');
        this.activeTab = 'settings';
        return;
      }
      if (!this.slug.trim()) {
        const base = (this.title || 'page').toLowerCase().replace(/[^a-z0-9-_]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        this.slug = base || ('page-' + Math.random().toString(36).substring(2, 7));
      }
      if (!this.telegramDestination.trim()) {
        alert('Please enter a Telegram Destination link in Settings.');
        this.activeTab = 'settings';
        return;
      }

      this.isActive = publish;
      document.getElementById('builder-form').submit();
    }
  };
}
</script>
@endsection
