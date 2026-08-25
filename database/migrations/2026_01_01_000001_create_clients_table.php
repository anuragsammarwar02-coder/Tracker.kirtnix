<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('client_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('status')->default('active'); // active, paused, archived
            $table->text('notes')->nullable();
            $table->string('timezone')->default('UTC');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
