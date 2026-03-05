<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CREANDO ROL 'SUPERIOR_PRODUCCION' ===" . PHP_EOL;

try {
    // 1. Verificar si ya existe
    $existingRole = \App\Models\Role::where('name', 'supervisor_produccion')->first();
    if ($existingRole) {
        echo "⚠  El rol 'supervisor_produccion' ya existe (ID: {$existingRole->id})" . PHP_EOL;
        $roleId = $existingRole->id;
    } else {
        // 2. Crear el nuevo rol
        $newRole = \App\Models\Role::create([
            'name' => 'supervisor_produccion',
            'description' => 'Supervisor de Producción - Gestión y supervisión de producción',
            'requires_credentials' => true,
        ]);
        
        echo "✅ Rol 'supervisor_produccion' creado exitosamente" . PHP_EOL;
        echo "   ID: {$newRole->id}" . PHP_EOL;
        echo "   Nombre: {$newRole->name}" . PHP_EOL;
        echo "   Descripción: {$newRole->description}" . PHP_EOL;
        $roleId = $newRole->id;
    }

    // 3. Mostrar roles actuales
    echo PHP_EOL . "ROLES ACTUALES:" . PHP_EOL;
    $roles = \App\Models\Role::orderBy('id')->get();
    foreach ($roles as $role) {
        $userCount = \App\Models\User::where('role_id', $role->id)->count();
        $marker = ($role->name === 'supervisor_produccion') ? '← NUEVO' : '';
        echo "   ID:{$role->id} - {$role->name} - {$userCount} usuarios {$marker}" . PHP_EOL;
    }

    // 4. Próximos pasos
    echo PHP_EOL . "PRÓXIMOS PASOS:" . PHP_EOL;
    echo "1. Actualizar rutas para incluir 'supervisor_produccion'" . PHP_EOL;
    echo "2. Actualizar PermissionHelper para incluir 'supervisor_produccion'" . PHP_EOL;
    echo "3. Actualizar sidebar para mostrar menú a 'supervisor_produccion'" . PHP_EOL;
    echo "4. Asignar usuarios al rol 'supervisor_produccion'" . PHP_EOL;

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== LISTO PARA CONTINUAR ===" . PHP_EOL;
