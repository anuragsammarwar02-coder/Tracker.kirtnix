<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Safely reset any legacy prototype defaults to 0.00 for accounts that have not recorded real campaign spend
        if (Schema::hasTable('ad_accounts')) {
            DB::table('ad_accounts')
                ->where('lifetime_spend', 23491.00)
                ->update(['lifetime_spend' => 0.00]);

            DB::table('ad_accounts')
                ->where('spend_limit', 23838.00)
                ->update(['spend_limit' => 0.00]);

            DB::table('ad_accounts')
                ->where('balance', 828.00)
                ->update(['balance' => 0.00]);

            DB::table('ad_accounts')
                ->where('active_daily_budget', 2314.00)
                ->update(['active_daily_budget' => 0.00]);
        }
    }

    public function down(): void
    {
        // No down migration needed as 0.00 is the correct base value
    }
};
