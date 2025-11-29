<?php

/**
 * Script para crear un usuario con rol insumos
 * 
 * Uso: php crear_usuario_insumos.php
 * 
 * Este script requiere que Laravel esté configurado correctamente
 * Ejecutar desde la raíz del proyecto
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Crear usuario de prueba con rol insumos
$user = User::updateOrCreate(
    ['email' => 'insumos@mundoindustrial.co'],
    [
        'name' => 'Usuario Insumos',
        'email' => 'insumos@mundoindustrial.co',
        'password' => Hash::make('insumos123456'),
        'role' => 'insumos',
        'email_verified_at' => now(),
    ]
);

echo "✓ Usuario creado/actualizado correctamente\n";
echo "Email: insumos@mundoindustrial.co\n";
echo "Contraseña: insumos123456\n";
echo "Rol: insumos\n";
