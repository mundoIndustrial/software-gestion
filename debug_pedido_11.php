<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "============================================================\n";
echo "  ANÁLISIS COMPLETO DEL PEDIDO NÚMERO 11\n";
echo "============================================================\n\n";

// 1. Pedido
$pedido = \DB::table('pedidos_produccion')->where('numero_pedido', 11)->first();
if (!$pedido) {
    echo "❌ Pedido con numero_pedido=11 NO encontrado\n";
    exit;
}

echo "📋 PEDIDO:\n";
echo "  ID: {$pedido->id}\n";
echo "  Numero Pedido: {$pedido->numero_pedido}\n";
echo "  Cliente: {$pedido->cliente}\n";
echo "  Estado: {$pedido->estado}\n";
echo "  Forma de pago: {$pedido->forma_de_pago}\n";
echo "  Área: {$pedido->area}\n\n";

// 2. Prendas
$prendas = \DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedido->id)
    ->get();

echo "👕 PRENDAS ({$prendas->count()}):\n";
echo str_repeat('-', 80) . "\n";
foreach ($prendas as $p) {
    $deBodega = $p->de_bodega ? 'SÍ (de_bodega=1)' : 'NO (de_bodega=0)';
    $deleted = $p->deleted_at ? " [ELIMINADA: {$p->deleted_at}]" : '';
    echo "  ID: {$p->id} | Nombre: {$p->nombre_prenda} | De Bodega: {$deBodega}{$deleted}\n";
    echo "  Descripción: " . ($p->descripcion ?: '(vacía)') . "\n";
    
    // Tallas de esta prenda (flujo 1)
    $tallas = \DB::table('prenda_pedido_tallas')
        ->where('prenda_pedido_id', $p->id)
        ->get();
    if ($tallas->count() > 0) {
        echo "  📏 Tallas (prenda_pedido_tallas): {$tallas->count()}\n";
        foreach ($tallas as $t) {
            echo "    - ID:{$t->id} | Género: {$t->genero} | Talla: {$t->talla} | Cantidad: {$t->cantidad}\n";
        }
    }
    
    // Tallas con colores (flujo 2)
    $tallasColores = \DB::table('prenda_pedido_talla_colores as pptc')
        ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
        ->where('ppt.prenda_pedido_id', $p->id)
        ->select('ppt.genero', 'ppt.talla', 'pptc.color_nombre', 'pptc.cantidad')
        ->get();
    if ($tallasColores->count() > 0) {
        echo "  🎨 Tallas con colores (prenda_pedido_talla_colores): {$tallasColores->count()}\n";
        foreach ($tallasColores as $tc) {
            echo "    - Género: {$tc->genero} | Talla: {$tc->talla} | Color: {$tc->color_nombre} | Cantidad: {$tc->cantidad}\n";
        }
    }
    
    // Procesos de esta prenda
    $procesos = \DB::table('pedidos_procesos_prenda_detalles as ppd')
        ->join('tipos_procesos as tp', 'tp.id', '=', 'ppd.tipo_proceso_id')
        ->where('ppd.prenda_pedido_id', $p->id)
        ->whereNull('ppd.deleted_at')
        ->select('ppd.*', 'tp.nombre as nombre_tipo_proceso')
        ->get();
    
    echo "  ⚙️ Procesos (pedidos_procesos_prenda_detalles): {$procesos->count()}\n";
    foreach ($procesos as $proc) {
        echo "    - ID:{$proc->id} | Tipo: {$proc->nombre_tipo_proceso} (tipo_proceso_id:{$proc->tipo_proceso_id})\n";
        echo "      Estado: {$proc->estado} | Tipo Recibo: " . ($proc->tipo_recibo ?: 'NULL') . " | Num Recibo: " . ($proc->numero_recibo ?: 'NULL') . "\n";
        echo "      Ubicaciones: " . ($proc->ubicaciones ?: 'NULL') . "\n";
        echo "      Observaciones: " . ($proc->observaciones ?: '(vacías)') . "\n";
        if ($proc->deleted_at) {
            echo "      ⚠️ ELIMINADO: {$proc->deleted_at}\n";
        }
    }
    
    echo str_repeat('-', 80) . "\n";
}

