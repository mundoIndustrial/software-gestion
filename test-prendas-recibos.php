#!/usr/bin/env php
<?php

/**
 * Script de Prueba: ObtenerPrendasRecibosService
 * 
 * Prueba que el nuevo servicio funcione correctamente y retorne datos válidos
 * 
 * Uso: php test-prendas-recibos.php
 */

// Incluir autoloader de Composer
require_once __DIR__ . '/vendor/autoload.php';

// Cargar aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Application\Operario\Services\ObtenerPrendasRecibosService;

echo "═══════════════════════════════════════════════════════════════════\n";
echo "TEST: ObtenerPrendasRecibosService\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

try {
    // 1. Buscar un usuario con rol de costurero o bodeguero
    echo "1. Buscando usuario con rol 'costurero' o 'bodeguero'...\n";
    $usuario = User::whereHas('roles', function ($query) {
        $query->whereIn('name', ['costurero', 'bodeguero', 'costura-reflectivo']);
    })->first();

    if (!$usuario) {
        echo "   ❌ No hay usuarios con rol de costurero/bodeguero/costura-reflectivo\n";
        echo "   Creando usuario de prueba...\n";
        
        $usuario = User::firstOrCreate(
            ['email' => 'costurero_test@test.com'],
            [
                'name' => 'Costurero Test',
                'password' => bcrypt('password')
            ]
        );
        
        $usuario->assignRole('costurero');
    }

    echo "   ✅ Usuario encontrado: {$usuario->name} ({$usuario->email})\n\n";

    // 2. Instanciar el servicio
    echo "2. Instanciando ObtenerPrendasRecibosService...\n";
    $servicio = app(ObtenerPrendasRecibosService::class);
    echo "   ✅ Servicio instanciado correctamente\n\n";

    // 3. Ejecutar el método
    echo "3. Ejecutando obtenerPrendasConRecibos()...\n";
    $prendasConRecibos = $servicio->obtenerPrendasConRecibos($usuario);
    echo "   ✅ Método ejecutado correctamente\n\n";

    // 4. Verificar resultados
    echo "4. Verificando resultados...\n";
    echo "   Total de prendas encontradas: " . $prendasConRecibos->count() . "\n\n";

    if ($prendasConRecibos->count() > 0) {
        echo "5. Primeras 3 prendas:\n";
        $prendasConRecibos->take(3)->each(function ($prenda, $index) {
            echo "\n   Prenda " . ($index + 1) . ":\n";
            echo "   ├─ Prenda ID: {$prenda['prenda_id']}\n";
            echo "   ├─ Número Pedido: #{$prenda['numero_pedido']}\n";
            echo "   ├─ Cliente: {$prenda['cliente']}\n";
            echo "   ├─ Nombre: {$prenda['nombre_prenda']}\n";
            echo "   ├─ De Bodega: " . ($prenda['de_bodega'] ? 'Sí' : 'No') . "\n";
            echo "   ├─ Total Recibos: {$prenda['total_recibos']}\n";
            echo "   └─ Recibos:\n";
            
            collect($prenda['recibos'])->each(function ($recibo, $ridx) {
                echo "      " . ($ridx === count($prenda['recibos']) - 1 ? '└' : '├') . "─ {$recibo['tipo_recibo']} (Consecutivo: {$recibo['consecutivo_actual']})\n";
            });
        });
    } else {
        echo "   ⚠️  No hay prendas con recibos activos en la base de datos\n\n";
        echo "   📊 Estadísticas de BD:\n";
        
        $totalRecibos = \App\Models\ConsecutivoReciboPedido::count();
        $recibosActivos = \App\Models\ConsecutivoReciboPedido::where('activo', 1)->count();
        $recibosRecostura = \App\Models\ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', ['COSTURA', 'COSTURA-BODEGA'])->count();

        echo "      ├─ Total recibos en BD: {$totalRecibos}\n";
        echo "      ├─ Recibos activos: {$recibosActivos}\n";
        echo "      └─ Recibos COSTURA/COSTURA-BODEGA activos: {$recibosRecostura}\n";
    }

    echo "\n═══════════════════════════════════════════════════════════════════\n";
    echo "✅ TEST COMPLETADO EXITOSAMENTE\n";
    echo "═══════════════════════════════════════════════════════════════════\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
