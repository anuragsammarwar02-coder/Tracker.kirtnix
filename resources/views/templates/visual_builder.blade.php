<!DOCTYPE html>
<html lang="en" style="min-height: 100%; overflow-x: hidden; overflow-y: auto;">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  @php
    $theme = $landingPage->theme ?? 'premium_dark';
    $isLight = ($theme === 'minimal_light');
  @endphp
  <meta name="theme-color" content="{{ $isLight ? '#F8FAFC' : '#0B0F19' }}" />
  <meta name="description" content="{{ e($landingPage->hero_subheading ?? $landingPage->title) }}" />
  <title>{{ e($landingPage->title) }}</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/branding/favicon.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'Outfit', 'sans-serif'],
          },
          colors: {
            brand: {
              gold: '#F0C14B',
              yellow: '#EAB308',
              blue: '#2563EB',
              blueHover: '#1D4ED8',
            }
          }
        }
      }
    }
  </script>

  @if(!empty($landingPage->gtm_id))
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','{{ e($landingPage->gtm_id) }}');</script>
  <!-- End Google Tag Manager -->
  @endif

  @if(!empty($landingPage->meta_pixel_id))
  <!-- Meta Pixel Code (Software-Managed) -->
  <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ e($landingPage->meta_pixel_id) }}');
    fbq('track', 'PageView', {}, {eventID: '{{ e($metaEventId ?? "") }}'});
  </script>
  <noscript>
    <img height="1" width="1" style="display:none"
      src="https://www.facebook.com/tr?id={{ e($landingPage->meta_pixel_id) }}&ev=PageView&noscript=1" alt="" />
  </noscript>
  <!-- End Meta Pixel Code -->
  @endif

  <style>
    html {
      scroll-behavior: smooth;
    }
    body {
      font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
      overflow-y: auto;
      min-height: 100vh;
    }
    .cta-btn-glow {
      box-shadow: 0 8px 24px rgba(37, 99, 235, 0.35);
      transition: transform 0.15s ease, filter 0.15s ease, box-shadow 0.15s ease;
    }
    .cta-btn-glow:hover {
      transform: translateY(-2px);
      filter: brightness(1.06);
      box-shadow: 0 12px 30px rgba(37, 99, 235, 0.45);
    }
    .cta-btn-glow:active {
      transform: translateY(0);
    }
  </style>

  @if(!empty($landingPage->custom_css))
  <style>
    {!! strip_tags($landingPage->custom_css) !!}
  </style>
  @endif

  @include('templates.partials.anti_inspection')
