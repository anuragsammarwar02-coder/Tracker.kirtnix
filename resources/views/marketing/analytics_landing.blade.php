<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KIRTNIX TG TRACKER | Turn your marketing data into decisions</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/branding/favicon.png') }}">

  <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #F8F9FA;
      --bg-card: #FFFFFF;
      --border: #E5E7EB;
      --border-subtle: #F3F4F6;
      --text: #0F172A;
      --muted: #64748B;
      --subtle: #94A3B8;
      --yellow: #EAB308;
      --yellow-hover: #CA8A04;
      --yellow-light: #FEF9C3;
      --green: #10B981;
      --blue: #0284C7;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      line-height: 1.5;
      font-size: 14px;
      overflow-x: hidden;
    }
    .container {
      max-width: 1180px;
      margin: 0 auto;
      padding: 0 24px;
    }

    /* Navbar */
    .nav-bar {
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(8px);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .nav-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }
    .brand-icon {
      width: 32px;
      height: 32px;
      background: #0F172A;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--yellow);
      font-weight: 800;
      font-size: 14px;
      border: 1px solid rgba(234, 179, 8, 0.3);
    }
    .brand-text {
      font-size: 15px;
      font-weight: 800;
      color: var(--text);
      letter-spacing: -0.2px;
    }
    .brand-text span { color: var(--yellow); }
    .nav-links {
      display: flex;
      align-items: center;
      gap: 24px;
    }
    .nav-links a {
      color: var(--muted);
      text-decoration: none;
      font-weight: 600;
      font-size: 13px;
      transition: color 0.15s;
    }
    .nav-links a:hover { color: var(--text); }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s;
      border: 1px solid transparent;
    }
    .btn-primary {
      background: var(--yellow);
      color: #000000 !important;
      border-color: var(--yellow-hover);
    }
    .btn-primary:hover { background: var(--yellow-hover); }
    .btn-secondary {
      background: #FFFFFF;
      border-color: var(--border);
      color: var(--text);
    }
    .btn-secondary:hover { background: var(--bg); }

    /* Hero Section */
    .hero-section {
      padding: 72px 0 48px;
      text-align: center;
    }
    .hero-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      background: var(--yellow-light);
      border: 1px solid #FDE047;
      border-radius: 20px;
      font-size: 11.5px;
      font-weight: 700;
      color: #854D0E;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 20px;
    }
    .hero-title {
      font-size: 48px;
      font-weight: 900;
      letter-spacing: -1.2px;
      line-height: 1.15;
      color: var(--text);
      max-width: 780px;
      margin: 0 auto 16px;
    }
    .hero-subtitle {
      font-size: 17px;
      color: var(--muted);
      max-width: 620px;
      margin: 0 auto 32px;
      line-height: 1.6;
    }
    .hero-cta-group {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 48px;
    }

    /* Dashboard Mockup Container */
    .mockup-wrapper {
      background: #FFFFFF;
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 16px;
      box-shadow: 0 20px 48px rgba(0, 0, 0, 0.08);
      max-width: 1040px;
      margin: 0 auto 64px;
      text-align: left;
    }
    .mockup-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--border-subtle);
      margin-bottom: 16px;
    }
    .mockup-dots {
      display: flex;
      gap: 6px;
    }
    .mockup-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: var(--border);
    }

    /* Feature Grid */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
      margin: 48px 0;
    }
    .feature-card {
      background: #FFFFFF;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 24px;
      transition: transform 0.15s, box-shadow 0.15s;
    }
    .feature-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
    }
    .feature-icon {
      font-size: 24px;
      margin-bottom: 12px;
      display: inline-block;
    }
    .feature-title {
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 6px;
      color: var(--text);
    }
    .feature-desc {
      font-size: 13px;
      color: var(--muted);
      line-height: 1.5;
    }

    /* Section Headers */
    .section-title {
      font-size: 30px;
      font-weight: 800;
      letter-spacing: -0.6px;
      text-align: center;
      margin-bottom: 10px;
    }
    .section-subtitle {
      font-size: 15px;
      color: var(--muted);
      text-align: center;
      max-width: 580px;
      margin: 0 auto 36px;
    }

    /* Problem vs Solution */
    .comparison-box {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin: 48px 0;
    }
    .box-bad {
      background: #FEF2F2;
      border: 1px solid #FECACA;
      border-radius: 12px;
      padding: 28px;
    }
    .box-good {
      background: #FEF9C3;
      border: 1px solid #FDE047;
      border-radius: 12px;
      padding: 28px;
    }

    /* Funnel Step */
    .funnel-steps {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #FFFFFF;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      margin: 36px 0;
      flex-wrap: wrap;
      gap: 12px;
    }
    .funnel-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }
    .funnel-arrow {
      color: var(--subtle);
      font-weight: 800;
      font-size: 16px;
    }

    /* Footer */
    .footer {
      background: #FFFFFF;
      border-top: 1px solid var(--border);
      padding: 48px 0 32px;
      margin-top: 72px;
      font-size: 13px;
    }
  </style>
