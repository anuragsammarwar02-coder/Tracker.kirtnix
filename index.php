<?php

/**
 * Laravel Root Entry Point
 * Used when Hostinger Web App Hosting Output directory is set to root './'
 */

define('LARAVEL_START', microtime(true));

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri) && !is_dir(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
