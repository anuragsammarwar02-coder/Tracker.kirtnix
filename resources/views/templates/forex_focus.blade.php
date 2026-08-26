<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#0a0b0d" />
  <meta name="description" content="{{ $landingPage->hero_subheading ?? $landingPage->title }}" />
  <title>{{ $landingPage->title }}</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/branding/favicon.png') }}">

  @if(!empty($landingPage->gtm_id))
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','{{ $landingPage->gtm_id }}');</script>
  <!-- End Google Tag Manager -->
  @endif

  @if(!empty($landingPage->meta_pixel_id))
  <!-- Meta Pixel Code -->
  <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $landingPage->meta_pixel_id }}');
    fbq('track', 'PageView', {}, {eventID: '{{ $metaEventId ?? "" }}'});
  </script>
  <noscript>
    <img height="1" width="1" style="display:none"
      src="https://www.facebook.com/tr?id={{ $landingPage->meta_pixel_id }}&ev=PageView&noscript=1" alt="" />
  </noscript>
  <!-- End Meta Pixel Code -->
  @endif

  <style>
    :root {
      --bg: #0a0b0d;
      --surface: #12141a;
      --surface2: #1a1d26;
      --border: #2a2e3a;
      --text: #f0f1f4;
      --muted: #8b90a0;
      --gold: #f0c14b;
      --gold-dim: rgba(240, 193, 75, 0.12);
      --blue: #2d8cff;
      --blue-dark: #1a6fd4;
      --green: #3ecf8e;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      min-height: 100vh;
      background: var(--bg);
      color: var(--text);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
    }

    .wrap {
      max-width: 640px;
      margin: 0 auto;
      padding: 40px 18px 48px;
    }

    .header {
      text-align: center;
      margin-bottom: 32px;
    }
    .logo {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      object-fit: cover;
      margin: 0 auto 14px;
      display: block;
      border: 2px solid rgba(240, 193, 75, 0.35);
      box-shadow: 0 0 40px rgba(240, 193, 75, 0.15);
    }
    .brand {
      font-size: 22px;
      font-weight: 800;
      letter-spacing: 0.6px;
      color: var(--gold);
      text-transform: uppercase;
    }
    .tagline {
      margin-top: 6px;
      font-size: 13px;
      color: var(--muted);
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      margin-top: 14px;
      padding: 6px 14px;
      border-radius: 999px;
      border: 1px solid rgba(62, 207, 142, 0.35);
      background: rgba(62, 207, 142, 0.08);
      color: var(--green);
      font-size: 12px;
      font-weight: 600;
    }
    .badge-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--green);
      box-shadow: 0 0 8px rgba(62, 207, 142, 0.7);
    }

    .hero {
      text-align: center;
      margin-bottom: 28px;
    }
    .hero h1 {
      font-size: 26px;
      font-weight: 800;
      line-height: 1.25;
      letter-spacing: -0.3px;
      margin-bottom: 12px;
    }
    .hero h1 span { color: var(--gold); }
    .hero p {
      font-size: 14px;
      color: var(--muted);
      max-width: 480px;
      margin: 0 auto;
      line-height: 1.65;
    }

    .cta-box {
      margin: 28px 0 32px;
      text-align: center;
    }
    .cta-primary {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      min-height: 54px;
      padding: 14px 24px;
      border: none;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
      color: #fff !important;
      font-size: 16px;
      font-weight: 700;
      text-decoration: none !important;
      cursor: pointer;
      box-shadow: 0 10px 28px rgba(45, 140, 255, 0.28);
      transition: transform 0.15s ease, filter 0.15s ease;
    }
    .cta-primary:hover {
      transform: translateY(-2px);
      filter: brightness(1.06);
    }
    .cta-primary:active { transform: translateY(0); }
    .cta-icon {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: #fff;
      color: var(--blue-dark);
      font-size: 12px;
      font-weight: 900;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .cta-hint {
      margin-top: 10px;
      font-size: 12px;
      color: var(--muted);
    }

    .section-title {
      font-size: 13px;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 12px;
      text-align: center;
    }
    .features {
      display: grid;
      gap: 10px;
      margin-bottom: 28px;
    }
    .feature {
      display: flex;
      gap: 14px;
      align-items: flex-start;
      padding: 16px 16px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      transition: border-color 0.15s ease;
    }
    .feature:hover { border-color: rgba(240, 193, 75, 0.35); }
    .feature-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: var(--surface2);
      border: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }
    .feature h3 {
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 4px;
      color: var(--text);
    }
    .feature p {
      font-size: 13px;
      color: var(--muted);
      line-height: 1.5;
    }

    .about, .disclaimer {
      padding: 18px;
      border-radius: 12px;
      background: var(--surface);
      border: 1px solid var(--border);
      margin-bottom: 24px;
    }
    .about h2 {
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--gold);
    }
    .about p {
      font-size: 13px;
      color: var(--muted);
      line-height: 1.6;
      margin-bottom: 8px;
    }
    .about p:last-child { margin-bottom: 0; }

    .disclaimer strong {
      display: block;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: #e5a038;
      margin-bottom: 6px;
    }
    .disclaimer p {
      font-size: 11.5px;
      color: #7a7f92;
      line-height: 1.55;
    }

    .cta-secondary {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      min-height: 48px;
      padding: 12px 20px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: var(--surface2);
      color: var(--text) !important;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none !important;
      margin-bottom: 32px;
      transition: all 0.15s ease;
    }
    .cta-secondary:hover {
      border-color: var(--gold);
      background: var(--surface);
    }

    footer {
      text-align: center;
      padding-top: 16px;
      border-top: 1px solid var(--border);
      font-size: 12px;
      color: var(--muted);
    }
    .footer-links {
      display: flex;
      justify-content: center;
      gap: 16px;
      margin: 10px 0 16px;
    }
    .footer-links a {
      color: var(--muted);
      text-decoration: none;
      font-size: 12px;
    }
    .footer-links a:hover { color: var(--gold); }
  </style>

  @if(!empty($landingPage->custom_css))
  <style>
    {!! $landingPage->custom_css !!}
  </style>
  @endif

  @if(!empty($landingPage->custom_head_code))
    {!! $landingPage->custom_head_code !!}
  @endif
