<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            // Update Meta App credentials in the settings table to new credentials
            DB::table('settings')->updateOrInsert(
                ['key' => 'meta_app_id'],
                ['value' => '1665222874974029', 'group' => 'meta', 'updated_at' => now()]
            );

            DB::table('settings')->updateOrInsert(
                ['key' => 'meta_app_secret'],
                ['value' => 'bc31b883e90c3a75c31f9f2a8a5dceae', 'group' => 'meta', 'updated_at' => now()]
            );

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
