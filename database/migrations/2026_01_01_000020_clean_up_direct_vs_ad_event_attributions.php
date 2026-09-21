<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('telegram_events')) {
            return;
        }

        // 1. Find duplicated cta_click_ids across events and keep only the earliest
        $events = DB::table('telegram_events')
            ->whereNotNull('cta_click_id')
            ->orderBy('id', 'asc')
            ->get();

        $seenClicks = [];
        foreach ($events as $event) {
            $clickId = $event->cta_click_id;

            // If click already used by earlier event, this event was a direct join that stole the click
            if (isset($seenClicks[$clickId])) {
                DB::table('telegram_events')
                    ->where('id', $event->id)
                    ->update([
                        'cta_click_id' => null,
                        'source' => 'direct',
                        'campaign_id' => null,
                    ]);
                continue;
            }

            // Check if the click's session was actually an ad session
            $click = DB::table('cta_clicks')->where('id', $clickId)->first();
            $isAd = false;
            if ($click && $click->tracking_session_id) {
                $session = DB::table('tracking_sessions')->where('id', $click->tracking_session_id)->first();
                if ($session) {
                    $utmSrc = strtolower($session->utm_source ?? '');
                    $utmMed = strtolower($session->utm_medium ?? '');
                    $hasFbclid = !empty($session->fbclid);
                    $hasCamp = !empty($session->utm_campaign) || !empty($session->campaign_id);
                    $isAdSource = in_array($utmSrc, ['meta', 'facebook', 'fb', 'ig', 'instagram', 'ads', 'paid']);
                    $isAdMedium = in_array($utmMed, ['cpc', 'paid', 'ads', 'cpm']);

                    if ($hasFbclid || $hasCamp || $isAdSource || $isAdMedium) {
                        $isAd = true;
                    }
                }
            }

            if ($isAd) {
                $seenClicks[$clickId] = $event->id;
            } else {
                DB::table('telegram_events')
                    ->where('id', $event->id)
                    ->update([
                        'cta_click_id' => null,
                        'source' => 'direct',
                        'campaign_id' => null,
                    ]);
            }
        }

        // 2. Events without any cta_click_id MUST be direct
        DB::table('telegram_events')
            ->whereNull('cta_click_id')
            ->where('source', '!=', 'direct')
            ->update([
                'source' => 'direct',
                'campaign_id' => null,
            ]);

        // 3. For leave events, inherit from user's join event in same channel
        $leaveEvents = DB::table('telegram_events')
            ->where('event_type', 'leave')
            ->get();

        foreach ($leaveEvents as $leave) {
            $joinEvent = DB::table('telegram_events')
                ->where('telegram_channel_id', $leave->telegram_channel_id)
                ->where('telegram_user_id', $leave->telegram_user_id)
                ->whereIn('event_type', ['join', 'join_request'])
                ->where('id', '<', $leave->id)
                ->latest('id')
                ->first();

            if ($joinEvent) {
                DB::table('telegram_events')
                    ->where('id', $leave->id)
                    ->update([
                        'source' => $joinEvent->source ?: 'direct',
                        'campaign_id' => $joinEvent->campaign_id,
                        'cta_click_id' => $joinEvent->cta_click_id,
                    ]);
            } else {
                DB::table('telegram_events')
                    ->where('id', $leave->id)
                    ->update([
                        'source' => 'direct',
                        'campaign_id' => null,
                        'cta_click_id' => null,
                    ]);
            }
        }

        // 4. Conversions alignment
        if (Schema::hasTable('conversions')) {
            $conversions = DB::table('conversions')->get();
            foreach ($conversions as $conv) {
                if ($conv->telegram_event_id) {
                    $ev = DB::table('telegram_events')->where('id', $conv->telegram_event_id)->first();
                    if ($ev) {
                        DB::table('conversions')
                            ->where('id', $conv->id)
                            ->update([
                                'source' => $ev->source,
                                'campaign_id' => $ev->campaign_id,
                                'cta_click_id' => $ev->cta_click_id,
                            ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
    }
};
