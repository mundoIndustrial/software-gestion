<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

// Test 1: Cargar usuario
$user = User::find(57);
echo "=== TEST 1: CARGAR USUARIO ===\n";
echo "Usuario: {$user->name} (ID: {$user->id})\n";
echo "roles_ids actual: " . json_encode($user->roles_ids) . "\n";
echo "Tipo: " . gettype($user->roles_ids) . "\n";
echo "\n";

// Test 2: Intentar agregar rol directamente
echo "=== TEST 2: AGREGAR ROL DIRECTAMENTE ===\n";
$user->roles_ids = [8, 1];
echo "roles_ids después de asignar: " . json_encode($user->roles_ids) . "\n";
$user->save();
echo "Guardado\n";

// Test 3: Recargar y verificar
$user->refresh();
echo "\n=== TEST 3: VERIFICAR DESPUÉS DE GUARDAR ===\n";
echo "roles_ids después de refresh: " . json_encode($user->roles_ids) . "\n";
echo "Roles:\n";
foreach ($user->roles() as $role) {
    echo "  • {$role->name}\n";
}
