<?php
/**
 * 🔍 Script de Análisis de Datos de Prendas
 * 
 * Propósito: Analizar qué información se está guardando en la tabla prenda_pedido_variantes
 * y detectar campos faltantes o vacíos.
 * 
 * Uso: php analizar_datos_prendas.php [numero_pedido]
 */

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;

// Obtener número de pedido del argumento
$numeroPedido = $argv[1] ?? null;

if (!$numeroPedido) {
    echo "\n❌ Error: Debes proporcionar un número de pedido\n";
    echo "Uso: php analizar_datos_prendas.php [numero_pedido]\n";
    echo "Ejemplo: php analizar_datos_prendas.php 50001\n\n";
    exit(1);
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║      🔍 ANÁLISIS DE DATOS DE PRENDAS Y VARIANTES          ║\n";
echo "║      Pedido: $numeroPedido\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    // 1. Buscar el pedido
    $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
    
    if (!$pedido) {
        echo "❌ No se encontró pedido con número: $numeroPedido\n\n";
        exit(1);
    }
    
    echo "📌 INFORMACIÓN DEL PEDIDO\n";
    echo "─────────────────────────────────────────────────────────\n";
    echo "ID: {$pedido->id}\n";
    echo "Número: {$pedido->numero_pedido}\n";
    echo "Cliente: {$pedido->cliente}\n";
    echo "Estado: {$pedido->estado}\n";
    echo "Fecha: {$pedido->created_at}\n\n";
    
    // 2. Obtener todas las prendas del pedido
    $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
    
    echo "📦 PRENDAS EN EL PEDIDO: " . $prendas->count() . "\n";
    echo "─────────────────────────────────────────────────────────\n\n";
    
    if ($prendas->isEmpty()) {
        echo "❌ No hay prendas en este pedido\n\n";
        exit(1);
    }
    
    // 3. Analizar cada prenda
    foreach ($prendas as $index => $prenda) {
        echo "┌─ PRENDA #" . ($index + 1) . " ─────────────────────────────────────────\n";
        echo "│ ID: {$prenda->id}\n";
        echo "│ Nombre: {$prenda->nombre_prenda}\n";
        echo "│ Descripción: {$prenda->descripcion}\n";
        echo "│ Género: {$prenda->genero}\n";
        echo "│ De Bodega: " . ($prenda->de_bodega ? 'SÍ' : 'NO') . "\n";
        
        // 4. Analizar variantes
        $variantes = $prenda->variantes;
        echo "│\n";
        echo "│ 📊 VARIANTES: " . $variantes->count() . "\n";
        
        if ($variantes->isEmpty()) {
            echo "│ ⚠️  ADVERTENCIA: No hay variantes para esta prenda\n";
        } else {
            foreach ($variantes as $varIndex => $variante) {
                echo "│\n";
                echo "│ ├─ Variante #" . ($varIndex + 1) . "\n";
                
                // Análisis de campos
                $campos = [
                    'talla' => 'Talla',
                    'cantidad' => 'Cantidad',
                    'color_id' => 'Color ID',
                    'tela_id' => 'Tela ID',
                    'tipo_manga_id' => 'Tipo Manga ID',
                    'tipo_broche_boton_id' => 'Tipo Broche/Botón ID',
                    'manga_obs' => 'Observación Manga',
                    'broche_boton_obs' => 'Observación Broche',
                    'tiene_bolsillos' => 'Tiene Bolsillos',
                    'bolsillos_obs' => 'Observación Bolsillos',
                ];
                
                foreach ($campos as $campo => $etiqueta) {
                    $valor = $variante->$campo;
                    $estado = '';
                    
                    // Validar estado del campo
                    if ($valor === null || $valor === '') {
                        $estado = '❌ VACÍO';
                    } elseif (in_array($campo, ['color_id', 'tela_id', 'tipo_manga_id', 'tipo_broche_boton_id'])) {
                        if ($valor > 0) {
                            $estado = '✅ ASIGNADO';
                        } else {
                            $estado = '❌ INVÁLIDO (0)';
                        }
                    } elseif ($campo === 'cantidad') {
                        if ($valor > 0) {
                            $estado = '✅ OK';
                        } else {
                            $estado = '❌ INVÁLIDO (0)';
                        }
                    } elseif ($campo === 'tiene_bolsillos') {
                        $estado = ($valor ? '✅ SÍ' : '❌ NO');
                    } else {
                        $estado = '✅ OK';
                    }
                    
                    $display_valor = is_bool($valor) ? ($valor ? 'true' : 'false') : $valor;
                    echo "│ │ • $etiqueta: $display_valor $estado\n";
                }
            }
        }
        
        echo "└─────────────────────────────────────────────────────────\n\n";
    }
    
    // 5. Análisis SQL directo
    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║            🔧 ANÁLISIS DE CAMPOS EN BASE DE DATOS          ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    // Contar campos vacíos por tipo
    $stats = DB::table('prenda_pedido_variantes as ppv')
        ->join('prendas_pedido as pp', 'ppv.prenda_pedido_id', '=', 'pp.id')
        ->where('pp.pedido_produccion_id', $pedido->id)
        ->select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN ppv.talla IS NULL OR ppv.talla = "" THEN 1 ELSE 0 END) as talla_vacia'),
            DB::raw('SUM(CASE WHEN ppv.cantidad IS NULL OR ppv.cantidad = 0 THEN 1 ELSE 0 END) as cantidad_vacia'),
            DB::raw('SUM(CASE WHEN ppv.color_id IS NULL OR ppv.color_id = 0 THEN 1 ELSE 0 END) as color_vacio'),
            DB::raw('SUM(CASE WHEN ppv.tela_id IS NULL OR ppv.tela_id = 0 THEN 1 ELSE 0 END) as tela_vacia'),
            DB::raw('SUM(CASE WHEN ppv.tipo_manga_id IS NULL OR ppv.tipo_manga_id = 0 THEN 1 ELSE 0 END) as manga_vacia'),
            DB::raw('SUM(CASE WHEN ppv.tipo_broche_boton_id IS NULL OR ppv.tipo_broche_boton_id = 0 THEN 1 ELSE 0 END) as broche_vacio')
        )
        ->first();
    
    echo "📊 RESUMEN ESTADÍSTICO\n";
    echo "─────────────────────────────────────────────────────────\n";
    echo "Total de variantes: {$stats->total}\n";
    echo "Tallas vacías: {$stats->talla_vacia} ❌\n";
    echo "Cantidades vacías: {$stats->cantidad_vacia} ❌\n";
    echo "Colores vacíos: {$stats->color_vacio} ❌\n";
    echo "Telas vacías: {$stats->tela_vacia} ❌\n";
    echo "Mangas vacías: {$stats->manga_vacia} ❌\n";
    echo "Broches vacíos: {$stats->broche_vacio} ❌\n\n";
    
    // 6. Mostrar query raw
    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║              📝 SQL QUERY PARA INSPECCIÓN                 ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $query = <<<SQL
