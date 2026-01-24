#!/usr/bin/env php
<?php
/**
 * Script para validar que todos los controladores referenciados en rutas existan
 */

$baseControllerPath = '/app/Http/Controllers/';
$baseInfraPath = '/app/Infrastructure/Http/Controllers/';

// Lista de controladores que se usan en las rutas (extraída manualmente)
$controladores = [
    // App\Http\Controllers\*
    'ProfileController' => 'Http/Controllers/ProfileController.php',
    'UserController' => 'Http/Controllers/UserController.php',
    'RegistroOrdenController' => 'Http/Controllers/RegistroOrdenController.php',
    'RegistroOrdenQueryController' => 'Http/Controllers/RegistroOrdenQueryController.php',
    'RegistroBodegaController' => 'Http/Controllers/RegistroBodegaController.php',
    'ConfiguracionController' => 'Http/Controllers/ConfiguracionController.php',
    'DashboardController' => 'Http/Controllers/DashboardController.php',
    'EntregaController' => 'Http/Controllers/EntregaController.php',
    'TablerosController' => 'Http/Controllers/TablerosController.php',
    'VistasController' => 'Http/Controllers/VistasController.php',
    'BalanceoController' => 'Http/Controllers/BalanceoController.php',
    'DebugRegistrosController' => 'Http/Controllers/DebugRegistrosController.php',
    'StorageController' => 'Http/Controllers/StorageController.php',
    'NotificationController' => 'Http/Controllers/NotificationController.php',
    'ContadorController' => 'Http/Controllers/ContadorController.php',
    'SupervisorPedidosController' => 'Http/Controllers/SupervisorPedidosController.php',
    'InvoiceController' => 'Http/Controllers/InvoiceController.php',
    'PDFCotizacionController' => 'Http/Controllers/PDFCotizacionController.php',
    'PrendaController' => 'Http/Controllers/PrendaController.php',

    // App\Http\Controllers\Auth\*
    'GoogleAuthController' => 'Http/Controllers/Auth/GoogleAuthController.php',
    'AuthenticatedSessionController' => 'Http/Controllers/Auth/AuthenticatedSessionController.php',
    'ConfirmablePasswordController' => 'Http/Controllers/Auth/ConfirmablePasswordController.php',
    'EmailVerificationNotificationController' => 'Http/Controllers/Auth/EmailVerificationNotificationController.php',
    'EmailVerificationPromptController' => 'Http/Controllers/Auth/EmailVerificationPromptController.php',
    'NewPasswordController' => 'Http/Controllers/Auth/NewPasswordController.php',
    'PasswordController' => 'Http/Controllers/Auth/PasswordController.php',
    'PasswordResetLinkController' => 'Http/Controllers/Auth/PasswordResetLinkController.php',
    'RegisteredUserController' => 'Http/Controllers/Auth/RegisteredUserController.php',
    'VerifyEmailController' => 'Http/Controllers/Auth/VerifyEmailController.php',

    // App\Http\Controllers\Api_temp\*
    'ProcesosController' => 'Http/Controllers/Api_temp/ProcesosController.php',
    'PedidoController' => 'Http/Controllers/Api_temp/PedidoController.php',
    'HorarioController' => 'Http/Controllers/Api_temp/HorarioController.php',
    'FestivosController' => 'Http/Controllers/Api_temp/FestivosController.php',
    'AsistenciaDetalladaController' => 'Http/Controllers/Api_temp/AsistenciaDetalladaController.php',
    'PersonalController' => 'Http/Controllers/Api_temp/PersonalController.php',

    // App\Http\Controllers\Api_temp\V1\*
    'OrdenController' => 'Http/Controllers/Api_temp/V1/OrdenController.php',

    // App\Http\Controllers\Insumos\*
    'InsumosController' => 'Http/Controllers/Insumos/InsumosController.php',

    // App\Infrastructure\Http\Controllers\*
    'CotizacionPrendaController' => 'Infrastructure/Http/Controllers/CotizacionPrendaController.php',
    'CotizacionBordadoController' => 'Infrastructure/Http/Controllers/CotizacionBordadoController.php',
    'CotizacionController' => 'Infrastructure/Http/Controllers/CotizacionController.php',
    'AsistenciaPersonalController' => 'Infrastructure/Http/Controllers/AsistenciaPersonalController.php',
    'AsistenciaPersonalWebController' => 'Infrastructure/Http/Controllers/AsistenciaPersonalWebController.php',

    // App\Infrastructure\Http\Controllers\Asesores\*
    'AsesoresController' => 'Infrastructure/Http/Controllers/Asesores/AsesoresController.php',
    'AsesoresAPIController' => 'Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php',
    'CotizacionesViewController' => 'Infrastructure/Http/Controllers/Asesores/CotizacionesViewController.php',
    'CotizacionesFiltrosController' => 'Infrastructure/Http/Controllers/Asesores/CotizacionesFiltrosController.php',
    'ReciboController' => 'Infrastructure/Http/Controllers/Asesores/ReciboController.php',
    'CrearPedidoEditableController' => 'Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php',
    'PedidosProduccionController' => 'Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php',

    // App\Infrastructure\Http\Controllers\Despacho\*
    'DespachoController' => 'Infrastructure/Http/Controllers/Despacho/DespachoController.php',

    // App\Infrastructure\Http\Controllers\Cotizaciones\*
    'ImagenBorradorController' => 'Infrastructure/Http/Controllers/Cotizaciones/ImagenBorradorController.php',

    // App\Modules\Pedidos\Infrastructure\Http\Controllers\*
    'PedidoEppController' => 'Modules/Pedidos/Infrastructure/Http/Controllers/PedidoEppController.php',
];

$workingDir = '/Users/Usuario/Documents/mundoindustrial/app';
$missing = [];
$found = [];

foreach ($controladores as $name => $path) {
    $fullPath = $workingDir . '/' . $path;
    if (file_exists($fullPath)) {
        $found[] = $name;
    } else {
        $missing[] = [
            'controller' => $name,
            'expected_path' => $path,
            'full_path' => $fullPath
        ];
    }
}

echo "=== ANÁLISIS DE CONTROLADORES ===\n\n";
echo " Controladores encontrados: " . count($found) . "\n";
echo "❌ Controladores FALTANTES: " . count($missing) . "\n\n";

if (!empty($missing)) {
    echo "CONTROLADORES FALTANTES:\n";
    foreach ($missing as $m) {
        echo "  ❌ {$m['controller']}\n";
        echo "     Ruta esperada: {$m['full_path']}\n";
    }
}

echo "\nControladores encontrados:\n";
foreach ($found as $f) {
    echo "   $f\n";
}
?>
