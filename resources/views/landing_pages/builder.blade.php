@extends('layouts.app')

@section('title', isset($landingPage) ? 'Edit: ' . $landingPage->title : 'Visual Landing Page Builder')
@section('page_title', 'Software Landing Page Builder')

@section('content')
@php
  $isEdit = isset($landingPage);
  $formAction = $isEdit ? route('landing-pages.update', $landingPage) : route('landing-pages.store');
  $defaultBlocks = $isEdit && !empty($landingPage->blocks_json) 
      ? $landingPage->blocks_json 
      : \App\Models\LandingPage::getDefaultBlocks($landingPage->brand_name ?? 'VIP TRADING', $landingPage->telegram_destination ?? 'https://t.me/kirtnix');
@endphp

<div 
  x-data="visualBuilder({
    blocks: {{ json_encode($defaultBlocks) }},
    title: '{{ addslashes(old('title', $landingPage->title ?? 'New Landing Page')) }}',
    slug: '{{ addslashes(old('slug', $landingPage->slug ?? 'vip-channel-' . substr(md5(uniqid()), 0, 5))) }}',
    brandName: '{{ addslashes(old('brand_name', $landingPage->brand_name ?? 'VIP TRADING')) }}',
    brandLogoUrl: '{{ addslashes(old('brand_logo_url', $landingPage->brand_logo_url ?? '')) }}',
    telegramDestination: '{{ addslashes(old('telegram_destination', $landingPage->telegram_destination ?? 'https://t.me/kirtnix')) }}',
    metaPixelId: '{{ addslashes(old('meta_pixel_id', $landingPage->meta_pixel_id ?? '')) }}',
    metaAccessToken: '{{ addslashes(old('meta_access_token', $landingPage->meta_access_token ?? '')) }}',
    metaTestEventCode: '{{ addslashes(old('meta_test_event_code', $landingPage->meta_test_event_code ?? '')) }}',
    gtmId: '{{ addslashes(old('gtm_id', $landingPage->gtm_id ?? '')) }}',
    clientId: '{{ old('client_id', $landingPage->client_id ?? ($selectedClientId ?? ($clients->first()?->id ?? ''))) }}',
    campaignId: '{{ old('campaign_id', $landingPage->campaign_id ?? '') }}',
    isActive: {{ old('is_active', $landingPage->is_active ?? true) ? 'true' : 'false' }},
  })"
  class="-mt-6 -mx-4 sm:-mx-6 lg:-mx-8 flex flex-col h-[calc(100vh-65px)] overflow-hidden bg-slate-950 text-slate-100 font-sans"
