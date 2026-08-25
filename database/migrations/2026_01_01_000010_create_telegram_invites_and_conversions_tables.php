<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Telegram Invites Table (Unique per-visitor/session generated Telegram invite link)
        if (!Schema::hasTable('telegram_invites')) {
            Schema::create('telegram_invites', function (Blueprint $table) {
                $table->id();
                $table->string('invite_link')->unique()->index();
                $table->string('invite_name')->nullable();
                $table->foreignId('tracking_session_id')->nullable()->constrained('tracking_sessions')->nullOnDelete();
                $table->foreignId('landing_page_id')->nullable()->constrained('landing_pages')->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->foreignId('telegram_bot_id')->nullable()->constrained('telegram_bots')->nullOnDelete();
                $table->foreignId('telegram_channel_id')->nullable()->constrained('telegram_channels')->nullOnDelete();
                $table->string('visitor_id')->index();
                $table->boolean('is_single_use')->default(true);
                $table->boolean('creates_join_request')->default(true);
                $table->string('status')->default('active')->index(); // active, used, expired, revoked
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Conversions Table (Verified End-to-End Attributed Telegram Conversions)
        if (!Schema::hasTable('conversions')) {
            Schema::create('conversions', function (Blueprint $table) {
                $table->id();
                $table->string('conversion_token')->unique()->index();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->foreignId('landing_page_id')->nullable()->constrained('landing_pages')->nullOnDelete();
                $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
                $table->foreignId('telegram_bot_id')->nullable()->constrained('telegram_bots')->nullOnDelete();
                $table->foreignId('telegram_channel_id')->nullable()->constrained('telegram_channels')->nullOnDelete();
                $table->foreignId('telegram_event_id')->nullable()->constrained('telegram_events')->nullOnDelete();
                $table->foreignId('tracking_session_id')->nullable()->constrained('tracking_sessions')->nullOnDelete();
                $table->foreignId('telegram_invite_id')->nullable()->constrained('telegram_invites')->nullOnDelete();
                $table->foreignId('cta_click_id')->nullable()->constrained('cta_clicks')->nullOnDelete();
                $table->string('visitor_id')->index();
                $table->string('telegram_user_id')->index();
                $table->string('telegram_username')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('event_type')->default('join')->index(); // join, join_request, approved, leave
                $table->string('status')->default('verified')->index(); // verified, pending, rejected
                $table->string('source')->default('ads')->index(); // ads, direct, organic
                $table->string('utm_source')->nullable()->index();
                $table->string('utm_medium')->nullable()->index();
                $table->string('utm_campaign')->nullable()->index();
                $table->string('utm_term')->nullable();
                $table->string('utm_content')->nullable();
                $table->string('fbclid')->nullable()->index();
                $table->string('fbc')->nullable();
                $table->string('fbp')->nullable();
                $table->string('device')->nullable();
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->string('country')->default('IN');
                $table->string('ip_hash')->nullable();
                $table->string('meta_event_id')->nullable()->index();
                $table->string('meta_capi_status')->default('pending')->index(); // pending, sent, failed, skipped
                $table->text('meta_capi_response')->nullable();
                $table->timestamp('meta_sent_at')->nullable();
                $table->integer('meta_retries')->default(0);
                $table->timestamp('event_time')->useCurrent()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
        Schema::dropIfExists('telegram_invites');
    }
};
