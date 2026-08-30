<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#0B0F19" />
  <meta name="description" content="{{ e($landingPage->hero_subheading ?? $landingPage->title) }}" />
  <title>{{ e($landingPage->title) }}</title>
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/branding/favicon.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['Outfit', 'sans-serif'],
            mono: ['JetBrains Mono', 'monospace'],
          },
          colors: {
            brand: {
              gold: '#F0C14B',
              yellow: '#EAB308',
              blue: '#2D8CFF',
              dark: '#0A0B0D',
              surface: '#12141A',
              card: '#1A1D26',
              border: '#2A2E3A',
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
    body {
      font-family: 'Outfit', sans-serif;
      background-color: #0a0b0d;
      color: #f0f1f4;
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
    }
    .cta-btn-glow {
      box-shadow: 0 8px 30px rgba(45, 140, 255, 0.35);
      transition: transform 0.15s ease, filter 0.15s ease, box-shadow 0.15s ease;
    }
    .cta-btn-glow:hover {
      transform: translateY(-2px);
      filter: brightness(1.08);
      box-shadow: 0 12px 35px rgba(45, 140, 255, 0.5);
    }
    .gold-accent {
      color: #f0c14b;
    }
  </style>

  @if(!empty($landingPage->custom_css))
  <style>
    {!! strip_tags($landingPage->custom_css) !!}
  </style>
  @endif
</head>
<body class="min-h-screen flex flex-col justify-between">

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
  @endphp

  <div class="w-full max-w-[680px] mx-auto px-4 sm:px-6 py-6 sm:py-10 space-y-6">

    @foreach($blocks as $block)
      @php
        $type = $block['type'] ?? 'text';
      @endphp

      {{-- 1. HERO BLOCK --}}
      @if($type === 'hero')
        <section class="text-center space-y-4 pt-4 pb-2">
          @if(!empty($landingPage->brand_logo_url) || !empty($block['logo_url']))
            <div class="flex justify-center mb-3">
              <img src="{{ e($block['logo_url'] ?? $landingPage->brand_logo_url) }}" alt="{{ e($brand) }}" class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-2 border-yellow-400/40 shadow-xl shadow-yellow-500/10">
            </div>
          @endif

          <div class="text-xs sm:text-sm font-extrabold uppercase tracking-wider text-yellow-400/90">
            {{ e($brand) }}
          </div>

          @if(!empty($block['badge']))
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400">
              <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
              <span>{{ e($block['badge']) }}</span>
            </div>
          @endif

          <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
            {{ e($block['heading'] ?? 'Join ' . $brand . ' On Telegram') }}
          </h1>

          @if(!empty($block['subheading']))
            <p class="text-sm sm:text-base text-slate-400 max-w-md mx-auto leading-relaxed">
              {{ e($block['subheading']) }}
            </p>
          @endif

          @if(!empty($block['image_url']))
            <div class="my-4 rounded-2xl overflow-hidden border border-slate-800 shadow-xl">
              <img src="{{ e($block['image_url']) }}" alt="Preview" class="w-full h-auto object-cover">
            </div>
          @endif

          <!-- Hero Primary CTA -->
          <div class="pt-3">
            <a
              id="btn-join-telegram"
              href="{{ $ctaUrl }}"
              data-kx-cta="1"
              data-kx-token="{{ $ctaToken }}"
              data-kx-fallback="{{ e($landingPage->telegram_destination) }}"
              class="cta-btn-glow w-full flex items-center justify-center gap-3 px-6 py-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-600 text-white font-bold text-base sm:text-lg shadow-lg hover:from-blue-600 hover:to-blue-700 transition"
            >
              <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
              <span>{{ e($block['button_text'] ?? 'Join Free Telegram Channel') }}</span>
            </a>
            @if(!empty($block['button_subtitle']))
              <p class="text-xs text-slate-500 mt-2 font-medium">
                {{ e($block['button_subtitle']) }}
              </p>
            @endif
          </div>
        </section>

      {{-- 2. FEATURES / CARDS GRID --}}
      @elseif($type === 'features_grid')
        <section class="space-y-3 pt-2">
          @if(!empty($block['title']))
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-center">
              {{ e($block['title']) }}
            </h2>
          @endif
          @if(!empty($block['subtitle']))
            <p class="text-xs text-slate-500 text-center -mt-1 mb-3">
              {{ e($block['subtitle']) }}
            </p>
          @endif

          <div class="grid grid-cols-1 gap-3">
            @foreach(($block['cards'] ?? []) as $card)
              <div class="flex items-start gap-4 p-4 rounded-2xl bg-[#12141A] border border-[#2A2E3A] hover:border-yellow-400/30 transition shadow-sm">
                <div class="text-2xl p-2.5 rounded-xl bg-[#1A1D26] border border-[#2A2E3A] flex items-center justify-center shrink-0">
                  {{ $card['icon'] ?? '✨' }}
                </div>
                <div class="space-y-1">
                  <h3 class="text-sm font-bold text-white">{{ e($card['title'] ?? '') }}</h3>
                  <p class="text-xs text-slate-400 leading-relaxed">{{ e($card['desc'] ?? '') }}</p>
                </div>
              </div>
            @endforeach
          </div>
        </section>

      {{-- 3. HEADING + PARAGRAPH / TEXT BLOCK --}}
      @elseif($type === 'heading_text' || $type === 'text')
        <section class="p-5 rounded-2xl bg-[#12141A] border border-[#2A2E3A] space-y-2">
          @if(!empty($block['heading']))
            <h2 class="text-base font-extrabold text-yellow-400">
              {{ e($block['heading']) }}
            </h2>
          @endif
          <div class="text-xs sm:text-sm text-slate-300 leading-relaxed space-y-2">
            {!! nl2br(e($block['text'] ?? $block['content'] ?? '')) !!}
          </div>
        </section>

      {{-- 4. IMAGE BLOCK --}}
      @elseif($type === 'image')
        <section class="rounded-2xl overflow-hidden border border-[#2A2E3A] shadow-lg">
          <img src="{{ e($block['url'] ?? $block['image_url'] ?? '') }}" alt="{{ e($block['alt'] ?? 'Image') }}" class="w-full h-auto object-cover">
          @if(!empty($block['caption']))
            <p class="text-xs text-slate-500 text-center py-2 bg-[#12141A]">{{ e($block['caption']) }}</p>
          @endif
        </section>

      {{-- 5. STANDALONE CTA BUTTON --}}
      @elseif($type === 'cta_button')
        <section class="text-center p-6 rounded-2xl bg-[#12141A] border border-[#2A2E3A] space-y-3">
          @if(!empty($block['heading']))
            <h2 class="text-lg font-extrabold text-white">
              {{ e($block['heading']) }}
            </h2>
          @endif
          @if(!empty($block['subheading']))
            <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto">
              {{ e($block['subheading']) }}
            </p>
          @endif
          <div class="pt-1">
            <a
              href="{{ $ctaUrl }}"
              data-kx-cta="1"
              data-kx-token="{{ $ctaToken }}"
              data-kx-fallback="{{ e($landingPage->telegram_destination) }}"
              class="cta-btn-glow w-full inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl bg-gradient-to-r from-yellow-400 to-amber-500 text-slate-950 font-bold text-sm sm:text-base shadow-md hover:brightness-105 transition"
            >
              <span>{{ e($block['button_text'] ?? 'Open Telegram Channel') }}</span>
              <span>↗</span>
            </a>
            @if(!empty($block['button_subtitle']))
              <p class="text-[11px] text-slate-500 mt-2">
                {{ e($block['button_subtitle']) }}
              </p>
            @endif
          </div>
        </section>

      {{-- 6. TWO-COLUMN SECTION --}}
      @elseif($type === 'two_column')
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 rounded-2xl bg-[#12141A] border border-[#2A2E3A]">
          <div class="space-y-2">
            @if(!empty($block['col1_heading']))
              <h3 class="text-sm font-bold text-yellow-400">{{ e($block['col1_heading']) }}</h3>
            @endif
            <p class="text-xs text-slate-300 leading-relaxed">{!! nl2br(e($block['col1_text'] ?? '')) !!}</p>
          </div>
          <div class="space-y-2">
            @if(!empty($block['col2_heading']))
              <h3 class="text-sm font-bold text-yellow-400">{{ e($block['col2_heading']) }}</h3>
            @endif
            <p class="text-xs text-slate-300 leading-relaxed">{!! nl2br(e($block['col2_text'] ?? '')) !!}</p>
          </div>
        </section>

      {{-- 7. FAQ BLOCK --}}
      @elseif($type === 'faq')
        <section class="space-y-3 pt-2">
          @if(!empty($block['title']))
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest text-center">
              {{ e($block['title']) }}
            </h2>
          @endif
          <div class="space-y-2">
            @foreach(($block['faqs'] ?? []) as $faq)
              <details class="group p-4 rounded-xl bg-[#12141A] border border-[#2A2E3A] text-xs transition open:border-yellow-400/40">
                <summary class="font-bold text-slate-200 cursor-pointer list-none flex items-center justify-between">
                  <span>{{ e($faq['q'] ?? '') }}</span>
                  <span class="text-slate-500 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="mt-2.5 pt-2 border-t border-slate-800 text-slate-400 leading-relaxed">
                  {{ e($faq['a'] ?? '') }}
                </div>
              </details>
            @endforeach
          </div>
        </section>

      {{-- 8. DISCLAIMER BLOCK --}}
      @elseif($type === 'disclaimer')
        <section class="p-4 rounded-xl bg-[#12141A]/60 border border-amber-500/20 text-[11px] text-slate-400 space-y-1.5">
          <strong class="text-amber-400 uppercase tracking-wider block font-bold text-[10px]">
            {{ e($block['title'] ?? 'Important Disclaimer') }}
          </strong>
          <p class="leading-relaxed text-slate-400/90">
            {!! nl2br(e($block['text'] ?? '')) !!}
          </p>
        </section>

      {{-- 9. FOOTER BLOCK --}}
      @elseif($type === 'footer')
        <footer class="pt-6 pb-2 text-center text-xs text-slate-500 border-t border-slate-800/80 space-y-2">
          <p>{{ e($block['copyright'] ?? '© ' . date('Y') . ' ' . $brand . '. All rights reserved.') }}</p>
          <div class="flex justify-center gap-4 text-xs font-medium text-slate-400">
            <a href="{{ $ctaUrl }}" class="hover:text-yellow-400 transition" data-kx-cta="1">Telegram</a>
            <span>•</span>
            <a href="#disclaimer" class="hover:text-yellow-400 transition" onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'}); return false;">Disclaimer</a>
          </div>
        </footer>
      @endif

    @endforeach

  </div>

  <!-- Software-Managed Kirtnix Tracker Client Script -->
  <script src="/api/public/kx.js?lp={{ e($landingPage->tracking_token ?? $landingPage->slug) }}" data-kx-lp="{{ e($landingPage->tracking_token ?? $landingPage->slug) }}"></script>
</body>
</html>
