<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $pageTitle }} | Opening Telegram...</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/branding/favicon.png') }}">
  <style>
    :root {
      --bg: #0b0e14;
      --card: #161922;
      --text: #f8fafc;
      --muted: #94a3b8;
      --yellow: #f5c518;
      --telegram: #229ed9;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .redirect-card {
      background: var(--card);
      border: 1px solid rgba(245, 197, 24, 0.15);
      border-radius: 20px;
      padding: 36px 28px;
      max-width: 440px;
      width: 100%;
      text-align: center;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
      animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.96); }
      to { opacity: 1; transform: scale(1); }
    }
    .logo-badge {
      width: 68px;
      height: 68px;
      background: rgba(34, 158, 217, 0.12);
      border: 1px solid rgba(34, 158, 217, 0.3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
    }
    .logo-badge svg {
      width: 36px;
      height: 36px;
      fill: var(--telegram);
    }
    h1 {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--text);
    }
    p {
      font-size: 14px;
      color: var(--muted);
      margin-bottom: 24px;
      line-height: 1.5;
    }
    .spinner {
      width: 32px;
      height: 32px;
      border: 3px solid rgba(245, 197, 24, 0.15);
      border-top-color: var(--yellow);
      border-radius: 50%;
      margin: 0 auto 20px;
      animation: spin 0.7s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    .cta-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      background: var(--telegram);
      color: #fff;
      text-decoration: none;
      font-weight: 600;
      font-size: 15px;
      padding: 14px 20px;
      border-radius: 12px;
      transition: all 0.2s;
    }
    .cta-btn:hover {
      background: #1c88bd;
      transform: translateY(-1px);
    }
    .brand-foot {
      margin-top: 24px;
      font-size: 11px;
      color: rgba(148, 163, 184, 0.6);
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }
  </style>
</head>
<body>
  <div class="redirect-card">
    <div class="logo-badge">
      <svg viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
      </svg>
    </div>

    <div class="spinner"></div>
    <h1>Connecting to Telegram...</h1>
    <p>Opening Telegram channel directly. If nothing happens, tap the button below.</p>

    <a id="tg-btn" href="{{ $webUrl }}" class="cta-btn">
      <span>Open Telegram Directly</span>
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
      </svg>
    </a>

    <div class="brand-foot">
      ⚡ Powered by KirtniX TG Tracker
    </div>
  </div>

  <script>
    (function() {
      var deepLink = {!! json_encode($deepLink, JSON_UNESCAPED_SLASHES) !!};
      var webUrl = {!! json_encode($webUrl, JSON_UNESCAPED_SLASHES) !!};

      // Attempt immediate Telegram app deep link protocol
      if (deepLink && deepLink.startsWith('tg://')) {
        window.location.href = deepLink;
        // Fallback to web link if native handler doesn't capture within 400ms
        setTimeout(function() {
          window.location.href = webUrl;
        }, 400);
      } else {
        window.location.href = webUrl;
      }
    })();
  </script>
</body>
</html>
