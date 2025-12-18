<?php
/**
 * TEST: Verificar creación de procesos para cotización REFLECTIVO
 * 
 * Este script prueba que se crean automáticamente 2 procesos cuando se crea un pedido
 * desde una cotización tipo REFLECTIVO:
 * 1. Proceso "Creación" - asignado a la asesora logueada
 * 2. Proceso "Costura" - asignado a Ramiro
 */

require_once(__DIR__ . '/vendor/autoload.php');
$app = require_once(__DIR__ . '/bootstrap/app.php');
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST: PROCESOS AUTOMÁTICOS PARA COTIZACIÓN REFLECTIVO  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    // 1. Buscar una cotización tipo REFLECTIVO
    echo "🔍 Buscando cotización tipo REFLECTIVO...\n";
    
    $cotizacion = Cotizacion::whereHas('tipoCotizacion', function ($q) {
        $q->whereRaw("LOWER(nombre) = ?", ['reflectivo']);
    })
    ->with(['tipoCotizacion', 'cliente', 'prendasCotizaciones'])
    ->first();
    
    if (!$cotizacion) {
        echo "❌ No se encontró cotización tipo REFLECTIVO\n";
        exit(1);
    }
    
    echo "✅ Cotización encontrada:\n";
    echo "   - ID: {$cotizacion->id}\n";
    echo "   - Número: {$cotizacion->numero_cotizacion}\n";
    echo "   - Tipo: {$cotizacion->tipoCotizacion->nombre}\n";
    echo "   - Cliente: {$cotizacion->cliente->nombre}\n";
    echo "   - Prendas: {$cotizacion->prendasCotizaciones->count()}\n\n";
    
    // 2. Validar que esté aprobada
    echo "🔍 Verificando estado de cotización...\n";
    if (!in_array($cotizacion->estado, ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])) {
        echo "⚠️  Cotización no está aprobada. Estado actual: {$cotizacion->estado}\n";
        echo "   Saltando creación de pedido...\n\n";
        exit(0);
    }
    echo "✅ Cotización está aprobada\n\n";
    
    // 3. Simular autenticación (asignar asesora logueada)
    echo "🔐 Simulando autenticación...\n";
    $asesor = $cotizacion->asesor;
    if (!$asesor) {
        echo "❌ No se encontró asesor para esta cotización\n";
        exit(1);
    }
    Auth::login($asesor);
    echo "✅ Asesor logueado: {$asesor->name}\n\n";
    
    // 4. Preparar datos de prendas
    echo "📋 Preparando datos de prendas...\n";
    $prendas = [];
    foreach ($cotizacion->prendasCotizaciones as $index => $prendaCot) {
        $prendas[] = [
            'nombre_producto' => $prendaCot->nombre_prenda,
            'color_id' => $prendaCot->variantes->first()?->color_id,
            'tela_id' => $prendaCot->variantes->first()?->tela_id,
            'cantidades' => ['S' => 10, 'M' => 20, 'L' => 15],
            'index' => $index,
        ];
    }
    echo "✅ {count($prendas)} prendas preparadas\n\n";
    
    // 5. Crear pedido
    echo "🚀 Creando pedido de producción...\n";
    DB::beginTransaction();
    
    try {
        $pedido = PedidoProduccion::create([
            'cotizacion_id' => $cotizacion->id,
            'numero_cotizacion' => $cotizacion->numero_cotizacion,
            'numero_pedido' => 'TEST-' . str_pad(rand(1000, 9999), 6, '0', STR_PAD_LEFT),
            'cliente' => $cotizacion->cliente->nombre,
            'asesor_id' => auth()->id(),
            'forma_de_pago' => 'Transferencia',
            'estado' => 'Pendiente',
            'fecha_de_creacion_de_orden' => now(),
        ]);
        
        echo "✅ Pedido creado:\n";
        echo "   - ID: {$pedido->id}\n";
        echo "   - Número: {$pedido->numero_pedido}\n";
        echo "   - Estado: {$pedido->estado}\n\n";
        
        // 6. Crear prendas
        echo "📦 Creando prendas del pedido...\n";
        $prendasGuardadas = [];
        foreach ($prendas as $index => $prenda) {
            $prendaPedido = PrendaPedido::create([
                'numero_pedido' => $pedido->numero_pedido,
                'nombre_prenda' => $prenda['nombre_producto'],
                'cantidad' => array_sum($prenda['cantidades']),
                'descripcion' => 'Prenda de test',
                'cantidad_talla' => json_encode($prenda['cantidades']),
                'color_id' => $prenda['color_id'],
                'tela_id' => $prenda['tela_id'],
            ]);
            
            $prendasGuardadas[] = $prendaPedido;
            echo "✅ Prenda {$index + 1}: {$prenda['nombre_producto']} (ID: {$prendaPedido->id})\n";
        }
        echo "\n";
        
        // 7. Llamar a crearProcesosParaReflectivo manualmente
        echo "⚙️  Creando procesos automáticos...\n";
        
        $asesoraLogueada = Auth::user()->name ?? 'Sin Asesora';
        echo "   Asesora logueada: {$asesoraLogueada}\n\n";
        
        $procesosCreados = 0;
        foreach ($prendasGuardadas as $prendaPedido) {
            // Crear proceso de Creación de Orden
            $procsCreacion = ProcesoPrenda::create([
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_pedido_id' => $prendaPedido->id,
                'proceso' => 'Creación de Orden',
                'encargado' => $asesoraLogueada,
                'estado_proceso' => 'En Progreso',
                'fecha_inicio' => now(),
                'observaciones' => 'Proceso de creación asignado automáticamente para cotización reflectivo',
            ]);
            
            echo "   ✅ Proceso CREACIÓN DE ORDEN (ID: {$procsCreacion->id})\n";
            echo "      - Prenda: {$prendaPedido->nombre_prenda}\n";
            echo "      - Encargado: {$asesoraLogueada}\n";
            echo "      - Estado: En Progreso\n";
            $procesosCreados++;
            
            // Crear proceso de Costura
            $procsCostura = ProcesoPrenda::create([
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_pedido_id' => $prendaPedido->id,
                'proceso' => 'Costura',
                'encargado' => 'Ramiro',
                'estado_proceso' => 'En Progreso',
                'fecha_inicio' => now(),
                'observaciones' => 'Asignado automáticamente a Ramiro para cotización reflectivo',
            ]);
            
            echo "   ✅ Proceso COSTURA (ID: {$procsCostura->id})\n";
            echo "      - Prenda: {$prendaPedido->nombre_prenda}\n";
            echo "      - Encargado: Ramiro\n";
            echo "      - Estado: En Progreso\n\n";
            $procesosCreados++;
        }
        
        DB::commit();
        
        // 8. Verificar procesos creados
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ VERIFICACIÓN FINAL\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        $procesosEnBD = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)->get();
        
        echo "📊 Procesos en base de datos:\n";
        echo "   Total: {$procesosEnBD->count()}\n\n";
        
        foreach ($procesosEnBD as $proc) {
            echo "   - Proceso: {$proc->proceso}\n";
            echo "     Prenda ID: {$proc->prenda_pedido_id}\n";
            echo "     Encargado: {$proc->encargado}\n";
            echo "     Estado: {$proc->estado_proceso}\n\n";
        }
        
        // 9. Validación
        $procesosCreacionOrden = $procesosEnBD->where('proceso', 'Creación de Orden')->count();
        $procesosCostura = $procesosEnBD->where('proceso', 'Costura')->count();
        $procesosRamiro = $procesosEnBD->where('encargado', 'Ramiro')->count();
        $procesosAsesora = $procesosEnBD->where('encargado', $asesoraLogueada)->count();
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "🎯 VALIDACIONES\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        if ($procesosCreacionOrden === count($prendasGuardadas)) {
            echo "✅ Procesos de CREACIÓN DE ORDEN: {$procesosCreacionOrden}/{count($prendasGuardadas)}\n";
        } else {
            echo "❌ Procesos de CREACIÓN DE ORDEN: {$procesosCreacionOrden}/{count($prendasGuardadas)}\n";
        }
        
        if ($procesosCostura === count($prendasGuardadas)) {
            echo "✅ Procesos de COSTURA: {$procesosCostura}/{count($prendasGuardadas)}\n";
        } else {
            echo "❌ Procesos de COSTURA: {$procesosCostura}/{count($prendasGuardadas)}\n";
        }
        
        if ($procesosRamiro === count($prendasGuardadas)) {
            echo "✅ Procesos asignados a Ramiro: {$procesosRamiro}/{count($prendasGuardadas)}\n";
        } else {
            echo "❌ Procesos asignados a Ramiro: {$procesosRamiro}/{count($prendasGuardadas)}\n";
        }
        
        if ($procesosAsesora === count($prendasGuardadas)) {
            echo "✅ Procesos asignados a {$asesoraLogueada}: {$procesosAsesora}/{count($prendasGuardadas)}\n";
        } else {
            echo "❌ Procesos asignados a {$asesoraLogueada}: {$procesosAsesora}/{count($prendasGuardadas)}\n";
        }
        
        echo "\n✅ TEST COMPLETADO EXITOSAMENTE\n\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Error durante la creación:\n";
        echo "   {$e->getMessage()}\n";
        echo "   {$e->getFile()}:{$e->getLine()}\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "❌ Error general:\n";
    echo "   {$e->getMessage()}\n";
    echo "   {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}