</head>
<body class="{{ $isLight ? 'bg-[#F4F6F9] text-slate-900' : 'bg-[#0A0B0D] text-slate-100' }} min-h-screen flex flex-col justify-between">

  @if(!empty($landingPage->gtm_id))
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ e($landingPage->gtm_id) }}"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->
  @endif

  @php
    $blocks = $landingPage->blocks_json ?: \App\Models\LandingPage::getDefaultBlocks($landingPage->brand_name, $landingPage->telegram_destination);
    $ctaUrl = $primaryCta ? route('public.cta_redirect', $primaryCta->tracking_token) : ($landingPage->telegram_destination ?: 'https://t.me/kirtnix');
    $ctaToken = $primaryCta?->tracking_token ?? ($landingPage->tracking_token ?? $landingPage->slug);
    $brand = $landingPage->brand_name ?: 'VIP Trading';
    $logo = !empty($landingPage->brand_logo_url) ? $landingPage->brand_logo_url : null;
  @endphp

  <!-- Main Scrollable Content Wrapper (Natural Height) -->
  <main class="w-full max-w-[620px] mx-auto px-4 sm:px-6 py-6 sm:py-10 space-y-5 flex-1">

    @foreach($blocks as $block)
      @php
        $type = $block['type'] ?? 'text';
      @endphp

      {{-- ================= 1. HERO BLOCK ================= --}}
      @if($type === 'hero')
        <section class="text-center space-y-3.5 pt-2 pb-1">

          {{-- Live Traders Status Pill --}}
          @if(!empty($block['live_traders_badge']) || $isLight)
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold {{ $isLight ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-sm' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' }}">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>{{ e($block['live_traders_badge'] ?? '6,547 Traders Online Now') }}</span>
            </div>
          @endif

          {{-- Logo Avatar --}}
          @if($logo || !empty($block['logo_url']))
            <div class="flex justify-center my-2">
              <img 
                src="{{ e($block['logo_url'] ?? $logo) }}" 
                alt="{{ e($brand) }}" 
                class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover {{ $isLight ? 'border-4 border-white shadow-lg bg-white ring-1 ring-slate-200' : 'border-2 border-yellow-400/40 shadow-xl shadow-yellow-500/10' }}"
              >
            </div>
          @endif

          {{-- Brand Name / Channel Subheading --}}
          <div class="space-y-0.5">
            <h2 class="text-lg sm:text-xl font-black tracking-tight {{ $isLight ? 'text-slate-900' : 'text-white' }}">
              {{ e($brand) }}
            </h2>
            @if(!empty($landingPage->brand_tagline) || !empty($block['tagline']))
              <p class="text-xs font-semibold {{ $isLight ? 'text-blue-600' : 'text-yellow-400/90' }}">
                {{ e($block['tagline'] ?? $landingPage->brand_tagline ?? 'Official Stock Market & VIP Trading Channel') }}
              </p>
            @endif
          </div>

          {{-- Rating Stars & Reviews --}}
          @if(!empty($block['rating_text']) || $isLight)
            <div class="flex items-center justify-center gap-1.5 text-xs font-bold text-amber-500">
              <span>⭐⭐⭐⭐⭐</span>
              <span class="{{ $isLight ? 'text-slate-600' : 'text-slate-300' }} font-semibold text-[11px]">
                {{ e($block['rating_text'] ?? '4.9/5 (2,340 Reviews)') }}
              </span>
            </div>
          @endif

          {{-- Status / Redirect Countdown Pill --}}
          @if(!empty($block['badge']))
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-xl text-xs font-semibold {{ $isLight ? 'bg-blue-50 text-blue-700 border border-blue-200/80' : 'bg-yellow-400/10 text-yellow-400 border border-yellow-400/30' }}">
              <span>⚡</span>
              <span>{{ e($block['badge']) }}</span>
            </div>
          @endif

          {{-- Main Hero Headline --}}
          @if(!empty($block['heading']))
            <h1 class="text-xl sm:text-3xl font-extrabold tracking-tight leading-snug {{ $isLight ? 'text-slate-900' : 'text-white' }}">
              {{ e($block['heading']) }}
            </h1>
          @endif

          {{-- Subheading Text --}}
          @if(!empty($block['subheading']))
            <p class="text-xs sm:text-sm {{ $isLight ? 'text-slate-600' : 'text-slate-400' }} max-w-md mx-auto leading-relaxed">
              {{ e($block['subheading']) }}
            </p>
          @endif

          {{-- Primary CTA Button --}}
          <div class="pt-2">
            <a
              id="btn-join-telegram-hero"
              href="{{ $ctaUrl }}"
              data-kx-cta="1"
              data-kx-token="{{ $ctaToken }}"
              data-kx-fallback="{{ e($landingPage->telegram_destination) }}"
              class="cta-btn-glow w-full flex items-center justify-center gap-2.5 px-6 py-3.5 sm:py-4 rounded-xl font-extrabold text-sm sm:text-base text-white {{ $isLight ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700' }} transition"
            >
              <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
              <span>{{ e($block['button_text'] ?? 'Join Free Telegram Channel →') }}</span>
            </a>
            @if(!empty($block['button_subtitle']))
              <p class="text-[11px] {{ $isLight ? 'text-slate-500' : 'text-slate-500' }} mt-1.5 font-medium">
                {{ e($block['button_subtitle']) }}
              </p>
            @endif
          </div>
        </section>

      {{-- ================= 2. STATS BLOCK ================= --}}
      @elseif($type === 'stats')
        <section class="grid grid-cols-3 gap-2 sm:gap-3">
          @foreach(($block['stats'] ?? [['value' => '50K+', 'label' => 'Members'], ['value' => '5+ Years', 'label' => 'Experience'], ['value' => '95%', 'label' => 'Accuracy']]) as $stat)
            <div class="text-center p-3 sm:p-3.5 rounded-xl {{ $isLight ? 'bg-white border border-slate-200/80 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]' }}">
              <div class="text-sm sm:text-base font-black {{ $isLight ? 'text-blue-600' : 'text-yellow-400' }}">
                {{ e($stat['value'] ?? '') }}
              </div>
              <div class="text-[10px] sm:text-xs font-semibold {{ $isLight ? 'text-slate-600' : 'text-slate-400' }} mt-0.5">
                {{ e($stat['label'] ?? '') }}
              </div>
            </div>
          @endforeach
        </section>

      {{-- ================= 3. FEATURES / BENEFITS GRID ================= --}}
      @elseif($type === 'features_grid')
        <section class="space-y-2.5 pt-1">
          @if(!empty($block['title']))
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-center {{ $isLight ? 'text-slate-700' : 'text-slate-400' }}">
              {{ e($block['title']) }}
            </h2>
          @endif
          @if(!empty($block['subtitle']))
            <p class="text-[11px] text-center {{ $isLight ? 'text-slate-500' : 'text-slate-500' }} -mt-1 mb-2">
              {{ e($block['subtitle']) }}
            </p>
          @endif

          <div class="space-y-2">
            @foreach(($block['cards'] ?? []) as $card)
              <div class="flex items-center gap-3.5 p-3.5 rounded-xl {{ $isLight ? 'bg-white border border-slate-200/80 shadow-sm hover:border-blue-300' : 'bg-[#12141A] border border-[#2A2E3A] hover:border-yellow-400/30' }} transition">
                <div class="text-xl p-2 rounded-lg {{ $isLight ? 'bg-blue-50 text-blue-600' : 'bg-[#1A1D26] border border-[#2A2E3A]' }} shrink-0 flex items-center justify-center">
                  {{ $card['icon'] ?? '📊' }}
                </div>
                <div class="space-y-0.5 min-w-0 flex-1">
                  <h3 class="text-xs sm:text-sm font-bold {{ $isLight ? 'text-slate-900' : 'text-white' }} truncate">
                    {{ e($card['title'] ?? '') }}
                  </h3>
                  @if(!empty($card['desc']))
                    <p class="text-[11px] {{ $isLight ? 'text-slate-500' : 'text-slate-400' }} leading-relaxed">
                      {{ e($card['desc']) }}
                    </p>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </section>

      {{-- ================= 4. HEADING + PARAGRAPH / TEXT ================= --}}
      @elseif($type === 'heading_text' || $type === 'text')
        <section class="p-4 sm:p-5 rounded-xl {{ $isLight ? 'bg-white border border-slate-200/80 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]' }} space-y-2">
          @if(!empty($block['heading']))
            <h2 class="text-sm sm:text-base font-extrabold {{ $isLight ? 'text-slate-900' : 'text-yellow-400' }}">
              {{ e($block['heading']) }}
            </h2>
          @endif
          <div class="text-xs sm:text-sm {{ $isLight ? 'text-slate-600' : 'text-slate-300' }} leading-relaxed space-y-2">
            {!! nl2br(e($block['text'] ?? $block['content'] ?? '')) !!}
          </div>
        </section>

      {{-- ================= 5. IMAGE BLOCK ================= --}}
      @elseif($type === 'image')
        <section class="rounded-xl overflow-hidden {{ $isLight ? 'border border-slate-200 shadow-sm bg-white' : 'border border-[#2A2E3A] shadow-lg bg-[#12141A]' }}">
          <img src="{{ e($block['url'] ?? $block['image_url'] ?? '') }}" alt="{{ e($block['alt'] ?? 'Image') }}" class="w-full h-auto object-cover max-h-96">
          @if(!empty($block['caption']))
            <p class="text-xs {{ $isLight ? 'text-slate-500 bg-slate-50' : 'text-slate-400 bg-[#12141A]' }} text-center py-2 border-t {{ $isLight ? 'border-slate-100' : 'border-[#2A2E3A]' }}">
              {{ e($block['caption']) }}
            </p>
          @endif
        </section>

      {{-- ================= 6. STANDALONE CTA BUTTON ================= --}}
      @elseif($type === 'cta_button')
        <section class="text-center p-4 sm:p-5 rounded-xl {{ $isLight ? 'bg-white border border-slate-200/80 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]' }} space-y-2.5">
          @if(!empty($block['heading']))
            <h2 class="text-sm sm:text-base font-extrabold {{ $isLight ? 'text-slate-900' : 'text-white' }}">
              {{ e($block['heading']) }}
            </h2>
          @endif
          @if(!empty($block['subheading']))
            <p class="text-xs {{ $isLight ? 'text-slate-600' : 'text-slate-400' }} max-w-md mx-auto">
              {{ e($block['subheading']) }}
            </p>
          @endif
          <div class="pt-1">
            <a
              href="{{ $ctaUrl }}"
              data-kx-cta="1"
              data-kx-token="{{ $ctaToken }}"
              data-kx-fallback="{{ e($landingPage->telegram_destination) }}"
              class="cta-btn-glow w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-extrabold text-sm text-white {{ $isLight ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gradient-to-r from-yellow-400 to-amber-500 text-slate-950 font-black hover:brightness-105' }} transition"
            >
              <span>{{ e($block['button_text'] ?? 'Join Free Telegram Channel →') }}</span>
            </a>
            @if(!empty($block['button_subtitle']))
              <p class="text-[11px] {{ $isLight ? 'text-slate-500' : 'text-slate-500' }} mt-1.5">
                {{ e($block['button_subtitle']) }}
              </p>
            @endif
          </div>
        </section>

      {{-- ================= 7. TWO-COLUMN SECTION ================= --}}
      @elseif($type === 'two_column')
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 sm:p-5 rounded-xl {{ $isLight ? 'bg-white border border-slate-200/80 shadow-sm' : 'bg-[#12141A] border border-[#2A2E3A]' }}">
          <div class="space-y-1.5">
            @if(!empty($block['col1_heading']))
              <h3 class="text-xs sm:text-sm font-bold {{ $isLight ? 'text-blue-600' : 'text-yellow-400' }}">{{ e($block['col1_heading']) }}</h3>
            @endif
            <p class="text-xs {{ $isLight ? 'text-slate-600' : 'text-slate-300' }} leading-relaxed">{!! nl2br(e($block['col1_text'] ?? '')) !!}</p>
          </div>
          <div class="space-y-1.5">
            @if(!empty($block['col2_heading']))
              <h3 class="text-xs sm:text-sm font-bold {{ $isLight ? 'text-blue-600' : 'text-yellow-400' }}">{{ e($block['col2_heading']) }}</h3>
            @endif
            <p class="text-xs {{ $isLight ? 'text-slate-600' : 'text-slate-300' }} leading-relaxed">{!! nl2br(e($block['col2_text'] ?? '')) !!}</p>
          </div>
        </section>

      {{-- ================= 8. FAQ BLOCK ================= --}}
      @elseif($type === 'faq')
        <section class="space-y-2 pt-1">
          @if(!empty($block['title']))
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-center {{ $isLight ? 'text-slate-700' : 'text-slate-400' }}">
              {{ e($block['title']) }}
            </h2>
          @endif
          <div class="space-y-2">
            @foreach(($block['faqs'] ?? []) as $faq)
              <details class="group p-3.5 rounded-xl {{ $isLight ? 'bg-white border border-slate-200/80 shadow-sm open:border-blue-300' : 'bg-[#12141A] border border-[#2A2E3A] open:border-yellow-400/40' }} text-xs transition">
                <summary class="font-bold {{ $isLight ? 'text-slate-800' : 'text-slate-200' }} cursor-pointer list-none flex items-center justify-between">
                  <span>{{ e($faq['q'] ?? '') }}</span>
                  <span class="text-slate-400 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="mt-2 pt-2 border-t {{ $isLight ? 'border-slate-100 text-slate-600' : 'border-slate-800 text-slate-400' }} leading-relaxed">
                  {{ e($faq['a'] ?? '') }}
                </div>
              </details>
            @endforeach
          </div>
        </section>

      {{-- ================= 9. DISCLAIMER BLOCK ================= --}}
      @elseif($type === 'disclaimer')
        <section class="p-3.5 rounded-xl {{ $isLight ? 'bg-slate-100/80 border border-slate-200 text-slate-500' : 'bg-[#12141A]/60 border border-amber-500/20 text-slate-400' }} text-[10px] space-y-1">
          <strong class="{{ $isLight ? 'text-slate-700' : 'text-amber-400' }} uppercase tracking-wider block font-bold text-[9.5px]">
            {{ e($block['title'] ?? 'Important Risk Disclaimer') }}
          </strong>
          <p class="leading-relaxed">
            {!! nl2br(e($block['text'] ?? '')) !!}
          </p>
        </section>

      {{-- ================= 10. FOOTER BLOCK ================= --}}
      @elseif($type === 'footer')
        <footer class="pt-4 pb-2 text-center text-xs {{ $isLight ? 'text-slate-500 border-t border-slate-200' : 'text-slate-500 border-t border-slate-800/80' }} space-y-1.5">
          <p>{{ e($block['copyright'] ?? '© ' . date('Y') . ' ' . $brand . '. All rights reserved.') }}</p>
          @if(!empty($block['managed_by']) || $isLight)
            <div class="text-[11px] font-medium {{ $isLight ? 'text-slate-400' : 'text-slate-500' }}">
              {{ e($block['managed_by'] ?? '⚡ Ads Managed by Kirtnix Media') }}
            </div>
          @endif
          <div class="flex justify-center gap-3 text-xs font-medium {{ $isLight ? 'text-slate-500' : 'text-slate-400' }} pt-1">
            <a href="{{ $ctaUrl }}" class="{{ $isLight ? 'hover:text-blue-600' : 'hover:text-yellow-400' }} transition" data-kx-cta="1">Telegram</a>
            <span>•</span>
            <a href="#disclaimer" class="{{ $isLight ? 'hover:text-blue-600' : 'hover:text-yellow-400' }} transition" onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'}); return false;">Disclaimer</a>
          </div>
        </footer>
      @endif

    @endforeach

  </main>

  <!-- Software-Managed Kirtnix Tracker Client Script -->
  <script src="/api/public/kx.js?lp={{ e($landingPage->tracking_token ?? $landingPage->slug) }}" data-kx-lp="{{ e($landingPage->tracking_token ?? $landingPage->slug) }}"></script>
</body>
</html>
