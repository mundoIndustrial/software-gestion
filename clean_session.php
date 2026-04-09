<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Session;

// Hacer que la sesión esté disponible
session_start();

// Limpiar todas las sesiones de viewed
$_SESSION = array_filter($_SESSION, function($key) {
    return strpos($key, 'viewed_') === false;
}, ARRAY_FILTER_USE_KEY);

echo "Sesión limpiada. Las notificaciones deberían mostrarse.\n";
echo "\nPara los cambios con sesión de archivo, ejecute: php artisan tinker\n";
echo "Y luego: Session::flush()\n";
