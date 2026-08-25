<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('landing_pages', 'page_source')) {
                $table->string('page_source', 32)->default('native')->after('template_type'); // native, vercel, netlify, html_upload
            }
            if (!Schema::hasColumn('landing_pages', 'external_url')) {
                $table->string('external_url')->nullable()->after('page_source');
            }
            if (!Schema::hasColumn('landing_pages', 'vercel_project_name')) {
                $table->string('vercel_project_name')->nullable()->after('external_url');
            }
            if (!Schema::hasColumn('landing_pages', 'tracking_token')) {
                $table->string('tracking_token', 64)->nullable()->index()->after('vercel_project_name');
            }
            if (!Schema::hasColumn('landing_pages', 'deployment_status')) {
                $table->string('deployment_status', 32)->default('published')->after('is_active');
            }
            if (!Schema::hasColumn('landing_pages', 'html_content')) {
                $table->longText('html_content')->nullable()->after('custom_css');
            }
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn(['page_source', 'external_url', 'vercel_project_name', 'tracking_token', 'deployment_status', 'html_content']);
        });
    }
};
