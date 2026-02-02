<?php

require_once 'bootstrap/app.php';

use App\Models\User;
use App\Models\Role;

echo "=== DIAGNOSTICANDO PROBLEMA DE LOGIN CON ROL COSTURERO ===\n\n";

// 1. Verificar si el rol costurero existe
echo "1️⃣  VERIFICANDO ROL COSTURERO:\n";
$costureroRole = Role::where('name', 'costurero')->first();
if ($costureroRole) {
    echo "   ✅ Rol encontrado: ID={$costureroRole->id}, Name={$costureroRole->name}\n\n";
} else {
    echo "   ❌ Rol NO encontrado\n\n";
    exit;
}

// 2. Buscar usuarios con rol costurero
echo "2️⃣  BUSCANDO USUARIOS CON ROL COSTURERO:\n";
$users = User::all();
$costureroUsers = [];

foreach ($users as $user) {
    // Verificar roles_ids
    $rolesIds = is_array($user->roles_ids) 
        ? $user->roles_ids 
        : json_decode($user->roles_ids ?? '[]', true);
    
    if (in_array($costureroRole->id, $rolesIds)) {
        $costureroUsers[] = $user;
        echo "   ✅ Usuario: {$user->name} (ID: {$user->id})\n";
        echo "      Email: {$user->email}\n";
        echo "      roles_ids (raw): {$user->roles_ids}\n";
        echo "      roles_ids (parsed): " . json_encode($rolesIds) . "\n";
    }
}

if (empty($costureroUsers)) {
    echo "   ℹ️  No hay usuarios con rol costurero\n";
} else {
    echo "   Total: " . count($costureroUsers) . " usuario(s) encontrado(s)\n";
}

echo "\n";

// 3. Probar hasRole() con cada usuario
echo "3️⃣  PROBANDO hasRole() CON USUARIOS COSTURERO:\n";
foreach ($costureroUsers as $user) {
    echo "\n   👤 {$user->name}:\n";
    
    // Recargar el usuario
    $userReloaded = User::find($user->id);
    
    echo "      - hasRole('costurero'): " . ($userReloaded->hasRole('costurero') ? '✅ true' : '❌ false') . "\n";
    echo "      - hasRole({$costureroRole->id}): " . ($userReloaded->hasRole($costureroRole->id) ? '✅ true' : '❌ false') . "\n";
    echo "      - hasAnyRole(['costurero']): " . ($userReloaded->hasAnyRole(['costurero']) ? '✅ true' : '❌ false') . "\n";
    
    // Obtener roles actuales
    $roles = $userReloaded->roles;
    echo "      - Roles actuales: " . json_encode($roles->pluck('name')->toArray()) . "\n";
    
    // Verificar roles_ids
    echo "      - roles_ids en BD: {$userReloaded->roles_ids}\n";
}

echo "\n";

// 4. Verificar middleware OperarioAccess
echo "4️⃣  VERIFICANDO MIDDLEWARE OperarioAccess:\n";
if (count($costureroUsers) > 0) {
    $testUser = $costureroUsers[0];
    
    // Simular el middleware
    if (!$testUser->hasAnyRole(['cortador', 'costurero'])) {
        echo "   ❌ PROBLEMA: El middleware rechazaría al usuario {$testUser->name}\n";
    } else {
        echo "   ✅ El middleware permitiría al usuario {$testUser->name}\n";
    }
} else {
    echo "   ⚠️  No hay usuarios costurero para probar\n";
}

echo "\n";

// 5. Listar todos los usuarios y sus roles
echo "5️⃣  LISTADO COMPLETO DE USUARIOS Y ROLES:\n";
foreach ($users as $user) {
    $rolesIds = is_array($user->roles_ids) 
        ? $user->roles_ids 
        : json_decode($user->roles_ids ?? '[]', true);
    
    $roles = Role::whereIn('id', $rolesIds)->pluck('name')->toArray();
    $rolesStr = count($roles) > 0 ? implode(', ', $roles) : 'SIN ROL';
    
    echo "   • {$user->name} ({$user->email}): [$rolesStr]\n";
}

echo "\n✅ Diagnóstico completado\n";
