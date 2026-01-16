<?php
/**
 * 🛠️  Script de Validación de Integridad
 * 
 * Propósito: Verificar que PedidoPrendaService está recibiendo y guardando
 * los datos correctamente. Detecta si el problema está en el servicio o en el frontend
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$numeroPedido = $argv[1] ?? null;

if (!$numeroPedido) {
    echo "\n❌ Debes proporcionar un número de pedido\n";
    echo "Uso: php validar_integridad_prendas.php [numero_pedido]\n\n";
    exit(1);
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║        🛠️  VALIDACIÓN DE INTEGRIDAD DE PRENDAS            ║\n";
echo "║        Número: $numeroPedido\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

try {
    $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
    
    if (!$pedido) {
        echo "❌ Pedido no encontrado\n\n";
        exit(1);
    }
    
    echo "✅ Pedido encontrado\n\n";
    
    // 1. ANÁLISIS DE PRENDAS
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║           1️⃣  ANÁLISIS DE TABLA prendas_pedido            ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $prendas = $pedido->prendas;
    echo "Total de prendas: {$prendas->count()}\n\n";
    
    foreach ($prendas as $p) {
        echo "PRENDA ID {$p->id}:\n";
        echo "  • nombre_prenda: '{$p->nombre_prenda}'\n";
        echo "  • descripcion: '{$p->descripcion}'\n";
        echo "  • genero: '{$p->genero}'\n";
        echo "  • de_bodega: " . ($p->de_bodega ? 'true' : 'false') . "\n";
        echo "\n";
    }
    
    // 2. ANÁLISIS DE VARIANTES
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║      2️⃣  ANÁLISIS DE TABLA prenda_pedido_variantes        ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $totalVariantes = 0;
    $variantes = [];
    
    foreach ($prendas as $prenda) {
        foreach ($prenda->variantes as $var) {
            $totalVariantes++;
            $variantes[] = $var;
            
            $estado = [];
            
            // Validar campos
            if (!$var->talla) $estado[] = "❌ TALLA VACÍA";
            if (!$var->cantidad || $var->cantidad <= 0) $estado[] = "❌ CANTIDAD VACÍA/0";
            if (!$var->color_id || $var->color_id <= 0) $estado[] = "❌ COLOR_ID VACÍO/0";
            if (!$var->tela_id || $var->tela_id <= 0) $estado[] = "❌ TELA_ID VACÍO/0";
            if (!$var->tipo_manga_id || $var->tipo_manga_id <= 0) $estado[] = "❌ MANGA_ID VACÍO/0";
            if (!$var->tipo_broche_boton_id || $var->tipo_broche_boton_id <= 0) $estado[] = "❌ BROCHE_ID VACÍO/0";
            
            echo "VARIANTE ID {$var->id} (Prenda {$var->prenda_pedido_id}):\n";
            echo "  Talla: '{$var->talla}'\n";
            echo "  Cantidad: {$var->cantidad}\n";
            echo "  color_id: {$var->color_id}\n";
            echo "  tela_id: {$var->tela_id}\n";
            echo "  tipo_manga_id: {$var->tipo_manga_id}\n";
            echo "  tipo_broche_boton_id: {$var->tipo_broche_boton_id}\n";
            echo "  manga_obs: '{$var->manga_obs}'\n";
            echo "  broche_boton_obs: '{$var->broche_boton_obs}'\n";
            echo "  tiene_bolsillos: " . ($var->tiene_bolsillos ? 'true' : 'false') . "\n";
            echo "  bolsillos_obs: '{$var->bolsillos_obs}'\n";
            
            if (!empty($estado)) {
                echo "  PROBLEMAS:\n";
                foreach ($estado as $s) {
                    echo "    $s\n";
                }
            } else {
                echo "  ✅ TODOS LOS CAMPOS CRÍTICOS CORRECTOS\n";
            }
            echo "\n";
        }
    }
    
    echo "Total de variantes: $totalVariantes\n\n";
    
    // 3. ESTADÍSTICAS
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║          3️⃣  ESTADÍSTICAS Y CONTEOS                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $stats = DB::table('prenda_pedido_variantes as ppv')
        ->join('prendas_pedido as pp', 'ppv.prenda_pedido_id', '=', 'pp.id')
        ->where('pp.pedido_produccion_id', $pedido->id)
        ->select(
            DB::raw('COUNT(*) as total'),
            DB::raw('COUNT(DISTINCT ppv.prenda_pedido_id) as total_prendas'),
            DB::raw('COUNT(CASE WHEN ppv.talla != "" AND ppv.talla IS NOT NULL THEN 1 END) as talla_ok'),
            DB::raw('COUNT(CASE WHEN ppv.cantidad > 0 THEN 1 END) as cantidad_ok'),
            DB::raw('COUNT(CASE WHEN ppv.color_id > 0 THEN 1 END) as color_ok'),
            DB::raw('COUNT(CASE WHEN ppv.tela_id > 0 THEN 1 END) as tela_ok'),
            DB::raw('COUNT(CASE WHEN ppv.tipo_manga_id > 0 THEN 1 END) as manga_ok'),
            DB::raw('COUNT(CASE WHEN ppv.tipo_broche_boton_id > 0 THEN 1 END) as broche_ok'),
            DB::raw('SUM(ppv.cantidad) as cantidad_total_unidades')
        )
        ->first();
    
    echo "Totales:\n";
    echo "  • Variantes: {$stats->total}\n";
    echo "  • Prendas únicas: {$stats->total_prendas}\n";
    echo "  • Cantidad total de unidades: {$stats->cantidad_total_unidades}\n\n";
    
    echo "Campos correctamente llenados:\n";
    echo "  • Talla: {$stats->talla_ok}/{$stats->total}\n";
    echo "  • Cantidad: {$stats->cantidad_ok}/{$stats->total}\n";
    echo "  • Color ID: {$stats->color_ok}/{$stats->total}\n";
    echo "  • Tela ID: {$stats->tela_ok}/{$stats->total}\n";
    echo "  • Manga ID: {$stats->manga_ok}/{$stats->total}\n";
    echo "  • Broche ID: {$stats->broche_ok}/{$stats->total}\n\n";
    
    // Calcular porcentajes
    $total = $stats->total;
    echo "Porcentajes de completitud:\n";
    if ($total > 0) {
        echo "  • Talla: " . round(($stats->talla_ok / $total) * 100, 1) . "%\n";
        echo "  • Cantidad: " . round(($stats->cantidad_ok / $total) * 100, 1) . "%\n";
        echo "  • Color: " . round(($stats->color_ok / $total) * 100, 1) . "%\n";
        echo "  • Tela: " . round(($stats->tela_ok / $total) * 100, 1) . "%\n";
        echo "  • Manga: " . round(($stats->manga_ok / $total) * 100, 1) . "%\n";
        echo "  • Broche: " . round(($stats->broche_ok / $total) * 100, 1) . "%\n";
    }
    
    echo "\n\n";
    
    // 4. PRUEBAS DE RELACIONES
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║        4️⃣  VALIDACIÓN DE RELACIONES (FK)                 ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    // Verificar que existen los IDs en las tablas relacionadas
    $problemasFK = [];
    
    foreach ($variantes as $var) {
        // Validar color
        $colorExists = DB::table('colores')->where('id', $var->color_id)->exists();
        if (!$colorExists && $var->color_id > 0) {
            $problemasFK[] = "Variante {$var->id}: color_id {$var->color_id} NO EXISTE en tabla colores";
        }
        
        // Validar tela
        $telaExists = DB::table('telas')->where('id', $var->tela_id)->exists();
        if (!$telaExists && $var->tela_id > 0) {
            $problemasFK[] = "Variante {$var->id}: tela_id {$var->tela_id} NO EXISTE en tabla telas";
        }
        
        // Validar manga
        $mangaExists = DB::table('tipos_manga')->where('id', $var->tipo_manga_id)->exists();
        if (!$mangaExists && $var->tipo_manga_id > 0) {
            $problemasFK[] = "Variante {$var->id}: tipo_manga_id {$var->tipo_manga_id} NO EXISTE en tabla tipos_manga";
        }
        
        // Validar broche
        $brocheExists = DB::table('tipos_broche_boton')->where('id', $var->tipo_broche_boton_id)->exists();
        if (!$brocheExists && $var->tipo_broche_boton_id > 0) {
            $problemasFK[] = "Variante {$var->id}: tipo_broche_boton_id {$var->tipo_broche_boton_id} NO EXISTE en tabla tipos_broche_boton";
        }
    }
    
    if (empty($problemasFK)) {
        echo "✅ Todas las relaciones de clave foránea son válidas\n\n";
    } else {
        echo count($problemasFK) . " problemas de FK encontrados:\n\n";
        foreach ($problemasFK as $problema) {
            echo "  ❌ $problema\n";
        }
        echo "\n";
    }
    
    // 5. REPORTE FINAL
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║            📊 REPORTE FINAL                              ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    $totalProblemas = 0;
    
    // Contar campos faltantes
    foreach ($variantes as $var) {
        if (!$var->talla) $totalProblemas++;
        if (!$var->cantidad || $var->cantidad <= 0) $totalProblemas++;
        if (!$var->color_id || $var->color_id <= 0) $totalProblemas++;
        if (!$var->tela_id || $var->tela_id <= 0) $totalProblemas++;
        if (!$var->tipo_manga_id || $var->tipo_manga_id <= 0) $totalProblemas++;
        if (!$var->tipo_broche_boton_id || $var->tipo_broche_boton_id <= 0) $totalProblemas++;
    }
    
    $totalProblemas += count($problemasFK);
    
    if ($totalProblemas === 0) {
        echo "✅ ¡TODO ESTÁ CORRECTO!\n\n";
        echo "Los datos se están guardando correctamente.\n";
        echo "Si hay otros problemas, están en otra parte del código.\n";
    } else {
        echo "❌ Se encontraron $totalProblemas problemas\n\n";
        echo "Acciones recomendadas:\n";
        echo "1. Revisar qué datos está enviando el frontend\n";
        echo "2. Validar que el usuario complete todos los campos obligatorios\n";
        echo "3. Revisar los logs: tail -200 storage/logs/laravel.log\n";
        echo "4. Ejecutar: php debug_flujo_prendas.php $numeroPedido\n";
    }
    
    echo "\n\n✅ Validación completada\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
?>
