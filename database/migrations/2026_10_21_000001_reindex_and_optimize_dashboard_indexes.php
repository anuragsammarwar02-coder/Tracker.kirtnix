<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rebuild indexes to heal corrupted indexes (e.g. conversions_event_time_index)
        try {
            DB::statement('PRAGMA busy_timeout = 30000;');
            DB::statement('REINDEX conversions;');
            DB::statement('REINDEX telegram_events;');
            DB::statement('REINDEX;');
        } catch (\Throwable $e) {
            // Ignore if driver/environment does not support REINDEX
        }

        // 2. High-performance composite indexes for rapid Dashboard aggregation & reporting
        $indexes = [
            // Conversions: Fast filtering by client, event_type, status, and time
            ['conversions', 'idx_conv_client_event_time', 'client_id, event_time'],
            ['conversions', 'idx_conv_type_event_time', 'event_type, event_time'],
            ['conversions', 'idx_conv_status_event_time', 'status, event_time'],

            // Telegram Events: Fast time-range and client aggregation
            ['telegram_events', 'idx_tg_events_client_time', 'client_id, event_time'],
            ['telegram_events', 'idx_tg_events_type_time', 'event_type, event_time'],

            // Landing Page Views: Fast client & time aggregation
            ['landing_page_views', 'idx_lp_views_client_time', 'client_id, viewed_at'],

            // CTA Clicks: Fast client & time aggregation
            ['cta_clicks', 'idx_cta_clicks_client_time', 'client_id, clicked_at'],

            // Tracking Sessions: Accelerate device & UTM breakdown queries
            ['tracking_sessions', 'idx_trk_sess_created_at', 'created_at'],
            ['tracking_sessions', 'idx_trk_sess_client_created', 'client_id, created_at'],
            ['tracking_sessions', 'idx_trk_sess_utm_source', 'utm_source, created_at'],
        ];

        foreach ($indexes as [$table, $indexName, $columns]) {
            if (Schema::hasTable($table)) {
                try {
                    DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} ({$columns});");
                } catch (\Throwable $e) {
                    // Ignore if already exists or not supported
                }
            }
        }
    }

    public function down(): void
    {
        $indexNames = [
            'idx_conv_client_event_time',
            'idx_conv_type_event_time',
            'idx_conv_status_event_time',
            'idx_tg_events_client_time',
            'idx_tg_events_type_time',
            'idx_lp_views_client_time',
            'idx_cta_clicks_client_time',
            'idx_trk_sess_created_at',
            'idx_trk_sess_client_created',
            'idx_trk_sess_utm_source',
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
