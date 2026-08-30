<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('landing_pages', 'blocks_json')) {
                $table->longText('blocks_json')->nullable()->after('features_json');
            }
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            if (Schema::hasColumn('landing_pages', 'blocks_json')) {
                $table->dropColumn('blocks_json');
            }
        });
    }
};
