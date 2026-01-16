<?php
/**
 * 🔍 Script de Debugging Completo del Flujo de Prendas
 * 
 * Propósito: Rastrear todo el flujo desde qué datos se envían desde el frontend
 * hasta qué se guarda en la base de datos
 * 
 * Uso: php debug_flujo_prendas.php [numero_pedido]
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$numeroPedido = $argv[1] ?? null;

if (!$numeroPedido) {
    echo "\n❌ Error: Debes proporcionar un número de pedido\n";
    echo "Uso: php debug_flujo_prendas.php [numero_pedido]\n\n";
    exit(1);
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║     🔍 DEBUG COMPLETO DEL FLUJO DE PRENDAS Y VARIANTES    ║\n";
echo "║     Número de Pedido: $numeroPedido\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // Buscar pedido
    $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
    
    if (!$pedido) {
        echo "❌ Pedido no encontrado\n\n";
        exit(1);
    }
    
    echo "✅ Pedido encontrado: {$pedido->numero_pedido}\n\n";
    
    // 1. ANÁLISIS DE PRENDAS
    echo "┌─ 1️⃣  PRENDAS (tabla: prendas_pedido)\n";
    echo "├─ Total: " . $pedido->prendas->count() . "\n";
    
    foreach ($pedido->prendas as $idx => $prenda) {
        echo "│\n";
        echo "├─ PRENDA #" . ($idx + 1) . " (ID: {$prenda->id})\n";
        echo "│  • Nombre: {$prenda->nombre_prenda}\n";
        echo "│  • Descripción: {$prenda->descripcion}\n";
        echo "│  • Género: {$prenda->genero}\n";
        echo "│  • De Bodega: " . ($prenda->de_bodega ? 'SÍ' : 'NO') . "\n";
        
        // 2. ANÁLISIS DE VARIANTES
        echo "│\n";
        echo "│  └─ 2️⃣  VARIANTES (tabla: prenda_pedido_variantes)\n";
        echo "│     • Total: " . $prenda->variantes->count() . "\n";
        
        if ($prenda->variantes->isEmpty()) {
            echo "│     ❌ ERROR: NO HAY VARIANTES\n";
        } else {
            foreach ($prenda->variantes as $vIdx => $var) {
                echo "│\n";
                echo "│     Variante #" . ($vIdx + 1) . " (ID: {$var->id})\n";
                echo "│     ┌─ Datos Básicos\n";
                echo "│     │  • Talla: " . ($var->talla ? "{$var->talla} ✅" : "VACÍO ❌") . "\n";
                echo "│     │  • Cantidad: " . ($var->cantidad ? "{$var->cantidad} ✅" : "VACÍO ❌") . "\n";
                
                echo "│     ├─ IDs de Relaciones\n";
                echo "│     │  • color_id: " . ($var->color_id ? "{$var->color_id} ✅" : "VACÍO ❌") . "\n";
                echo "│     │  • tela_id: " . ($var->tela_id ? "{$var->tela_id} ✅" : "VACÍO ❌") . "\n";
                echo "│     │  • tipo_manga_id: " . ($var->tipo_manga_id ? "{$var->tipo_manga_id} ✅" : "VACÍO ❌") . "\n";
                echo "│     │  • tipo_broche_boton_id: " . ($var->tipo_broche_boton_id ? "{$var->tipo_broche_boton_id} ✅" : "VACÍO ❌") . "\n";
                
                echo "│     ├─ Observaciones\n";
                echo "│     │  • manga_obs: " . ($var->manga_obs ? "✅ ({$var->manga_obs})" : "VACÍO") . "\n";
                echo "│     │  • broche_boton_obs: " . ($var->broche_boton_obs ? "✅ ({$var->broche_boton_obs})" : "VACÍO") . "\n";
                
                echo "│     └─ Especiales\n";
                echo "│        • tiene_bolsillos: " . ($var->tiene_bolsillos ? "SÍ ✅" : "NO") . "\n";
                echo "│        • bolsillos_obs: " . ($var->bolsillos_obs ? "✅ ({$var->bolsillos_obs})" : "VACÍO") . "\n";
            }
        }
    }
    
    echo "│\n└─\n\n";
    
    // 3. REPORTE DE PROBLEMAS
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║             🚨 DETECCIÓN DE PROBLEMAS                     ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $problemas = [];
    
    foreach ($pedido->prendas as $prenda) {
        foreach ($prenda->variantes as $var) {
            // Verificar campos críticos
            if (!$var->talla) {
                $problemas[] = "PRENDA #{$prenda->id} VARIANTE #{$var->id}: Talla vacía";
            }
            if (!$var->cantidad) {
                $problemas[] = "PRENDA #{$prenda->id} VARIANTE #{$var->id}: Cantidad vacía o 0";
            }
            if (!$var->color_id) {
                $problemas[] = "PRENDA #{$prenda->id} VARIANTE #{$var->id}: Color ID vacío";
            }
            if (!$var->tela_id) {
                $problemas[] = "PRENDA #{$prenda->id} VARIANTE #{$var->id}: Tela ID vacía";
            }
            if (!$var->tipo_manga_id) {
                $problemas[] = "PRENDA #{$prenda->id} VARIANTE #{$var->id}: Tipo Manga ID vacío";
            }
            if (!$var->tipo_broche_boton_id) {
                $problemas[] = "PRENDA #{$prenda->id} VARIANTE #{$var->id}: Tipo Broche ID vacío";
            }
        }
    }
    
    if (empty($problemas)) {
        echo "✅ No se detectaron problemas\n\n";
    } else {
        echo count($problemas) . " problemas detectados:\n";
        foreach ($problemas as $idx => $problema) {
            echo ($idx + 1) . ". ❌ $problema\n";
        }
        echo "\n";
    }
    
    // 4. RECOMENDACIONES
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║           📋 SIGUIENTES PASOS PARA DEBUGGING              ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    echo "1. Revisar los logs:\n";
    echo "   tail -50 storage/logs/laravel.log | grep -i 'prenda'\n\n";
    
    echo "2. Consultar directamente la BD:\n";
    $query = "SELECT * FROM prenda_pedido_variantes WHERE prenda_pedido_id IN (SELECT id FROM prendas_pedido WHERE pedido_produccion_id = {$pedido->id}) ORDER BY id DESC LIMIT 5;";
    echo "   $query\n\n";
    
    echo "3. Revisar el controlador que maneja la creación:\n";
    echo "   - app/Http/Controllers/Asesores/PedidosProduccionViewController.php\n";
    echo "   - app/Application/Services/PedidoPrendaService.php\n\n";
    
    echo "4. Ejecutar test específico:\n";
    echo "   php artisan test --filter PrendaPedido\n\n";
    
    // 5. DATOS PARA COPIAR/PEGAR EN LOGS
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║           🔍 JSON DE DATOS PARA ANÁLISIS                  ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $dataJson = [
        'pedido' => [
            'id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
        ],
        'prendas' => $pedido->prendas->map(function($p) {
            return [
                'id' => $p->id,
                'nombre' => $p->nombre_prenda,
                'variantes_count' => $p->variantes->count(),
                'variantes' => $p->variantes->map(function($v) {
                    return [
                        'id' => $v->id,
                        'talla' => $v->talla,
                        'cantidad' => $v->cantidad,
                        'color_id' => $v->color_id,
                        'tela_id' => $v->tela_id,
                        'tipo_manga_id' => $v->tipo_manga_id,
                        'tipo_broche_boton_id' => $v->tipo_broche_boton_id,
                    ];
                })->toArray(),
            ];
        })->toArray(),
    ];
    
    echo json_encode($dataJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    echo "✅ Análisis completado\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
?>
