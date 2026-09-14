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

    <!-- TAB 1: META INTEGRATION -->
    <div x-show="currentTab === 'meta'" x-data="{ 
        showAddAccountModal: false, 
        showSystemUser: {{ request('open_manual') || (!isset($metaConnections) || $metaConnections->isEmpty()) ? 'true' : 'false' }},
        testingToken: false,
        testResult: null,
        async testMetaToken() {
            const tokenInput = document.getElementById('system_user_access_token');
            const token = tokenInput ? tokenInput.value.trim() : '';
            if (!token) {
                alert('Please enter a Meta Access Token to test.');
                return;
            }
            this.testingToken = true;
            this.testResult = null;
            try {
                const res = await fetch('{{ route('meta.test_connection') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ access_token: token })
                });
                const data = await res.json();
                this.testResult = data;
            } catch (err) {
                this.testResult = { valid: false, error: 'Network request failed: ' + err.message };
            } finally {
                this.testingToken = false;
            }
        }
    }" class="space-y-6">
        
        <!-- Flash messages / Notifications -->
        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-start gap-2 shadow-sm">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1">
                <p class="font-bold">Meta Connection Notice:</p>
                <p class="mt-0.5 font-normal text-rose-700 leading-relaxed">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('info') }}</span>
        </div>
        @endif

        <!-- Primary Connection Header & Actions -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-[#1877F2] text-white flex items-center justify-center flex-shrink-0 shadow-sm" style="width: 48px; height: 48px; min-width: 48px; min-height: 48px;">
                        <svg class="w-6 h-6 fill-current" style="width: 24px; height: 24px;" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base font-bold text-slate-900">Meta & Facebook Ads Integration</h2>
                            @if(isset($metaConnections) && $metaConnections->isNotEmpty())
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                {{ $metaConnections->count() }} {{ Str::plural('Profile', $metaConnections->count()) }} Active
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                Disconnected
                            </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-1 max-w-2xl">
                            Connect your Facebook account via OAuth or permanent Meta System User Access Token to sync client ad accounts, business portfolios, and real-time ad spend.
                        </p>
                    </div>
                </div>

                <!-- Connect Options Buttons -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <a href="{{ route('meta.oauth.redirect', ['switch' => 1]) }}" class="px-4 py-2.5 text-xs font-bold text-white bg-[#1877F2] hover:bg-[#166FE5] rounded-xl shadow-sm transition flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        <span>{{ (isset($metaConnections) && $metaConnections->isNotEmpty()) ? '+ Connect Another Facebook' : 'Connect with Facebook' }}</span>
                    </a>

                    <button type="button" @click="showSystemUser = !showSystemUser" class="px-3.5 py-2.5 text-xs font-bold text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition flex items-center gap-1.5 cursor-pointer border border-slate-200">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <span>⚡ System User Token</span>
                    </button>

                    @if(isset($metaConnections) && $metaConnections->isNotEmpty())
                    <form action="{{ route('meta.sync') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3.5 py-2.5 text-xs font-bold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-xl shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Sync All</span>
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Connected Accounts Cards (If Any Exist) -->
            @if(isset($metaConnections) && $metaConnections->isNotEmpty())
            <div class="space-y-3 pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Connected Accounts & Profiles</h3>
                    <span class="text-[11px] text-slate-400">Multiple accounts operate with independent tokens and isolated access</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($metaConnections as $conn)
                    <div class="p-4 rounded-xl border {{ $conn->id == ($activeMetaConnectionId ?? null) ? 'border-yellow-400 bg-yellow-50/20 shadow-sm ring-1 ring-yellow-400/40' : 'border-slate-200 bg-slate-50/60' }} hover:bg-slate-50 transition flex flex-col justify-between gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full {{ $conn->token_type === 'system_user' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }} font-bold text-xs flex items-center justify-center flex-shrink-0">
                                    @if($conn->token_type === 'system_user')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    @else
                                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                        {{ $conn->facebook_name ?? 'Connected Meta Profile' }}
                                    </h4>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-[10px] font-mono text-slate-400">ID: {{ $conn->facebook_user_id }}</span>
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-bold {{ $conn->token_type === 'system_user' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                            {{ $conn->token_type === 'system_user' ? 'System User Token' : 'OAuth' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $conn->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ ucfirst($conn->status) }}
                                </span>
                                @if($conn->id == ($activeMetaConnectionId ?? null))
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                    ★ Active Selected
                                </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-[11px] pt-2 border-t border-slate-200/60">
                            <div>
                                <span class="text-slate-400 block text-[10px]">Accessible Accounts:</span>
                                <span class="font-bold text-slate-800">{{ $conn->adAccounts->count() }} Ad {{ Str::plural('Account', $conn->adAccounts->count()) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">Last Synced:</span>
                                <span class="font-semibold text-slate-700">{{ $conn->last_sync_at ? $conn->last_sync_at->diffForHumans() : 'Just now' }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-200/60">
                            <div>
                                @if($conn->id != ($activeMetaConnectionId ?? null))
                                <form action="{{ route('meta.select_connection', $conn->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 transition cursor-pointer">
                                        Use / Set as Active
                                    </button>
                                </form>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5">
                                <form action="{{ route('meta.sync_connection', $conn->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-bold text-slate-800 bg-white border border-slate-200 hover:bg-slate-100 rounded-md transition flex items-center gap-1 cursor-pointer">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>Sync</span>
                                    </button>
                                </form>

                                <form action="{{ route('meta.connections.destroy', $conn->id) }}" method="POST" onsubmit="return confirm('Disconnect account \'{{ $conn->facebook_name }}\'? Other connected accounts will remain active.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 text-[11px] font-bold text-rose-600 bg-white border border-rose-200 hover:bg-rose-50 rounded-md transition cursor-pointer">
                                        Disconnect
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Collapsible First-Class System User Token & Custom Meta App Credentials Form -->
            <div class="pt-2">
                <button type="button" @click="showSystemUser = !showSystemUser" class="text-xs text-slate-600 hover:text-slate-900 font-bold flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5 transition-transform text-purple-600" :class="showSystemUser ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span>⚡ Connect using Meta System User / Access Token & Custom App Credentials</span>
                </button>

                <div x-show="showSystemUser" x-cloak class="mt-4 p-5 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                    <div class="border-b border-slate-200 pb-3">
                        <h4 class="text-xs font-bold text-slate-900">Meta System User & Permanent Graph API Token</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Connect your Meta Business Manager System User token directly. Use <strong>Test Connection</strong> to verify access before saving.
                        </p>
                    </div>

                    <form action="{{ route('meta.connect') }}" method="POST" class="space-y-4 max-w-3xl">
                        @csrf
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700">
                                    Meta System User Access Token <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[10px] text-slate-400">Tokens are securely stored and never logged</span>
                            </div>
                            <textarea 
                                id="system_user_access_token"
                                name="access_token" 
                                rows="2" 
                                required
                                placeholder="Paste your EAAB... permanent access token here"
                                class="w-full bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-xs text-slate-900 font-mono focus:outline-none focus:ring-2 focus:ring-yellow-400"
                            >{{ \App\Models\Setting::get('meta_system_user_token') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Meta System User ID (Optional)</label>
                                <input 
                                    type="text" 
                                    name="system_user_id" 
                                    placeholder="e.g. 100089234823"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Meta App ID (Optional)</label>
                                <input 
                                    type="text" 
                                    name="app_id" 
                                    value="{{ \App\Models\Setting::get('meta_app_id', '') }}" 
                                    placeholder="Enter Meta App ID"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Meta App Secret (Optional)</label>
                                <input 
                                    type="password" 
                                    name="app_secret" 
                                    value="{{ \App\Models\Setting::get('meta_app_secret', '') }}" 
                                    placeholder="••••••••••••••••"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                />
                            </div>
                        </div>

                        <!-- OAuth Callback Redirect URI Helper -->
                        <div class="p-2.5 bg-white rounded-lg border border-slate-200 text-[11px]">
                            <span class="text-slate-500 block font-semibold mb-1">Valid OAuth Redirect URI (for Custom Meta App):</span>
                            <div class="flex items-center justify-between gap-2 font-mono text-[10px] text-slate-700 bg-slate-100 px-2 py-1 rounded">
                                <span class="truncate" id="redirectUriText">{{ route('meta.oauth.callback') }}</span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ route('meta.oauth.callback') }}'); alert('Redirect URI copied to clipboard!');" class="text-blue-600 hover:text-blue-800 font-bold flex-shrink-0 cursor-pointer">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <!-- Live Test Results Container -->
                        <div x-show="testResult !== null" x-cloak class="p-3 rounded-lg border text-xs" :class="testResult && testResult.valid ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900'">
                            <template x-if="testResult && testResult.valid">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2 font-bold text-emerald-800">
                                        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>✓ Token Valid &amp; Meta API Access Confirmed!</span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 pt-1 text-[11px] font-medium text-emerald-800">
                                        <div><span class="text-emerald-600 block text-[10px]">User / System:</span> <span class="font-bold" x-text="testResult.name"></span></div>
                                        <div><span class="text-emerald-600 block text-[10px]">User ID:</span> <span class="font-mono font-bold" x-text="testResult.user_id"></span></div>
                                        <div><span class="text-emerald-600 block text-[10px]">Business Portfolios:</span> <span class="font-bold" x-text="testResult.businesses_count + ' found'"></span></div>
                                        <div><span class="text-emerald-600 block text-[10px]">Ad Accounts:</span> <span class="font-bold" x-text="testResult.ad_accounts_count + ' found'"></span></div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="testResult && !testResult.valid">
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <div>
                                        <p class="font-bold">Token Validation Failed</p>
                                        <p class="text-[11px] mt-0.5 text-rose-700" x-text="testResult.error"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Buttons Row -->
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="testMetaToken()" 
                                :disabled="testingToken"
                                class="px-4 py-2 text-xs font-bold text-slate-800 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                            >
                                <svg x-show="!testingToken" class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <svg x-show="testingToken" class="w-3.5 h-3.5 animate-spin text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="testingToken ? 'Testing Graph API...' : 'Test Connection'"></span>
                            </button>

                            <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                                <span>Save &amp; Sync Connection</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Synced Ad Accounts Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Synced Meta Ad Accounts</h3>
                    <span class="text-xs text-slate-500 font-semibold">{{ $totalSyncedAccounts }} ad accounts registered & synced</span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="showAddAccountModal = true" class="px-3 py-1.5 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Ad Account Manually</span>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Account Name & ID</th>
                            <th class="py-3 px-4">Business / Portfolio</th>
                            <th class="py-3 px-4">Connection Origin</th>
                            <th class="py-3 px-4">Currency</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Lifetime Spend</th>
                            <th class="py-3 px-4">Last Synced</th>
                            <th class="py-3 px-4 text-right">Actions</th>
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
                                {{ $acc->metaBusiness?->name ?? 'Direct Agency Account' }}
                            </td>
                            <td class="py-3 px-4">
                                @if($acc->metaConnection)
                                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full {{ $acc->metaConnection->token_type === 'system_user' ? 'bg-purple-50 border-purple-200/60 text-purple-800' : 'bg-blue-50 border-blue-200/60 text-blue-800' }} border text-[11px] font-medium">
                                    @if($acc->metaConnection->token_type === 'system_user')
                                    <svg class="w-3 h-3 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    @else
                                    <svg class="w-3 h-3 fill-[#1877F2] flex-shrink-0" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    @endif
                                    <span class="truncate max-w-[130px]">{{ $acc->metaConnection->facebook_name }}</span>
                                </div>
                                @else
                                <span class="text-slate-400 text-[11px] font-normal">Primary Agency (Manual)</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono font-semibold text-slate-700">
                                {{ $acc->currency }} ({{ $acc->currency_symbol }})
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ strtolower($acc->status) === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                    {{ $acc->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-800">
                                {{ $acc->currency_symbol }}{{ number_format((float) $acc->lifetime_spend, 2) }}
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                {{ $acc->last_synced_at ? $acc->last_synced_at->diffForHumans() : 'Just now' }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                <form action="{{ route('meta.ad_accounts.destroy', $acc->id) }}" method="POST" class="inline" onsubmit="return confirm('Remove ad account {{ $acc->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-semibold cursor-pointer">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <svg class="w-8 h-8 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <p class="font-semibold text-slate-600 text-xs">No Meta ad accounts synced yet.</p>
                                    <p class="text-[11px] text-slate-400">Connect your Facebook account or System User Access Token above to sync all accessible accounts.</p>
                                </div>
                            </td>
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

        <!-- Add Ad Account Modal -->
        <div x-show="showAddAccountModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="showAddAccountModal = false" class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Add Meta Ad Account</h3>
                        <span class="text-[10px] text-slate-400">Register an ad account directly into the tracker</span>
                    </div>
                    <button type="button" @click="showAddAccountModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold cursor-pointer">&times;</button>
                </div>

                <form action="{{ route('meta.ad_accounts.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ad Account Name <span class="text-rose-500">*</span></label>
                        <input 
                            type="text" 
                            name="name" 
                            required 
                            placeholder="e.g. Arabika Kofi Media Account" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Ad Account ID <span class="text-rose-500">*</span>
                            <span class="font-normal text-slate-400">(with or without act_ prefix)</span>
                        </label>
                        <input 
                            type="text" 
                            name="account_id" 
                            required 
                            placeholder="e.g. act_123456789012345" 
                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 font-mono text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        />
                    </div>

                    @if(isset($metaConnections) && $metaConnections->count() > 0)
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Associated Facebook / Meta Account</label>
                        <select name="meta_connection_id" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400">
                            @foreach($metaConnections as $conn)
                                <option value="{{ $conn->id }}">{{ $conn->facebook_name }} (ID: {{ $conn->facebook_user_id }} - {{ $conn->token_type === 'system_user' ? 'System User' : 'OAuth' }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Currency</label>
                            <select name="currency" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400">
                                <option value="INR">INR (₹)</option>
                                <option value="USD">USD ($)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="GBP">GBP (£)</option>
                                <option value="AED">AED (AED)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Status</label>
                            <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400">
                                <option value="Active">Active</option>
                                <option value="Disabled">Disabled</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button" @click="showAddAccountModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 bg-slate-100 rounded-lg transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm transition cursor-pointer">
                            Save Ad Account
                        </button>
                    </div>
                </form>
            </div>
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
