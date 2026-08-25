<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ctas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained('landing_pages')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('name')->default('Primary Telegram CTA');
            $table->string('button_text')->default('Join Free Telegram Channel');
            $table->string('button_type')->default('primary'); // primary, secondary, floating, inline
            $table->string('tracking_token')->unique()->index(); // e.g. kx_abc123
            $table->string('telegram_destination'); // destination URL
            $table->string('direct_protocol')->default('auto'); // auto, tg_resolve, tg_join, tme_link
            $table->unsignedBigInteger('click_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ctas');
    }
};
