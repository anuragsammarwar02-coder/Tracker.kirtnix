<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\TelegramEvent;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;

class TelegramBotController extends Controller
{
    public function __construct(protected TelegramService $telegramService) {}

    /**
     * Telegram Tracking & Bot Management Dashboard
     */
    public function index(Request $request): View
    {
        $bots = TelegramBot::with(['client', 'channels'])
            ->withCount(['events', 'channels'])
            ->orderBy('id', 'asc')
            ->get();

        $selectedBotId = $request->input('bot_id');
        $selectedBot = $selectedBotId ? TelegramBot::find($selectedBotId) : $bots->first();

        if ($selectedBot) {
            $this->telegramService->syncAndPersistAccessibleChannels($selectedBot);
        }

        $trackedChannels = $selectedBot
            ? TelegramChannel::where('telegram_bot_id', $selectedBot->id)->orWhereNull('telegram_bot_id')->latest('id')->get()
            : TelegramChannel::latest('id')->get();

        $liveMembers = $selectedBot
            ? TelegramEvent::with(['channel', 'campaign', 'click'])
                ->where('telegram_bot_id', $selectedBot->id)
                ->whereIn('event_type', ['join', 'leave', 'join_request', 'pending'])
                ->latest('event_time')
                ->limit(20)
                ->get()
            : TelegramEvent::with(['channel', 'campaign', 'click'])
                ->whereIn('event_type', ['join', 'leave', 'join_request', 'pending'])
                ->latest('event_time')
                ->limit(20)
                ->get();

        $clients = Client::orderBy('company_name')->get();

        return view('telegram.index', compact(
            'bots',
            'selectedBot',
            'trackedChannels',
            'liveMembers',
            'clients'
        ));
    }

    /**
     * Add new Telegram bot with real token validation.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'bot_token' => ['required', 'string'],
        ]);

        $token = trim($request->input('bot_token'));
        $tokenInfo = $this->telegramService->validateBotToken($token);

        if (!$tokenInfo['valid']) {
            return back()->with('error', $tokenInfo['error'] ?? 'Invalid Telegram Bot Token.');
        }

        $bot = TelegramBot::updateOrCreate(
            ['username' => $tokenInfo['username']],
            [
                'client_id' => $request->input('client_id'),
                'name' => $tokenInfo['first_name'],
                'username' => $tokenInfo['username'],
                'bot_token' => $token,
                'is_active' => true,
                'webhook_secret' => 'whsec_' . Str::random(28),
            ]
        );

        $result = $this->telegramService->setWebhook($bot);

        // Auto-discover and persist all accessible channels for this bot immediately upon connect
        $this->telegramService->syncAndPersistAccessibleChannels($bot);

        return redirect()->route('telegram.index', ['bot_id' => $bot->id])
            ->with('success', "Bot @{$bot->username} connected successfully! Webhook active.");
    }

    /**
     * Reconnect / Re-sync Webhook
     */
    public function syncWebhook(TelegramBot $bot): RedirectResponse
    {
        $result = $this->telegramService->setWebhook($bot);
        $this->telegramService->syncAndPersistAccessibleChannels($bot);

        if ($result['success']) {
            return back()->with('success', "Telegram Webhook for @{$bot->username} reconnected successfully!");
        }

        return back()->with('error', 'Webhook registration error: ' . ($result['message'] ?? 'Unknown'));
    }

    /**
     * Health check
     */
    public function health(TelegramBot $bot): RedirectResponse
    {
        $res = $this->telegramService->validateBotToken($bot->bot_token);
        if ($res['valid']) {
            $bot->update(['last_webhook_ping_at' => now()]);
            return back()->with('success', "Health check passed: Bot @{$bot->username} is active and connected.");
        }
        return back()->with('error', "Health check failed: " . ($res['error'] ?? 'API unresponsive'));
    }

    /**
     * Disconnect / Delete bot
     */
    public function destroy(TelegramBot $bot): RedirectResponse
    {
        $this->telegramService->deleteWebhook($bot);
        $bot->delete();

        return redirect()->route('telegram.index')->with('info', "Bot @{$bot->username} disconnected.");
    }
}
