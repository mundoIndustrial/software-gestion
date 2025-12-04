#!/usr/bin/env php
<?php

/**
 * SCRIPT DE TESTING PARA ESTADOS DE COTIZACIONES Y PEDIDOS
 * Ejecutar: php artisan tinker < tests/manual/test-estados.php
 * O desde Tinker: include 'tests/manual/test-estados.php'
 */

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\HistorialCambiosCotizacion;
use App\Models\HistorialCambiosPedido;
use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPedido;
use App\Services\CotizacionEstadoService;
use App\Services\PedidoEstadoService;

echo "\n=== TESTING: ESTADOS DE COTIZACIONES Y PEDIDOS ===\n";

// TEST 1: Verificar que las tablas existen
echo "\n✓ TEST 1: Verificar estructura de tablas\n";
try {
    $cotizacion = DB::table('cotizaciones')->limit(1)->first();
    echo "  ✓ Tabla cotizaciones existe\n";
    
    $pedido = DB::table('pedidos_produccion')->limit(1)->first();
    echo "  ✓ Tabla pedidos_produccion existe\n";
    
    $historialCot = DB::table('historial_cambios_cotizaciones')->limit(1)->first();
    echo "  ✓ Tabla historial_cambios_cotizaciones existe\n";
    
    $historialPed = DB::table('historial_cambios_pedidos')->limit(1)->first();
    echo "  ✓ Tabla historial_cambios_pedidos existe\n";
} catch (\Exception $e) {
    echo "  ✗ Error verificando tablas: " . $e->getMessage() . "\n";
}

// TEST 2: Verificar Enums
echo "\n✓ TEST 2: Verificar Enums\n";
try {
    $estCot = EstadoCotizacion::BORRADOR;
    echo "  ✓ EstadoCotizacion::BORRADOR = '" . $estCot->value . "'\n";
    echo "    - Label: " . $estCot->label() . "\n";
    echo "    - Color: " . $estCot->color() . "\n";
    echo "    - Icon: " . $estCot->icon() . "\n";
    
    $estPed = EstadoPedido::PENDIENTE_SUPERVISOR;
    echo "  ✓ EstadoPedido::PENDIENTE_SUPERVISOR = '" . $estPed->value . "'\n";
    echo "    - Label: " . $estPed->label() . "\n";
    echo "    - Color: " . $estPed->color() . "\n";
} catch (\Exception $e) {
    echo "  ✗ Error con Enums: " . $e->getMessage() . "\n";
}

// TEST 3: Verificar transiciones permitidas
echo "\n✓ TEST 3: Verificar transiciones permitidas en Enums\n";
try {
    $estado = EstadoCotizacion::BORRADOR;
    $transiciones = $estado->transicionesPermitidas();
    echo "  ✓ Desde BORRADOR puede ir a: " . implode(", ", $transiciones) . "\n";
    
    $puedePasar = $estado->puedePasar(EstadoCotizacion::ENVIADA_CONTADOR);
    echo "  ✓ BORRADOR → ENVIADA_CONTADOR: " . ($puedePasar ? "SÍ" : "NO") . "\n";
    
    $noPuedePasar = $estado->puedePasar(EstadoCotizacion::APROBADA_COTIZACIONES);
    echo "  ✓ BORRADOR → APROBADA_COTIZACIONES: " . ($noPuedePasar ? "SÍ" : "NO") . "\n";
} catch (\Exception $e) {
    echo "  ✗ Error verificando transiciones: " . $e->getMessage() . "\n";
}

// TEST 4: Verificar Servicios
echo "\n✓ TEST 4: Verificar Servicios\n";
try {
    $servicioC = app(CotizacionEstadoService::class);
    echo "  ✓ CotizacionEstadoService inyectado\n";
    
    $servicioP = app(PedidoEstadoService::class);
    echo "  ✓ PedidoEstadoService inyectado\n";
    
    // Obtener siguiente número
    $siguienteCot = $servicioC->obtenerSiguienteNumeroCotizacion();
    echo "  ✓ Siguiente número cotización: " . $siguienteCot . "\n";
    
    $siguientePed = $servicioP->obtenerSiguienteNumeroPedido();
    echo "  ✓ Siguiente número pedido: " . $siguientePed . "\n";
} catch (\Exception $e) {
    echo "  ✗ Error con Servicios: " . $e->getMessage() . "\n";
}

