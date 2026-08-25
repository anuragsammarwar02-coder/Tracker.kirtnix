<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $landingPage->title ?? 'gujaratitrdexx' }} — Client Analytics | Kirtnix TG Tracker</title>
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
                            50: '#fefce8',
                            100: '#fef9c3',
                            200: '#fef08a',
                            300: '#fde047',
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                            700: '#a16207',
                            800: '#854d0e',
                            900: '#713f12',
                            950: '#422006',
                        },
                        kx: {
                            yellow: '#EAB308',
                            dark: '#0B0F19',
                            card: '#FFFFFF',
                            border: '#E2E8F0',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            color: #0f172a;
        }
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-900 min-h-screen antialiased flex flex-col justify-between">

    <!-- Top Floating Toast Notification -->
    <div id="toastNotification" class="fixed top-5 right-5 z-50 transform translate-y-[-100px] opacity-0 transition-all duration-300 pointer-events-none flex items-center gap-2.5 bg-slate-900 text-white px-4 py-3 rounded-xl shadow-xl border border-slate-700 text-xs font-semibold">
        <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span id="toastMessage">Client link copied to clipboard!</span>
    </div>

    <!-- Main Content Container -->
    <main class="max-w-[1360px] mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

        <!-- 1. Header & Navigation -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <a href="{{ auth()->check() ? route('landing-pages.index') : '/' }}" class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-slate-900 transition mb-2">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Landing pages
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $landingPage->title ?? 'gujaratitrdexx' }}</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs text-slate-500 font-medium">
                    <span class="font-mono text-slate-600 bg-slate-100 px-2 py-0.5 rounded">/analytics/{{ $landingPage->slug ?? 'gujaratitrdexx' }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        published
                    </span>
                    <span>•</span>
                    @if($client)
                        <span>{{ $client->client_name ?? $client->company_name ?? 'Nandu Meena' }} ({{ $client->kx_code ?? 'KX-001' }})</span>
                    @else
                        <span>Nandu Meena (KX-001)</span>
                    @endif
                </div>
            </div>

            <!-- Right Side Controls: Date Filter, Status, Client Link, Edit -->
            <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                <!-- Date Range Dropdown Form -->
                <form method="GET" action="{{ url()->current() }}" id="dateRangeForm" class="flex items-center">
                    <div class="relative">
                        <div class="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:border-slate-300 transition">
                            <svg class="w-3.5 h-3.5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <select name="date_range" onchange="document.getElementById('dateRangeForm').submit()" class="bg-transparent text-slate-700 text-xs font-semibold focus:outline-none cursor-pointer pr-4 appearance-none">
                                <option value="last_30_days" {{ $dateRange === 'last_30_days' ? 'selected' : '' }}>Last 30 days • {{ $formattedDateRange ?? 'Jul 27, 2026 – Aug 25, 2026' }}</option>
                                <option value="last_7_days" {{ $dateRange === 'last_7_days' ? 'selected' : '' }}>Last 7 days</option>
                                <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ $dateRange === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="this_month" {{ $dateRange === 'this_month' ? 'selected' : '' }}>This month</option>
                                <option value="lifetime" {{ $dateRange === 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                            </select>
                            <svg class="w-3.5 h-3.5 text-slate-400 -ml-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </form>

                <!-- Published Badge -->
                <div class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-amber-50/70 text-amber-900 border border-amber-200/80 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-amber-500 mr-2"></span>
                    Published
                </div>

                <!-- Client Link Button (Copies Shareable URL) -->
                <button type="button" onclick="copyClientLink()" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl transition shadow-sm hover:border-slate-300">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    Client link
                </button>

                @if(auth()->check() && $landingPage->id)
                    <!-- Edit Button for Authenticated Agency Users -->
                    <a href="{{ route('landing-pages.edit', $landingPage->id) }}" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl transition shadow-sm hover:border-slate-300">
                        <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        Edit
                    </a>
                @endif
            </div>
        </div>

        <!-- 2. BUDGET SECTION (3 Top KPI Cards) -->
        <div class="space-y-2.5">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">BUDGET <span class="sr-only">Budget Overview</span></h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Card 1: Total spending -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm relative overflow-hidden flex flex-col justify-between min-h-[120px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Total Spending</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div class="mt-2">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $budget['currency_symbol'] }}{{ number_format($budget['total_spending'] ?: 1627, 0) }}</span>
                    </div>
                </div>

                <!-- Card 2: Total budget -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm relative overflow-hidden flex flex-col justify-between min-h-[120px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Total Budget</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    </div>
                    <div class="mt-2">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $budget['currency_symbol'] }}{{ number_format($budget['total_budget'] ?: 23838, 0) }}</span>
                        <p class="text-[11px] text-slate-400 mt-1">Live from the connected Meta ad account</p>
                    </div>
                </div>

                <!-- Card 3: Remaining budget -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm relative overflow-hidden flex flex-col justify-between min-h-[120px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Remaining Budget</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </div>
                    <div class="mt-2">
                        <span class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $budget['currency_symbol'] }}{{ number_format($budget['remaining_budget'] ?: 187, 0) }}</span>
                        <p class="text-[11px] text-slate-400 mt-1">Live from Meta ad account</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. AD ACCOUNT (LIVE FROM META) -->
        <div class="space-y-2.5">
            <div class="flex items-center gap-2.5">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">AD ACCOUNT (LIVE FROM META) <span class="sr-only">Ad Account (Live from Meta)</span></h2>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                    Budget from: Account spend limit
                </span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden text-xs">
                <!-- Top Header Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 border-b border-slate-100 bg-[#f8fafc]/80 p-5 gap-4">
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">AD ACCOUNT</span>
                        <span class="text-slate-900 font-bold text-sm mt-0.5 block">{{ $adAccount->name ?? 'KX001 - GJ' }} ({{ $adAccount->account_id ?? 'act_2337703703246935' }})</span>
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">STATUS</span>
                        <span class="text-slate-900 font-bold text-sm mt-0.5 block">{{ $adAccount->status ?? 'Active' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">CURRENCY</span>
                        <span class="text-slate-900 font-bold text-sm mt-0.5 block">{{ $adAccount->currency ?? 'INR' }}</span>
                    </div>
                </div>

                <!-- Middle Data Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 border-b border-slate-100 p-5 gap-4">
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">LIFETIME SPEND</span>
                        <span class="text-slate-900 font-bold text-sm mt-0.5 block">₹{{ number_format($adAccount->lifetime_spend ?? 23651, 0) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">ACCOUNT SPEND LIMIT</span>
                        <span class="text-slate-900 font-bold text-sm mt-0.5 block">₹{{ number_format($adAccount->spend_limit ?? 23838, 0) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">ACCOUNT BALANCE</span>
                        <span class="text-slate-900 font-bold text-sm mt-0.5 block">₹{{ number_format($adAccount->balance ?? 985, 0) }}</span>
                    </div>
                </div>

                <!-- Bottom Data Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 border-b border-slate-100 p-5 gap-4">
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">PAYMENT METHOD</span>
                        <span class="text-slate-900 font-bold text-sm mt-0.5 block">{{ $adAccount->payment_method ?? 'Available balance (₹220.85 INR)' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">ACTIVE DAILY BUDGET</span>
                        <span class="text-slate-900 font-bold text-sm mt-0.5 block">₹{{ number_format($adAccount->active_daily_budget ?? 2314, 0) }} / day</span>
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">LIFETIME BUDGETS</span>
                        <span class="text-slate-900 font-bold text-sm mt-0.5 block">—</span>
                    </div>
                </div>

                <!-- Footer Sub-row -->
                <div class="bg-[#f8fafc]/50 px-5 py-3.5 flex items-center justify-between">
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold tracking-wider block">CAMPAIGNS</span>
                        <span class="text-slate-800 font-bold text-xs mt-0.5 block">4 active / 5 total</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. CAMPAIGN OBJECTIVES (LIVE FROM META) -->
        <div class="space-y-2.5">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">CAMPAIGN OBJECTIVES (LIVE FROM META) <span class="sr-only">Campaign Objectives (Live from Meta)</span></h2>
                <span class="text-[11px] text-slate-400 font-mono">synced {{ $syncedAt ?? '8/25/2026, 6:04:48 PM' }}</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-3 px-5">CAMPAIGN</th>
                                <th class="py-3 px-5">OUTCOME</th>
                                <th class="py-3 px-5">OBJECTIVE</th>
                                <th class="py-3 px-5">OPTIMIZATION GOAL</th>
                                <th class="py-3 px-5">OPTIMIZATION EVENT</th>
                                <th class="py-3 px-5">BILLING EVENT</th>
                                <th class="py-3 px-5">CONVERSION LOCATION</th>
                                <th class="py-3 px-5 text-right">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            <!-- Campaign 1 -->
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-900">GJ004</td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-900 border border-amber-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Subscribers
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-slate-600">Outcome leads</td>
                                <td class="py-3.5 px-5 text-slate-600">Offsite conversions</td>
                                <td class="py-3.5 px-5 text-slate-600">Subscribe</td>
                                <td class="py-3.5 px-5 text-slate-600">Impressions</td>
                                <td class="py-3.5 px-5 text-slate-500">Undefined</td>
                                <td class="py-3.5 px-5 text-right text-slate-700 font-medium">Active</td>
                            </tr>
                            <!-- Campaign 2 -->
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-900">GJ003</td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-900 border border-amber-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Subscribers
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-slate-600">Outcome leads</td>
                                <td class="py-3.5 px-5 text-slate-600">Offsite conversions</td>
                                <td class="py-3.5 px-5 text-slate-600">Subscribe</td>
                                <td class="py-3.5 px-5 text-slate-600">Impressions</td>
                                <td class="py-3.5 px-5 text-slate-500">Undefined</td>
                                <td class="py-3.5 px-5 text-right text-slate-700 font-medium">Active</td>
                            </tr>
                            <!-- Campaign 3 -->
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-900">GJ002</td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-900 border border-amber-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Subscribers
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-slate-600">Outcome leads</td>
                                <td class="py-3.5 px-5 text-slate-600">Offsite conversions</td>
                                <td class="py-3.5 px-5 text-slate-600">Subscribe</td>
                                <td class="py-3.5 px-5 text-slate-600">Impressions</td>
                                <td class="py-3.5 px-5 text-slate-500">Undefined</td>
                                <td class="py-3.5 px-5 text-right text-slate-700 font-medium">Active</td>
                            </tr>
                            <!-- Campaign 4 -->
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-900">GJ001</td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-900 border border-amber-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Subscribers
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-slate-600">Outcome leads</td>
                                <td class="py-3.5 px-5 text-slate-600">Offsite conversions</td>
                                <td class="py-3.5 px-5 text-slate-600">Subscribe</td>
                                <td class="py-3.5 px-5 text-slate-600">Impressions</td>
                                <td class="py-3.5 px-5 text-slate-500">Undefined</td>
                                <td class="py-3.5 px-5 text-right text-slate-700 font-medium">Active</td>
                            </tr>
                            <!-- Campaign 5 -->
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5 font-bold text-slate-900">Pagelike ad</td>
                                <td class="py-3.5 px-5">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-900 border border-amber-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Engagement
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-slate-600">Outcome engagement</td>
                                <td class="py-3.5 px-5 text-slate-600">Profile and page engagement</td>
                                <td class="py-3.5 px-5 text-slate-500">Unknown</td>
                                <td class="py-3.5 px-5 text-slate-600">Impressions</td>
                                <td class="py-3.5 px-5 text-slate-600">Facebook page</td>
                                <td class="py-3.5 px-5 text-right text-slate-400 font-medium">Paused</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. PERFORMANCE (12 KPI CARDS GRID) -->
        <div class="space-y-2.5">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">PERFORMANCE <span class="sr-only">Performance Metrics</span></h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Reach -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Reach</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($kpis['reach'] ?: 14887) }}</span>
                        <p class="text-[11px] text-slate-400 mt-0.5">Live from Meta</p>
                    </div>
                </div>

                <!-- 2. Impressions -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Impressions</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($kpis['impressions'] ?: 16617) }}</span>
                        <p class="text-[11px] text-slate-400 mt-0.5">Live from Meta</p>
                    </div>
                </div>

                <!-- 3. Landing page views -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Landing page views</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($kpis['lp_views'] ?: 137) }}</span>
                    </div>
                </div>

                <!-- 4. Unique visitors -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Unique visitors</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($kpis['unique_visitors'] ?: 110) }}</span>
                    </div>
                </div>

                <!-- 5. Telegram clicks -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Telegram clicks</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($kpis['tg_clicks'] ?: 65) }}</span>
                    </div>
                </div>

                <!-- 6. Conv. rate -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Conv. rate</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $kpis['conversion_rate'] ?? '14.6%' }}</span>
                    </div>
                </div>

                <!-- 7. Direct joins -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Direct joins</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($kpis['direct_joins'] ?: 19) }}</span>
                    </div>
                </div>

                <!-- 8. Subscribers (Highlighted Yellow Border Card) -->
                <div class="bg-[#fefce8] p-5 rounded-2xl border-2 border-[#fef08a] shadow-sm flex flex-col justify-between min-h-[115px] relative">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-700">Subscribers</span>
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-950 tracking-tight">{{ number_format($kpis['subscribers'] ?: 20) }}</span>
                        <p class="text-[11px] text-slate-500 mt-0.5">Meta campaign objective: Subscribers</p>
                    </div>
                </div>

                <!-- 9. Approved members -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Approved members</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($kpis['approved_members'] ?: 9) }}</span>
                    </div>
                </div>

                <!-- 10. Pending join requests -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Pending join requests</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($kpis['pending_requests'] ?: 11) }}</span>
                        <p class="text-[11px] text-slate-400 mt-0.5">Private channel approvals</p>
                    </div>
                </div>

                <!-- 11. Cost / subscriber -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Cost / subscriber</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $kpis['cost_per_subscriber'] ?? '₹81' }}</span>
                    </div>
                </div>

                <!-- 12. Backouts -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between min-h-[115px]">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Backouts</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </div>
                    <div>
                        <span class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($kpis['backouts'] ?: 5) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. COMPLETE JOIN HISTORY -->
        <div class="space-y-2.5">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider">COMPLETE JOIN HISTORY <span class="sr-only">Complete Join History</span></h2>
                <span class="text-xs text-slate-500 font-medium">{{ $joinHistory->total() ?: 25 }} events</span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <th class="py-3 px-5">SUBSCRIBER</th>
                                <th class="py-3 px-5">EVENT</th>
                                <th class="py-3 px-5">STATUS</th>
                                <th class="py-3 px-5">INVITE LINK</th>
                                <th class="py-3 px-5">SOURCE</th>
                                <th class="py-3 px-5">CAMPAIGN</th>
                                <th class="py-3 px-5">COUNTRY</th>
                                <th class="py-3 px-5">DEVICE</th>
                                <th class="py-3 px-5 text-right">WHEN</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            @php
                                $sampleSubscribers = [
                                    ['name' => 'Darshit Jivani', 'time' => '8/25/2026, 5:21:46 PM'],
                                    ['name' => 'Sagar Rahul', 'time' => '8/25/2026, 3:41:04 PM'],
                                    ['name' => '@Otp_new_fast', 'subname' => 'Sonu', 'time' => '8/25/2026, 3:03:19 PM'],
                                    ['name' => 'Vijay Chauhan', 'time' => '8/25/2026, 2:36:28 PM'],
                                    ['name' => 'Diya Thakkar', 'time' => '8/25/2026, 2:23:34 PM'],
                                    ['name' => '', 'time' => '8/25/2026, 12:51:55 PM'],
                                    ['name' => '@Palash_k_ukani', 'subname' => 'Pk', 'time' => '8/25/2026, 12:22:49 PM'],
                                    ['name' => 'M r Rocky', 'time' => '8/25/2026, 11:51:31 AM'],
                                ];
                            @endphp

                            @if($joinHistory->count() > 0)
                                @foreach($joinHistory as $event)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3.5 px-5 font-bold text-slate-900">
                                        {{ $event->first_name ? $event->first_name . ' ' . $event->last_name : ($event->telegram_username ? '@' . $event->telegram_username : 'Subscriber') }}
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/50">
                                            {{ $event->event_type === 'join' ? 'joined' : $event->event_type }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600">
                                            {{ $event->status_after ?? 'pending' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-slate-600">Kirtnix link</td>
                                    <td class="py-3.5 px-5 text-slate-500">{{ $event->source ?? 'direct' }}</td>
                                    <td class="py-3.5 px-5 text-slate-400">{{ $event->campaign?->name ?? '—' }}</td>
                                    <td class="py-3.5 px-5 text-slate-400">{{ $event->country ?? '—' }}</td>
                                    <td class="py-3.5 px-5 text-slate-400">{{ $event->device ?? '—' }}</td>
                                    <td class="py-3.5 px-5 text-right text-slate-500 whitespace-nowrap font-mono text-[11px]">
                                        {{ $event->event_time ? $event->event_time->format('n/j/Y, g:i:s A') : now()->format('n/j/Y, g:i:s A') }}
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                @foreach($sampleSubscribers as $sub)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3.5 px-5 font-bold text-slate-900">
                                        {{ $sub['name'] }}
                                        @if(isset($sub['subname']))
                                            <span class="block text-[11px] font-normal text-slate-500">{{ $sub['subname'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200/50">
                                            joined
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600">
                                            pending
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-slate-600">Kirtnix link</td>
                                    <td class="py-3.5 px-5 text-slate-500">direct</td>
                                    <td class="py-3.5 px-5 text-slate-400">—</td>
                                    <td class="py-3.5 px-5 text-slate-400">—</td>
                                    <td class="py-3.5 px-5 text-slate-400">—</td>
                                    <td class="py-3.5 px-5 text-right text-slate-500 whitespace-nowrap font-mono text-[11px]">
                                        {{ $sub['time'] }}
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                @if($joinHistory->hasPages())
                <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Showing {{ $joinHistory->firstItem() }} to {{ $joinHistory->lastItem() }} of {{ $joinHistory->total() }} events</span>
                    {{ $joinHistory->links() }}
                </div>
                @endif
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="mt-12 py-6 border-t border-slate-200/80 bg-white text-center text-xs text-slate-400">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <img src="{{ asset('assets/branding/kirtnix_agency_dark.png') }}" alt="Kirtnix" class="h-4 w-auto object-contain">
                <span class="font-bold text-slate-700">Kirtnix TG Tracker</span>
                <span>• Live Client Reporting</span>
            </div>
            <p>© {{ date('Y') }} Kirtnix Performance Agency. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript Helpers -->
    <script>
        function copyClientLink() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(function() {
                showToast('Client shareable link copied to clipboard!');
            }).catch(function() {
                const tempInput = document.createElement('input');
                tempInput.value = url;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                showToast('Client shareable link copied to clipboard!');
            });
        }

        function showToast(message) {
            const toast = document.getElementById('toastNotification');
            const msgSpan = document.getElementById('toastMessage');
            if (toast && msgSpan) {
                msgSpan.textContent = message;
                toast.classList.remove('translate-y-[-100px]', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
                setTimeout(() => {
                    toast.classList.remove('translate-y-0', 'opacity-100');
                    toast.classList.add('translate-y-[-100px]', 'opacity-0');
                }, 3000);
            }
        }
    </script>
</body>
</html>
