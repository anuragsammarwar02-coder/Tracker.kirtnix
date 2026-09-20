<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tracking_sessions')) {
            return;
        }

        // Re-align tracking_sessions campaign_id from their utm_campaign
        $sessions = DB::table('tracking_sessions')
            ->whereNotNull('utm_campaign')
            ->get();

        foreach ($sessions as $session) {
            $utmCampaign = trim($session->utm_campaign);
            $cleanUtm = trim(urldecode($utmCampaign));
            $slugUtm = Str::slug($cleanUtm);

            $camp = DB::table('campaigns')
                ->where(function ($q) use ($session) {
                    if ($session->client_id) {
                        $q->where('client_id', $session->client_id);
                    }
                })
                ->where(function ($q) use ($utmCampaign, $cleanUtm, $slugUtm) {
                    $q->where('utm_campaign', $utmCampaign)
                      ->orWhere('name', $utmCampaign)
                      ->orWhere('name', $cleanUtm)
                      ->orWhere('name', 'like', "%{$cleanUtm}%")
                      ->orWhere('campaign_id', $utmCampaign)
                      ->orWhere('campaign_id', 'cmp_' . $utmCampaign)
                      ->orWhere('slug', $slugUtm)
                      ->orWhere('slug', $utmCampaign);
                })
                ->first();

            if ($camp) {
                DB::table('tracking_sessions')
                    ->where('id', $session->id)
                    ->update(['campaign_id' => $camp->id]);

                // Also update matching cta_clicks
                DB::table('cta_clicks')
                    ->where('tracking_session_id', $session->id)
                    ->update(['campaign_id' => $camp->id]);
            }
        }

        // Now re-run telegram_events re-alignment
        if (Schema::hasTable('telegram_events')) {
            $events = DB::table('telegram_events')->get();
            foreach ($events as $event) {
                $resolvedCampaignId = null;

                if ($event->cta_click_id) {
                    $click = DB::table('cta_clicks')->where('id', $event->cta_click_id)->first();
                    if ($click && $click->campaign_id) {
                        $resolvedCampaignId = $click->campaign_id;
                    }
                }

                if ($resolvedCampaignId) {
                    DB::table('telegram_events')
                        ->where('id', $event->id)
                        ->update(['campaign_id' => $resolvedCampaignId]);
                }
            }
        }
    }

    public function down(): void
    {
    }
};
