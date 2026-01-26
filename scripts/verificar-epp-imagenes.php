#!/usr/bin/env php
<?php

/**
 * Script de verificación: Sistema EPP sin tabla epp_imagenes
 * 
 * Ejecutar desde terminal:
 * php scripts/verificar-epp-imagenes.php
 */

require __DIR__ . '/../bootstrap/app.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Cargar .env
$app->make('Illuminate\Foundation\Configuration\Env')->load();

// Ejecutar comando
$status = $kernel->call('epp:verificar-imagenes-ignorada');

exit($status);
