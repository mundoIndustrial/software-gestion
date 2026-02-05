<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICANDO ROLES Y USUARIOS ===\n\n";

echo "1. Todos los roles en la BD:\n";
$roles = \App\Models\Role::all();
foreach ($roles as $role) {
    echo "   ID: {$role->id}, Nombre: {$role->name}\n";
}

echo "\n2. Usuarios y sus roles:\n";
$users = \App\Models\User::limit(5)->get();
foreach ($users as $user) {
    echo "\n   Usuario: {$user->name} (ID: {$user->id})\n";
    echo "   roles_ids: " . json_encode($user->roles_ids) . "\n";
    
    $rolesIds = is_array($user->roles_ids) ? $user->roles_ids : json_decode($user->roles_ids ?? '[]', true);
    if (!empty($rolesIds)) {
        $userRoles = \App\Models\Role::whereIn('id', $rolesIds)->get();
        echo "   Roles asignados:\n";
        foreach ($userRoles as $role) {
            echo "     - {$role->name}\n";
        }
    } else {
        echo "   (Sin roles asignados)\n";
    }
}

echo "\n";
