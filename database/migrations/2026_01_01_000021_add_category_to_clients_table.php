<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('clients', 'category')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('category', 100)->nullable()->default('Stock Market & Options Trading')->after('industry');
            });
        }

        // Populate category from existing industry if available
        DB::statement("UPDATE clients SET category = industry WHERE (category IS NULL OR category = '') AND industry IS NOT NULL AND industry != ''");
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'category')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
