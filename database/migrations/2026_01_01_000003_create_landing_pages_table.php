<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique()->index();
            $table->string('template_type')->default('forex_focus'); // forex_focus, gujarati_trader, custom
            $table->string('brand_name')->nullable();
            $table->string('brand_tagline')->nullable();
            $table->string('brand_logo_url')->nullable();
            $table->string('badge_text')->nullable();
            $table->text('hero_heading')->nullable();
            $table->text('hero_subheading')->nullable();
            $table->string('hero_video_url')->nullable();
            $table->string('hero_image_url')->nullable();
            $table->json('features_json')->nullable();
            $table->string('about_heading')->nullable();
            $table->text('about_text')->nullable();
            $table->text('disclaimer_text')->nullable();
            $table->string('footer_text')->nullable();
            $table->string('primary_cta_text')->default('Join Free Telegram Channel');
            $table->string('secondary_cta_text')->default('Open Telegram Channel');
            $table->string('telegram_destination')->nullable(); // e.g. https://t.me/+xyz or username
            $table->string('telegram_channel_username')->nullable();
            $table->string('meta_pixel_id')->nullable();
            $table->text('meta_access_token')->nullable(); // Encrypted server-side token
            $table->string('meta_test_event_code')->nullable();
            $table->string('gtm_id')->nullable();
            $table->longText('custom_head_code')->nullable();
            $table->longText('custom_css')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
