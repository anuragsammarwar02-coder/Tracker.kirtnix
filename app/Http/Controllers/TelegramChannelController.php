<?php

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TelegramChannelController extends Controller
{
    public function __construct(protected TelegramService $telegramService) {}

    /**
     * Auto-detect private and public channels for selected bot.
     */
    public function autoDetect(Request $request): JsonResponse
    {
        $botId = $request->input('bot_id');
        $bot = TelegramBot::find($botId) ?? TelegramBot::first();

        if (!$bot) {
            return response()->json([
                'success' => false,
                'message' => 'No active Telegram bot found. Please add a bot first.',
                'channels' => [],
            ], 404);
        }

        $discovered = $this->telegramService->discoverAccessibleChannels($bot);

        return response()->json([
            'success' => true,
            'bot_username' => $bot->username,
            'channels' => $discovered,
        ]);
    }

    /**
     * Verify and connect a Telegram channel.
     */
    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'telegram_bot_id' => 'nullable|exists:telegram_bots,id',
            'channel_identifier' => 'required|string',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $bot = TelegramBot::find($request->input('telegram_bot_id')) ?? TelegramBot::first();
        if (!$bot) {
            return back()->with('error', 'Please connect a Telegram Bot before verifying a channel.');
        }

        $res = $this->telegramService->verifyChannel(
            $bot, 
            $request->input('channel_identifier'),
            $request->input('client_id') ? (int) $request->input('client_id') : null
        );

        if ($request->wantsJson()) {
            return response()->json($res);
        }

        if ($res['success']) {
            return back()->with('success', $res['message']);
        }

        return back()->with('error', $res['message'] ?? 'Failed to verify channel.');
    }

    /**
     * Disconnect a tracked channel.
     */
    public function destroy(TelegramChannel $channel): RedirectResponse
    {
        $channel->delete();
        return back()->with('info', 'Tracked channel disconnected successfully.');
    }
}
