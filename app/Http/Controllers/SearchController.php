<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\LandingPage;
use App\Models\Campaign;
use App\Models\Report;
use App\Models\TelegramBot;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 1) {
            return response()->json(['results' => []]);
        }

        $results = [];

        // Clients
        $clients = Client::where('company_name', 'like', "%{$q}%")
            ->orWhere('client_name', 'like', "%{$q}%")
            ->orWhere('kx_code', 'like', "%{$q}%")
            ->orWhere('email', 'like', "%{$q}%")
            ->limit(4)
            ->get();

        foreach ($clients as $c) {
            $results[] = [
                'type' => 'Client',
                'title' => "{$c->company_name} ({$c->client_name})",
                'subtitle' => "Code: {$c->kx_code} · {$c->industry}",
                'url' => route('clients.show', $c),
                'badge' => $c->kx_code,
            ];
        }

        // Landing Pages
        $pages = LandingPage::where('title', 'like', "%{$q}%")
            ->orWhere('slug', 'like', "%{$q}%")
            ->orWhere('brand_name', 'like', "%{$q}%")
            ->limit(4)
            ->get();

        foreach ($pages as $p) {
            $results[] = [
                'type' => 'Landing Page',
                'title' => $p->title,
                'subtitle' => "/lp/{$p->slug} · " . ($p->client?->company_name ?? 'Agency'),
                'url' => route('landing-pages.show', $p),
                'badge' => 'LP',
            ];
        }

        // Campaigns
        $campaigns = Campaign::where('name', 'like', "%{$q}%")
            ->orWhere('utm_campaign', 'like', "%{$q}%")
            ->orWhere('slug', 'like', "%{$q}%")
            ->limit(4)
            ->get();

        foreach ($campaigns as $camp) {
            $results[] = [
                'type' => 'Campaign',
                'title' => $camp->name,
                'subtitle' => "UTM: {$camp->utm_campaign} · Budget: \${$camp->budget}",
                'url' => route('campaigns.show', $camp),
                'badge' => 'Ads',
            ];
        }

        // Reports
        $reports = Report::where('title', 'like', "%{$q}%")
            ->limit(3)
            ->get();

        foreach ($reports as $rep) {
            $results[] = [
                'type' => 'Report',
                'title' => $rep->title,
                'subtitle' => "Joins: {$rep->joins} · Cost/Join: \${$rep->cost_per_join}",
                'url' => route('reports.index', ['client_id' => $rep->client_id]),
                'badge' => 'Audit',
            ];
        }

        return response()->json(['results' => $results]);
    }
}
