<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

echo "=== TEST: SIMULAR ENVÍO DE FORMULARIO ===\n\n";

// Test 1: Crear usuario con roles_ids
echo "TEST 1: Crear usuario con roles_ids\n";
echo "---\n";

$userData = [
    'name' => 'Test User ' . time(),
    'email' => 'testuser' . time() . '@example.com',
    'password' => Hash::make('password123'),
    'roles_ids' => [1, 2], // admin, contador
];

echo "Datos a crear:\n";
echo "  - name: {$userData['name']}\n";
echo "  - email: {$userData['email']}\n";
echo "  - roles_ids: " . json_encode($userData['roles_ids']) . "\n";

try {
    $user = User::create($userData);
    echo "\n✅ Usuario creado exitosamente\n";
    echo "  - ID: {$user->id}\n";
    echo "  - roles_ids guardado: " . json_encode($user->roles_ids) . "\n";
    echo "  - Roles:\n";
    foreach ($user->roles() as $role) {
        echo "    • {$role->name}\n";
    }
} catch (\Exception $e) {
    echo "\n❌ Error al crear usuario:\n";
    echo "  - " . $e->getMessage() . "\n";
}

echo "\n\n";

// Test 2: Actualizar usuario con roles_ids
echo "TEST 2: Actualizar usuario con roles_ids\n";
echo "---\n";

$user = User::find(57); // KATIA
if ($user) {
    echo "Usuario: {$user->name} (ID: {$user->id})\n";
    echo "Roles actuales: " . json_encode($user->roles_ids) . "\n";
    
    // Simular actualización
    $newRolesIds = [1, 8]; // admin, insumos
    echo "\nActualizando a: " . json_encode($newRolesIds) . "\n";
    
    try {
        $user->update([
            'name' => $user->name,
            'email' => $user->email,
            'roles_ids' => $newRolesIds,
        ]);
        
        $user->refresh();
        echo "\n✅ Usuario actualizado exitosamente\n";
        echo "  - roles_ids guardado: " . json_encode($user->roles_ids) . "\n";
        echo "  - Roles:\n";
        foreach ($user->roles() as $role) {
            echo "    • {$role->name}\n";
        }
    } catch (\Exception $e) {
        echo "\n❌ Error al actualizar usuario:\n";
        echo "  - " . $e->getMessage() . "\n";
    }
} else {
    echo "Usuario no encontrado\n";
}

echo "\n\n";

// Test 3: Verificar que los roles se guardaron correctamente
echo "TEST 3: Verificar datos en BD\n";
echo "---\n";

$user = User::find(57);
if ($user) {
    echo "Usuario: {$user->name} (ID: {$user->id})\n";
    echo "roles_ids (BD): " . json_encode($user->getRawOriginal('roles_ids')) . "\n";
    echo "roles_ids (cast): " . json_encode($user->roles_ids) . "\n";
    echo "Tipo: " . gettype($user->roles_ids) . "\n";
    echo "Count: " . count($user->roles_ids) . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
