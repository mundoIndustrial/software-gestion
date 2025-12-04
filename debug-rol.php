<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Obtener usuario autenticado
$user = auth()->user();

if ($user) {
    echo "Usuario: " . $user->name . "\n";
    echo "Roles IDs: " . json_encode($user->roles_ids) . "\n";
    echo "Roles:\n";
    
    if (!empty($user->roles_ids)) {
        $roles = \App\Models\Role::whereIn('id', $user->roles_ids)->get();
        foreach ($roles as $role) {
            echo "  - ID: " . $role->id . ", Name: " . $role->name . "\n";
        }
    } else {
        echo "  (Sin roles asignados)\n";
    }
    
    // Probar hasRole
    echo "\nProbando hasRole:\n";
    echo "  hasRole('contador'): " . ($user->hasRole('contador') ? 'TRUE' : 'FALSE') . "\n";
    echo "  hasRole('Contador'): " . ($user->hasRole('Contador') ? 'TRUE' : 'FALSE') . "\n";
    echo "  hasRole('admin'): " . ($user->hasRole('admin') ? 'TRUE' : 'FALSE') . "\n";
    echo "  hasRole('Admin'): " . ($user->hasRole('Admin') ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "No hay usuario autenticado\n";
}

?>