</head>
<body>
  @if(!empty($landingPage->gtm_id))
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $landingPage->gtm_id }}"
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->
  @endif

  <div class="wrap">
    <header class="header">
      @if(!empty($landingPage->brand_logo_url))
        <img class="logo" src="{{ $landingPage->brand_logo_url }}" alt="{{ $landingPage->brand_name }} Logo" />
      @endif

      <div class="brand">{{ $landingPage->brand_name }}</div>
      @if(!empty($landingPage->brand_tagline))
        <div class="tagline">{{ $landingPage->brand_tagline }}</div>
      @endif

      @if(!empty($landingPage->badge_text))
        <div class="badge">
          <span class="badge-dot"></span>
          <span>{{ $landingPage->badge_text }}</span>
        </div>
      @endif
    </header>

    <div class="hero">
      <h1>{!! nl2br(e($landingPage->hero_heading)) !!}</h1>
      @if(!empty($landingPage->hero_subheading))
        <p>{{ $landingPage->hero_subheading }}</p>
      @endif
    </div>

    <!-- Primary Hero CTA -->
    <div class="cta-box">
      <a
        id="btn-join-telegram"
        class="cta-primary"
        href="{{ $primaryCta ? route('public.cta_redirect', $primaryCta->tracking_token) : '#' }}"
        data-kx-cta="1"
        data-kx-fallback="{{ $landingPage->telegram_destination }}"
        data-kx-token="{{ $primaryCta?->tracking_token }}"
      >
        <span class="cta-icon">➤</span>
        <span>{{ $landingPage->primary_cta_text }}</span>
      </a>
      <p class="cta-hint">Free access · Telegram app or web</p>
    </div>

    @if(!empty($landingPage->features_json) && is_array($landingPage->features_json))
    <div class="section-title">What we cover</div>
    <div class="features">
      @foreach($landingPage->features_json as $feature)
      <article class="feature">
        <div class="feature-icon">{{ $feature['icon'] ?? '📊' }}</div>
        <div>
          <h3>{{ $feature['title'] ?? '' }}</h3>
          <p>{{ $feature['desc'] ?? '' }}</p>
        </div>
      </article>
      @endforeach
    </div>
    @endif

    @if(!empty($landingPage->about_text))
    <div class="about">
      <h2>{{ $landingPage->about_heading ?? 'About this community' }}</h2>
      <p>{!! nl2br(e($landingPage->about_text)) !!}</p>
    </div>
    @endif

    @if(!empty($landingPage->disclaimer_text))
    <div class="disclaimer">
      <strong>Important disclaimer</strong>
      <p>{!! nl2br(e($landingPage->disclaimer_text)) !!}</p>
    </div>
    @endif

    <!-- Secondary CTA -->
    @if(!empty($landingPage->secondary_cta_text))
    <a
      id="btn-join-telegram-alt"
      class="cta-secondary"
      href="#btn-join-telegram"
      onclick="document.getElementById('btn-join-telegram').scrollIntoView({behavior:'smooth'});return false;"
    >
      {{ $landingPage->secondary_cta_text }}
    </a>
    @endif

    <footer>
      <p>{{ $landingPage->footer_text ?? '© 2026 ' . $landingPage->brand_name }}</p>
      <div class="footer-links">
        <a href="#btn-join-telegram" onclick="document.getElementById('btn-join-telegram').scrollIntoView({behavior:'smooth'});return false;">Telegram</a>
        <a href="#disclaimer" onclick="document.querySelector('.disclaimer').scrollIntoView({behavior:'smooth'});return false;">Disclaimer</a>
      </div>
    </footer>
  </div>

  <!-- KirtniX Tracker Client Script -->
  <script src="/api/public/kx.js?lp={{ $landingPage->tracking_token }}" data-kx-lp="{{ $landingPage->tracking_token }}"></script>
</body>
</html>
