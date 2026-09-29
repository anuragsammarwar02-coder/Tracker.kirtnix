<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            // Landing page views
            ['landing_page_views', 'idx_lp_views_client_id', 'client_id'],
            ['landing_page_views', 'idx_lp_views_lp_id', 'landing_page_id'],
            ['landing_page_views', 'idx_lp_views_client_lp', 'client_id, landing_page_id'],
            ['landing_page_views', 'idx_lp_views_viewed_at', 'viewed_at'],

            // CTA Clicks
            ['cta_clicks', 'idx_cta_clicks_client_id', 'client_id'],
            ['cta_clicks', 'idx_cta_clicks_lp_id', 'landing_page_id'],
            ['cta_clicks', 'idx_cta_clicks_cta_id', 'cta_id'],
            ['cta_clicks', 'idx_cta_clicks_client_lp', 'client_id, landing_page_id'],
            ['cta_clicks', 'idx_cta_clicks_clicked_at', 'clicked_at'],

            // Telegram Events
            ['telegram_events', 'idx_tg_events_client_id', 'client_id'],
            ['telegram_events', 'idx_tg_events_bot_id', 'telegram_bot_id'],
            ['telegram_events', 'idx_tg_events_campaign_id', 'campaign_id'],
            ['telegram_events', 'idx_tg_events_event_type', 'event_type'],
            ['telegram_events', 'idx_tg_events_client_event', 'client_id, event_type'],
            ['telegram_events', 'idx_tg_events_event_time', 'event_time'],

            // Tracking Sessions
            ['tracking_sessions', 'idx_trk_sess_client_id', 'client_id'],
            ['tracking_sessions', 'idx_trk_sess_lp_id', 'landing_page_id'],
            ['tracking_sessions', 'idx_trk_sess_campaign_id', 'campaign_id'],

            // Telegram Invites
            ['telegram_invites', 'idx_tg_inv_client_id', 'client_id'],
            ['telegram_invites', 'idx_tg_inv_lp_id', 'landing_page_id'],
            ['telegram_invites', 'idx_tg_inv_status', 'status'],

            // Conversions
            ['conversions', 'idx_conv_client_id', 'client_id'],
            ['conversions', 'idx_conv_lp_id', 'landing_page_id'],
            ['conversions', 'idx_conv_campaign_id', 'campaign_id'],
            ['conversions', 'idx_conv_status', 'status'],
            ['conversions', 'idx_conv_event_type', 'event_type'],
            ['conversions', 'idx_conv_client_status', 'client_id, status'],

            // CTAs & Landing Pages
            ['ctas', 'idx_ctas_client_id', 'client_id'],
            ['ctas', 'idx_ctas_lp_id', 'landing_page_id'],
            ['landing_pages', 'idx_lp_client_id', 'client_id'],

            // Campaigns & Insights
            ['campaigns', 'idx_camp_client_id', 'client_id'],
            ['campaigns', 'idx_camp_ad_account_id', 'ad_account_id'],
            ['campaign_insights', 'idx_ci_campaign_id', 'campaign_id'],

            // Ad accounts, Telegram Bots & Channels, Reports & Notifications
            ['ad_accounts', 'idx_ad_accounts_client_id', 'client_id'],
            ['telegram_bots', 'idx_tg_bots_client_id', 'client_id'],
            ['telegram_channels', 'idx_tg_channels_client_id', 'client_id'],
            ['reports', 'idx_reports_client_id', 'client_id'],
            ['notifications', 'idx_notif_client_id', 'client_id'],
        ];

        foreach ($indexes as [$table, $indexName, $columns]) {
            if (Schema::hasTable($table)) {
                try {
                    DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} ({$columns});");
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
        }
    }

    public function down(): void
    {
        $indexNames = [
            'idx_lp_views_client_id', 'idx_lp_views_lp_id', 'idx_lp_views_client_lp', 'idx_lp_views_viewed_at',
            'idx_cta_clicks_client_id', 'idx_cta_clicks_lp_id', 'idx_cta_clicks_cta_id', 'idx_cta_clicks_client_lp', 'idx_cta_clicks_clicked_at',
            'idx_tg_events_client_id', 'idx_tg_events_bot_id', 'idx_tg_events_campaign_id', 'idx_tg_events_event_type', 'idx_tg_events_client_event', 'idx_tg_events_event_time',
            'idx_trk_sess_client_id', 'idx_trk_sess_lp_id', 'idx_trk_sess_campaign_id',
            'idx_tg_inv_client_id', 'idx_tg_inv_lp_id', 'idx_tg_inv_status',
            'idx_conv_client_id', 'idx_conv_lp_id', 'idx_conv_campaign_id', 'idx_conv_status', 'idx_conv_event_type', 'idx_conv_client_status',
            'idx_ctas_client_id', 'idx_ctas_lp_id', 'idx_lp_client_id',
            'idx_camp_client_id', 'idx_camp_ad_account_id', 'idx_ci_campaign_id',
            'idx_ad_accounts_client_id', 'idx_tg_bots_client_id', 'idx_tg_channels_client_id',
            'idx_reports_client_id', 'idx_notif_client_id',
        ];

        foreach ($indexNames as $indexName) {
            try {
                DB::statement("DROP INDEX IF EXISTS {$indexName};");
            } catch (\Throwable $e) {
                // Ignore
            }
        }
    }
};
