<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Client;
use App\Models\LandingPage;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\MetaConnection;
use App\Models\AdAccount;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class DatabasePersistenceCheckCommand extends Command
{
    protected $signature = 'db:persistence-check {--snapshot : Save a pre-deploy snapshot} {--verify : Compare against saved pre-deploy snapshot}';
    protected $description = 'Audit and verify business record persistence across deployments and server restarts';

    public function handle(): int
    {
        $snapshotFile = storage_path('app/backups/persistence-snapshot.json');

        $currentStats = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database_driver' => config('database.default'),
            'database_path' => config('database.connections.sqlite.database'),
            'users' => Schema::hasTable('users') ? User::count() : 0,
            'clients' => Schema::hasTable('clients') ? Client::count() : 0,
            'landing_pages' => Schema::hasTable('landing_pages') ? LandingPage::count() : 0,
            'telegram_bots' => Schema::hasTable('telegram_bots') ? TelegramBot::count() : 0,
            'telegram_channels' => Schema::hasTable('telegram_channels') ? TelegramChannel::count() : 0,
            'meta_connections' => Schema::hasTable('meta_connections') ? MetaConnection::count() : 0,
            'ad_accounts' => Schema::hasTable('ad_accounts') ? AdAccount::count() : 0,
            'assigned_ad_accounts' => Schema::hasTable('clients') ? Client::whereNotNull('ad_account_id')->count() : 0,
            'vercel_connected' => Schema::hasTable('settings') && !empty(Setting::get('vercel_token')),
        ];

        if ($this->option('snapshot')) {
            $dir = dirname($snapshotFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($snapshotFile, json_encode($currentStats, JSON_PRETTY_PRINT));
            $this->info("✓ Pre-deployment persistence snapshot saved to: {$snapshotFile}");
            $this->table(['Metric', 'Count / Value'], [
                ['Users', $currentStats['users']],
                ['Clients', $currentStats['clients']],
                ['Landing Pages', $currentStats['landing_pages']],
                ['Telegram Bots', $currentStats['telegram_bots']],
                ['Telegram Channels', $currentStats['telegram_channels']],
                ['Meta Connections', $currentStats['meta_connections']],
                ['Ad Accounts', $currentStats['ad_accounts']],
                ['Assigned Ad Accounts', $currentStats['assigned_ad_accounts']],
                ['Vercel Connected', $currentStats['vercel_connected'] ? 'Yes' : 'No'],
            ]);
            return 0;
        }

        if ($this->option('verify')) {
            if (!file_exists($snapshotFile)) {
                $this->warn("No previous snapshot found at {$snapshotFile}. Printing current state.");
                $this->printStats($currentStats);
                return 0;
            }

            $saved = json_decode(file_get_contents($snapshotFile), true);
            $hasRegression = false;
            $comparison = [];

            $keys = ['users', 'clients', 'landing_pages', 'telegram_bots', 'telegram_channels', 'meta_connections', 'ad_accounts', 'assigned_ad_accounts'];

            foreach ($keys as $k) {
                $before = $saved[$k] ?? 0;
                $after = $currentStats[$k] ?? 0;
                $status = ($after < $before) ? '❌ REGRESSION (Data Lost)' : '✓ PRESERVED';
                if ($after < $before) {
                    $hasRegression = true;
                }
                $comparison[] = [ucwords(str_replace('_', ' ', $k)), $before, $after, $status];
            }

            // Vercel check
            $vBefore = !empty($saved['vercel_connected']) ? 'Yes' : 'No';
            $vAfter = $currentStats['vercel_connected'] ? 'Yes' : 'No';
            $vStatus = ($vBefore === 'Yes' && $vAfter === 'No') ? '❌ REGRESSION (Disconnected)' : '✓ PRESERVED';
            if ($vBefore === 'Yes' && $vAfter === 'No') {
                $hasRegression = true;
            }
            $comparison[] = ['Vercel Connected', $vBefore, $vAfter, $vStatus];

            $this->table(['Metric', 'Before Deployment', 'After Deployment', 'Status'], $comparison);

            if ($hasRegression) {
                $this->error('CRITICAL: Business data was lost or disconnected during deployment!');
                return 1;
            }

            $this->info('✓ All business records and integrations successfully verified and preserved!');
            return 0;
        }

        $this->printStats($currentStats);
        return 0;
    }

    private function printStats(array $stats): void
    {
        $this->info("Current Database Record Counts:");
        $this->table(['Metric', 'Count / Value'], [
            ['Database Driver', $stats['database_driver']],
            ['Database Path', $stats['database_path']],
            ['Users', $stats['users']],
            ['Clients', $stats['clients']],
            ['Landing Pages', $stats['landing_pages']],
            ['Telegram Bots', $stats['telegram_bots']],
            ['Telegram Channels', $stats['telegram_channels']],
            ['Meta Connections', $stats['meta_connections']],
            ['Ad Accounts', $stats['ad_accounts']],
            ['Assigned Ad Accounts', $stats['assigned_ad_accounts']],
            ['Vercel Connected', $stats['vercel_connected'] ? 'Yes' : 'No'],
        ]);
    }
}
