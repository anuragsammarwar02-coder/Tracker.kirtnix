<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('visitor_id')->index(); // UUID cookie
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('landing_page_id')->nullable()->constrained('landing_pages')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 32)->default('desktop')->index(); // mobile, desktop, tablet, bot
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->text('referrer')->nullable();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('fbclid')->nullable()->index();
            $table->string('gclid')->nullable()->index();
            $table->string('fbc')->nullable();
            $table->string('fbp')->nullable();
            $table->timestamps();
        });

        Schema::create('landing_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_session_id')->nullable()->constrained('tracking_sessions')->nullOnDelete();
            $table->foreignId('landing_page_id')->constrained('landing_pages')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('visitor_id')->index();
            $table->boolean('is_unique')->default(true)->index();
            $table->timestamp('viewed_at')->useCurrent()->index();
            $table->timestamps();
        });

        Schema::create('cta_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_session_id')->nullable()->constrained('tracking_sessions')->nullOnDelete();
            $table->foreignId('cta_id')->constrained('ctas')->onDelete('cascade');
            $table->foreignId('landing_page_id')->constrained('landing_pages')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('tracking_token')->index();
            $table->string('visitor_id')->index();
            $table->boolean('is_unique')->default(true)->index();
            $table->string('destination_url');
            $table->string('meta_event_id')->nullable()->index();
            $table->string('meta_capi_status')->default('skipped'); // pending, sent, failed, skipped
            $table->text('meta_capi_response')->nullable();
            $table->timestamp('clicked_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cta_clicks');
        Schema::dropIfExists('landing_page_views');
        Schema::dropIfExists('tracking_sessions');
    }
};
