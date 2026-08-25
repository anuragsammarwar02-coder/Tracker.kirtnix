<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Auto-initialize .env if missing on Hostinger / cloud servers
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    $exampleEnv = __DIR__ . '/../.env.example';
    if (file_exists($exampleEnv)) {
        @copy($exampleEnv, $envPath);
    } else {
        @file_put_contents($envPath, "APP_NAME=\"KirtniX TG Tracker\"\nAPP_ENV=production\nAPP_KEY=base64:r8qB7Xq0xV5yW9p3zL1m0vK4jH8tG2eF6dC4bA2s9U=\nAPP_DEBUG=false\nAPP_TIMEZONE=Asia/Kolkata\nAPP_URL=https://tracker.kirtnix.in\nDB_CONNECTION=sqlite\nSESSION_DRIVER=file\nCACHE_STORE=file\nQUEUE_CONNECTION=database\n");
    }
}

// Auto-initialize SQLite database file if missing
$sqlitePath = __DIR__ . '/../database/database.sqlite';
if (!file_exists($sqlitePath)) {
    if (!is_dir(dirname($sqlitePath))) {
        @mkdir(dirname($sqlitePath), 0777, true);
    }
    @touch($sqlitePath);
}

// Ensure all writable storage directories exist with proper permissions
$storageDirs = [
    __DIR__ . '/../storage/app',
    __DIR__ . '/../storage/framework/cache',
    __DIR__ . '/../storage/framework/cache/data',
    __DIR__ . '/../storage/framework/sessions',
    __DIR__ . '/../storage/framework/views',
    __DIR__ . '/../storage/logs',
    __DIR__ . '/../bootstrap/cache',
];
foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} else {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Kirtnix Tracker Setup</title><style>body{font-family:sans-serif;background:#090D14;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}.card{background:#121826;padding:32px;border-radius:16px;border:1px solid #1E293B;max-width:520px;text-align:center;line-height:1.6;}h1{color:#FACC15;margin-top:0;font-size:22px;}code{background:#0D121D;padding:3px 8px;border-radius:6px;color:#FACC15;font-family:monospace;}</style></head><body><div class="card"><h1>⚡ Kirtnix Tracker Setup</h1><p>Synchronizing application dependencies... Please ensure <code>vendor/</code> is uploaded or run <code>composer install</code> on your server.</p></div></body></html>';
    exit;
}

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
