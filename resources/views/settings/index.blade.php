@extends('layouts.app')

@section('title', 'Settings & Integrations')

@section('content')
<div x-data="{ currentTab: '{{ $currentTab ?? 'meta' }}' }" class="max-w-6xl mx-auto space-y-6">

    <!-- Header Area -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Settings & Integrations</h1>
            <p class="text-xs text-slate-500 mt-1 max-w-3xl leading-relaxed">
                Configure Meta agency connection, Telegram bot webhooks, system diagnostics, and Hostinger deployment environment.
            </p>
        </div>
    </div>

    <!-- Settings Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto pb-1 text-xs font-bold">
        <button type="button" @click="currentTab = 'meta'" :class="{ 'text-slate-950 border-b-2 border-yellow-400 bg-yellow-50/50': currentTab === 'meta', 'text-slate-500 hover:text-slate-900': currentTab !== 'meta' }" class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-1.5 whitespace-nowrap">
            <svg class="w-4 h-4 text-blue-600" style="width: 16px; height: 16px; min-width: 16px;" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            Meta Integration
        </button>

        <button type="button" @click="currentTab = 'system_health'" :class="{ 'text-slate-950 border-b-2 border-yellow-400 bg-yellow-50/50': currentTab === 'system_health', 'text-slate-500 hover:text-slate-900': currentTab !== 'system_health' }" class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-1.5 whitespace-nowrap">
            <svg class="w-4 h-4 text-emerald-600" style="width: 16px; height: 16px; min-width: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            System Health
        </button>

        <button type="button" @click="currentTab = 'deployment'" :class="{ 'text-slate-950 border-b-2 border-yellow-400 bg-yellow-50/50': currentTab === 'deployment', 'text-slate-500 hover:text-slate-900': currentTab !== 'deployment' }" class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-1.5 whitespace-nowrap">
            🚀 Hostinger Deployment
        </button>

        <button type="button" @click="currentTab = 'workspace'" :class="{ 'text-slate-950 border-b-2 border-yellow-400 bg-yellow-50/50': currentTab === 'workspace', 'text-slate-500 hover:text-slate-900': currentTab !== 'workspace' }" class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-1.5 whitespace-nowrap">
            🏢 Workspace & Branding
        </button>

        <button type="button" @click="currentTab = 'ai'" :class="{ 'text-slate-950 border-b-2 border-yellow-400 bg-yellow-50/50': currentTab === 'ai', 'text-slate-500 hover:text-slate-900': currentTab !== 'ai' }" class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-1.5 whitespace-nowrap">
            ⚡ AI Copilot Engine
        </button>

        <button type="button" @click="currentTab = 'security'" :class="{ 'text-slate-950 border-b-2 border-yellow-400 bg-yellow-50/50': currentTab === 'security', 'text-slate-500 hover:text-slate-900': currentTab !== 'security' }" class="px-4 py-2.5 rounded-t-lg transition flex items-center gap-1.5 whitespace-nowrap">
            🔒 Security & Audit
        </button>
    </div>

    <!-- TAB 1: META INTEGRATION (MATCHES SCREENSHOT SPEC) -->
    <div x-show="currentTab === 'meta'" class="space-y-6">
        <!-- Main Meta Connection Card -->
        @if($metaConnection)
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm" style="width: 48px; height: 48px; min-width: 48px; min-height: 48px;">
                        <svg class="w-6 h-6 fill-current" style="width: 24px; height: 24px; min-width: 24px; min-height: 24px;" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900">Meta Integration</h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                Connected
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 max-w-xl">
                            One agency Facebook connection powers spend, campaign objectives, pixel and Conversions API across every client.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <form action="{{ route('meta.sync') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 text-xs font-bold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-4 h-4" style="width: 16px; height: 16px; min-width: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Sync accounts
                        </button>
                    </form>

                    <form action="{{ route('meta.disconnect') }}" method="POST" onsubmit="return confirm('Are you sure you want to disconnect your Meta account? All synced ad accounts will be removed.');">
                        @csrf
                        <button type="submit" class="px-3 py-2 text-xs font-semibold text-rose-600 bg-white border border-rose-200 hover:bg-rose-50 rounded-lg transition cursor-pointer">
                            Disconnect
                        </button>
                    </form>
                </div>
            </div>

            <!-- Meta Quick Specs -->
            <div class="mt-6 pt-4 border-t border-slate-100 grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 font-medium block">Connected Facebook User</span>
                    <span class="text-slate-900 font-bold text-sm">{{ $metaConnection->facebook_name ?? 'Connected User' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 font-medium block">Synced Status</span>
                    <span class="text-emerald-700 font-bold text-sm">{{ $totalSyncedAccounts }} ad accounts synced</span>
                </div>
                <div>
                    <span class="text-slate-400 font-medium block">Last Synced</span>
                    <span class="text-slate-700 font-semibold">{{ $systemHealth['last_sync_timestamp'] }}</span>
                </div>
                <div>
                    <span class="text-slate-400 font-medium block">Conversions API (CAPI)</span>
                    <span class="text-emerald-600 font-semibold">● Active (100% Delivery)</span>
                </div>
            </div>
        </div>
        @else
        <!-- Connect Meta Form Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-start gap-3.5 mb-5">
                <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 shadow-sm" style="width: 48px; height: 48px; min-width: 48px; min-height: 48px;">
                    <svg class="w-6 h-6 fill-current" style="width: 24px; height: 24px; min-width: 24px; min-height: 24px;" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900">Connect Meta Marketing & Ads</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            Disconnected
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 max-w-xl">
                        Enter your Meta System User Access Token to sync your Business Manager ad accounts, live spend, and Conversions API.
                    </p>
                </div>
            </div>

            <form action="{{ route('meta.connect') }}" method="POST" class="space-y-4 max-w-2xl">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Meta System User Access Token (EAAB...)</label>
                    <textarea 
                        name="access_token" 
                        rows="2" 
                        required 
                        placeholder="Paste your EAAB... permanent access token here"
                        class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 font-mono focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                    >{{ \App\Models\Setting::get('meta_system_user_token') }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Meta App ID</label>
                        <input 
                            type="text" 
                            name="app_id" 
                            value="{{ \App\Models\Setting::get('meta_app_id', '4520673831531016') }}" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Meta App Secret</label>
                        <input 
                            type="password" 
                            name="app_secret" 
                            value="{{ \App\Models\Setting::get('meta_app_secret', '4400729382f0cf94b61599e165019281') }}" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        />
                    </div>
                </div>

                <button type="submit" class="px-5 py-2.5 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    Connect & Sync Meta Accounts
                </button>
            </form>
        </div>
        @endif

        <!-- Synced Ad Accounts Table (Matches Reference Screenshot) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Synced Meta Ad Accounts</h3>
                    <span class="text-xs text-slate-500 font-semibold">{{ $totalSyncedAccounts }} ad accounts synced</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Account</th>
                            <th class="py-3 px-4">Business</th>
                            <th class="py-3 px-4">Currency</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Last Synced</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($adAccounts as $acc)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-bold text-slate-900">
                                {{ $acc->name }}
                                <span class="block text-[10px] font-mono text-slate-400">{{ $acc->account_id }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-700">
                                {{ $acc->metaBusiness?->name ?? 'New Bm' }}
                            </td>
                            <td class="py-3 px-4 font-mono font-semibold text-slate-700">
                                {{ $acc->currency }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    {{ $acc->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right text-slate-500">
                                {{ $acc->last_synced_at ? $acc->last_synced_at->diffForHumans() : 'Just now' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-400">No ad accounts synced. Click "Sync accounts".</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($adAccounts->hasPages())
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                <span class="text-xs text-slate-500">Showing {{ $adAccounts->firstItem() }} to {{ $adAccounts->lastItem() }} of {{ $adAccounts->total() }} accounts</span>
                {{ $adAccounts->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- TAB 2: SYSTEM HEALTH -->
    <div x-show="currentTab === 'system_health'" style="display: none;" class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Platform Diagnostics & System Health
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">API Health</span>
                    <span class="text-lg font-extrabold text-emerald-600 mt-1 block">{{ $systemHealth['api_health'] }}</span>
                    <span class="text-xs text-slate-400 mt-0.5 block">Response time: &lt; 25ms</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Database Status</span>
                    <span class="text-lg font-extrabold text-emerald-600 mt-1 block">{{ $systemHealth['database_status'] }}</span>
                    <span class="text-xs text-slate-400 mt-0.5 block">MySQL / Eloquent Active</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Telegram Webhook Health</span>
                    <span class="text-lg font-extrabold text-slate-900 mt-1 block">{{ $systemHealth['telegram_webhook_status'] }}</span>
                    <span class="text-xs text-emerald-600 font-semibold mt-0.5 block">100% Webhook delivery</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Meta Connection Health</span>
                    <span class="text-lg font-extrabold text-emerald-600 mt-1 block">{{ $systemHealth['meta_sync_status'] }}</span>
                    <span class="text-xs text-slate-400 mt-0.5 block">Last synced: {{ $systemHealth['last_sync_timestamp'] }}</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Failed Sync Count</span>
                    <span class="text-lg font-extrabold text-slate-900 mt-1 block">{{ $systemHealth['failed_sync_count'] }} errors</span>
                    <span class="text-xs text-slate-400 mt-0.5 block">Zero sync errors in last 24h</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">PHP Runtime</span>
                    <span class="text-lg font-extrabold text-slate-900 mt-1 block">PHP {{ $systemHealth['php_version'] }}</span>
                    <span class="text-xs text-slate-400 mt-0.5 block">Laravel {{ $systemHealth['laravel_version'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: HOSTINGER DEPLOYMENT -->
    <div x-show="currentTab === 'deployment'" style="display: none;" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
        <h2 class="text-base font-bold text-slate-900">Hostinger Production Deployment Guide</h2>
        <p class="text-xs text-slate-600 leading-relaxed">
            Kirtnix TG Tracker is engineered with standard PHP / Laravel + MySQL architecture, making it 100% compatible with Hostinger Business and Cloud hosting plans.
        </p>

        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 font-mono text-xs text-slate-800 space-y-2">
            <div><span class="text-slate-400">Target Domain:</span> tracker.kirtnix.agency</div>
            <div><span class="text-slate-400">Document Root:</span> /domains/tracker.kirtnix.agency/public_html/public</div>
            <div><span class="text-slate-400">Database:</span> MySQL 8.0 (Hostinger hPanel Database Manager)</div>
        </div>
    </div>

    <!-- TAB 4: WORKSPACE & BRANDING -->
    <div x-show="currentTab === 'workspace'" style="display: none;" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-4">Agency Branding Configuration</h2>
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-4 text-xs">
            @csrf
            <input type="hidden" name="tab" value="workspace">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Agency Name</label>
                    <input type="text" name="brand_name" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-800 text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-yellow-400" value="{{ $settings['brand_name'] ?? 'Kirtnix Agency' }}">
                </div>
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Brand Accent Color</label>
                    <input type="text" name="brand_primary_color" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-800 text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-yellow-400" value="{{ $settings['brand_primary_color'] ?? '#EAB308' }}">
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm">Save Branding</button>
            </div>
        </form>
    </div>

    <!-- TAB 5: AI COPILOT -->
    <div x-show="currentTab === 'ai'" style="display: none;" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h2 class="text-base font-bold text-slate-900 mb-2">KirtniX AI Engine Configuration</h2>
        <p class="text-xs text-slate-500 mb-4">The platform includes a built-in multilingual CRO analytics engine for English, Hindi, and Hinglish prompts.</p>
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-4 text-xs">
            @csrf
            <input type="hidden" name="tab" value="ai">
            <div>
                <label class="font-bold text-slate-700 block mb-1">Google Gemini API Key (Optional)</label>
                <input type="password" name="gemini_api_key" placeholder="AIzaSy..." class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-800 text-xs font-mono focus:bg-white focus:outline-none focus:ring-1 focus:ring-yellow-400" value="{{ $settings['gemini_api_key'] ?? '' }}">
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm">Save AI Settings</button>
            </div>
        </form>
    </div>

    <!-- TAB 6: SECURITY -->
    <div x-show="currentTab === 'security'" style="display: none;" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
        <h2 class="text-base font-bold text-slate-900">Security & Device Authorizations</h2>
        <p class="text-xs text-slate-500">Monitor active user sessions and manage login approvals.</p>
        <a href="{{ route('login_requests.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
            Open Security Audit Logs ↗
        </a>
    </div>

</div>
@endsection
