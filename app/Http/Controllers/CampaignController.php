<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with(['client'])->withCount(['landingPages', 'views', 'clicks']);

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('utm_campaign', 'like', "%{$search}%");
        }

        if ($clientId = $request->query('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $campaigns = $query->latest('id')->paginate(10)->withQueryString();
        $clients = Client::orderBy('company_name')->get();

        return view('campaigns.index', compact('campaigns', 'clients'));
    }

    public function create()
    {
        $clients = Client::where('status', 'active')->orderBy('company_name')->get();
        return view('campaigns.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:campaigns,slug'],
            'description' => ['nullable', 'string'],
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,paused,completed'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        }

        if (empty($validated['utm_campaign'])) {
            $validated['utm_campaign'] = Str::snake($validated['name']);
        }

        $campaign = Campaign::create($validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', "Campaign {$campaign->name} created successfully.");
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['client', 'landingPages.ctas']);

        $viewsCount = $campaign->views()->count();
        $clicksCount = $campaign->clicks()->count();
        $joinsCount = $campaign->telegramEvents()->where('event_type', 'join')->count();
        $leavesCount = $campaign->telegramEvents()->where('event_type', 'leave')->count();

        $ctr = $viewsCount > 0 ? round(($clicksCount / $viewsCount) * 100, 2) : 0;
        $joinRate = $clicksCount > 0 ? round(($joinsCount / $clicksCount) * 100, 2) : 0;

        return view('campaigns.show', compact(
            'campaign',
            'viewsCount',
            'clicksCount',
            'joinsCount',
            'leavesCount',
            'ctr',
            'joinRate'
        ));
    }

    public function edit(Campaign $campaign)
    {
        $clients = Client::orderBy('company_name')->get();
        return view('campaigns.edit', compact('campaign', 'clients'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:campaigns,slug,' . $campaign->id],
            'description' => ['nullable', 'string'],
            'utm_source' => ['nullable', 'string', 'max:100'],
            'utm_medium' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,paused,completed'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $campaign->update($validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', "Campaign {$campaign->name} updated successfully.");
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign archived.');
    }
}
