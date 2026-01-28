<?php
require __DIR__.'/bootstrap/app.php';
$app = app();

use App\Models\User;
use App\Models\Role;

$user = User::where('email', 'yus22@gmail.com')->first();

if($user) {
    echo "=== INFORMACIÓN DEL USUARIO ===\n";
    echo "Email: " . $user->email . "\n";
    echo "ID: " . $user->id . "\n";
    
    $roles = $user->roles()->pluck('name')->toArray();
    echo "Roles actuales: " . json_encode($roles) . "\n\n";
    
    // Verificar si tiene supervisor_pedidos
    if(in_array('supervisor_pedidos', $roles)) {
        echo "✓ TIENE el rol supervisor_pedidos\n";
    } else {
        echo "✗ NO TIENE el rol supervisor_pedidos\n";
        echo "\nAsignando rol supervisor_pedidos...\n";
        
        $role = Role::where('name', 'supervisor_pedidos')->first();
        if($role) {
            $user->roles()->attach($role->id);
            echo "✓ Rol asignado correctamente\n";
        } else {
            echo "✗ El rol supervisor_pedidos no existe en la BD\n";
        }
    }
} else {
    echo "✗ Usuario yus22@gmail.com no encontrado\n";
}
