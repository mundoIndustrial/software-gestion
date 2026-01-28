<?php
require __DIR__.'/vendor/autoload.php';
require __DIR__.'/bootstrap/app.php';

$app = app();

use App\Models\User;
use App\Models\Role;

$user = User::where('email', 'yus22@gmail.com')->first();

if($user) {
    echo "=== INFORMACIÓN DEL USUARIO ===\n";
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "roles_ids (raw): " . json_encode($user->roles_ids) . "\n";
    
    echo "\n=== ROLES ASIGNADOS ===\n";
    $roles = $user->roles;
    if($roles && $roles->count() > 0) {
        foreach($roles as $role) {
            echo "  - " . $role->name . " (ID: " . $role->id . ")\n";
        }
    } else {
        echo "  No roles assigned\n";
    }
    
    echo "\n=== VERIFICACIÓN ===\n";
    echo "Has role 'supervisor_pedidos': " . ($user->hasRole('supervisor_pedidos') ? 'YES' : 'NO') . "\n";
    echo "Has role 'asesor': " . ($user->hasRole('asesor') ? 'YES' : 'NO') . "\n";
} else {
    echo "Usuario no encontrado\n";
}
