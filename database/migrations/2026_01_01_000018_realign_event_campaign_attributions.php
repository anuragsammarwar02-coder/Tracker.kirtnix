<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('telegram_events')) {
            return;
        }

        $campaigns = DB::table('campaigns')->get();
        if ($campaigns->isEmpty()) {
            return;
        }

        // 1. Re-align Telegram Events campaign_id based on session UTMs and clicks
        $events = DB::table('telegram_events')->get();
        foreach ($events as $event) {
            $resolvedCampaignId = null;
            $utmCampaign = null;

            if ($event->cta_click_id) {
                $click = DB::table('cta_clicks')->where('id', $event->cta_click_id)->first();
                if ($click && $click->tracking_session_id) {
                    $session = DB::table('tracking_sessions')->where('id', $click->tracking_session_id)->first();
                    if ($session) {
                        $resolvedCampaignId = $session->campaign_id;
                        $utmCampaign = $session->utm_campaign;
                    }
                }
            }

            if (!$resolvedCampaignId && $utmCampaign) {
                $cleanUtm = trim(urldecode($utmCampaign));
                $slugUtm = Str::slug($cleanUtm);
                $camp = DB::table('campaigns')
                    ->where(function ($q) use ($event) {
                        if ($event->client_id) {
                            $q->where('client_id', $event->client_id);
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
                    $resolvedCampaignId = $camp->id;
                }
            }

            if ($resolvedCampaignId) {
                DB::table('telegram_events')
                    ->where('id', $event->id)
                    ->update(['campaign_id' => $resolvedCampaignId]);
            }
        }

        // 2. Re-align Conversions table as well
        if (Schema::hasTable('conversions')) {
            $conversions = DB::table('conversions')->get();
            foreach ($conversions as $conv) {
                $resolvedCampaignId = null;
                $utmCampaign = null;

                if ($conv->cta_click_id) {
                    $click = DB::table('cta_clicks')->where('id', $conv->cta_click_id)->first();
                    if ($click && $click->tracking_session_id) {
                        $session = DB::table('tracking_sessions')->where('id', $click->tracking_session_id)->first();
                        if ($session) {
                            $resolvedCampaignId = $session->campaign_id;
                            $utmCampaign = $session->utm_campaign;
                        }
                    }
                }

                if (!$resolvedCampaignId && $utmCampaign) {
                    $cleanUtm = trim(urldecode($utmCampaign));
                    $slugUtm = Str::slug($cleanUtm);
                    $camp = DB::table('campaigns')
                        ->where(function ($q) use ($conv) {
                            if ($conv->client_id) {
                                $q->where('client_id', $conv->client_id);
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
                        $resolvedCampaignId = $camp->id;
                    }
                }

                if ($resolvedCampaignId) {
                    DB::table('conversions')
                        ->where('id', $conv->id)
                        ->update(['campaign_id' => $resolvedCampaignId]);
                }
            }
        }
    }

    public function down(): void
    {
        // No down migration needed
    }
};
