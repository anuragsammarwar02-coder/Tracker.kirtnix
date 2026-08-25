<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('name');
            $table->string('username');
            $table->text('bot_token'); // Encrypted token
            $table->string('channel_id')->nullable(); // Target Telegram chat/channel ID
            $table->string('channel_title')->nullable();
            $table->string('channel_username')->nullable();
            $table->string('webhook_secret')->unique();
            $table->string('webhook_url')->nullable();
            $table->boolean('is_webhook_active')->default(false);
            $table->timestamp('last_webhook_ping_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('telegram_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_bot_id')->nullable()->constrained('telegram_bots')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('cta_click_id')->nullable()->constrained('cta_clicks')->nullOnDelete();
            $table->string('telegram_user_id')->index();
            $table->string('telegram_username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('event_type')->index(); // join, leave, backout, status_change
            $table->string('invite_link')->nullable()->index();
            $table->string('status_before')->nullable();
            $table->string('status_after')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('event_time')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_events');
        Schema::dropIfExists('telegram_bots');
    }
};