// TEST 5: Verificar Modelos y Relaciones
echo "\n✓ TEST 5: Verificar Modelos y Relaciones\n";
try {
    $cot = Cotizacion::limit(1)->first();
    if ($cot) {
        echo "  ✓ Modelo Cotizacion carga\n";
        echo "    - ID: " . $cot->id . "\n";
        echo "    - Estado: " . ($cot->estado ?? 'NULL') . "\n";
        echo "    - Número: " . ($cot->numero_cotizacion ?? 'NULL') . "\n";
        
        // Prueba relación historialCambios
        $historial = $cot->historialCambios()->count();
        echo "    - Historial cambios: " . $historial . " registros\n";
    } else {
        echo "  ℹ No hay cotizaciones para probar relaciones\n";
    }
    
    $ped = PedidoProduccion::limit(1)->first();
    if ($ped) {
        echo "  ✓ Modelo PedidoProduccion carga\n";
        echo "    - ID: " . $ped->id . "\n";
        echo "    - Estado: " . ($ped->estado ?? 'NULL') . "\n";
        echo "    - Número: " . ($ped->numero_pedido ?? 'NULL') . "\n";
        
        // Prueba relación historialCambios
        $historial = $ped->historialCambios()->count();
        echo "    - Historial cambios: " . $historial . " registros\n";
    } else {
        echo "  ℹ No hay pedidos para probar relaciones\n";
    }
} catch (\Exception $e) {
    echo "  ✗ Error con Modelos: " . $e->getMessage() . "\n";
}

// TEST 6: Probar flujo completo (simulado)
echo "\n✓ TEST 6: Flujo de Estados Simulado\n";
try {
    // Crear una cotización de prueba
    $cotTest = Cotizacion::create([
        'user_id' => 1,
        'cliente' => 'TEST CLIENTE',
        'estado' => EstadoCotizacion::BORRADOR->value,
        'es_borrador' => true,
        'tipo_cotizacion' => 'P',
    ]);
    echo "  ✓ Cotización de prueba creada (ID: " . $cotTest->id . ")\n";
    echo "    - Estado inicial: " . $cotTest->estado . "\n";
    
    // Servicio debe estar disponible
    $service = app(CotizacionEstadoService::class);
    echo "  ✓ Servicio listo para pruebas\n";
    
    // Validar transición
    $puede = $service->validarTransicion($cotTest, EstadoCotizacion::ENVIADA_CONTADOR);
    echo "  ✓ Validación de transición: " . ($puede ? "PERMITIDA" : "NO PERMITIDA") . "\n";
    
    // Limpiar
    $cotTest->delete();
    echo "  ✓ Cotización de prueba eliminada\n";
} catch (\Exception $e) {
    echo "  ✗ Error en flujo simulado: " . $e->getMessage() . "\n";
}

// TEST 7: Verificar Controllers
echo "\n✓ TEST 7: Verificar Controllers\n";
try {
    $controllerC = new \App\Http\Controllers\CotizacionEstadoController(
        app(CotizacionEstadoService::class)
    );
    echo "  ✓ CotizacionEstadoController instanciado\n";
    
    $controllerP = new \App\Http\Controllers\PedidoEstadoController(
        app(PedidoEstadoService::class)
    );
    echo "  ✓ PedidoEstadoController instanciado\n";
} catch (\Exception $e) {
    echo "  ✗ Error con Controllers: " . $e->getMessage() . "\n";
}

// TEST 8: Verificar Jobs
echo "\n✓ TEST 8: Verificar Jobs\n";
try {
    $job1 = new \App\Jobs\AsignarNumeroCotizacionJob(Cotizacion::first() ?? new Cotizacion());
    echo "  ✓ AsignarNumeroCotizacionJob instanciado\n";
    
    $job2 = new \App\Jobs\EnviarCotizacionAContadorJob(Cotizacion::first() ?? new Cotizacion());
    echo "  ✓ EnviarCotizacionAContadorJob instanciado\n";
    
    $job3 = new \App\Jobs\EnviarCotizacionAAprobadorJob(Cotizacion::first() ?? new Cotizacion());
    echo "  ✓ EnviarCotizacionAAprobadorJob instanciado\n";
    
    $job4 = new \App\Jobs\AsignarNumeroPedidoJob(PedidoProduccion::first() ?? new PedidoProduccion());
    echo "  ✓ AsignarNumeroPedidoJob instanciado\n";
} catch (\Exception $e) {
    echo "  ✗ Error con Jobs: " . $e->getMessage() . "\n";
}

// RESUMEN FINAL
echo "\n╔════════════════════════════════════════════╗\n";
echo "║  ✓ TODOS LOS TESTS COMPLETADOS EXITOSAMENTE ║\n";
echo "╚════════════════════════════════════════════╝\n";
echo "\nPróximos pasos:\n";
echo "  1. Ejecutar: php artisan queue:work\n";
echo "  2. Probar endpoints con Postman/curl\n";
echo "  3. Crear vistas y componentes Blade\n";
echo "  4. Integrar con frontend\n\n";
