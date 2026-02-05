<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = \App\Models\User::find(113);
if($user) {
    echo "Usuario: " . $user->name . "\n";
    echo "ID: " . $user->id . "\n";
    echo "Role ID: " . $user->role_id . "\n";
    echo "Roles IDs: " . ($user->roles_ids ?? 'NULL') . "\n";
    
    $roleIds = json_decode($user->roles_ids ?? '[]', true) ?? [];
    if(!empty($roleIds)) {
        $roles = \App\Models\Role::whereIn('id', $roleIds)->get();
        echo "\nRoles asignados:\n";
        foreach($roles as $r) {
            echo "  - " . $r->name . " (ID: " . $r->id . ")\n";
        }
    } else {
        echo "\nSin roles en roles_ids\n";
    }
    
    if($user->role_id) {
        $mainRole = \App\Models\Role::find($user->role_id);
        if($mainRole) {
            echo "\nRol principal (legacy): " . $mainRole->name . " (ID: " . $mainRole->id . ")\n";
        }
    }
} else {
    echo "Usuario 113 no encontrado\n";
}
