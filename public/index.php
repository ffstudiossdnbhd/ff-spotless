<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$applicationRoot = dirname(__DIR__);

/*
 * When this front controller is copied to Hostinger's public_html directory,
 * the Laravel application lives in the sibling ffspotless directory. Locally
 * and in Docker, the normal ../ paths continue to be used.
 */
if (! is_file($applicationRoot.'/vendor/autoload.php')) {
    $hostingerApplicationRoot = dirname(__DIR__).DIRECTORY_SEPARATOR.'ffspotless';

    if (is_file($hostingerApplicationRoot.'/vendor/autoload.php')) {
        $applicationRoot = $hostingerApplicationRoot;
    }
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Link: </manifest.webmanifest>; rel="manifest"', false);

try {
    // Determine if the application is in maintenance mode...
    if (file_exists($maintenance = $applicationRoot.'/storage/framework/maintenance.php')) {
        require $maintenance;
    }

    // Register the Composer autoloader...
    if (! file_exists($applicationRoot.'/vendor/autoload.php')) {
        throw new \RuntimeException("Autoloader not found at: {$applicationRoot}/vendor/autoload.php");
    }
    require $applicationRoot.'/vendor/autoload.php';

    // Bootstrap Laravel and handle the request...
    /** @var Application $app */
    $app = require_once $applicationRoot.'/bootstrap/app.php';

    $app->handleRequest(Request::capture());
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "DIAGNOSTIC ERROR: " . $e->getMessage() . "\n";
    echo "IN FILE: " . $e->getFile() . " LINE " . $e->getLine() . "\n\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
