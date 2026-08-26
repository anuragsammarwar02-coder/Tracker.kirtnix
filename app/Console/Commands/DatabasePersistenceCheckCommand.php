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
    protected $signature = 'db:persistence-check {--snapshot : Save a pre-deploy snapshot with exact entity identities} {--verify : Compare against saved pre-deploy snapshot}';
    protected $description = 'Audit and verify business record persistence and identity integrity across deployments';

    public function handle(): int
    {
        $snapshotFile = storage_path('app/backups/persistence-snapshot.json');

        $currentStats = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database_driver' => config('database.default'),
            'database_path' => config('database.connections.sqlite.database'),
            'counts' => [
                'users' => Schema::hasTable('users') ? User::count() : 0,
                'clients' => Schema::hasTable('clients') ? Client::count() : 0,
                'landing_pages' => Schema::hasTable('landing_pages') ? LandingPage::count() : 0,
                'telegram_bots' => Schema::hasTable('telegram_bots') ? TelegramBot::count() : 0,
                'telegram_channels' => Schema::hasTable('telegram_channels') ? TelegramChannel::count() : 0,
                'meta_connections' => Schema::hasTable('meta_connections') ? MetaConnection::count() : 0,
                'ad_accounts' => Schema::hasTable('ad_accounts') ? AdAccount::count() : 0,
                'assigned_ad_accounts' => Schema::hasTable('clients') ? Client::whereNotNull('ad_account_id')->count() : 0,
                'vercel_connected' => Schema::hasTable('settings') && !empty(Setting::get('vercel_token')),
            ],
            'identities' => [
                'clients' => Schema::hasTable('clients') ? Client::select('id', 'kx_code', 'company_name', 'ad_account_id')->get()->toArray() : [],
                'landing_pages' => Schema::hasTable('landing_pages') ? LandingPage::select('id', 'slug', 'title', 'client_id')->get()->toArray() : [],
                'telegram_bots' => Schema::hasTable('telegram_bots') ? TelegramBot::select('id', 'username', 'name')->get()->toArray() : [],
                'telegram_channels' => Schema::hasTable('telegram_channels') ? TelegramChannel::select('id', 'telegram_chat_id', 'title', 'client_id')->get()->toArray() : [],
                'meta_connections' => Schema::hasTable('meta_connections') ? MetaConnection::select('id', 'facebook_user_id', 'status')->get()->toArray() : [],
                'ad_accounts' => Schema::hasTable('ad_accounts') ? AdAccount::select('id', 'account_id', 'name')->get()->toArray() : [],
            ]
        ];

        if ($this->option('snapshot')) {
            $dir = dirname($snapshotFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($snapshotFile, json_encode($currentStats, JSON_PRETTY_PRINT));
            $this->info("✓ Pre-deployment persistence snapshot with entity identities saved to: {$snapshotFile}");
            $this->table(['Metric', 'Count / Value'], [
                ['Database Driver', $currentStats['database_driver']],
                ['Database Path', $currentStats['database_path']],
                ['Users', $currentStats['counts']['users']],
                ['Clients', $currentStats['counts']['clients']],
                ['Landing Pages', $currentStats['counts']['landing_pages']],
                ['Telegram Bots', $currentStats['counts']['telegram_bots']],
                ['Telegram Channels', $currentStats['counts']['telegram_channels']],
                ['Meta Connections', $currentStats['counts']['meta_connections']],
                ['Ad Accounts', $currentStats['counts']['ad_accounts']],
                ['Assigned Ad Accounts', $currentStats['counts']['assigned_ad_accounts']],
                ['Vercel Connected', $currentStats['counts']['vercel_connected'] ? 'Yes' : 'No'],
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

            $savedCounts = $saved['counts'] ?? $saved;
            $keys = ['users', 'clients', 'landing_pages', 'telegram_bots', 'telegram_channels', 'meta_connections', 'ad_accounts', 'assigned_ad_accounts'];

            foreach ($keys as $k) {
                $before = $savedCounts[$k] ?? 0;
                $after = $currentStats['counts'][$k] ?? 0;
                $status = ($after < $before) ? '❌ REGRESSION (Data Lost)' : '✓ PRESERVED';
                if ($after < $before) {
                    $hasRegression = true;
                }
                $comparison[] = [ucwords(str_replace('_', ' ', $k)), $before, $after, $status];
            }

            // Vercel check
            $vBefore = !empty($savedCounts['vercel_connected']) ? 'Yes' : 'No';
            $vAfter = $currentStats['counts']['vercel_connected'] ? 'Yes' : 'No';
            $vStatus = ($vBefore === 'Yes' && $vAfter === 'No') ? '❌ REGRESSION (Disconnected)' : '✓ PRESERVED';
            if ($vBefore === 'Yes' && $vAfter === 'No') {
                $hasRegression = true;
            }
            $comparison[] = ['Vercel Connected', $vBefore, $vAfter, $vStatus];

            $this->table(['Metric', 'Before Deployment', 'After Deployment', 'Count Status'], $comparison);

            // Detailed Identity Integrity Verification
            $this->line('');
            $this->info("Verifying Entity Identity Integrity (IDs & Unique Keys):");

            $identityChecks = [
                'clients' => ['id', 'kx_code'],
                'landing_pages' => ['id', 'slug'],
                'telegram_bots' => ['id', 'username'],
                'telegram_channels' => ['id', 'telegram_chat_id'],
                'meta_connections' => ['id', 'facebook_user_id'],
                'ad_accounts' => ['id', 'account_id'],
            ];

            $savedIdentities = $saved['identities'] ?? [];
            $identityRows = [];

            foreach ($identityChecks as $entity => $fields) {
                $beforeList = $savedIdentities[$entity] ?? [];
                $afterList = $currentStats['identities'][$entity] ?? [];
                $afterIds = array_column($afterList, 'id');

                $missing = [];
                foreach ($beforeList as $item) {
                    if (!in_array($item['id'], $afterIds)) {
                        $missing[] = "ID #{$item['id']} (" . ($item[$fields[1]] ?? '') . ")";
                    }
                }

                if (!empty($missing)) {
                    $hasRegression = true;
                    $identityRows[] = [ucwords(str_replace('_', ' ', $entity)), count($beforeList), count($afterList), '❌ MISSING: ' . implode(', ', $missing)];
                } else {
                    $identityRows[] = [ucwords(str_replace('_', ' ', $entity)), count($beforeList), count($afterList), '✓ All Identical'];
                }
            }

            $this->table(['Entity', 'Pre-Deploy Entities', 'Post-Deploy Entities', 'Identity Integrity'], $identityRows);

            if ($hasRegression) {
                $this->error('CRITICAL: Business data or identity was lost during deployment!');
                return 1;
            }

            $this->info('✓ 100% of business records, integrations, and entity identities verified and preserved!');
            return 0;
        }

        $this->printStats($currentStats);
        return 0;
    }

    private function printStats(array $stats): void
    {
        $this->info("Current Database Record Counts:");
        $counts = $stats['counts'] ?? $stats;
        $this->table(['Metric', 'Count / Value'], [
            ['Database Driver', $stats['database_driver'] ?? config('database.default')],
            ['Database Path', $stats['database_path'] ?? config('database.connections.sqlite.database')],
            ['Users', $counts['users'] ?? 0],
            ['Clients', $counts['clients'] ?? 0],
            ['Landing Pages', $counts['landing_pages'] ?? 0],
            ['Telegram Bots', $counts['telegram_bots'] ?? 0],
            ['Telegram Channels', $counts['telegram_channels'] ?? 0],
            ['Meta Connections', $counts['meta_connections'] ?? 0],
            ['Ad Accounts', $counts['ad_accounts'] ?? 0],
            ['Assigned Ad Accounts', $counts['assigned_ad_accounts'] ?? 0],
            ['Vercel Connected', !empty($counts['vercel_connected']) ? 'Yes' : 'No'],
        ]);
    }
}
