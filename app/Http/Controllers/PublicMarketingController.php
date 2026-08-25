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
        $clientCount = Client::count() ?: 18;
        $campaignCount = Campaign::count() ?: 45;
        $pageCount = LandingPage::count() ?: 62;

        return view('marketing.analytics_landing', compact('clientCount', 'campaignCount', 'pageCount'));
    }
}