>

  <!-- Top Builder Navigation Bar -->
  <header class="h-14 bg-slate-900 border-b border-slate-800 px-4 sm:px-6 flex items-center justify-between shrink-0 z-30">
    <div class="flex items-center gap-3">
      <a href="{{ route('landing-pages.index') }}" class="px-2.5 py-1 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800/80 hover:bg-slate-800 rounded-lg border border-slate-700/60 transition flex items-center gap-1.5">
        <span>←</span>
        <span>Back</span>
      </a>
      <div class="h-4 w-px bg-slate-800 hidden sm:block"></div>
      <div class="flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-yellow-400 animate-pulse"></span>
        <span class="text-xs font-bold text-white tracking-wide truncate max-w-[200px] sm:max-w-[300px]" x-text="title || 'Untitled Page'"></span>
        <span class="text-[11px] font-mono text-slate-400 bg-slate-800 px-2 py-0.5 rounded hidden md:inline" x-text="'/lp/' + slug"></span>
      </div>
    </div>

    <!-- Responsive Viewport Switcher -->
    <div class="flex items-center bg-slate-950 p-1 rounded-lg border border-slate-800">
      <button 
        type="button" 
        @click="viewport = 'desktop'" 
        :class="viewport === 'desktop' ? 'bg-slate-800 text-yellow-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
        class="px-2.5 py-1 rounded text-xs font-medium transition flex items-center gap-1"
        title="Desktop View"
      >
        <span>🖥️</span>
        <span class="hidden lg:inline text-[11px]">Desktop</span>
      </button>
      <button 
        type="button" 
        @click="viewport = 'tablet'" 
        :class="viewport === 'tablet' ? 'bg-slate-800 text-yellow-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
        class="px-2.5 py-1 rounded text-xs font-medium transition flex items-center gap-1"
        title="Tablet View (540px)"
      >
        <span>📱</span>
        <span class="hidden lg:inline text-[11px]">Tablet</span>
      </button>
      <button 
        type="button" 
        @click="viewport = 'mobile'" 
        :class="viewport === 'mobile' ? 'bg-slate-800 text-yellow-400 shadow-sm' : 'text-slate-400 hover:text-slate-200'"
        class="px-2.5 py-1 rounded text-xs font-medium transition flex items-center gap-1"
        title="Mobile View (375px)"
      >
        <span>📱</span>
        <span class="hidden lg:inline text-[11px]">Mobile</span>
      </button>
    </div>

    <!-- Actions: Preview & Save -->
    <div class="flex items-center gap-2">
      @if($isEdit)
        <a 
          :href="'/lp/' + slug" 
          target="_blank" 
          class="px-3 py-1.5 text-xs font-semibold text-slate-300 bg-slate-800/80 hover:bg-slate-800 hover:text-white rounded-lg border border-slate-700 transition flex items-center gap-1"
        >
          <span>👁️</span>
          <span class="hidden sm:inline">Preview</span>
        </a>
      @endif

      <button 
        type="button" 
        @click="submitForm(false)" 
        class="px-3.5 py-1.5 text-xs font-bold text-slate-200 bg-slate-800 hover:bg-slate-700 rounded-lg border border-slate-700 transition"
      >
        Save Draft
      </button>

      <button 
        type="button" 
        @click="submitForm(true)" 
        class="px-4 py-1.5 text-xs font-extrabold text-slate-950 bg-gradient-to-r from-yellow-400 to-amber-400 hover:from-yellow-300 hover:to-amber-300 rounded-lg shadow-sm shadow-yellow-500/20 transition flex items-center gap-1.5"
      >
        <span>🚀</span>
        <span>Publish</span>
      </button>
    </div>
  </header>

  <!-- Main Body: Canvas (Left) + Inspector Sidebar (Right) -->
  <div class="flex-1 flex overflow-hidden">

    <!-- LEFT / MAIN CANVAS -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-8 bg-slate-950 flex flex-col items-center">
      <div 
        class="w-full transition-all duration-300 mx-auto shadow-2xl rounded-2xl overflow-hidden border border-slate-800/80 bg-[#0A0B0D] min-h-[700px] flex flex-col justify-between"
        :class="{
          'max-w-[680px]': viewport === 'desktop',
          'max-w-[540px]': viewport === 'tablet',
          'max-w-[375px]': viewport === 'mobile'
        }"
      >
        <!-- Canvas Blocks Container -->
        <div class="p-4 sm:p-6 space-y-4">

          <template x-for="(block, index) in blocks" :key="block.id || index">
            <div 
              @click="selectBlock(index)"
              class="relative group rounded-2xl transition-all duration-150 cursor-pointer"
              :class="selectedBlockIndex === index ? 'ring-2 ring-yellow-400 ring-offset-2 ring-offset-slate-950 bg-slate-900/40 p-2 -m-2' : 'hover:ring-1 hover:ring-slate-700/80 p-1 -m-1'"
            >
              <!-- Mini Block Action Toolbar (Top-Right of Block) -->
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

              <!-- 1. HERO BLOCK CANVAS RENDER -->
              <template x-if="block.type === 'hero'">
                <div class="text-center space-y-3 pt-4 pb-2">
                  <template x-if="brandLogoUrl">
                    <div class="flex justify-center mb-2">
                      <img :src="brandLogoUrl" :alt="brandName" class="w-20 h-20 rounded-full object-cover border-2 border-yellow-400/40 shadow-lg">
                    </div>
                  </template>

                  <div class="text-xs font-extrabold uppercase tracking-wider text-yellow-400" x-text="brandName"></div>

                  <template x-if="block.badge">
                    <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
                      <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                      <span x-text="block.badge"></span>
                    </div>
                  </template>

                  <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight" x-text="block.heading || 'Join VIP Community'"></h1>
                  <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto" x-text="block.subheading"></p>

                  <div class="pt-2">
                    <div class="w-full flex items-center justify-center gap-2 px-5 py-3.5 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-600 text-white font-bold text-sm sm:text-base shadow-lg shadow-blue-500/25">
                      <span>➤</span>
                      <span x-text="block.button_text || 'Join Free Telegram Channel'"></span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5" x-text="block.button_subtitle || 'Free access • Instant entry'"></p>
                  </div>
                </div>
              </template>

              <!-- 2. FEATURES GRID CANVAS RENDER -->
              <template x-if="block.type === 'features_grid'">
                <div class="space-y-3 pt-2">
                  <template x-if="block.title">
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-center" x-text="block.title"></h2>
                  </template>
                  <div class="grid grid-cols-1 gap-2.5">
                    <template x-for="(card, cardIdx) in (block.cards || [])" :key="cardIdx">
                      <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-[#12141A] border border-[#2A2E3A]">
                        <div class="text-xl p-2 rounded-xl bg-[#1A1D26] border border-[#2A2E3A]" x-text="card.icon || '✨'"></div>
                        <div class="space-y-0.5">
                          <h3 class="text-xs font-bold text-white" x-text="card.title"></h3>
                          <p class="text-[11px] text-slate-400 leading-relaxed" x-text="card.desc"></p>
                        </div>
                      </div>
                    </template>
                  </div>
                </div>
              </template>

              <!-- 3. HEADING + TEXT CANVAS RENDER -->
              <template x-if="block.type === 'heading_text' || block.type === 'text'">
                <div class="p-4 rounded-2xl bg-[#12141A] border border-[#2A2E3A] space-y-1.5">
                  <template x-if="block.heading">
                    <h2 class="text-sm font-extrabold text-yellow-400" x-text="block.heading"></h2>
                  </template>
                  <p class="text-xs text-slate-300 whitespace-pre-wrap leading-relaxed" x-text="block.text || block.content"></p>
                </div>
              </template>

              <!-- 4. IMAGE CANVAS RENDER -->
              <template x-if="block.type === 'image'">
                <div class="rounded-2xl overflow-hidden border border-[#2A2E3A] bg-[#12141A]">
                  <template x-if="block.url || block.image_url">
                    <img :src="block.url || block.image_url" :alt="block.alt || 'Image'" class="w-full h-auto object-cover max-h-72">
                  </template>
                  <template x-if="!block.url && !block.image_url">
                    <div class="p-8 text-center text-slate-500 text-xs font-mono">
                      🖼️ Image placeholder (Add image URL in sidebar)
                    </div>
                  </template>
                  <template x-if="block.caption">
                    <p class="text-[11px] text-slate-400 text-center py-1.5" x-text="block.caption"></p>
                  </template>
                </div>
              </template>

              <!-- 5. STANDALONE CTA BUTTON CANVAS RENDER -->
              <template x-if="block.type === 'cta_button'">
                <div class="text-center p-5 rounded-2xl bg-[#12141A] border border-[#2A2E3A] space-y-2">
                  <template x-if="block.heading">
                    <h2 class="text-base font-extrabold text-white" x-text="block.heading"></h2>
                  </template>
                  <template x-if="block.subheading">
                    <p class="text-xs text-slate-400" x-text="block.subheading"></p>
                  </template>
                  <div class="pt-1">
                    <div class="w-full flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-yellow-400 to-amber-500 text-slate-950 font-bold text-xs sm:text-sm">
                      <span x-text="block.button_text || 'Open Telegram Channel'"></span>
                      <span>↗</span>
                    </div>
                    <template x-if="block.button_subtitle">
                      <p class="text-[10px] text-slate-500 mt-1" x-text="block.button_subtitle"></p>
                    </template>
                  </div>
                </div>
              </template>

              <!-- 6. TWO-COLUMN SECTION CANVAS RENDER -->
              <template x-if="block.type === 'two_column'">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 rounded-2xl bg-[#12141A] border border-[#2A2E3A]">
                  <div class="space-y-1">
                    <h3 class="text-xs font-bold text-yellow-400" x-text="block.col1_heading || 'Column 1'"></h3>
                    <p class="text-[11px] text-slate-300 leading-relaxed whitespace-pre-wrap" x-text="block.col1_text || 'Content 1'"></p>
                  </div>
                  <div class="space-y-1">
                    <h3 class="text-xs font-bold text-yellow-400" x-text="block.col2_heading || 'Column 2'"></h3>
                    <p class="text-[11px] text-slate-300 leading-relaxed whitespace-pre-wrap" x-text="block.col2_text || 'Content 2'"></p>
                  </div>
                </div>
              </template>

              <!-- 7. FAQ CANVAS RENDER -->
              <template x-if="block.type === 'faq'">
                <div class="space-y-2 pt-2">
                  <template x-if="block.title">
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-center" x-text="block.title"></h2>
                  </template>
                  <div class="space-y-1.5">
                    <template x-for="(faq, faqIdx) in (block.faqs || [])" :key="faqIdx">
                      <div class="p-3 rounded-xl bg-[#12141A] border border-[#2A2E3A] text-xs">
                        <div class="font-bold text-slate-200 flex justify-between items-center">
                          <span x-text="faq.q"></span>
                          <span class="text-slate-500">▼</span>
                        </div>
                        <div class="mt-1.5 pt-1.5 border-t border-slate-800 text-[11px] text-slate-400" x-text="faq.a"></div>
                      </div>
                    </template>
                  </div>
                </div>
              </template>

              <!-- 8. DISCLAIMER CANVAS RENDER -->
              <template x-if="block.type === 'disclaimer'">
                <div class="p-3.5 rounded-xl bg-[#12141A]/60 border border-amber-500/20 text-[10px] text-slate-400 space-y-1">
                  <strong class="text-amber-400 uppercase tracking-wider block font-bold text-[9px]" x-text="block.title || 'Risk Disclaimer'"></strong>
                  <p class="leading-relaxed whitespace-pre-wrap" x-text="block.text"></p>
                </div>
              </template>

              <!-- 9. FOOTER CANVAS RENDER -->
              <template x-if="block.type === 'footer'">
                <div class="pt-4 pb-2 text-center text-[11px] text-slate-500 border-t border-slate-800/80 space-y-1">
                  <p x-text="block.copyright || ('© ' + brandName)"></p>
                  <div class="flex justify-center gap-3 text-[10px] text-slate-400">
                    <span>Telegram</span>
                    <span>•</span>
                    <span>Disclaimer</span>
                  </div>
                </div>
              </template>

            </div>
          </template>

        </div>

        <!-- Add Section Button at bottom of Canvas -->
        <div class="p-4 border-t border-slate-800/80 bg-slate-900/30 flex justify-center">
          <button 
            type="button" 
            @click="libraryOpen = true" 
            class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-yellow-400 font-bold text-xs border border-slate-700/80 shadow-md transition flex items-center gap-2 group"
          >
            <span class="text-base leading-none group-hover:scale-125 transition-transform">+</span>
            <span>Add Section / Block</span>
          </button>
        </div>
      </div>
    </main>

    <!-- RIGHT SIDEBAR (INSPECTOR PANEL) -->
    <aside class="w-80 sm:w-96 bg-slate-900 border-l border-slate-800 flex flex-col shrink-0 z-20">

      <!-- Sidebar Tabs -->
      <div class="flex border-b border-slate-800 bg-slate-950/60 p-1">
        <button 
          type="button" 
          @click="activeTab = 'settings'" 
          :class="activeTab === 'settings' ? 'bg-slate-800 text-yellow-400 border-slate-700' : 'text-slate-400 hover:text-slate-200 border-transparent'"
          class="flex-1 py-2 text-xs font-bold rounded-lg border transition flex items-center justify-center gap-1.5"
        >
          <span>⚙️</span>
          <span>Page & Tracking</span>
        </button>

        <button 
          type="button" 
          @click="activeTab = 'block'" 
          :class="activeTab === 'block' ? 'bg-slate-800 text-yellow-400 border-slate-700' : 'text-slate-400 hover:text-slate-200 border-transparent'"
          class="flex-1 py-2 text-xs font-bold rounded-lg border transition flex items-center justify-center gap-1.5"
        >
          <span>🎨</span>
          <span>Block Inspector</span>
          <template x-if="selectedBlockIndex !== null">
            <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
          </template>
        </button>
      </div>

      <!-- Tab Content Area -->
      <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-5">

        <!-- TAB 1: PAGE & TRACKING SETTINGS -->
        <div x-show="activeTab === 'settings'" class="space-y-4">
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-yellow-400/90 mb-3 pb-1 border-b border-slate-800">
              1. General & Client
            </h3>
            
            <div class="space-y-3 text-xs">
              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Assigned Client *</label>
                <select x-model="clientId" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 focus:ring-1 focus:ring-yellow-400 outline-none">
                  <option value="">Select Client</option>
                  @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                  @endforeach
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Attached Campaign (Optional)</label>
                <select x-model="campaignId" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 focus:ring-1 focus:ring-yellow-400 outline-none">
                  <option value="">No Campaign Attached</option>
                  @foreach($campaigns as $camp)
                    <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                  @endforeach
                </select>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Page Title *</label>
                <input type="text" x-model="title" placeholder="Forex VIP Community" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Public URL Slug * (/lp/slug)</label>
                <input type="text" x-model="slug" placeholder="forex-vip" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Brand Name *</label>
                <input type="text" x-model="brandName" placeholder="FOREX FOCUS" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 focus:border-yellow-400 outline-none">
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
                <input type="text" x-model="telegramDestination" placeholder="https://t.me/your_channel or https://t.me/+xyz" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
                <p class="text-[10px] text-slate-500 mt-1">Supports public channel username, private invite link, or request-to-join links.</p>
              </div>
            </div>
          </div>

          <!-- Software-Managed Meta Pixel Settings -->
          <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-yellow-400/90 mb-3 pb-1 border-b border-slate-800 flex items-center justify-between">
              <span>3. Meta Pixel & CAPI</span>
              <span class="text-[10px] font-normal text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">Automated</span>
            </h3>
            
            <div class="space-y-3 text-xs">
              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Meta Pixel ID</label>
                <input type="text" x-model="metaPixelId" placeholder="1018611380802707" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
                <p class="text-[10px] text-slate-500 mt-1">Automatically generates browser PageView & CTA events. No script tags needed.</p>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Meta Access Token (Server CAPI)</label>
                <input type="password" x-model="metaAccessToken" placeholder="EAA..." class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
                <p class="text-[10px] text-slate-500 mt-1">Stored securely on server. Never exposed to browser or visible HTML.</p>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Meta Test Event Code</label>
                <input type="text" x-model="metaTestEventCode" placeholder="TEST12345" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-300 mb-1">Google Tag Manager ID</label>
                <input type="text" x-model="gtmId" placeholder="GTM-XXXXXX" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px] focus:border-yellow-400 outline-none">
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: BLOCK INSPECTOR -->
        <div x-show="activeTab === 'block'" class="space-y-4">
          <template x-if="selectedBlockIndex === null">
            <div class="text-center py-10 px-4 bg-slate-950/60 rounded-2xl border border-dashed border-slate-800">
              <div class="text-3xl mb-2">👆</div>
              <h4 class="text-xs font-bold text-white mb-1">No Block Selected</h4>
              <p class="text-[11px] text-slate-400 mb-3 leading-relaxed">
                Click any section in the live canvas on the left to edit its headlines, buttons, and content.
              </p>
              <button 
                type="button" 
                @click="libraryOpen = true" 
                class="px-3 py-1.5 rounded-lg bg-yellow-400 hover:bg-yellow-500 text-slate-950 font-bold text-xs transition"
              >
                + Add New Block
              </button>
            </div>
          </template>

          <template x-if="selectedBlockIndex !== null">
            <div class="space-y-4">
              <!-- Selected Block Header -->
              <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <div class="flex items-center gap-2">
                  <span class="text-base">✏️</span>
                  <span class="text-xs font-bold uppercase tracking-wider text-yellow-400 font-mono" x-text="currentBlock.type"></span>
                </div>
                <button type="button" @click="deleteBlock(selectedBlockIndex)" class="text-xs font-bold text-red-400 hover:text-red-300">
                  Delete Block
                </button>
              </div>

              <!-- HERO BLOCK SETTINGS -->
              <template x-if="currentBlock.type === 'hero'">
                <div class="space-y-3 text-xs">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Badge Text</label>
                    <input type="text" x-model="currentBlock.badge" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Main Heading</label>
                    <input type="text" x-model="currentBlock.heading" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Subheading</label>
                    <textarea rows="2" x-model="currentBlock.subheading" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100"></textarea>
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Button Text</label>
                    <input type="text" x-model="currentBlock.button_text" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Button Subtitle Hint</label>
                    <input type="text" x-model="currentBlock.button_subtitle" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Hero Image URL (Optional)</label>
                    <input type="text" x-model="currentBlock.image_url" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px]">
                  </div>
                </div>
              </template>

              <!-- FEATURES GRID BLOCK SETTINGS -->
              <template x-if="currentBlock.type === 'features_grid'">
                <div class="space-y-3 text-xs">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Section Title</label>
                    <input type="text" x-model="currentBlock.title" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Section Subtitle</label>
                    <input type="text" x-model="currentBlock.subtitle" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>

                  <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-[11px] font-bold text-slate-300">Feature Cards</span>
                      <button type="button" @click="addCardToFeatures(currentBlock)" class="text-[11px] font-bold text-yellow-400 hover:underline">+ Add Card</button>
                    </div>
                    <div class="space-y-2">
                      <template x-for="(card, cardIdx) in (currentBlock.cards || [])" :key="cardIdx">
                        <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800 space-y-1.5">
                          <div class="flex items-center justify-between">
                            <input type="text" x-model="card.icon" placeholder="Icon" class="w-12 bg-slate-900 border border-slate-700 rounded p-1 text-center text-xs">
                            <button type="button" @click="currentBlock.cards.splice(cardIdx, 1)" class="text-red-400 hover:text-red-300 text-xs">✕</button>
                          </div>
                          <input type="text" x-model="card.title" placeholder="Card Title" class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-xs text-white">
                          <textarea rows="2" x-model="card.desc" placeholder="Card description" class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-xs text-slate-300"></textarea>
                        </div>
                      </template>
                    </div>
                  </div>
                </div>
              </template>

              <!-- HEADING + TEXT SETTINGS -->
              <template x-if="currentBlock.type === 'heading_text' || currentBlock.type === 'text'">
                <div class="space-y-3 text-xs">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Section Heading</label>
                    <input type="text" x-model="currentBlock.heading" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Body Text</label>
                    <textarea rows="5" x-model="currentBlock.text" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100"></textarea>
                  </div>
                </div>
              </template>

              <!-- IMAGE BLOCK SETTINGS -->
              <template x-if="currentBlock.type === 'image'">
                <div class="space-y-3 text-xs">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Image URL *</label>
                    <input type="text" x-model="currentBlock.url" placeholder="https://example.com/photo.jpg" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100 font-mono text-[11px]">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Alt Text</label>
                    <input type="text" x-model="currentBlock.alt" placeholder="Image description" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Caption</label>
                    <input type="text" x-model="currentBlock.caption" placeholder="Optional caption" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                </div>
              </template>

              <!-- CTA BUTTON SETTINGS -->
              <template x-if="currentBlock.type === 'cta_button'">
                <div class="space-y-3 text-xs">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Heading</label>
                    <input type="text" x-model="currentBlock.heading" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Subheading</label>
                    <input type="text" x-model="currentBlock.subheading" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Button Text</label>
                    <input type="text" x-model="currentBlock.button_text" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Button Subtitle Hint</label>
                    <input type="text" x-model="currentBlock.button_subtitle" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                </div>
              </template>

              <!-- TWO-COLUMN SETTINGS -->
              <template x-if="currentBlock.type === 'two_column'">
                <div class="space-y-3 text-xs">
                  <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800 space-y-2">
                    <span class="font-bold text-yellow-400 block text-[11px]">Column 1</span>
                    <input type="text" x-model="currentBlock.col1_heading" placeholder="Col 1 Heading" class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-xs text-white">
                    <textarea rows="3" x-model="currentBlock.col1_text" placeholder="Col 1 Text" class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-xs text-slate-300"></textarea>
                  </div>

                  <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800 space-y-2">
                    <span class="font-bold text-yellow-400 block text-[11px]">Column 2</span>
                    <input type="text" x-model="currentBlock.col2_heading" placeholder="Col 2 Heading" class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-xs text-white">
                    <textarea rows="3" x-model="currentBlock.col2_text" placeholder="Col 2 Text" class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-xs text-slate-300"></textarea>
                  </div>
                </div>
              </template>

              <!-- FAQ SETTINGS -->
              <template x-if="currentBlock.type === 'faq'">
                <div class="space-y-3 text-xs">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Section Title</label>
                    <input type="text" x-model="currentBlock.title" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div class="pt-2">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-[11px] font-bold text-slate-300">Questions & Answers</span>
                      <button type="button" @click="addFaqItem(currentBlock)" class="text-[11px] font-bold text-yellow-400 hover:underline">+ Add FAQ</button>
                    </div>
                    <div class="space-y-2">
                      <template x-for="(faq, faqIdx) in (currentBlock.faqs || [])" :key="faqIdx">
                        <div class="p-2.5 bg-slate-950 rounded-xl border border-slate-800 space-y-1.5">
                          <div class="flex items-center justify-between">
                            <span class="text-[10px] font-mono text-slate-500 font-bold" x-text="'Q' + (faqIdx + 1)"></span>
                            <button type="button" @click="currentBlock.faqs.splice(faqIdx, 1)" class="text-red-400 hover:text-red-300 text-xs">✕</button>
                          </div>
                          <input type="text" x-model="faq.q" placeholder="Question" class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-xs text-white">
                          <textarea rows="2" x-model="faq.a" placeholder="Answer" class="w-full bg-slate-900 border border-slate-700 rounded p-1.5 text-xs text-slate-300"></textarea>
                        </div>
                      </template>
                    </div>
                  </div>
                </div>
              </template>

              <!-- DISCLAIMER SETTINGS -->
              <template x-if="currentBlock.type === 'disclaimer'">
                <div class="space-y-3 text-xs">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Title</label>
                    <input type="text" x-model="currentBlock.title" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Disclaimer Content</label>
                    <textarea rows="4" x-model="currentBlock.text" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100"></textarea>
                  </div>
                </div>
              </template>

              <!-- FOOTER SETTINGS -->
              <template x-if="currentBlock.type === 'footer'">
                <div class="space-y-3 text-xs">
                  <div>
                    <label class="block text-[11px] font-semibold text-slate-300 mb-1">Copyright Text</label>
                    <input type="text" x-model="currentBlock.copyright" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2 text-slate-100">
                  </div>
                </div>
              </template>

            </div>
          </template>
        </div>

      </div>
    </aside>

  </div>

  <!-- BLOCK LIBRARY MODAL -->
  <div x-show="libraryOpen" style="display: none;" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="libraryOpen = false">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-800">
        <div>
          <h3 class="text-sm font-extrabold text-white">Add New Block</h3>
          <p class="text-xs text-slate-400">Choose a section to add to your landing page.</p>
        </div>
        <button type="button" @click="libraryOpen = false" class="text-slate-400 hover:text-white text-lg">✕</button>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[60vh] overflow-y-auto p-1">
        <button type="button" @click="addBlock('hero')" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-yellow-400/60 text-left transition group">
          <div class="text-xl mb-1 group-hover:scale-110 transition-transform">⚡</div>
          <div class="text-xs font-bold text-white group-hover:text-yellow-400">Hero Section</div>
          <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Title, badge, primary CTA</div>
        </button>

        <button type="button" @click="addBlock('features_grid')" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-yellow-400/60 text-left transition group">
          <div class="text-xl mb-1 group-hover:scale-110 transition-transform">✨</div>
          <div class="text-xs font-bold text-white group-hover:text-yellow-400">Feature Cards</div>
          <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Icons, benefits, value prop</div>
        </button>

        <button type="button" @click="addBlock('cta_button')" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-yellow-400/60 text-left transition group">
          <div class="text-xl mb-1 group-hover:scale-110 transition-transform">🎯</div>
          <div class="text-xs font-bold text-white group-hover:text-yellow-400">Call to Action</div>
          <div class="text-[10px] text-slate-500 leading-tight mt-0.5">High-impact Telegram CTA</div>
        </button>

        <button type="button" @click="addBlock('heading_text')" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-yellow-400/60 text-left transition group">
          <div class="text-xl mb-1 group-hover:scale-110 transition-transform">📝</div>
          <div class="text-xs font-bold text-white group-hover:text-yellow-400">Heading & Text</div>
          <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Title and descriptive copy</div>
        </button>

        <button type="button" @click="addBlock('image')" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-yellow-400/60 text-left transition group">
          <div class="text-xl mb-1 group-hover:scale-110 transition-transform">🖼️</div>
          <div class="text-xs font-bold text-white group-hover:text-yellow-400">Image / Media</div>
          <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Proof screenshots, charts</div>
        </button>

        <button type="button" @click="addBlock('two_column')" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-yellow-400/60 text-left transition group">
          <div class="text-xl mb-1 group-hover:scale-110 transition-transform">⚖️</div>
          <div class="text-xs font-bold text-white group-hover:text-yellow-400">Two-Column</div>
          <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Side-by-side comparison</div>
        </button>

        <button type="button" @click="addBlock('faq')" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-yellow-400/60 text-left transition group">
          <div class="text-xl mb-1 group-hover:scale-110 transition-transform">❓</div>
          <div class="text-xs font-bold text-white group-hover:text-yellow-400">FAQ Section</div>
          <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Questions & answers</div>
        </button>

        <button type="button" @click="addBlock('disclaimer')" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-yellow-400/60 text-left transition group">
          <div class="text-xl mb-1 group-hover:scale-110 transition-transform">⚠️</div>
          <div class="text-xs font-bold text-white group-hover:text-yellow-400">Disclaimer</div>
          <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Risk & compliance notes</div>
        </button>

        <button type="button" @click="addBlock('footer')" class="p-3.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-yellow-400/60 text-left transition group">
          <div class="text-xl mb-1 group-hover:scale-110 transition-transform">🏷️</div>
          <div class="text-xs font-bold text-white group-hover:text-yellow-400">Page Footer</div>
          <div class="text-[10px] text-slate-500 leading-tight mt-0.5">Copyright & links</div>
        </button>
      </div>

      <div class="flex justify-end pt-2">
        <button type="button" @click="libraryOpen = false" class="px-4 py-1.5 rounded-lg bg-slate-800 text-slate-300 hover:text-white text-xs font-semibold">
          Cancel
        </button>
      </div>
    </div>
  </div>

  <!-- Hidden Form for submission -->
  <form id="builder-form" method="POST" action="{{ $formAction }}" class="hidden">
    @csrf
    @if($isEdit)
      @method('PUT')
    @endif
    <input type="hidden" name="client_id" :value="clientId">
    <input type="hidden" name="campaign_id" :value="campaignId">
    <input type="hidden" name="title" :value="title">
    <input type="hidden" name="slug" :value="slug">
    <input type="hidden" name="brand_name" :value="brandName">
    <input type="hidden" name="brand_logo_url" :value="brandLogoUrl">
    <input type="hidden" name="telegram_destination" :value="telegramDestination">
    <input type="hidden" name="meta_pixel_id" :value="metaPixelId">
    <input type="hidden" name="meta_access_token" :value="metaAccessToken">
    <input type="hidden" name="meta_test_event_code" :value="metaTestEventCode">
    <input type="hidden" name="gtm_id" :value="gtmId">
    <input type="hidden" name="template_type" value="visual_builder">
    <input type="hidden" name="is_active" :value="isActive ? '1' : '0'">
    <input type="hidden" name="blocks_json" :value="JSON.stringify(blocks)">
  </form>

