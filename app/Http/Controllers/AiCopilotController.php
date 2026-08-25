<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\AnalyticsService;
use App\Services\AiCopilotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiCopilotController extends Controller
{
    protected AnalyticsService $analyticsService;
    protected AiCopilotService $aiCopilotService;

    public function __construct(AnalyticsService $analyticsService, AiCopilotService $aiCopilotService)
    {
        $this->analyticsService = $analyticsService;
        $this->aiCopilotService = $aiCopilotService;
    }

    public function index(Request $request)
    {
        $clientId = $request->query('client_id');
        $range = $request->query('range', '7d');

        $metrics = $this->analyticsService->getMetricsSummary([
            'range' => $range,
            'client_id' => $clientId,
        ]);

        $aiAnalysis = $this->aiCopilotService->generateInsights($metrics);
        $clients = Client::orderBy('company_name')->get();

        return view('ai.index', compact('metrics', 'aiAnalysis', 'clients', 'clientId', 'range'));
    }

    public function chat(Request $request): JsonResponse
    {
        $query = $request->input('message', '');
        $clientId = $request->input('client_id');

        if (empty($query)) {
            return response()->json(['reply' => 'Please provide a question for KirtniX AI.'], 400);
        }

        $reply = $this->aiCopilotService->ask($query, $clientId ? (int)$clientId : null);

        return response()->json([
            'reply' => $reply,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
