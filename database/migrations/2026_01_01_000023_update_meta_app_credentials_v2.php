<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            // Update Meta App credentials in the settings table
            DB::table('settings')->updateOrInsert(
                ['key' => 'meta_app_id'],
                ['value' => '1427417489333099', 'group' => 'meta', 'updated_at' => now()]
            );

            DB::table('settings')->updateOrInsert(
                ['key' => 'meta_app_secret'],
                ['value' => '345d6529f4891af099e8116fe350b03d', 'group' => 'meta', 'updated_at' => now()]
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
