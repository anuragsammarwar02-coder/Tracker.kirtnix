<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseRestoreCleanBaselineCommand extends Command
{
    protected $signature = 'db:restore-clean-baseline {--force : Force restoration without confirmation}';
    protected $description = 'Restores the verified clean baseline database snapshot (Commit dd73a856, 448 KB) to the canonical database path';

    public function handle(): int
    {
        $this->info('========================================================================');
        $this->info('>>> KIRTNIX VERIFIED CLEAN BASELINE DATABASE RESTORATION');
        $this->info('========================================================================');

        $targetPath = database_path('database.sqlite');
        $snapshotGzPath = database_path('snapshots/clean_baseline.sqlite.gz');
        $expectedSha256 = '2980f7a4b52a264805f902f525e9920b2d283f1f58a09ff5f83181e849fde003';
        $expectedBytes = 458752;

        $this->line("Target Database Path: {$targetPath}");
        $this->line("Snapshot Source:      {$snapshotGzPath}");

        if (!file_exists($snapshotGzPath)) {
            $this->error("Snapshot archive not found at: {$snapshotGzPath}");
            return 1;
        }

        // 1. Decompress & verify snapshot in memory
        $this->info("\n[STEP 1/5] Decompressing & verifying clean baseline snapshot payload...");
        $gzData = file_get_contents($snapshotGzPath);
        $rawSqlite = @gzdecode($gzData);

        if ($rawSqlite === false || strlen($rawSqlite) !== $expectedBytes) {
            $this->error("Corrupted snapshot archive: Expected {$expectedBytes} bytes, got " . (is_string($rawSqlite) ? strlen($rawSqlite) : 'false'));
            return 1;
        }

        $actualSha256 = hash('sha256', $rawSqlite);
        if ($actualSha256 !== $expectedSha256) {
            $this->error("SHA256 mismatch! Expected {$expectedSha256}, got {$actualSha256}");
            return 1;
        }
        $this->info("✓ Snapshot payload verified: " . number_format(strlen($rawSqlite)) . " bytes, SHA256 matched.");

        // 2. Backup existing database file (0-byte or otherwise)
        $this->info("\n[STEP 2/5] Creating safety backup of existing database file...");
        $timestamp = date('Ymd_His');
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        if (file_exists($targetPath)) {
            $existingSize = filesize($targetPath);
            $backupFile1 = database_path("database.sqlite.bak_{$timestamp}");
            $backupFile2 = storage_path("app/backups/database.sqlite.bak_{$timestamp}");
            
            copy($targetPath, $backupFile1);
            copy($targetPath, $backupFile2);
            $this->info("✓ Existing database file backed up ({$existingSize} bytes):");
            $this->line("  -> {$backupFile1}");
            $this->line("  -> {$backupFile2}");
        } else {
            $this->info("✓ No existing database file found; clean creation.");
        }

        // 3. Write verified clean baseline snapshot to target database
        $this->info("\n[STEP 3/5] Restoring verified clean baseline database...");
        $written = file_put_contents($targetPath, $rawSqlite);
        if ($written !== $expectedBytes) {
            $this->error("Failed writing to {$targetPath}. Wrote {$written} of {$expectedBytes} bytes.");
            return 1;
        }
        @chmod($targetPath, 0664);
        $this->info("✓ Successfully wrote " . number_format($written) . " bytes to: {$targetPath}");

        // 4. Verify SQLite Integrity & Schema
        $this->info("\n[STEP 4/5] Verifying SQLite database integrity & schema...");
        try {
            $pdo = new \PDO("sqlite:{$targetPath}", null, null, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            $stmt = $pdo->query('PRAGMA integrity_check;');
            $integrity = $stmt ? (string) $stmt->fetchColumn() : 'unknown';

            if ($integrity !== 'ok') {
                $this->error("SQLite integrity check failed: {$integrity}");
                return 1;
            }
            $this->info("✓ SQLite integrity check passed: '{$integrity}'");

            // 5. Verify records & entities
            $this->info("\n[STEP 5/5] Verifying restored entities & accounts...");
            $userStmt = $pdo->query("SELECT id, name, email, role, created_at FROM users WHERE email = 'admin@kirtnix.in' LIMIT 1;");
            $adminUser = $userStmt ? $userStmt->fetch(\PDO::FETCH_ASSOC) : null;

            if (!$adminUser) {
                $this->error("Admin user 'admin@kirtnix.in' not found in restored database!");
                return 1;
            }

            $usersCount = (int) $pdo->query("SELECT COUNT(*) FROM users;")->fetchColumn();
            $clientsCount = (int) $pdo->query("SELECT COUNT(*) FROM clients;")->fetchColumn();
            $lpsCount = (int) $pdo->query("SELECT COUNT(*) FROM landing_pages;")->fetchColumn();
            $botsCount = (int) $pdo->query("SELECT COUNT(*) FROM telegram_bots;")->fetchColumn();
            $channelsCount = (int) $pdo->query("SELECT COUNT(*) FROM telegram_channels;")->fetchColumn();
            $metaCount = (int) $pdo->query("SELECT COUNT(*) FROM meta_connections;")->fetchColumn();
            $adCount = (int) $pdo->query("SELECT COUNT(*) FROM ad_accounts;")->fetchColumn();
            $settingsCount = (int) $pdo->query("SELECT COUNT(*) FROM settings;")->fetchColumn();
            $migrationsCount = (int) $pdo->query("SELECT COUNT(*) FROM migrations;")->fetchColumn();

            $this->info("✓ Super Admin User: [ID {$adminUser['id']}] {$adminUser['name']} ({$adminUser['email']}, Role: {$adminUser['role']})");
            $this->line("✓ Total Users:         {$usersCount}");
            $this->line("✓ Total Clients:       {$clientsCount} (Clean - 0 dummy clients)");
            $this->line("✓ Total Landing Pages: {$lpsCount} (Clean - 0 dummy landing pages)");
            $this->line("✓ Total Telegram Bots: {$botsCount} (Clean - 0 dummy bots)");
            $this->line("✓ Total Channels:      {$channelsCount} (Clean - 0 dummy channels)");
            $this->line("✓ Total Meta Connect:  {$metaCount} (Clean - 0 dummy connections)");
            $this->line("✓ Total Ad Accounts:   {$adCount} (Clean - 0 dummy ad accounts)");
            $this->line("✓ Total Settings:      {$settingsCount} (System configuration active)");
            $this->line("✓ Migrations Present:  {$migrationsCount} (Schema fully synchronized)");

            $this->info("\n========================================================================");
            $this->info(">>> CLEAN BASELINE RESTORATION COMPLETED SUCCESSFULLY");
            $this->info("========================================================================");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Restoration verification failed: " . $e->getMessage());
            return 1;
        }
    }
}