</head>
<body>

  <!-- Navigation -->
  <nav class="nav-bar">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      <a href="/" class="nav-brand">
        <div class="brand-icon">KX</div>
        <div class="brand-text">KIRTNi<span>X</span>ITY</div>
      </a>

      <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#funnel">Conversion Funnel</a>
        <a href="#reporting">Client Reporting</a>
        <a href="#ai">AI Insights</a>
        <a href="#integrations">Integrations</a>
      </div>

      <div style="display: flex; gap: 10px;">
        <a href="{{ route('login') }}" class="btn btn-secondary">Login</a>
        <a href="{{ route('dashboard') }}" class="btn btn-primary">Open SaaS Dashboard</a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container">
      <div class="hero-pill">⚡ Performance Marketing & Telegram Tracking SaaS</div>
      <h1 class="hero-title">Turn your marketing data into decisions.</h1>
      <p class="hero-subtitle">
        Track Meta Ads, landing pages, Telegram conversions and client performance from one intelligent analytics platform.
      </p>

      <div class="hero-cta-group">
        <a href="{{ route('dashboard') }}" class="btn btn-primary" style="padding: 12px 24px; font-size: 14px;">Start tracking ↗</a>
        <a href="{{ route('login') }}" class="btn btn-secondary" style="padding: 12px 24px; font-size: 14px;">View demo</a>
      </div>

      <!-- Hero Visual Mockup -->
      <div class="mockup-wrapper">
        <div class="mockup-header">
          <div class="mockup-dots">
            <div class="mockup-dot" style="background: #EF4444;"></div>
            <div class="mockup-dot" style="background: #EAB308;"></div>
            <div class="mockup-dot" style="background: #10B981;"></div>
          </div>
          <div style="font-size: 11px; color: var(--muted); font-family: 'JetBrains Mono', monospace;">tracker.kirtnix.agency/analytics</div>
          <div style="font-size: 10.5px; font-weight: 700; color: var(--green);">● Live Data</div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px;">
          <div style="background: var(--bg); padding: 12px; border-radius: 8px; border: 1px solid var(--border-subtle);">
            <div style="font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Meta Spend</div>
            <div style="font-size: 18px; font-weight: 800; margin-top: 2px;">$5,410.50</div>
          </div>
          <div style="background: var(--bg); padding: 12px; border-radius: 8px; border: 1px solid var(--border-subtle);">
            <div style="font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase;">LP Views</div>
            <div style="font-size: 18px; font-weight: 800; margin-top: 2px;">8,420</div>
          </div>
          <div style="background: var(--bg); padding: 12px; border-radius: 8px; border: 1px solid var(--border-subtle); border-left: 3px solid var(--yellow);">
            <div style="font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Telegram Joins</div>
            <div style="font-size: 18px; font-weight: 800; color: #854D0E; margin-top: 2px;">2,480</div>
          </div>
          <div style="background: var(--bg); padding: 12px; border-radius: 8px; border: 1px solid var(--border-subtle);">
            <div style="font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase;">Cost / Join</div>
            <div style="font-size: 18px; font-weight: 800; color: var(--green); margin-top: 2px;">$1.18</div>
          </div>
        </div>

        <div style="background: var(--bg); border: 1px solid var(--border-subtle); border-radius: 8px; padding: 16px; font-size: 12px;">
          <div style="font-weight: 700; margin-bottom: 4px;">⚡ KirtniX AI Insight:</div>
          <div style="color: var(--muted); line-height: 1.5;">Campaign <strong>GJ001 (Nandu Meena - STOXK)</strong> generated 1,480 verified Telegram members at an ultra-low Cost Per Join of <strong>$0.96</strong> with 100% Meta CAPI event delivery.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- 1. Trusted / Platform Integrations -->
  <section style="padding: 24px 0 48px; text-align: center; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); background: #FFFFFF;">
    <div class="container">
      <div style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 16px;">
        Trusted Integrations Across the Entire Conversion Stack
      </div>
      <div style="display: flex; justify-content: center; gap: 32px; flex-wrap: wrap; font-size: 14px; font-weight: 700; color: var(--text);">
        <div>Meta Ads Manager</div>
        <div>Telegram Bot API</div>
        <div>Dynamic Landing Pages</div>
        <div>Meta Conversions API (CAPI)</div>
        <div>Real-Time Webhooks</div>
        <div>Google Tag Manager</div>
      </div>
    </div>
  </section>

  <!-- 2. Problem Section -->
  <section style="padding: 64px 0;">
    <div class="container">
      <h2 class="section-title">Your data is everywhere.</h2>
      <p class="section-subtitle">
        Performance agencies lose hours piecing together spreadsheets, ad managers, and Telegram member lists.
      </p>

      <div class="comparison-box">
        <div class="box-bad">
          <h3 style="font-size: 16px; font-weight: 800; color: #991B1B; margin-bottom: 10px;">❌ The Disconnected Way</h3>
          <ul style="padding-left: 18px; color: #7F1D1D; font-size: 13px; line-height: 1.8;">
            <li>Meta Ads reporting clicks without verified channel joins</li>
            <li>Zero attribution on which ad creative drove which Telegram member</li>
            <li>Manual spreadsheet exports for every single client</li>
            <li>Delayed lead reporting and untracked member backouts</li>
          </ul>
        </div>

        <div class="box-good">
          <h3 style="font-size: 16px; font-weight: 800; color: #854D0E; margin-bottom: 10px;">✨ The Kirtnix Way</h3>
          <ul style="padding-left: 18px; color: #713F12; font-size: 13px; line-height: 1.8;">
            <li>Direct Telegram app redirect without slow preview pages</li>
            <li>Real-time Bot webhook verification for joins & leaves</li>
            <li>Automated Meta Conversions API dispatching for every join</li>
            <li>Client-wise performance dashboards and AI audit reports</li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- 3. Analytics Features (8 Cards) -->
  <section id="features" style="padding: 48px 0; background: #FFFFFF; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);">
    <div class="container">
      <h2 class="section-title">Complete Performance Tracking Suite</h2>
      <p class="section-subtitle">Built specifically for media buyers, agencies, and performance marketers.</p>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">📊</div>
          <div class="feature-title">Real-Time Campaign Analytics</div>
          <div class="feature-desc">Track live spend, CTR, Telegram joins, and cost per join across Meta ad sets.</div>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🎯</div>
          <div class="feature-title">Conversion Funnel Breakdown</div>
          <div class="feature-desc">Monitor drop-offs at every stage from ad impression to verified channel join.</div>
        </div>

        <div class="feature-card">
          <div class="feature-icon">👤</div>
          <div class="feature-title">Client Performance Profiles</div>
          <div class="feature-desc">Individual KX codes for every client with dedicated spend and Telegram views.</div>
        </div>

        <div class="feature-card">
          <div class="feature-icon">🤖</div>
          <div class="feature-title">Telegram Member Attribution</div>
          <div class="feature-desc">Verify actual channel join events and backouts via automated bot webhooks.</div>
        </div>

        <div class="feature-card">
          <div class="feature-icon">📄</div>
          <div class="feature-title">Dynamic Landing Page Builder</div>
          <div class="feature-desc">Deploy high-converting templates with integrated Meta Pixel & CAPI support.</div>
        </div>

        <div class="feature-card">
          <div class="feature-icon">💰</div>
          <div class="feature-title">Meta Ads Spend Tracking</div>
          <div class="feature-desc">Live synchronization of daily budgets, ad reach, frequency, and CPM.</div>
        </div>

        <div class="feature-card">
          <div class="feature-icon">⚡</div>
          <div class="feature-title">KirtniX AI Insights</div>
          <div class="feature-desc">Intelligent assistant that analyzes funnel bottlenecks in English and Hindi.</div>
        </div>

        <div class="feature-card">
          <div class="feature-icon">📈</div>
          <div class="feature-title">Automated Client Reports</div>
          <div class="feature-desc">Generate executive audit reports with AI summaries and one-click CSV/PDF export.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- 5. Conversion Funnel Section -->
  <section id="funnel" style="padding: 64px 0;">
    <div class="container">
      <h2 class="section-title">The Verified Conversion Funnel</h2>
      <p class="section-subtitle">How Kirtnix connects ad clicks to verified Telegram community members.</p>

      <div class="funnel-steps">
        <div class="funnel-step">
          <div style="font-size: 20px;">📢</div>
          <strong style="font-size: 13px; margin-top: 4px;">1. Meta Ad</strong>
          <span style="font-size: 11px; color: var(--muted);">Reels / Feed</span>
        </div>
        <div class="funnel-arrow">→</div>
        <div class="funnel-step">
          <div style="font-size: 20px;">📄</div>
          <strong style="font-size: 13px; margin-top: 4px;">2. Landing Page</strong>
          <span style="font-size: 11px; color: var(--muted);">/lp/{slug}</span>
        </div>
        <div class="funnel-arrow">→</div>
        <div class="funnel-step">
          <div style="font-size: 20px;">⚡</div>
          <strong style="font-size: 13px; margin-top: 4px;">3. Direct Launch</strong>
          <span style="font-size: 11px; color: var(--muted);">tg:// Protocol</span>
        </div>
        <div class="funnel-arrow">→</div>
        <div class="funnel-step">
          <div style="font-size: 20px;">🤖</div>
          <strong style="font-size: 13px; margin-top: 4px;">4. Verified Join</strong>
          <span style="font-size: 11px; color: var(--muted);">Bot Webhook</span>
        </div>
        <div class="funnel-arrow">→</div>
        <div class="funnel-step">
          <div style="font-size: 20px;">✅</div>
          <strong style="font-size: 13px; margin-top: 4px;">5. Meta CAPI</strong>
          <span style="font-size: 11px; color: var(--green);">Lead Event</span>
        </div>
      </div>
    </div>
  </section>

  <!-- 9. Final CTA -->
  <section style="padding: 72px 0; background: #0F172A; color: #FFFFFF; text-align: center;">
    <div class="container">
      <h2 style="font-size: 36px; font-weight: 900; letter-spacing: -0.8px; margin-bottom: 12px;">
        Know what is working. Know what needs fixing.
      </h2>
      <p style="font-size: 16px; color: #94A3B8; max-width: 540px; margin: 0 auto 28px;">
        Start tracking Telegram marketing campaigns with real member attribution and Meta Conversions API synchronization.
      </p>
      <a href="{{ route('dashboard') }}" class="btn btn-primary" style="padding: 14px 28px; font-size: 15px;">
        Start Tracking Now ↗
      </a>
    </div>
  </section>

  <!-- 10. Footer -->
  <footer class="footer">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
      <div>
        <strong>KIRTNIX TG TRACKER</strong> · Self-Hostable Performance Marketing Platform
      </div>
      <div style="display: flex; gap: 20px; color: var(--muted);">
        <a href="{{ route('public.analytics') }}" style="color: var(--muted); text-decoration: none;">Analytics</a>
        <a href="{{ route('clients.index') }}" style="color: var(--muted); text-decoration: none;">Clients</a>
        <a href="{{ route('reports.index') }}" style="color: var(--muted); text-decoration: none;">Reports</a>
        <a href="{{ route('support.index') }}" style="color: var(--muted); text-decoration: none;">Support</a>
      </div>
    </div>
  </footer>

</body>
</html>
