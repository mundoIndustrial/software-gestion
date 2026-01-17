#!/usr/bin/env php
<?php
/**
 * Script de Prueba: Guardar EPP con Imágenes de Prueba
 * 
 * Crea un pedido de prueba con EPP e imágenes para verificar el flujo
 * 
 * Uso: php test_guardar_epp_imagenes.php
 */

require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\Epp;
use App\Models\User;
use App\Services\PedidoEppService;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST: Guardar EPP con Imágenes                                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    DB::beginTransaction();
    
    // 1. Obtener o crear usuario de prueba
    $usuario = User::first();
    if (!$usuario) {
        echo "❌ No hay usuarios en la base de datos\n";
        exit(1);
    }
    echo "✅ Usuario: " . $usuario->name . " (ID: {$usuario->id})\n";
    
    // 2. Obtener EPP disponibles
    $eppsDisponibles = Epp::limit(3)->get();
    if ($eppsDisponibles->isEmpty()) {
        echo "❌ No hay EPP registrados en la base de datos\n";
        exit(1);
    }
    echo "✅ EPP disponibles: " . $eppsDisponibles->count() . "\n";
    
    // 3. Crear pedido de prueba
    $pedido = PedidoProduccion::create([
        'numero_pedido' => 'TEST-' . time(),
        'usuario_id' => $usuario->id,
        'estado' => 'borrador',
        'json_datos' => json_encode(['test' => true]),
    ]);
    echo "✅ Pedido creado: #{$pedido->numero_pedido} (ID: {$pedido->id})\n";
    
    // 4. Preparar datos de EPP con imágenes (simuladas)
    $eppsParaGuardar = [];
    
    foreach ($eppsDisponibles as $idx => $epp) {
        // Crear imágenes de prueba (ruta simulada)
        $imagenes = [];
        for ($i = 0; $i < 3; $i++) {
            $imagenes[] = [
                'archivo' => "epp/test/{$epp->id}_imagen_" . ($i + 1) . ".jpg",
                'principal' => $i === 0,  // Primera es principal
                'orden' => $i,
            ];
        }
        
        $eppsParaGuardar[] = [
            'epp_id' => $epp->id,
            'cantidad' => ($idx + 1),
            'tallas_medidas' => json_encode(['S', 'M', 'L']),
            'observaciones' => "EPP de prueba #" . ($idx + 1),
            'imagenes' => $imagenes,
        ];
    }
    
    echo "\n📦 Datos de EPP a guardar:\n";
    echo "─────────────────────────────────────────────────────────────\n";
    foreach ($eppsParaGuardar as $idx => $eppData) {
        echo "  EPP #" . ($idx + 1) . ":\n";
        echo "    ├─ ID: {$eppData['epp_id']}\n";
        echo "    ├─ Cantidad: {$eppData['cantidad']}\n";
        echo "    ├─ Imágenes: " . count($eppData['imagenes']) . "\n";
        foreach ($eppData['imagenes'] as $imgIdx => $img) {
            echo "    │  └─ [{$imgIdx}] " . basename($img['archivo']) . 
                 " (Principal: " . ($img['principal'] ? 'Sí' : 'No') . ")\n";
        }
    }
    
    // 5. Guardar EPPs usando el servicio
    echo "\n✳️  Guardando EPPs...\n";
    $service = new PedidoEppService();
    $pedidosEppGuardados = $service->guardarEppsDelPedido($pedido, $eppsParaGuardar);
    echo "✅ EPPs guardados: " . count($pedidosEppGuardados) . "\n";
    
    // 6. Verificar en base de datos
    echo "\n📊 Verificando en base de datos...\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $pedidosEppEnBd = PedidoEpp::where('pedido_produccion_id', $pedido->id)
        ->with('imagenes')
        ->get();
    
    echo "✅ Registros pedido_epp: " . $pedidosEppEnBd->count() . "\n";
    
    $totalImagenes = 0;
    foreach ($pedidosEppEnBd as $pe) {
        $totalImagenes += $pe->imagenes->count();
        echo "   └─ EPP ID {$pe->id}: {$pe->imagenes->count()} imágenes\n";
    }
    
    echo "✅ Registros pedido_epp_imagenes: $totalImagenes\n";
    
    // 7. Mostrar datos completos
    echo "\n📋 DATOS COMPLETOS EN BASE DE DATOS:\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $datos = DB::table('pedido_epp as pe')
        ->leftJoin('epp as e', 'pe.epp_id', '=', 'e.id')
        ->leftJoin('pedido_epp_imagenes as pei', 'pe.id', '=', 'pei.pedido_epp_id')
        ->where('pe.pedido_produccion_id', $pedido->id)
        ->select(
            'pe.id as pedido_epp_id',
            'e.nombre as epp_nombre',
            'pe.cantidad',
            'pei.id as imagen_id',
            'pei.archivo',
            'pei.principal',
            'pei.orden'
        )
        ->orderBy('pe.id')
        ->orderBy('pei.orden')
        ->get();
    
    foreach ($datos as $row) {
        $imgInfo = $row->imagen_id 
            ? "Imagen: {$row->archivo} (Principal: " . ($row->principal ? 'Sí' : 'No') . ")"
            : "(Sin imágenes)";
        
        echo "  EPP {$row->pedido_epp_id}: {$row->epp_nombre} (Qty: {$row->cantidad}) - $imgInfo\n";
    }
    
    DB::commit();
    
    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ TEST COMPLETADO EXITOSAMENTE                               ║\n";
    echo "╠════════════════════════════════════════════════════════════════╣\n";
    echo "║  Pedido: " . str_pad("#{$pedido->numero_pedido}", 60) . "║\n";
    echo "║  EPPs: " . str_pad($eppsParaGuardar, 62) . "║\n";
    echo "║  Total Imágenes: " . str_pad($totalImagenes, 55) . "║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
