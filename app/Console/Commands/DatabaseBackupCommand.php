<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup {--keep=20 : Number of latest backups to retain}';
    protected $description = 'Create a verified, timestamped backup of the production database';

    public function handle(): int
    {
        $this->info('Starting production database backup...');

        $driver = config('database.default');

        if ($driver === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');

            if (!$dbPath || $dbPath === ':memory:') {
                $this->error('In-memory SQLite database cannot be backed up to disk.');
                return 1;
            }

            if (!file_exists($dbPath)) {
                $this->error("Database file not found at: {$dbPath}");
                return 1;
            }

            $backupDir = storage_path('app/backups');
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = date('Y-m-d-His');
            $backupFilename = "database-backup-{$timestamp}.sqlite";
            $backupPath = $backupDir . DIRECTORY_SEPARATOR . $backupFilename;

            // Copy file to backup destination
            if (!copy($dbPath, $backupPath)) {
                $this->error("Failed to copy database to: {$backupPath}");
                return 1;
            }

            // Verify integrity of backup
            try {
                $pdo = new \PDO("sqlite:{$backupPath}");
                $stmt = $pdo->query('PRAGMA integrity_check;');
                $result = $stmt->fetchColumn();

                if ($result !== 'ok') {
                    $this->error("Database backup integrity check failed: {$result}");
                    unlink($backupPath);
                    return 1;
                }
            } catch (\Throwable $e) {
                $this->error('Failed to verify backup integrity: ' . $e->getMessage());
                if (file_exists($backupPath)) {
                    unlink($backupPath);
                }
                return 1;
            }

            $backupSize = filesize($backupPath);
            $this->info("✓ Database backup created and verified successfully!");
            $this->line("  File: {$backupPath}");
            $this->line("  Size: " . number_format($backupSize) . " bytes");
            $this->line("  Integrity: OK");

            // Rotate / clean older backups
            $keep = (int) $this->option('keep');
            $files = glob($backupDir . DIRECTORY_SEPARATOR . 'database-backup-*.sqlite');
            if ($files && count($files) > $keep) {
                rsort($files);
                $toDelete = array_slice($files, $keep);
                foreach ($toDelete as $oldBackup) {
                    @unlink($oldBackup);
                }
                $this->line("  Cleaned older backups (retained {$keep} most recent).");
            }

            return 0;
        }

        $this->info("Non-sqlite driver detected ({$driver}). External backup recommended.");
        return 0;
    }
}
