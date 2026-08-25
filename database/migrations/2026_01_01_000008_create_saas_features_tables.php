<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to clients table if missing
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'kx_code')) {
                $table->string('kx_code', 32)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('clients', 'industry')) {
                $table->string('industry', 64)->default('Trading / Finance')->after('client_name');
            }
            if (!Schema::hasColumn('clients', 'meta_ads_connected')) {
                $table->boolean('meta_ads_connected')->default(true)->after('status');
            }
            if (!Schema::hasColumn('clients', 'monthly_budget')) {
                $table->decimal('monthly_budget', 12, 2)->default(0.00)->after('meta_ads_connected');
            }
            if (!Schema::hasColumn('clients', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('logo_path');
            }
        });

        // 2. Add fields to campaigns table if missing
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'reach')) {
                $table->unsignedBigInteger('reach')->default(0)->after('budget');
            }
            if (!Schema::hasColumn('campaigns', 'impressions')) {
                $table->unsignedBigInteger('impressions')->default(0)->after('reach');
            }
            if (!Schema::hasColumn('campaigns', 'spend')) {
                $table->decimal('spend', 12, 2)->default(0.00)->after('budget');
            }
        });

        // 3. Notifications table
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('type'); // tracking_issue, meta_api, telegram, conversion_failure, budget_alert, spend_anomaly, ai_recommendation
                $table->string('severity')->default('info'); // info, warning, critical
                $table->string('title');
                $table->text('message');
                $table->string('link')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Reports table
        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->string('date_range')->default('Last 7 Days');
                $table->decimal('spend', 12, 2)->default(0.00);
                $table->unsignedBigInteger('reach')->default(0);
                $table->unsignedBigInteger('views')->default(0);
                $table->unsignedBigInteger('joins')->default(0);
                $table->unsignedBigInteger('exits')->default(0);
                $table->decimal('cost_per_join', 8, 2)->default(0.00);
                $table->decimal('conversion_rate', 5, 2)->default(0.00);
                $table->text('ai_summary')->nullable();
                $table->text('ai_observations')->nullable();
                $table->text('ai_recommendations')->nullable();
                $table->text('ai_issues')->nullable();
                $table->text('ai_next_actions')->nullable();
                $table->string('status')->default('completed'); // generating, completed, draft
                $table->timestamps();
            });
        }

        // 5. Login requests / Security audit table
        if (!Schema::hasTable('login_requests')) {
            Schema::create('login_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('email');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->string('location')->nullable();
                $table->string('device')->nullable();
                $table->string('status')->default('approved'); // approved, pending, rejected
                $table->timestamp('requested_at')->useCurrent();
                $table->timestamps();
            });
        }

        // 6. Access management & Team permissions table
        if (!Schema::hasTable('team_permissions')) {
            Schema::create('team_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('role'); // owner, admin, manager, analyst, client
                $table->string('permission_key');
                $table->boolean('is_granted')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('team_permissions');
        Schema::dropIfExists('login_requests');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('notifications');
    }
};
