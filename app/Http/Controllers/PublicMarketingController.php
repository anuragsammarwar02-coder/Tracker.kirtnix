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
            $clientCount = Client::count();
            $campaignCount = Campaign::count();
            $pageCount = LandingPage::count();
        } catch (\Throwable $e) {
            $clientCount = 0;
            $campaignCount = 0;
            $pageCount = 0;
        }

        return view('marketing.analytics_landing', compact('clientCount', 'campaignCount', 'pageCount'));
    }
}
