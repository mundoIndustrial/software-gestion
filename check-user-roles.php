<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\DB;

// Obtener usuario por email desde la BD sin usar modelos
$user = DB::table('users')->where('email', 'yus26@gmail.com')->first();

if (!$user) {
    echo "❌ Usuario no encontrado\n";
    exit(1);
}

echo "=== VERIFICACIÓN DE USUARIO: yus26@gmail.com ===\n\n";
echo "ID: " . $user->id . "\n";
echo "Nombre: " . $user->name . "\n";
echo "Email: " . $user->email . "\n";
echo "roles_ids (raw): " . $user->roles_ids . "\n";

// Parsear roles_ids
$rolesIds = is_array($user->roles_ids) 
    ? $user->roles_ids 
    : json_decode($user->roles_ids ?? '[]', true);

echo "roles_ids (parsed): " . json_encode($rolesIds) . "\n";

// Obtener nombres de roles
if (!empty($rolesIds)) {
    echo "\n✅ Roles asignados:\n";
    $roles = DB::table('roles')->whereIn('id', $rolesIds)->get();
    foreach ($roles as $role) {
        echo "   - ID: {$role->id}, Nombre: {$role->name}\n";
    }
} else {
    echo "\n⚠️  Sin roles asignados\n";
}

// Buscar el rol Costura-Bodega
echo "\n=== BÚSQUEDA DEL ROL COSTURA-BODEGA ===\n";
$construraRole = DB::table('roles')->where('name', 'Costura-Bodega')->first();
if ($construraRole) {
    echo "✅ Rol 'Costura-Bodega' existe:\n";
    echo "   ID: {$construraRole->id}\n";
    echo "   Nombre: {$construraRole->name}\n";
    echo "   ¿Usuario tiene este rol? " . (in_array($construraRole->id, $rolesIds) ? "SÍ ✅" : "NO ❌") . "\n";
} else {
    echo "❌ Rol 'Costura-Bodega' NO existe en la BD\n";
}

echo "\n";
