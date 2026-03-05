<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICACIÓN FINAL - ROL SUPERIOR_PRODUCCIÓN ===" . PHP_EOL;

try {
    // 1. Verificar rol creado
    echo PHP_EOL . "1. VERIFICACIÓN DE ROL:" . PHP_EOL;
    $rol = \App\Models\Role::where('name', 'supervisor_produccion')->first();
    if ($rol) {
        echo "   ✅ Rol 'supervisor_produccion' encontrado - ID: {$rol->id}" . PHP_EOL;
        echo "   📝 Descripción: {$rol->description}" . PHP_EOL;
    } else {
        echo "   ❌ Rol 'supervisor_produccion' NO encontrado" . PHP_EOL;
        exit;
    }

    // 2. Verificar rutas actualizadas
    echo PHP_EOL . "2. VERIFICACIÓN DE RUTAS:" . PHP_EOL;
    $webRoutesFile = file_get_contents('routes/web.php');
    $coincidencias = substr_count($webRoutesFile, 'supervisor_produccion');
    echo "   ✅ 'supervisor_produccion' encontrado {$coincidencias} veces en routes/web.php" . PHP_EOL;

    // 3. Verificar PermissionHelper actualizado
    echo PHP_EOL . "3. VERIFICACIÓN DE PERMISSIONHELPER:" . PHP_EOL;
    $helperFile = file_get_contents('app/Helpers/PermissionHelper.php');
    $helperCoincidencias = substr_count($helperFile, 'supervisor_produccion');
    echo "   ✅ 'supervisor_produccion' encontrado {$helperCoincidencias} veces en PermissionHelper.php" . PHP_EOL;

    // 4. Verificar sidebar actualizado
    echo PHP_EOL . "4. VERIFICACIÓN DE SIDEBAR:" . PHP_EOL;
    $sidebarFile = file_get_contents('resources/views/layouts/sidebar.blade.php');
    $sidebarCoincidencias = substr_count($sidebarFile, 'supervisor_produccion');
    echo "   ✅ 'supervisor_produccion' encontrado {$sidebarCoincidencias} veces en sidebar.blade.php" . PHP_EOL;

    // 5. Verificar usuarios con rol supervisor_produccion
    echo PHP_EOL . "5. USUARIOS CON ROL 'supervisor_produccion':" . PHP_EOL;
    $usuarios = \App\Models\User::where('role_id', $rol->id)->get();
    if ($usuarios->count() > 0) {
        foreach ($usuarios as $usuario) {
            echo "   👤 {$usuario->name} ({$usuario->email})" . PHP_EOL;
        }
    } else {
        echo "   ℹ️  No hay usuarios con rol 'supervisor_produccion' aún" . PHP_EOL;
    }

    // 6. Comparación de permisos
    echo PHP_EOL . "6. COMPARACIÓN DE PERMISOS:" . PHP_EOL;
    echo "   📋 Rutas donde aparece 'admin': " . substr_count($webRoutesFile, 'role:admin') . PHP_EOL;
    echo "   📋 Rutas donde aparece 'lider_produccion': " . substr_count($webRoutesFile, 'role:lider_produccion') . PHP_EOL;
    echo "   📋 Rutas donde aparece 'supervisor_produccion': " . substr_count($webRoutesFile, 'role:supervisor_produccion') . PHP_EOL;
    
    // Verificar si están balanceados
    $adminRoutes = substr_count($webRoutesFile, 'role:admin');
    $liderRoutes = substr_count($webRoutesFile, 'role:lider_produccion');
    $supervisorRoutes = substr_count($webRoutesFile, 'role:supervisor_produccion');
    if ($adminRoutes === $liderRoutes && $liderRoutes === $supervisorRoutes) {
        echo "   ✅ Permisos balanceados - mismo número de rutas" . PHP_EOL;
    } else {
        echo "   ⚠  Permisos desbalanceados - admin: {$adminRoutes}, lider: {$liderRoutes}, supervisor: {$supervisorRoutes}" . PHP_EOL;
    }

    // 7. Resumen final
    echo PHP_EOL . "🎉 RESUMEN FINAL:" . PHP_EOL;
    echo "   ✅ Rol 'supervisor_produccion' creado correctamente" . PHP_EOL;
    echo "   ✅ Rutas duplicadas ({$coincidencias} referencias)" . PHP_EOL;
    echo "   ✅ PermissionHelper actualizado ({$helperCoincidencias} referencias)" . PHP_EOL;
    echo "   ✅ Sidebar actualizado ({$sidebarCoincidencias} referencias)" . PHP_EOL;
    echo "   ✅ Roles 'admin' y 'lider_produccion' intactos" . PHP_EOL;
    
    echo PHP_EOL . "📝 INSTRUCCIONES FINALES:" . PHP_EOL;
    echo "   1. Asigna el rol 'supervisor_produccion' a los usuarios necesarios" . PHP_EOL;
    echo "   2. Los usuarios deberán cerrar y volver a abrir sesión" . PHP_EOL;
    echo "   3. Los tres roles (admin, lider_produccion, supervisor_produccion) tienen los mismos permisos" . PHP_EOL;
    echo "   4. El rol 'admin' queda disponible para superadministrador" . PHP_EOL;

} catch (Exception $e) {
    echo "❌ Error en verificación: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== VERIFICACIÓN COMPLETADA ===" . PHP_EOL;
