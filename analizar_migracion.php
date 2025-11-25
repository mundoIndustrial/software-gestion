<?php

/**
 * ANÁLISIS PRE-MIGRACIÓN DE DATOS
 * 
 * Objetivo: Entender la estructura de datos antigua y planificar la migración
 * a la nueva arquitectura
 */

use Illuminate\Support\Facades\DB;

echo "\n";
echo str_repeat("=", 100) . "\n";
echo "ANÁLISIS PRE-MIGRACIÓN: ARQUITECTURA ANTIGUA vs NUEVA\n";
echo str_repeat("=", 100) . "\n\n";

// ============================================
// 1. ANALIZAR tabla_original
// ============================================
echo "1️⃣ TABLA ANTIGUA: tabla_original\n";
echo str_repeat("-", 100) . "\n";

$totalPedidosAntiguos = DB::table('tabla_original')->count();
$pedidosConAsesora = DB::table('tabla_original')->whereNotNull('asesora')->count();
$pedidosConCliente = DB::table('tabla_original')->whereNotNull('cliente')->count();

echo "   Total pedidos: $totalPedidosAntiguos\n";
echo "   Pedidos con asesora: $pedidosConAsesora\n";
echo "   Pedidos con cliente: $pedidosConCliente\n\n";

// Obtener muestra de datos
$muestraPedidos = DB::table('tabla_original')->limit(3)->get();
echo "   MUESTRA de datos:\n";
foreach ($muestraPedidos as $i => $ped) {
    echo "   \n   Pedido $i:\n";
    echo "     - Pedido #: {$ped->pedido}\n";
    echo "     - Cliente: {$ped->cliente}\n";
    echo "     - Asesora: {$ped->asesora}\n";
    echo "     - Estado: {$ped->estado}\n";
    echo "     - Descripción: " . substr($ped->descripcion ?? '', 0, 60) . "...\n";
    echo "     - Cantidad: {$ped->cantidad}\n";
}

// ============================================
// 2. ANALIZAR registros_por_orden
// ============================================
echo "\n\n2️⃣ TABLA ANTIGUA: registros_por_orden\n";
echo str_repeat("-", 100) . "\n";

$totalRegistrosAntiguos = DB::table('registros_por_orden')->count();
$totalPrendas = DB::table('registros_por_orden')->distinct()->pluck('prenda')->count();
$totalTallas = DB::table('registros_por_orden')->distinct()->pluck('talla')->count();

echo "   Total registros: $totalRegistrosAntiguos\n";
echo "   Prendas únicas: $totalPrendas\n";
echo "   Tallas únicas: $totalTallas\n\n";

// Agrupar por pedido
$registrosPorPedido = DB::table('registros_por_orden')
    ->selectRaw('pedido, COUNT(*) as total_registros, COUNT(DISTINCT prenda) as prendas_unicas')
    ->groupBy('pedido')
    ->limit(3)
    ->get();

echo "   MUESTRA de registros por pedido:\n";
foreach ($registrosPorPedido as $reg) {
    echo "     Pedido #{$reg->pedido}: {$reg->total_registros} registros, {$reg->prendas_unicas} prendas\n";
    
    $detalles = DB::table('registros_por_orden')
        ->where('pedido', $reg->pedido)
        ->limit(2)
        ->get();
    
    foreach ($detalles as $det) {
        echo "       - {$det->prenda} (Talla: {$det->talla}, Cant: {$det->cantidad})\n";
    }
}

// ============================================
// 3. DATOS EXISTENTES EN NUEVA TABLA
// ============================================
echo "\n\n3️⃣ TABLA NUEVA: pedidos_produccion (estado actual)\n";
echo str_repeat("-", 100) . "\n";

$totalPedidosNuevos = DB::table('pedidos_produccion')->count();
echo "   Pedidos existentes en tabla nueva: $totalPedidosNuevos\n\n";

if ($totalPedidosNuevos > 0) {
    $muestraNuevos = DB::table('pedidos_produccion')->limit(2)->get();
    echo "   MUESTRA de pedidos nuevos:\n";
    foreach ($muestraNuevos as $ped) {
        echo "     - Pedido #{$ped->numero_pedido}: cliente_id={$ped->cliente_id}, asesor_id={$ped->asesor_id}\n";
    }
}

// ============================================
// 4. DATOS EXISTENTES EN PRENDAS_PEDIDO
// ============================================
echo "\n\n4️⃣ TABLA NUEVA: prendas_pedido (estado actual)\n";
echo str_repeat("-", 100) . "\n";

$totalPrendasNuevas = DB::table('prendas_pedido')->count();
echo "   Prendas existentes en tabla nueva: $totalPrendasNuevas\n\n";