// 3. Consecutivos
$consecutivos = \DB::table('consecutivos_recibos_pedidos')
    ->where('pedido_produccion_id', $pedido->id)
    ->get();

echo "\n🔢 CONSECUTIVOS (consecutivos_recibos_pedidos): {$consecutivos->count()}\n";
echo str_repeat('-', 80) . "\n";
foreach ($consecutivos as $c) {
    $prendaNombre = 'GENERAL (sin prenda)';
    if ($c->prenda_id) {
        $prendaRef = $prendas->firstWhere('id', $c->prenda_id);
        $prendaNombre = $prendaRef ? $prendaRef->nombre_prenda : "ID:{$c->prenda_id} (no encontrada)";
    }
    $activo = $c->activo ? 'SÍ' : 'NO';
    echo "  ID:{$c->id} | Prenda: {$prendaNombre} (prenda_id:" . ($c->prenda_id ?: 'NULL') . ")\n";
    echo "    Tipo Recibo: {$c->tipo_recibo} | Consecutivo Actual: {$c->consecutivo_actual} | Inicial: {$c->consecutivo_inicial} | Activo: {$activo}\n";
    echo "    Notas: " . ($c->notas ?: '(vacías)') . "\n";
}

// 4. Resumen de lógica
echo "\n\n============================================================\n";
echo "  📊 ANÁLISIS DE RECIBOS QUE DEBERÍAN GENERARSE\n";
echo "============================================================\n\n";

foreach ($prendas as $p) {
    $deBodega = (bool)$p->de_bodega;
    echo "PRENDA: {$p->nombre_prenda} (ID:{$p->id}) - de_bodega: " . ($deBodega ? 'TRUE' : 'FALSE') . "\n";
    
    $procesos = \DB::table('pedidos_procesos_prenda_detalles as ppd')
        ->join('tipos_procesos as tp', 'tp.id', '=', 'ppd.tipo_proceso_id')
        ->where('ppd.prenda_pedido_id', $p->id)
        ->whereNull('ppd.deleted_at')
        ->select('ppd.*', 'tp.nombre as nombre_tipo_proceso')
        ->get();
    
    // Recibo base
    if ($deBodega) {
        echo "  ✅ Recibo BASE: COSTURA-BODEGA\n";
    } else {
        echo "  ✅ Recibo BASE: COSTURA\n";
    }
    
    // Procesos adicionales
    foreach ($procesos as $proc) {
        $tipoProceso = strtolower($proc->nombre_tipo_proceso);
        
        if (!$deBodega && $tipoProceso === 'reflectivo') {
            echo "  ❌ Proceso {$proc->nombre_tipo_proceso}: EXCLUIDO (de_bodega=false, se embebe en costura)\n";
        } else {
            echo "  ✅ Proceso {$proc->nombre_tipo_proceso}: RECIBO SEPARADO\n";
        }
    }
    
    // ¿Qué consecutivos tiene?
    $consec = $consecutivos->where('prenda_id', $p->id);
    $consecGen = $consecutivos->whereNull('prenda_id');
    if ($consec->count() > 0 || $consecGen->count() > 0) {
        echo "  📎 Consecutivos asignados:\n";
        foreach ($consec as $c) {
            echo "    - {$c->tipo_recibo}: #{$c->consecutivo_actual} (activo: " . ($c->activo ? 'sí' : 'no') . ")\n";
        }
        foreach ($consecGen as $c) {
            echo "    - {$c->tipo_recibo}: #{$c->consecutivo_actual} (GENERAL, activo: " . ($c->activo ? 'sí' : 'no') . ")\n";
        }
    } else {
        echo "  ⚠️ Sin consecutivos asignados\n";
    }
    
    echo "\n";
}

