<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\LandingPage;
use App\Models\Campaign;
use Illuminate\Http\Request;

class PublicMarketingController extends Controller
{
    public function home()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }
        return $this->analytics();
    }

    public function analytics()
    {
        try {
            $clientCount = Client::count() ?: 18;
            $campaignCount = Campaign::count() ?: 45;
            $pageCount = LandingPage::count() ?: 62;
        } catch (\Throwable $e) {
            $clientCount = 18;
            $campaignCount = 45;
            $pageCount = 62;
        }

        return view('marketing.analytics_landing', compact('clientCount', 'campaignCount', 'pageCount'));
    }
}