</div>

<script>
function visualBuilder(config) {
  return {
    blocks: config.blocks || [],
    selectedBlockIndex: 0,
    viewport: 'desktop',
    activeTab: 'settings',
    libraryOpen: false,

    title: config.title || '',
    slug: config.slug || '',
    brandName: config.brandName || '',
    brandLogoUrl: config.brandLogoUrl || '',
    telegramDestination: config.telegramDestination || 'https://t.me/kirtnix',
    metaPixelId: config.metaPixelId || '',
    metaAccessToken: config.metaAccessToken || '',
    metaTestEventCode: config.metaTestEventCode || '',
    gtmId: config.gtmId || '',
    clientId: config.clientId || '',
    campaignId: config.campaignId || '',
    isActive: config.isActive,

    get currentBlock() {
      if (this.selectedBlockIndex !== null && this.blocks[this.selectedBlockIndex]) {
        return this.blocks[this.selectedBlockIndex];
      }
      return {};
    },

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
      const copy = JSON.parse(JSON.stringify(this.blocks[index]));
      copy.id = 'block_' + Math.random().toString(36).substr(2, 8);
      this.blocks.splice(index + 1, 0, copy);
      this.selectedBlockIndex = index + 1;
    },

    deleteBlock(index) {
      if (confirm('Delete this section?')) {
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
            badge: '⚡ 100% FREE ACCESS',
            heading: 'Join ' + (this.brandName || 'VIP Trading') + ' On Telegram',
            subheading: 'Get real-time market analysis and verified trading signals.',
            button_text: 'Join Free Telegram Channel',
            button_subtitle: 'Instant Telegram access • Free forever',
          };
          break;
        case 'features_grid':
          newBlock = {
            id: newId,
            type: 'features_grid',
            title: 'Why Join Us',
            subtitle: 'Professional advantages of our community',
            cards: [
              { icon: '📈', title: 'Daily Setups', desc: 'High-probability signals with strict risk rules.' },
              { icon: '🎯', title: 'Clear Targets', desc: 'Precise entry, stop loss, and target levels.' },
              { icon: '⚡', title: 'Instant Alerts', desc: 'Real-time Telegram notifications.' }
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
            heading: 'Start Trading Smarter Today',
            subheading: 'Tap below to enter the channel for free.',
            button_text: 'Open Telegram Channel Now',
            button_subtitle: 'Opens directly in Telegram app'
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
              { q: 'Is access really free?', a: 'Yes, our main educational channel is 100% free.' },
              { q: 'How do I join?', a: 'Click the button above to launch Telegram and press "Join".' }
            ]
          };
          break;
        case 'disclaimer':
          newBlock = {
            id: newId,
            type: 'disclaimer',
            title: 'Risk Disclaimer',
            text: 'Trading financial instruments involves significant risk and can result in loss of capital. Past performance is not indicative of future results.'
          };
          break;
        case 'footer':
          newBlock = {
            id: newId,
            type: 'footer',
            copyright: '© ' + new Date().getFullYear() + ' ' + (this.brandName || 'VIP Trading') + '. All rights reserved.'
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

    submitForm(publish) {
      if (!this.clientId) {
        alert('Please select an Assigned Client in Page Settings.');
        this.activeTab = 'settings';
        return;
      }
      if (!this.title.trim()) {
        alert('Please enter a Page Title.');
        this.activeTab = 'settings';
        return;
      }
      if (!this.slug.trim()) {
        alert('Please enter a Public URL Slug.');
        this.activeTab = 'settings';
        return;
      }
      if (!this.telegramDestination.trim()) {
        alert('Please enter a Telegram Destination link.');
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