echo "\n============================================================\n";
echo "  🌐 SIMULACIÓN: Qué ve INSUMOS/MATERIALES\n";
echo "============================================================\n\n";

echo "En insumos/materiales:\n";
echo "- Se usa endpoint /registros/{id}/recibos-datos\n";
echo "- Se filtran prendas con de_bodega=true (SOLO muestra de_bodega=false)\n";
echo "- ReceiptBuilder EXCLUYE proceso Reflectivo si de_bodega=false\n";
echo "- Formatters EMBEBE reflectivo dentro del recibo de costura si de_bodega=false\n\n";

foreach ($prendas as $p) {
    $deBodega = (bool)$p->de_bodega;
    
    if ($deBodega) {
        echo "PRENDA: {$p->nombre_prenda} (ID:{$p->id}) → ❌ FILTRADA (de_bodega=true, no visible en insumos)\n";
    } else {
        echo "PRENDA: {$p->nombre_prenda} (ID:{$p->id}) → ✅ VISIBLE (de_bodega=false)\n";
        
        $procesos = \DB::table('pedidos_procesos_prenda_detalles as ppd')
            ->join('tipos_procesos as tp', 'tp.id', '=', 'ppd.tipo_proceso_id')
            ->where('ppd.prenda_pedido_id', $p->id)
            ->whereNull('ppd.deleted_at')
            ->select('ppd.*', 'tp.nombre as nombre_tipo_proceso')
            ->get();
        
        echo "  Recibos que se generan:\n";
        echo "    1. COSTURA (recibo base)\n";
        
        $idx = 2;
        foreach ($procesos as $proc) {
            $tipoProceso = strtolower($proc->nombre_tipo_proceso);
            if ($tipoProceso === 'reflectivo') {
                echo "    ⚠️ {$proc->nombre_tipo_proceso}: NO se crea recibo separado → se embebe en COSTURA\n";
            } else {
                echo "    {$idx}. {$proc->nombre_tipo_proceso} (recibo separado)\n";
                $idx++;
            }
        }
    }
    echo "\n";
}

echo "\n============================================================\n";
echo "  🌐 SIMULACIÓN: Qué ve SUPERVISOR-PEDIDOS\n";
echo "============================================================\n\n";

echo "En supervisor-pedidos:\n";
echo "- Se usa endpoint /pedidos-public/{id}/recibos-datos\n";
echo "- NO se filtran prendas por de_bodega\n";
echo "- ReceiptBuilder EXCLUYE proceso Reflectivo si de_bodega=false\n\n";

foreach ($prendas as $p) {
    $deBodega = (bool)$p->de_bodega;
    
    echo "PRENDA: {$p->nombre_prenda} (ID:{$p->id}) - de_bodega: " . ($deBodega ? 'TRUE' : 'FALSE') . " → ✅ VISIBLE\n";
    
    $procesos = \DB::table('pedidos_procesos_prenda_detalles as ppd')
        ->join('tipos_procesos as tp', 'tp.id', '=', 'ppd.tipo_proceso_id')
        ->where('ppd.prenda_pedido_id', $p->id)
        ->whereNull('ppd.deleted_at')
        ->select('ppd.*', 'tp.nombre as nombre_tipo_proceso')
        ->get();
    
    echo "  Recibos que se generan:\n";
    
    if ($deBodega) {
        echo "    1. COSTURA-BODEGA (recibo base - de_bodega=true)\n";
    } else {
        echo "    1. COSTURA (recibo base)\n";
    }
    
    $idx = 2;
    foreach ($procesos as $proc) {
        $tipoProceso = strtolower($proc->nombre_tipo_proceso);
        if (!$deBodega && $tipoProceso === 'reflectivo') {
            echo "    ⚠️ {$proc->nombre_tipo_proceso}: NO se crea recibo separado → se embebe en COSTURA\n";
        } else {
            echo "    {$idx}. {$proc->nombre_tipo_proceso} (recibo separado)\n";
            $idx++;
        }
    }
    echo "\n";
}
