<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\CampaignInsight;
use App\Models\Client;
use App\Models\MetaConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::with(['adAccount.metaBusiness'])->withCount(['landingPages', 'campaigns', 'views', 'clicks']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('kx_code', 'like', "%{$search}%")
                  ->orWhere('industry', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $clients = $query->latest('id')->paginate(12)->withQueryString();
        $availableAdAccounts = AdAccount::with('metaBusiness')->orderBy('name')->get();
        $hasGlobalMetaConnection = MetaConnection::where('status', 'active')->exists();

        return view('clients.index', compact('clients', 'availableAdAccounts', 'hasGlobalMetaConnection'));
    }

    public function create()
    {
        $count = Client::count() + 1;
        $suggestedKxCode = 'KX-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        while (Client::where('kx_code', $suggestedKxCode)->exists()) {
            $count++;
            $suggestedKxCode = 'KX-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        }
        $availableAdAccounts = AdAccount::with('metaBusiness')->orderBy('name')->get();
        $hasGlobalMetaConnection = MetaConnection::where('status', 'active')->exists();

        return view('clients.create', compact('suggestedKxCode', 'availableAdAccounts', 'hasGlobalMetaConnection'));
    }

    public function store(Request $request)
    {
        if ($request->filled('kx_code')) {
            // Clean up any soft-deleted legacy record holding this code
            Client::withTrashed()->where('kx_code', trim($request->kx_code))->whereNotNull('deleted_at')->forceDelete();
        }

        $validated = $request->validate([
            'kx_code' => ['nullable', 'string', 'max:32', \Illuminate\Validation\Rule::unique('clients', 'kx_code')->whereNull('deleted_at')],
            'company_name' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'meta_ads_connected' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'status' => ['required', 'in:active,paused,archived'],
            'notes' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
        ]);

        if (empty($validated['kx_code'])) {
            $count = Client::count() + 1;
            $code = 'KX-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            while (Client::where('kx_code', $code)->exists()) {
                $count++;
                $code = 'KX-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }
            $validated['kx_code'] = $code;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('clients/logos', 'public');
            $validated['logo_path'] = $path;
        }

        $adAccountId = $request->input('ad_account_id');
        $validated['ad_account_id'] = $adAccountId ?: null;
        $validated['meta_ads_connected'] = !empty($adAccountId) || $request->has('meta_ads_connected');

        $client = Client::create($validated);

        if ($adAccountId) {
            AdAccount::where('id', $adAccountId)->update(['client_id' => $client->id]);
        }

        return redirect()->route('clients.show', $client)
            ->with('success', "Client {$client->company_name} ({$client->kx_code}) onboarded successfully.");
    }

    public function show(Client $client)
    {
        $client->load([
            'adAccount.metaBusiness',
            'adAccount.campaigns.insights',
            'landingPages.ctas',
            'landingPages.views',
            'landingPages.clicks',
            'campaigns',
            'telegramBots',
            'reports',
            'clicks',
            'telegramEvents',
            'views',
        ]);

        $viewsCount = $client->views()->count();
        $clicksCount = $client->clicks()->count();
        $joinsCount = $client->telegramEvents()->whereIn('event_type', ['join', 'join_request'])->count();
        $leavesCount = $client->telegramEvents()->where('event_type', 'leave')->count();

        // Scoped Meta Ads Account Details and Real Data Metrics
        $assignedAdAccount = $client->adAccount;
        $metaMetrics = [
            'connected' => false,
            'account_name' => 'Not Assigned',
            'account_id' => 'None',
            'business_name' => 'Personal / Agency',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'status' => 'Inactive',
            'timezone' => 'Asia/Kolkata',
            'last_sync' => 'Never',
            'spend_today' => 0.00,
            'spend_month' => 0.00,
            'spend_total' => 0.00,
            'clicks' => 0,
            'impressions' => 0,
            'reach' => 0,
            'leads' => 0,
            'ctr' => 0.00,
            'cpc' => 0.00,
            'cpm' => 0.00,
            'roas' => 0.00,
            'campaigns_count' => 0,
        ];

        if ($assignedAdAccount) {
            $metaMetrics = app(\App\Services\MetaSyncService::class)->getAdAccountMetrics($assignedAdAccount);
        }

        $ctr = $viewsCount > 0 ? round(($clicksCount / $viewsCount) * 100, 2) : 0.00;
        $joinRate = $clicksCount > 0 ? round(($joinsCount / $clicksCount) * 100, 2) : 0.00;
        $costPerJoin = $joinsCount > 0 ? round(($metaMetrics['spend_total'] ?? 0.00) / $joinsCount, 2) : 0.00;

        $availableAdAccounts = AdAccount::with('metaBusiness')->orderBy('name')->get();
        $hasGlobalMetaConnection = MetaConnection::where('status', 'active')->exists();

        return view('clients.show', compact(
            'client',
            'viewsCount',
            'clicksCount',
            'joinsCount',
            'leavesCount',
            'ctr',
            'joinRate',
            'costPerJoin',
            'assignedAdAccount',
            'metaMetrics',
            'availableAdAccounts',
            'hasGlobalMetaConnection'
        ));
    }

    public function edit(Client $client)
    {
        $availableAdAccounts = AdAccount::with('metaBusiness')->orderBy('name')->get();
        $hasGlobalMetaConnection = MetaConnection::where('status', 'active')->exists();

        return view('clients.edit', compact('client', 'availableAdAccounts', 'hasGlobalMetaConnection'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'kx_code' => ['required', 'string', 'max:32', \Illuminate\Validation\Rule::unique('clients', 'kx_code')->ignore($client->id)->whereNull('deleted_at')],
            'company_name' => ['required', 'string', 'max:255'],
            'client_name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
            'meta_ads_connected' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'status' => ['required', 'in:active,paused,archived'],
            'notes' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('logo')) {
            if ($client->logo_path && !str_starts_with($client->logo_path, 'assets/')) {
                Storage::disk('public')->delete($client->logo_path);
            }
            $path = $request->file('logo')->store('clients/logos', 'public');
            $validated['logo_path'] = $path;
        }

        $adAccountId = $request->input('ad_account_id');
        $validated['ad_account_id'] = $adAccountId ?: null;
        $validated['meta_ads_connected'] = !empty($adAccountId) || $request->has('meta_ads_connected');

        $client->update($validated);

        if ($adAccountId) {
            AdAccount::where('id', $adAccountId)->update(['client_id' => $client->id]);
            AdAccount::where('client_id', $client->id)->where('id', '!=', $adAccountId)->update(['client_id' => null]);
        } else {
            AdAccount::where('client_id', $client->id)->update(['client_id' => null]);
        }

        return redirect()->route('clients.show', $client)
            ->with('success', "Client {$client->company_name} updated successfully.");
    }

    /**
     * Dedicated action to assign or change Meta Ad Account from client overview.
     */
    public function assignAdAccount(Request $request, Client $client)
    {
        $validated = $request->validate([
            'ad_account_id' => ['nullable', 'exists:ad_accounts,id'],
        ]);

        $adAccountId = $validated['ad_account_id'] ?? null;

        if ($adAccountId) {
            $adAccount = AdAccount::findOrFail($adAccountId);
            $client->update([
                'ad_account_id' => $adAccountId,
                'meta_ads_connected' => true,
            ]);

            // Sync bi-directional reference
            AdAccount::where('id', $adAccountId)->update(['client_id' => $client->id]);
            AdAccount::where('client_id', $client->id)->where('id', '!=', $adAccountId)->update(['client_id' => null]);

            return back()->with('success', "Meta Ad Account '{$adAccount->name}' ({$adAccount->account_id}) assigned to {$client->company_name} successfully.");
        }

        // Unassign Ad Account
        $client->update([
            'ad_account_id' => null,
            'meta_ads_connected' => false,
        ]);
        AdAccount::where('client_id', $client->id)->update(['client_id' => null]);

        return back()->with('success', "Meta Ad Account unassigned from {$client->company_name}.");
    }

    public function destroy(Client $client)
    {
        $name = $client->company_name;
        $clientId = $client->id;

        DB::transaction(function () use ($client, $clientId) {
            // 1. Delete associated Landing Pages and their CTAs, Views, Clicks, Invites
            $landingPageIds = \App\Models\LandingPage::where('client_id', $clientId)->pluck('id');
            \App\Models\LandingPageView::whereIn('landing_page_id', $landingPageIds)->orWhere('client_id', $clientId)->delete();
            \App\Models\CtaClick::whereIn('landing_page_id', $landingPageIds)->orWhere('client_id', $clientId)->delete();
            \App\Models\Cta::whereIn('landing_page_id', $landingPageIds)->orWhere('client_id', $clientId)->delete();
            \App\Models\TelegramInvite::whereIn('landing_page_id', $landingPageIds)->orWhere('client_id', $clientId)->delete();
            \App\Models\LandingPage::where('client_id', $clientId)->delete();

            // 2. Delete Campaigns and Insights
            $campaignIds = Campaign::where('client_id', $clientId)->pluck('id');
            CampaignInsight::whereIn('campaign_id', $campaignIds)->delete();
            Campaign::where('client_id', $clientId)->delete();

            // 3. Delete Telegram Bots, Channels, Events & Conversions
            \App\Models\TelegramEvent::where('client_id', $clientId)->delete();
            \App\Models\Conversion::where('client_id', $clientId)->delete();
            \App\Models\TelegramChannel::where('client_id', $clientId)->delete();
            \App\Models\TelegramBot::where('client_id', $clientId)->delete();

            // 4. Delete Tracking Sessions, Reports, Notifications
            \App\Models\TrackingSession::where('client_id', $clientId)->delete();
            \App\Models\Report::where('client_id', $clientId)->delete();
            \App\Models\Notification::where('client_id', $clientId)->delete();

            // 5. Unassign Ad Accounts
            AdAccount::where('client_id', $clientId)->update(['client_id' => null]);

            // 6. Delete the client permanently
            $client->forceDelete();
        });

        return redirect()->route('clients.index')
            ->with('success', "Client '{$name}' and all associated tracking data, campaigns & landing pages removed successfully.");
    }
}

