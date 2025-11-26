<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CONTENIDO DE NUEVAS TABLAS ===\n\n";

echo "📊 pedidos_produccion (primeros 3):\n";
$pedidos = DB::table('pedidos_produccion')->limit(3)->get();
foreach($pedidos as $p) {
    echo "\n  ID: {$p->id}\n";
    echo "  numero_pedido: {$p->numero_pedido}\n";
    echo "  cliente: {$p->cliente}\n";
    echo "  estado: {$p->estado}\n";
    echo "  asesor_id: {$p->asesor_id}\n";
    echo "  cliente_id: {$p->cliente_id}\n";
}

echo "\n\n📊 prendas_pedido (primeros 3):\n";
$prendas = DB::table('prendas_pedido')->limit(3)->get();
foreach($prendas as $pr) {
    echo "\n  ID: {$pr->id}\n";
    echo "  pedido_produccion_id: {$pr->pedido_produccion_id}\n";
    echo "  nombre_prenda: {$pr->nombre_prenda}\n";
    echo "  cantidad: {$pr->cantidad}\n";
}

echo "\n\n📊 procesos_prenda (todos):\n";
$procesos = DB::table('procesos_prenda')->get();
foreach($procesos as $pr) {
    echo "\n  ID: {$pr->id}\n";
    echo "  pedidos_produccion_id: {$pr->pedidos_produccion_id}\n";
    echo "  proceso: {$pr->proceso}\n";
    echo "  fecha_inicio: {$pr->fecha_inicio}\n";
}

echo "\n\n⚠️ ANÁLISIS:\n";
echo "- pedidos_produccion: {$pedidos->count()} de " . DB::table('pedidos_produccion')->count() . " migrados\n";
echo "- prendas_pedido: {$prendas->count()} de " . DB::table('prendas_pedido')->count() . " migrados\n";
echo "- procesos_prenda: {$procesos->count()} de " . DB::table('procesos_prenda')->count() . " migrados\n";

// Verificar qué falta
echo "\n\n🔍 VERIFICAR DATOS FALTANTES:\n";
$pedidos_null_asesor = DB::table('pedidos_produccion')->whereNull('asesor_id')->count();
$pedidos_null_cliente = DB::table('pedidos_produccion')->whereNull('cliente_id')->count();
echo "- pedidos con asesor_id NULL: $pedidos_null_asesor\n";
echo "- pedidos con cliente_id NULL: $pedidos_null_cliente\n";
?>