SELECT 
    ppv.id,
    ppv.prenda_pedido_id,
    ppv.talla,
    ppv.cantidad,
    ppv.color_id,
    ppv.tela_id,
    ppv.tipo_manga_id,
    ppv.tipo_broche_boton_id,
    ppv.manga_obs,
    ppv.broche_boton_obs,
    ppv.tiene_bolsillos,
    ppv.bolsillos_obs
FROM prenda_pedido_variantes ppv
JOIN prendas_pedido pp ON ppv.prenda_pedido_id = pp.id
WHERE pp.pedido_produccion_id = {$pedido->id}
ORDER BY ppv.id;
SQL;
    
    echo $query . "\n\n";
    
    // 7. Revisar logs
    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║              📋 ÚLTIMOS LOGS RELEVANTES                   ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $logFile = storage_path('logs/laravel.log');
    
    if (file_exists($logFile)) {
        // Leer últimas 100 líneas
        $lines = array_slice(file($logFile), -100);
        $relevantLines = array_filter($lines, function($line) use ($pedido) {
            return strpos($line, 'PedidoPrendaService') !== false || 
                   strpos($line, (string)$pedido->id) !== false ||
                   strpos($line, $pedido->numero_pedido) !== false;
        });
        
        if (empty($relevantLines)) {
            echo "⚠️  No se encontraron logs relevantes en los últimos registros\n";
        } else {
            foreach ($relevantLines as $line) {
                echo $line;
            }
        }
    } else {
        echo "⚠️  No se encontró archivo de log\n";
    }
    
    echo "\n\n✅ Análisis completado\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
?>
