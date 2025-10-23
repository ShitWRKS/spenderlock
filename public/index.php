<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
$autoloader = __DIR__.'/../vendor/autoload.php';
if (!is_file($autoloader)) {
    $installer = __DIR__.'/installer.php';
    if (is_file($installer)) {
        require $installer;
        return;
    }

    http_response_code(503);
    echo 'Dipendenze non installate e installer non disponibile. Esegui composer install.';
    exit;
}

require $autoloader;

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
