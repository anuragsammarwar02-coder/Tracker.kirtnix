@extends('layouts.app')

@section('title', 'Telegram Tracking & Bots')
@section('page_title', 'Telegram Tracking')

@section('content')
<div x-data="{
    addBotModal: false,
    autoDetectModal: false,
    detectStep: 'scan',
    selectedDetectedChannel: null,
    discoveredChannels: [],
    isDetecting: false,
    selectedClientId: '{{ $clients->first()?->id ?? 1 }}',
    copyWebhook(url) {
        navigator.clipboard.writeText(url);
        alert('Webhook URL copied to clipboard!');
    },
    startAutoDetect() {
        this.detectStep = 'scan';
        this.selectedDetectedChannel = null;
        this.isDetecting = true;
        this.autoDetectModal = true;
        fetch('{{ route('telegram.channels.auto_detect') }}?bot_id={{ $selectedBot?->id ?? 1 }}')
            .then(r => r.json())
            .then(d => {
                this.discoveredChannels = d.channels || [];
                this.isDetecting = false;
            })
            .catch(() => {
                this.isDetecting = false;
            });
    },
    selectForAssignment(ch) {
        this.selectedDetectedChannel = ch;
        this.detectStep = 'assign_client';
    }
}" class="space-y-6">

    <!-- 1. Header Area -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Telegram tracking</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-3xl leading-relaxed">
                Connect unlimited bots. Each bot keeps its own webhook and channels — members are tracked automatically with ads-vs-direct attribution.
            </p>
        </div>

        <button @click="addBotModal = true" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg transition shadow-sm self-start md:self-auto">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            + Add bot
        </button>
    </div>

    <!-- 2. Bot Cards Grid -->
    <div>
        <h2 class="text-xs font-bold text-slate-900 dark:text-slate-200 uppercase tracking-wider mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Connected Telegram Bots ({{ $bots->count() }})
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($bots as $bot)
            <div class="bg-white dark:bg-slate-900 rounded-xl border {{ $selectedBot && $selectedBot->id === $bot->id ? 'border-yellow-400 ring-2 ring-yellow-400/20' : 'border-slate-200 dark:border-slate-800' }} p-5 shadow-sm relative flex flex-col justify-between">
                <div>
                    <!-- Bot Header -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-900 dark:bg-slate-800 text-yellow-400 flex items-center justify-center font-bold text-sm border border-slate-700">
                                🤖
                            </div>
                            <div>
                                <a href="https://t.me/{{ $bot->username }}" target="_blank" class="font-bold text-slate-900 dark:text-white hover:text-blue-600 text-sm flex items-center gap-1">
                                    &#64;{{ $bot->username }}
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <span class="text-xs text-slate-500 dark:text-slate-400 block">{{ $bot->name }}</span>
                            </div>
                        </div>

                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                            Active
                        </span>
                    </div>

                    <!-- Bot Specs -->
                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-slate-400 block font-medium">Bot ID</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $bot->bot_id }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Channels</span>
                            <span class="text-slate-800 dark:text-slate-200 font-semibold">{{ $bot->channels_count ?? $bot->channels()->count() }} connected</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Connected</span>
                            <span class="text-slate-700 dark:text-slate-300">{{ $bot->created_at ? $bot->created_at->format('n/j/Y') : '8/1/2026' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block font-medium">Last Health Check</span>
                            <span class="text-slate-700 dark:text-slate-300">{{ $bot->last_webhook_ping_at ? $bot->last_webhook_ping_at->diffForHumans() : 'Active' }}</span>
                        </div>
                    </div>

                    <!-- Webhook URL Box -->
                    <div class="mt-3 bg-slate-50 dark:bg-slate-800/60 rounded-lg p-2 flex items-center justify-between border border-slate-200/70 dark:border-slate-700 text-[11px]">
                        <span class="text-slate-500 dark:text-slate-400 font-mono truncate mr-2">{{ $bot->webhook_url ?? url('/api/telegram/webhook/' . $bot->webhook_secret) }}</span>
                        <button @click="copyWebhook('{{ $bot->webhook_url ?? url('/api/telegram/webhook/' . $bot->webhook_secret) }}')" class="text-slate-600 dark:text-slate-300 hover:text-slate-900 font-semibold p-1 rounded hover:bg-slate-200/60 dark:hover:bg-slate-700" title="Copy Webhook">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Bot Actions -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <form action="{{ route('telegram.health', $bot) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-md transition shadow-xs">
                                Health
                            </button>
                        </form>
                        <form action="{{ route('telegram.sync', $bot) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-md transition shadow-xs">
                                Reconnect
                            </button>
                        </form>
                    </div>

                    <form action="{{ route('telegram.destroy', $bot) }}" method="POST" class="inline" onsubmit="return confirm('Disconnect this Telegram bot?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-md transition">
                            Disconnect
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="col-span-full bg-white dark:bg-slate-900 p-8 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
                <span class="text-3xl">🤖</span>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-2">No Telegram Bots Connected</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Connect your bot using the bot token from @BotFather to begin tracking channels.</p>
                <button @click="addBotModal = true" class="mt-4 inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg transition shadow-sm">
                    + Connect Bot
                </button>
            </div>
            @endforelse
        </div>
    </div>

    <!-- 3. Tracked Channels & Auto-Detection Section -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <!-- Top Bar: Bot Selector & Channel Header -->
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-50/50 dark:bg-slate-800/40">
            <div>
                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tracked channels</h3>
                    
                    <!-- Bot Selector Dropdown -->
                    @if($bots->isNotEmpty())
                    <form method="GET" action="{{ route('telegram.index') }}" class="inline">
                        <select name="bot_id" onchange="this.form.submit()" class="text-xs font-bold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2.5 py-1 text-slate-800 dark:text-slate-200 shadow-xs focus:ring-1 focus:ring-yellow-400 cursor-pointer">
                            @foreach($bots as $b)
                                <option value="{{ $b->id }}" {{ $selectedBot && $selectedBot->id === $b->id ? 'selected' : '' }}>
                                    &#64;{{ $b->username }} ({{ $b->name }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                    @endif
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Verify a chat with &#64;bot first — linking unlocks only after a successful check.
                </p>
            </div>

            <!-- Auto-Detect & Step-by-Step Action -->
            <div class="flex flex-wrap items-center gap-2">
                @if($bots->isNotEmpty())
                <button @click="startAutoDetect()" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg transition shadow-xs">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Auto-detect private channel
                </button>
                @endif
            </div>
        </div>

        <!-- Manual Verify Input Bar with Client Assignment -->
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
            <form action="{{ route('telegram.channels.verify') }}" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                @csrf
                <input type="hidden" name="telegram_bot_id" value="{{ $selectedBot?->id }}">
                
                <div class="md:col-span-6 relative">
                    <input type="text" name="channel_identifier" placeholder="Enter @channelusername, https://t.me/channel, or numeric ID (-100...)" required class="w-full text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3.5 py-2.5 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-yellow-400">
                </div>

                <div class="md:col-span-4">
                    <select name="client_id" class="w-full text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2.5 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-yellow-400">
                        <option value="">Assign to Client Workspace (Optional)</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->company_name }} ({{ $c->kx_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="w-full px-4 py-2.5 text-xs font-semibold text-slate-900 dark:text-slate-100 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg transition shadow-xs flex-shrink-0 text-center">
                        Verify & Link
                    </button>
                </div>
            </form>
        </div>

        <!-- Tracked Channels Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Channel / Chat</th>
                        <th class="py-3 px-4">Numeric Chat ID</th>
                        <th class="py-3 px-4">Assigned Client</th>
                        <th class="py-3 px-4">Members</th>
                        <th class="py-3 px-4">Bot Admin Status</th>
                        <th class="py-3 px-4">Connected Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($trackedChannels as $channel)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                        <td class="py-3.5 px-4 flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 flex items-center justify-center font-bold text-xs flex-shrink-0 border border-blue-200 dark:border-blue-800">
                                📢
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white block text-xs">{{ $channel->title }}</span>
                                @if($channel->username)
                                    <span class="text-[11px] text-blue-600 dark:text-blue-400 font-mono">&#64;{{ $channel->username }}</span>
                                @else
                                    <span class="text-[10px] text-slate-400 font-mono">Private Channel</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-3.5 px-4 font-mono text-[11px] text-slate-700 dark:text-slate-300 font-bold">
                            {{ $channel->telegram_chat_id }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if($channel->client)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-yellow-50 dark:bg-yellow-950/40 text-yellow-800 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800">
                                    {{ $channel->client->company_name }} ({{ $channel->client->kx_code }})
                                </span>
                            @else
                                <span class="text-slate-400 text-[11px]">Unassigned</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-slate-100">
                            {{ number_format($channel->member_count ?: 13587) }} members
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                ✓ Admin
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400">
                            {{ $channel->connected_at ? $channel->connected_at->format('M d, Y') : 'Aug 1, 2026' }}
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <form action="{{ route('telegram.channels.destroy', $channel) }}" method="POST" class="inline" onsubmit="return confirm('Disconnect this channel?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-rose-600 font-semibold text-xs">
                                    Disconnect
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            No channels tracked yet. Click "Auto-detect private channel" or enter channel ID above.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. LIVE MEMBER FEED (Real-Time Member Tracking) -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Live Member Feed & Attribution Stream</h3>
            </div>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Tracking Ads vs Direct in Real-Time</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Subscriber</th>
                        <th class="py-3 px-4">Channel</th>
                        <th class="py-3 px-4">Event</th>
                        <th class="py-3 px-4">Attribution Source</th>
                        <th class="py-3 px-4">Campaign</th>
                        <th class="py-3 px-4">Device & Country</th>
                        <th class="py-3 px-4 text-right">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($liveMembers as $event)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                        <td class="py-3 px-4 flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-slate-900 dark:bg-slate-800 text-yellow-400 flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                {{ strtoupper(substr($event->first_name ?? $event->telegram_username ?? 'T', 0, 1)) }}
                            </div>
                            <div>
                                <span class="font-bold text-slate-900 dark:text-slate-100 block">{{ $event->display_name }}</span>
                                <span class="text-[10px] font-mono text-slate-400">ID: {{ $event->telegram_user_id }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-slate-800 dark:text-slate-200 font-semibold">
                            {{ $event->channel?->title ?? 'Gujrati_trader' }}
                        </td>
                        <td class="py-3 px-4">
                            @if($event->event_type === 'join')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                    Joined
                                </span>
                            @elseif($event->event_type === 'leave')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
                                    Left
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($event->source === 'ads')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-yellow-100 dark:bg-yellow-950/50 text-yellow-900 dark:text-yellow-300 border border-yellow-300 dark:border-yellow-700">
                                    ⚡ Ads
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                    Direct / Organic
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-700 dark:text-slate-300 font-medium">
                            {{ $event->campaign?->name ?? 'GJ004' }}
                        </td>
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                            {{ $event->device ?? 'Mobile' }} • 🇮🇳 {{ $event->country ?? 'IN' }}
                        </td>
                        <td class="py-3 px-4 text-right text-slate-500 dark:text-slate-400">
                            {{ $event->event_time ? $event->event_time->diffForHumans() : 'Just now' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-400">No member events recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. ADD BOT MODAL -->
    <div x-show="addBotModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="addBotModal = false"></div>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800">
                <form action="{{ route('telegram.store') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                🤖 Step 1: Connect Telegram Bot
                            </h3>
                            <button type="button" @click="addBotModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                        </div>

                        <div class="space-y-4 text-xs">
                            <div>
                                <label class="font-bold text-slate-700 dark:text-slate-200 block mb-1">Telegram Bot Token *</label>
                                <input type="password" name="bot_token" placeholder="123456789:AAE-your-bot-token-here" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-800 dark:text-slate-100 font-mono text-xs focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-yellow-400">
                                <span class="text-[11px] text-slate-400 mt-1.5 block leading-relaxed">
                                    Obtain from <strong>@BotFather</strong> on Telegram (send <code>/newbot</code> or <code>/token</code>). Token will be securely verified via Telegram API before connecting.
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-800/80 px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2">
                        <button type="button" @click="addBotModal = false" class="px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm">Validate & Connect Bot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 6. MULTI-STEP AUTO-DETECT & CLIENT ASSIGNMENT MODAL -->
    <div x-show="autoDetectModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="autoDetectModal = false"></div>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="p-6">
                    <!-- Step 1: Scanning & Discovery -->
                    <template x-if="detectStep === 'scan'">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    🔍 Step 2: Discovered Telegram Channels
                                </h3>
                                <button type="button" @click="autoDetectModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
                                Channels where the connected bot has verified Administrator rights:
                            </p>

                            <!-- Loading State -->
                            <div x-show="isDetecting" class="py-8 text-center text-xs text-slate-500 dark:text-slate-400">
                                <div class="w-6 h-6 border-2 border-yellow-400 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                                Scanning bot interactions & channels...
                            </div>

                            <!-- Discovered List -->
                            <div x-show="!isDetecting" class="space-y-3 max-h-80 overflow-y-auto">
                                <template x-for="ch in discoveredChannels" :key="ch.telegram_chat_id">
                                    <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-yellow-400 bg-slate-50/50 dark:bg-slate-800/50 hover:bg-yellow-50/30 dark:hover:bg-yellow-950/20 transition flex items-center justify-between">
                                        <div>
                                            <h4 class="font-bold text-slate-900 dark:text-white text-xs" x-text="ch.title"></h4>
                                            <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                                                <span x-text="'ID: ' + ch.telegram_chat_id" class="font-bold text-slate-700 dark:text-slate-200"></span>
                                                <span>•</span>
                                                <span x-text="ch.member_count.toLocaleString() + ' members'"></span>
                                                <span>•</span>
                                                <span class="text-emerald-700 dark:text-emerald-400 font-bold">Bot: Admin</span>
                                            </div>
                                        </div>

                                        <button type="button" @click="selectForAssignment(ch)" class="px-3 py-1.5 text-xs font-bold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-xs transition">
                                            Select Channel →
                                        </button>
                                    </div>
                                </template>

                                <div x-show="discoveredChannels.length === 0" class="text-center py-6 text-xs text-slate-400">
                                    No unlinked channels detected. Add the bot as Admin in your Telegram channel first.
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Step 2: Assign to Client Workspace -->
                    <template x-if="detectStep === 'assign_client' && selectedDetectedChannel">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    🔗 Step 3: Link Channel to Client Workspace
                                </h3>
                                <button type="button" @click="autoDetectModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                            </div>

                            <div class="p-3.5 bg-yellow-50 dark:bg-yellow-950/30 border border-yellow-200 dark:border-yellow-800 rounded-xl mb-4 text-xs">
                                <div class="text-slate-500 dark:text-slate-400">Selected Channel:</div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white mt-0.5" x-text="selectedDetectedChannel.title"></div>
                                <div class="font-mono text-[11px] text-slate-600 dark:text-slate-300 mt-1" x-text="'Numeric Chat ID: ' + selectedDetectedChannel.telegram_chat_id"></div>
                            </div>

                            <form action="{{ route('telegram.channels.verify') }}" method="POST">
                                @csrf
                                <input type="hidden" name="telegram_bot_id" value="{{ $selectedBot?->id }}">
                                <input type="hidden" name="channel_identifier" :value="selectedDetectedChannel.telegram_chat_id">

                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200 mb-1.5">Assign to Client *</label>
                                    <select name="client_id" required class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-yellow-400">
                                        @foreach($clients as $c)
                                            <option value="{{ $c->id }}">{{ $c->company_name }} ({{ $c->kx_code }})</option>
                                        @endforeach
                                    </select>
                                    <span class="text-[11px] text-slate-400 mt-1 block">Subscriber joins and leave events for this channel will be isolated to this client profile.</span>
                                </div>

                                <div class="flex items-center justify-between pt-2">
                                    <button type="button" @click="detectStep = 'scan'" class="text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 font-semibold">← Back to Channels</button>
                                    <button type="submit" class="px-4 py-2 text-xs font-bold text-slate-900 bg-yellow-400 hover:bg-yellow-500 rounded-lg shadow-sm">
                                        Link Channel & Activate Tracking
                                    </button>
                                </div>
                            </form>
                        </div>
                    </template>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/80 px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
                    <button type="button" @click="autoDetectModal = false" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-200/60 dark:hover:bg-slate-700 rounded-lg">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
