<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            // Non-destructive update of meta app credentials in the settings table
            DB::table('settings')->updateOrInsert(
                ['key' => 'meta_app_id'],
                ['value' => '2089627038309067', 'group' => 'meta', 'updated_at' => now()]
            );

            if ($secret = env('META_APP_SECRET')) {
                DB::table('settings')->updateOrInsert(
                    ['key' => 'meta_app_secret'],
                    ['value' => $secret, 'group' => 'meta', 'updated_at' => now()]
                );
            }

            DB::table('settings')->updateOrInsert(
                ['key' => 'meta_api_version'],
                ['value' => 'v20.0', 'group' => 'meta', 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Non-destructive, retain settings
    }
};
