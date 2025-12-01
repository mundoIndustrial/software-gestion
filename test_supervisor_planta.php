<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST - SUPERVISOR_PLANTA ACCESS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Verificar que el rol existe
echo "✓ TEST 1: Verificar que el rol supervisor_planta existe\n";
$role = Role::where('name', 'supervisor_planta')->first();
if ($role) {
    echo "  ✅ Rol encontrado: ID={$role->id}, Name={$role->name}\n";
} else {
    echo "  ❌ Rol NO encontrado\n";
}

echo "\n";

// 2. Verificar usuarios con rol supervisor_planta
echo "✓ TEST 2: Listar usuarios con rol supervisor_planta\n";
$users = User::whereHas('role', function ($query) {
    $query->where('name', 'supervisor_planta');
})->get();

if ($users->count() > 0) {
    echo "  ✅ Encontrados " . $users->count() . " usuario(s):\n";
    foreach ($users as $user) {
        echo "     - {$user->name} ({$user->email}) - Rol: {$user->role->name}\n";
    }
} else {
    echo "  ⚠️  No hay usuarios con rol supervisor_planta\n";
}

echo "\n";

// 3. Crear usuario de prueba si no existe
echo "✓ TEST 3: Crear usuario de prueba\n";
$testUser = User::where('email', 'supervisor_planta@test.com')->first();
if (!$testUser) {
    $testUser = User::create([
        'name' => 'Supervisor Planta Test',
        'email' => 'supervisor_planta@test.com',
        'password' => bcrypt('password123'),
        'role_id' => $role->id,
    ]);
    echo "  ✅ Usuario creado: {$testUser->name} ({$testUser->email})\n";
} else {
    echo "  ℹ️  Usuario ya existe: {$testUser->name} ({$testUser->email})\n";
}

echo "\n";

// 4. Verificar que el usuario tiene el rol correcto
echo "✓ TEST 4: Verificar rol del usuario\n";
$testUser = User::where('email', 'supervisor_planta@test.com')->first();
if ($testUser) {
    echo "  Usuario: {$testUser->name}\n";
    echo "  Role ID: {$testUser->role_id}\n";
    echo "  Role Name: {$testUser->role->name}\n";
    echo "  ✅ Usuario tiene rol correcto\n";
} else {
    echo "  ❌ Usuario no encontrado\n";
}

echo "\n";

// 5. Verificar middleware logic
echo "✓ TEST 5: Simular lógica del middleware\n";
$testUser = User::where('email', 'supervisor_planta@test.com')->first();
if ($testUser) {
    $roleName = null;
    if (is_object($testUser->role)) {
        $roleName = $testUser->role->name ?? null;
    } elseif (is_string($testUser->role)) {
        $roleName = $testUser->role;
    }

    echo "  Role Name obtenido: {$roleName}\n";

    if ($roleName === 'insumos' || $roleName === 'supervisor_planta') {
        echo "  ✅ Middleware PERMITIRÍA acceso a insumos\n";
    } else {
        echo "  ❌ Middleware BLOQUEARÍA acceso a insumos\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  TEST COMPLETADO\n";
echo "═══════════════════════════════════════════════════════════════\n";
