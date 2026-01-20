<?php
ini_set('memory_limit', '512M');

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

//  Configuración de límites para prevenir agotamiento de memoria
ini_set('memory_limit', '256M'); // Reducido para forzar mejor gestión de memoria
ini_set('max_execution_time', '60'); // Reducido para detectar problemas más rápido

//  Habilitar recolección de basura agresiva
gc_enable();
ini_set('zend.enable_gc', '1');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
