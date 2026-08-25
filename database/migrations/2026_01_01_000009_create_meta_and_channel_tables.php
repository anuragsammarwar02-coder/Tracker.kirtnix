<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Meta Connections
        Schema::create('meta_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('facebook_user_id')->nullable();
            $table->string('facebook_name')->nullable();
            $table->text('access_token')->nullable(); // Encrypted / protected
            $table->string('token_type')->default('bearer');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active'); // active, expired, error, disconnected
            $table->timestamp('last_sync_at')->nullable();
            $table->string('sync_status')->default('idle'); // idle, syncing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 2. Meta Businesses
        Schema::create('meta_businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_connection_id')->constrained('meta_connections')->onDelete('cascade');
            $table->string('business_id')->unique();
            $table->string('name');
            $table->string('verification_status')->default('verified');
            $table->timestamps();
        });

        // 3. Ad Accounts
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_business_id')->nullable()->constrained('meta_businesses')->nullOnDelete();
            $table->foreignId('meta_connection_id')->nullable()->constrained('meta_connections')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('account_id')->unique(); // e.g. act_48291048291
            $table->string('name'); // e.g. KX001 - GJ, 01-HK-Focus-2026
            $table->string('currency')->default('INR'); // INR, USD
            $table->string('status')->default('Active'); // Active, Disabled, Unsettled
            $table->decimal('spend_limit', 14, 2)->default(23838.00);
            $table->decimal('balance', 14, 2)->default(828.00);
            $table->decimal('lifetime_spend', 14, 2)->default(23491.00);
            $table->decimal('active_daily_budget', 14, 2)->default(2314.00);
            $table->string('payment_method')->default('Available balance');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // 4. Update Campaigns Table with Meta details
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('ad_account_id')->nullable()->constrained('ad_accounts')->nullOnDelete();
            $table->string('campaign_id')->nullable()->index(); // Meta Campaign ID
            $table->string('outcome')->nullable(); // e.g. Subscribers, Engagement
            $table->string('objective')->nullable(); // OUTCOME_LEADS, OUTCOME_ENGAGEMENT
            $table->string('optimization_goal')->nullable(); // LINK_CLICKS, OFFSITE_CONVERSIONS, SUBSCRIBERS
            $table->string('optimization_event')->nullable(); // Lead, Subscribe, Click
            $table->string('billing_event')->nullable(); // IMPRESSIONS, LINK_CLICKS
            $table->string('conversion_location')->nullable(); // Telegram Channel, Website
            $table->decimal('active_daily_budget', 12, 2)->nullable();
            $table->integer('subscribers')->default(0);
            $table->decimal('cost_per_subscriber', 10, 2)->nullable();
        });

        // 5. Campaign Insights (Daily Time-Series Metrics from Meta)
        Schema::create('campaign_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->date('date')->index();
            $table->decimal('spend', 12, 2)->default(0);
            $table->bigInteger('reach')->default(0);
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->integer('subscribers')->default(0);
            $table->decimal('cost_per_subscriber', 10, 2)->default(0);
            $table->decimal('ctr', 6, 2)->default(0);
            $table->decimal('cpm', 10, 2)->default(0);
            $table->timestamps();
        });

        // 6. Tracked Telegram Channels (Explicit Canonical Numeric Chat IDs)
        Schema::create('telegram_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_bot_id')->constrained('telegram_bots')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('landing_page_id')->nullable()->constrained('landing_pages')->nullOnDelete();
            $table->string('telegram_chat_id')->index(); // Canonical e.g. -1001234567890
            $table->string('title'); // e.g. Gujrati_trader
            $table->string('username')->nullable()->index(); // @channelusername
            $table->string('type')->default('channel'); // channel, supergroup, group
            $table->bigInteger('member_count')->default(0);
            $table->boolean('is_bot_admin')->default(true);
            $table->string('bot_status')->default('administrator'); // administrator, member, restricted, left
            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->useCurrent();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // 7. Update Telegram Events Table with attribution source, channel & update_id idempotency
        Schema::table('telegram_events', function (Blueprint $table) {
            $table->foreignId('telegram_channel_id')->nullable()->constrained('telegram_channels')->nullOnDelete();
            $table->bigInteger('update_id')->nullable()->index();
            $table->string('source')->default('direct')->index(); // ads, direct, organic, unknown
            $table->string('country')->nullable();
            $table->string('device')->nullable();
            $table->string('tracking_token')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('telegram_events', function (Blueprint $table) {
            $table->dropForeign(['telegram_channel_id']);
            $table->dropColumn(['telegram_channel_id', 'update_id', 'source', 'country', 'device', 'tracking_token']);
        });

        Schema::dropIfExists('telegram_channels');
        Schema::dropIfExists('campaign_insights');

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['ad_account_id']);
            $table->dropColumn([
                'ad_account_id', 'campaign_id', 'outcome', 'objective',
                'optimization_goal', 'optimization_event', 'billing_event',
                'conversion_location', 'active_daily_budget', 'subscribers', 'cost_per_subscriber'
            ]);
        });

        Schema::dropIfExists('ad_accounts');
        Schema::dropIfExists('meta_businesses');
        Schema::dropIfExists('meta_connections');
    }
};
