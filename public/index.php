<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Robust Base Path Discovery for Hostinger / Shared / Web App Hosting
$candidates = [
    __DIR__ . '/..',
    __DIR__,
    dirname(__DIR__),
    $_SERVER['DOCUMENT_ROOT'] ?? '',
    dirname($_SERVER['DOCUMENT_ROOT'] ?? ''),
];

$basePath = __DIR__ . '/..';
foreach ($candidates as $cand) {
    if (!empty($cand) && file_exists($cand . '/vendor/autoload.php')) {
        $basePath = $cand;
        break;
    }
}

// Auto-initialize .env if missing
$envPath = $basePath . '/.env';
if (!file_exists($envPath)) {
    $exampleEnv = $basePath . '/.env.example';
    if (file_exists($exampleEnv)) {
        @copy($exampleEnv, $envPath);
    } else {
        @file_put_contents($envPath, "APP_NAME=\"KirtniX TG Tracker\"\nAPP_ENV=production\nAPP_KEY=base64:r8qB7Xq0xV5yW9p3zL1m0vK4jH8tG2eF6dC4bA2s9U=\nAPP_DEBUG=false\nAPP_TIMEZONE=Asia/Kolkata\nAPP_URL=https://tracker.kirtnix.in\nDB_CONNECTION=sqlite\nSESSION_DRIVER=file\nCACHE_STORE=file\nQUEUE_CONNECTION=database\n");
    }
}

// Auto-initialize SQLite database
$sqlitePath = $basePath . '/database/database.sqlite';
if (!file_exists($sqlitePath)) {
    if (!is_dir(dirname($sqlitePath))) {
        @mkdir(dirname($sqlitePath), 0777, true);
    }
    @touch($sqlitePath);
}
@chmod($sqlitePath, 0666);
@chmod(dirname($sqlitePath), 0777);

// Ensure writable storage directories
$storageDirs = [
    $basePath . '/storage',
    $basePath . '/storage/app',
    $basePath . '/storage/framework',
    $basePath . '/storage/framework/cache',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/framework/views',
    $basePath . '/storage/logs',
    $basePath . '/bootstrap/cache',
];
foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @chmod($dir, 0777);
}

// Determine if in maintenance mode
if (file_exists($maintenance = $basePath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Autoloader
$autoloaderFound = false;
foreach ([$basePath . '/vendor/autoload.php', __DIR__ . '/../vendor/autoload.php', __DIR__ . '/vendor/autoload.php'] as $p) {
    if (file_exists($p)) {
        require $p;
        $autoloaderFound = true;
        break;
    }
}

if (!$autoloaderFound) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Kirtnix Tracker Setup</title><style>body{font-family:sans-serif;background:#090D14;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}.card{background:#121826;padding:32px;border-radius:16px;border:1px solid #1E293B;max-width:520px;text-align:center;line-height:1.6;}h1{color:#FACC15;margin-top:0;font-size:22px;}code{background:#0D121D;padding:3px 8px;border-radius:6px;color:#FACC15;font-family:monospace;}</style></head><body><div class="card"><h1>⚡ Kirtnix Tracker Setup</h1><p>In Hostinger <b>Deployments ➔ Settings</b>, change <b>Output directory</b> to <code>./</code> and click <b>Save and redeploy</b>.</p></div></body></html>';
    exit;
}

// Bootstrap Laravel
$bootstrapPath = $basePath . '/bootstrap/app.php';
if (!file_exists($bootstrapPath)) {
    $bootstrapPath = __DIR__ . '/../bootstrap/app.php';
}

(require_once $bootstrapPath)
    ->handleRequest(Request::capture());
