<?php

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/bootstrap/app.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Obtener todos los usuarios con rol supervisor_pedidos
$users = \App\Models\User::whereJsonContains('roles_ids', function($query) {
    // Obtener el ID del rol supervisor_pedidos
    $role = \App\Models\Role::where('name', 'supervisor_pedidos')->first();
    return $role ? $role->id : null;
})->get();

echo "=== USUARIOS CON ROL supervisor_pedidos ===\n";
if($users->count() > 0) {
    foreach($users as $user) {
        echo "Email: " . $user->email . " | ID: " . $user->id . "\n";
    }
} else {
    echo "⚠️ NO HAY USUARIOS CON ROL supervisor_pedidos\n\n";
    
    // Buscar el rol
    $role = \App\Models\Role::where('name', 'supervisor_pedidos')->first();
    if($role) {
        echo "✓ El rol 'supervisor_pedidos' EXISTE (ID: " . $role->id . ")\n\n";
    } else {
        echo "✗ El rol 'supervisor_pedidos' NO EXISTE en la base de datos\n\n";
    }
}

// Mostrar todos los roles disponibles
echo "=== TODOS LOS ROLES DISPONIBLES ===\n";
$roles = \App\Models\Role::all();
foreach($roles as $role) {
    echo "- " . $role->name . " (ID: " . $role->id . ")\n";
}

// Mostrar usuarios supervisores
echo "\n=== USUARIOS CON 'supervisor' EN SUS EMAILS ===\n";
$supervisors = \App\Models\User::where('email', 'like', '%supervisor%')->get();
foreach($supervisors as $sup) {
    $roles = $sup->roles()->pluck('name')->toArray();
    echo "Email: " . $sup->email . " | Roles: " . json_encode($roles) . "\n";
}
