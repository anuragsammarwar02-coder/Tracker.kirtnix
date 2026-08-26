<?php

namespace App\Http\Controllers;

use App\Models\TelegramBot;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function handle(string $secret, Request $request)
    {
        $bot = TelegramBot::where('webhook_secret', $secret)->first();

        if (!$bot) {
            return response()->json(['error' => 'Invalid webhook secret'], 403);
        }

        $payload = $request->all();
        $event = $this->telegramService->processWebhookUpdate($bot, $payload);

        return response()->json([
            'ok' => true,
            'processed' => !is_null($event),
            'event_id' => $event?->id,
        ]);
    }
}