if ($totalPrendasNuevas > 0) {
    $muestraPrendasNuevas = DB::table('prendas_pedido')->limit(2)->get();
    echo "   MUESTRA de prendas nuevas:\n";
    foreach ($muestraPrendasNuevas as $prenda) {
        echo "     - {$prenda->nombre_prenda} (Pedido ID: {$prenda->pedido_produccion_id})\n";
    }
}

// ============================================
// 5. ANÁLISIS DE USUARIOS Y CLIENTES
// ============================================
echo "\n\n5️⃣ USUARIOS Y CLIENTES EXISTENTES\n";
echo str_repeat("-", 100) . "\n";

$totalUsuarios = DB::table('users')->count();
$totalClientes = DB::table('clientes')->count();
echo "   Usuarios existentes: $totalUsuarios\n";
echo "   Clientes existentes: $totalClientes\n\n";

// Asesoras únicas en tabla_original
$asesorasUnicas = DB::table('tabla_original')
    ->distinct()
    ->whereNotNull('asesora')
    ->pluck('asesora')
    ->count();

$clientesUnicos = DB::table('tabla_original')
    ->distinct()
    ->whereNotNull('cliente')
    ->pluck('cliente')
    ->count();

echo "   Asesoras únicas en tabla_original: $asesorasUnicas\n";
echo "   Clientes únicos en tabla_original: $clientesUnicos\n";

// ============================================
// 6. PLAN DE MIGRACIÓN
// ============================================
echo "\n\n6️⃣ PLAN DE MIGRACIÓN PROPUESTO\n";
echo str_repeat("=", 100) . "\n";

echo "   PASO 1: Crear usuarios (asesoras)\n";
echo "           - Crear $asesorasUnicas usuarios si no existen\n";
echo "           - Basados en: tabla_original.asesora\n\n";

echo "   PASO 2: Crear clientes\n";
echo "           - Crear $clientesUnicos clientes si no existen\n";
echo "           - Basados en: tabla_original.cliente\n\n";

echo "   PASO 3: Migrar pedidos\n";
echo "           - Insertar $totalPedidosAntiguos pedidos a pedidos_produccion\n";
echo "           - Relacionar con asesor_id y cliente_id\n";
echo "           - Copiar: numero_pedido, cliente (string), asesor_id, cliente_id, estado, etc.\n\n";

echo "   PASO 4: Migrar prendas\n";
echo "           - Agrupar $totalRegistrosAntiguos registros por pedido + prenda\n";
echo "           - Crear prendas en prendas_pedido\n";
echo "           - Guardar tallas en JSON (cantidad_talla)\n";
echo "           - Ejemplo estructura JSON:\n";
echo "             {\n";
echo "               'talla': 'M',\n";
echo "               'cantidad': 10\n";
echo "             }\n\n";

// ============================================
// 7. VERIFICACIONES PREVIAS
// ============================================
echo "\n7️⃣ VERIFICACIONES PREVIAS NECESARIAS\n";
echo str_repeat("-", 100) . "\n";

$pedidosSinAsesora = DB::table('tabla_original')->whereNull('asesora')->count();
$pedidosSinCliente = DB::table('tabla_original')->whereNull('cliente')->count();
$registrosSinPrenda = DB::table('registros_por_orden')->whereNull('prenda')->count();

echo "   ⚠️ Pedidos sin asesora: $pedidosSinAsesora\n";
echo "   ⚠️ Pedidos sin cliente: $pedidosSinCliente\n";
echo "   ⚠️ Registros sin prenda: $registrosSinPrenda\n\n";

if ($pedidosSinAsesora > 0 || $pedidosSinCliente > 0 || $registrosSinPrenda > 0) {
    echo "   ⚠️ ATENCIÓN: Hay datos incompletos que necesitan revisión manual\n\n";
}

// ============================================
// 8. ESTIMACIÓN DE TIEMPO
// ============================================
echo "\n8️⃣ ESTIMACIÓN\n";
echo str_repeat("=", 100) . "\n";
echo "   - Usuarios a crear: $asesorasUnicas\n";
echo "   - Clientes a crear: $clientesUnicos\n";
echo "   - Pedidos a migrar: $totalPedidosAntiguos\n";
echo "   - Prendas a migrar: $totalPrendasNuevas (ya existen) + nuevas\n";
echo "   - Registros a procesar: $totalRegistrosAntiguos\n\n";

echo "\n" . str_repeat("=", 100) . "\n";
echo "✅ ANÁLISIS COMPLETADO\n";
echo str_repeat("=", 100) . "\n\n";
