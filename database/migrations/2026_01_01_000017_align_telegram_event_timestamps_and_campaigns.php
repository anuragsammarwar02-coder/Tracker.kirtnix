<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Align event_time with created_at for all telegram_events where event_time was updated to approval time
        if (Schema::hasTable('telegram_events')) {
            DB::table('telegram_events')
                ->whereColumn('event_time', '>', 'created_at')
                ->update(['event_time' => DB::raw('created_at')]);

            // 2. Backfill campaign_id on paid ads telegram events if missing
            $clients = DB::table('clients')->get();
            foreach ($clients as $client) {
                $campaign = DB::table('campaigns')
                    ->where(function ($q) use ($client) {
                        $q->where('client_id', $client->id);
                        if (!empty($client->ad_account_id)) {
                            $q->orWhere('ad_account_id', $client->ad_account_id);
                        }
                    })
                    ->whereIn('status', ['active', 'ACTIVE'])
                    ->latest('id')
                    ->first()
                    ?? DB::table('campaigns')
                        ->where(function ($q) use ($client) {
                            $q->where('client_id', $client->id);
                            if (!empty($client->ad_account_id)) {
                                $q->orWhere('ad_account_id', $client->ad_account_id);
                            }
                        })
                        ->latest('id')
                        ->first();

                if ($campaign) {
                    DB::table('telegram_events')
                        ->where('client_id', $client->id)
                        ->where('source', 'ads')
                        ->whereNull('campaign_id')
                        ->update(['campaign_id' => $campaign->id]);

                    if (Schema::hasTable('conversions')) {
                        DB::table('conversions')
                            ->where('client_id', $client->id)
                            ->where('source', 'ads')
                            ->whereNull('campaign_id')
                            ->update(['campaign_id' => $campaign->id]);
                    }
                }
            }
        }

        if (Schema::hasTable('conversions')) {
            DB::table('conversions')
                ->whereColumn('event_time', '>', 'created_at')
                ->update(['event_time' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        // No down migration needed
    }
};
