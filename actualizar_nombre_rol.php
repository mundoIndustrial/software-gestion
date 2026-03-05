<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ACTUALIZANDO NOMBRE DE ROL A 'lider_produccion' ===" . PHP_EOL;

try {
    // 1. Actualizar nombre del rol
    $role = \App\Models\Role::find(35);
    if ($role) {
        $oldName = $role->name;
        $role->name = 'lider_produccion';
        $role->save();
        
        echo "✅ Rol actualizado:" . PHP_EOL;
        echo "   Nombre anterior: '{$oldName}'" . PHP_EOL;
        echo "   Nombre nuevo: '{$role->name}'" . PHP_EOL;
        echo "   ID: {$role->id}" . PHP_EOL;
    } else {
        echo "❌ Rol con ID 35 no encontrado" . PHP_EOL;
    }

    // 2. Verificar rol actualizado
    echo PHP_EOL . "📋 VERIFICACIÓN:" . PHP_EOL;
    $updatedRole = \App\Models\Role::where('name', 'lider_produccion')->first();
    if ($updatedRole) {
        echo "   ✅ Rol 'lider_produccion' encontrado con ID: {$updatedRole->id}" . PHP_EOL;
    } else {
        echo "   ❌ Rol 'lider_produccion' no encontrado" . PHP_EOL;
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== LISTO PARA DUPLICAR PERMISOS ===" . PHP_EOL;
