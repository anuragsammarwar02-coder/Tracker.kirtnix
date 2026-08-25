<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in | Kirtnix — TG TRACKER</title>
  
  <!-- Kirtnix Favicon -->
  <link rel="icon" type="image/png" href="{{ asset('assets/branding/kirtnix_favicon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/png">

  <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: {
              yellow: '#FACC15',
              'yellow-hover': '#EAB308',
              'yellow-dark': '#CA8A04',
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace']
          }
        }
      }
    }
  </script>

  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #FAF9F6;
    }
    .ambient-glow {
      background: radial-gradient(circle at center, rgba(250, 204, 21, 0.18) 0%, rgba(250, 204, 21, 0.04) 55%, transparent 75%);
    }
    @keyframes cardEntrance {
      from {
        opacity: 0;
        transform: translateY(12px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .animate-card-entrance {
      animation: cardEntrance 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @media (prefers-reduced-motion: reduce) {
      .animate-card-entrance {
        animation: none !important;
      }
      * {
        transition-duration: 0.01ms !important;
      }
    }
    .logo-container img {
      max-width: 100%;
      height: auto;
      display: block;
      object-fit: contain;
    }
  </style>
</head>
<body class="min-h-screen text-slate-900 flex flex-col justify-between antialiased selection:bg-yellow-400 selection:text-slate-950">

  <!-- Main Container -->
  <div class="max-w-7xl mx-auto w-full px-6 sm:px-8 lg:px-12 py-6 flex flex-col min-h-screen justify-between">
    
    <!-- Top Header -->
    <header class="w-full flex items-center justify-between mb-8 lg:mb-4">
      <div class="flex items-center gap-3.5">
        <div class="logo-container" style="width: 180px; min-width: 160px;">
          <img 
            src="{{ asset('assets/branding/kirtnix_agency_dark.png') }}" 
            alt="Kirtnix Digital Agency" 
            class="w-full h-auto object-contain"
            style="width: 180px; height: auto;"
          />
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10.5px] font-bold tracking-wider uppercase text-slate-600 bg-slate-100/90 border border-slate-200 shadow-2xs">
          TG TRACKER
        </span>
      </div>
    </header>

    <!-- Main Grid Content -->
    <main class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center my-auto py-4">
      
      <!-- LEFT MARKETING COLUMN -->
      <div class="lg:col-span-7 flex flex-col justify-center">
        
        <!-- Greeting Tag -->
        <div class="mb-5">
          <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-700 bg-amber-50 px-3.5 py-1 rounded-full border border-amber-200/70 shadow-2xs">
            {{ $greeting ?? 'Good Afternoon' }} <span class="text-sm">👋</span>
          </span>
        </div>

        <!-- Main Headline -->
        <h1 class="text-3xl sm:text-4xl lg:text-[50px] font-extrabold text-slate-900 tracking-tight leading-[1.12]">
          Every Telegram Subscriber.<br>
          <span class="text-yellow-500">Verified.</span> Never Estimated.
        </h1>

        <!-- Subtitle -->
        <p class="mt-4 text-xs sm:text-sm text-slate-500 max-w-xl leading-relaxed">
          Kirtnix automatically verifies real Telegram joins using Meta Ads, Landing Pages and Telegram Bots. No guessed conversions — only verified tracking.
        </p>

        <!-- 6 Metric Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 mt-8">
          
          <!-- Card 1 -->
          <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">SUBSCRIBERS TODAY</span>
              <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div class="text-xl font-extrabold text-slate-900 tracking-tight font-sans">{{ $subscribersToday ?? 348 }}</div>
          </div>

          <!-- Card 2 -->
          <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">TODAY'S SPEND</span>
              <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div class="text-xl font-extrabold text-slate-900 tracking-tight font-sans">{{ $todaySpendFormatted ?? '₹12,480' }}</div>
          </div>

          <!-- Card 3 -->
          <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">COST / SUBSCRIBER</span>
              <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-xl font-extrabold text-slate-900 tracking-tight font-sans">{{ $costPerSub ?? '₹35.8' }}</div>
          </div>

          <!-- Card 4 -->
          <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">TELEGRAM CLICKS</span>
              <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="text-xl font-extrabold text-slate-900 tracking-tight font-sans">{{ $telegramClicksFormatted ?? '1,204' }}</div>
          </div>

          <!-- Card 5 -->
          <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">VERIFIED JOINS</span>
              <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-xl font-extrabold text-slate-900 tracking-tight font-sans">{{ $verifiedJoinsFormatted ?? '311' }}</div>
          </div>

          <!-- Card 6 -->
          <div class="bg-white rounded-2xl border border-slate-200/90 p-4 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
            <div class="flex items-center justify-between mb-1.5">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">CONVERSION QUEUE</span>
              <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            </div>
            <div class="text-xl font-extrabold text-slate-900 tracking-tight font-sans">{{ $conversionQueue ?? 6 }}</div>
          </div>

        </div>

        <!-- Status Indicators -->
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs font-semibold text-slate-600 mt-8">
          <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Meta Connected</span>
          <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Telegram Bot Online</span>
          <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Tracking Engine Healthy</span>
          <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Conversion Queue Active</span>
        </div>

        <!-- Live Ticker Pill Bar -->
        <div class="mt-4">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-slate-200/90 bg-white text-[11px] text-slate-600 shadow-2xs">
            <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>✓ Meta conversion delivered</span>
          </div>
        </div>

      </div>

      <!-- RIGHT LOGIN CARD COLUMN -->
      <div class="lg:col-span-5 flex flex-col items-center lg:items-end justify-center">
        <div class="relative w-full max-w-[420px]">
          
          <!-- Subtle warm background ambient aura -->
          <div class="absolute -inset-5 ambient-glow rounded-3xl blur-2xl -z-10"></div>

          <!-- White Floating Card -->
          <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl shadow-slate-200/50 p-8 sm:p-9 w-full animate-card-entrance">
            
            <!-- Centered Prominent Logo -->
            <div class="text-center mb-4 flex justify-center">
              <div class="logo-container" style="width: 175px; min-width: 150px;">
                <img 
                  src="{{ asset('assets/branding/kirtnix_agency_dark.png') }}" 
                  alt="Kirtnix Digital Agency" 
                  class="w-full h-auto object-contain mx-auto"
                  style="width: 175px; height: auto;"
                />
              </div>
            </div>

            <!-- Private Platform Pill -->
            <div class="flex justify-center mb-5">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-medium text-slate-500 bg-slate-50 border border-slate-200/90 shadow-2xs">
                🔒 Private platform — invitation only
              </span>
            </div>

            <!-- Card Heading -->
            <div class="mb-5">
              <h2 class="text-xl font-bold text-slate-900 tracking-tight">Welcome back</h2>
              <p class="text-xs text-slate-500 mt-1">Sign in to your Kirtnix workspace. Access is granted by the administrator.</p>
            </div>

            <!-- Google SSO Button -->
            <button type="button" onclick="alert('Google Workspace SSO is restricted to authorized domain emails. Please use your workspace administrator login below.')" class="w-full py-2.5 px-4 rounded-xl border border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 flex items-center justify-center gap-2.5 transition-all duration-150 shadow-2xs cursor-pointer active:scale-[0.99]">
              <svg class="w-4 h-4" viewBox="0 0 24 24">
                <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.4 1 3.5 3.6 1.6 7.4l3.7 2.9C6.2 7.4 8.9 5 12 5z"/>
                <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
                <path fill="#FBBC05" d="M5.3 14.7c-.2-.7-.4-1.5-.4-2.4 0-.9.2-1.7.4-2.4L1.6 7c-.8 1.6-1.3 3.4-1.3 5.3s.5 3.7 1.3 5.3l3.7-2.9z"/>
                <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.8-2.1-6.7-5L1.6 16.2C3.5 20.1 7.4 23 12 23z"/>
              </svg>
              <span>Continue with Google</span>
            </button>

            <!-- Divider -->
            <div class="relative my-4 flex items-center justify-center">
              <div class="border-t border-slate-200/90 w-full"></div>
              <span class="bg-white px-3 text-[10px] text-slate-400 font-medium absolute">or</span>
            </div>

            <!-- Error Alerts -->
            @if ($errors->any())
              <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-medium space-y-1">
                @foreach ($errors->all() as $error)
                  <div class="flex items-center gap-1.5">
                    <span>⚠️</span>
                    <span>{{ $error }}</span>
                  </div>
                @endforeach
              </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
              @csrf

              <!-- Email Field -->
              <div>
                <label for="email" class="text-xs font-semibold text-slate-700 block mb-1">Email</label>
                <input 
                  type="email" 
                  id="email" 
                  name="email" 
                  value="{{ old('email', 'admin@kirtnix.agency') }}" 
                  required 
                  autofocus 
                  placeholder="admin@kirtnix.agency"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400/50 focus:border-yellow-400 transition-all duration-150 font-medium"
                />
              </div>

              <!-- Password Field -->
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label for="password" class="text-xs font-semibold text-slate-700">Password</label>
                  <a href="javascript:void(0)" onclick="alert('Please contact the KirtniX agency administrator to reset your password or generate new workspace credentials.')" class="text-[11px] font-semibold text-yellow-600 hover:text-yellow-700 transition">
                    Forgot password?
                  </a>
                </div>
                <input 
                  type="password" 
                  id="password" 
                  name="password" 
                  value="Kirtnix@2026!" 
                  required 
                  placeholder="••••••••••••"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400/50 focus:border-yellow-400 transition-all duration-150 font-medium"
                />
              </div>

              <!-- Submit CTA Button -->
              <button 
                type="submit" 
                class="w-full mt-2 bg-[#FACC15] hover:bg-[#EAB308] text-slate-950 font-bold text-xs py-3 rounded-xl shadow-sm hover:shadow-md transition-all duration-150 flex items-center justify-center gap-1.5 cursor-pointer active:scale-[0.99]"
              >
                <span>Sign in</span>
                <span class="text-sm">→</span>
              </button>
            </form>

            <!-- Bottom Invitation Notice -->
            <p class="text-[11px] text-slate-400 text-center leading-relaxed mt-4">
              Sign-ups are permanently disabled. Only emails approved by the Kirtnix administrator can access this platform.
            </p>

          </div>

          <!-- Security Footer Badges -->
          <div class="mt-4 space-y-1 text-center text-[10px] text-slate-400 font-medium">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3 flex-wrap">
              <span class="inline-flex items-center gap-1"><span class="text-amber-500">🛡️</span> Protected by Kirtnix Security</span>
              <span class="inline-flex items-center gap-1"><span class="text-amber-500">🔑</span> 256-bit Encryption</span>
              <span class="inline-flex items-center gap-1"><span class="text-amber-500">🔒</span> Invitation-only Workspace</span>
            </div>
            <div class="text-center text-slate-400 pt-0.5">
              <span>👤</span> Owner Controlled Access
            </div>
          </div>

        </div>
      </div>

    </main>

    <!-- Subtle Footer Space -->
    <footer class="w-full py-2"></footer>

  </div>

</body>
</html>
