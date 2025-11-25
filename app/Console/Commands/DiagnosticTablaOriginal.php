<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosticTablaOriginal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnostic:tabla-original';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Realiza diagnóstico completo de tabla_original y registros_por_orden';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════╗');
        $this->info('║  Diagnóstico: tabla_original & registros_por_orden   ║');
        $this->info('╚═══════════════════════════════════════════════════════╝');

        $this->newLine();

        // 1. Análisis de tabla_original
        $this->section('📊 ANÁLISIS: tabla_original');
        $this->diagnosticoTablaOriginal();

        // 2. Análisis de registros_por_orden
        $this->section('📋 ANÁLISIS: registros_por_orden');
        $this->diagnosticoRegistrosPorOrden();

        // 3. Análisis de relaciones
        $this->section('🔗 ANÁLISIS: Relaciones entre tablas');
        $this->diagnosticoRelaciones();

        // 4. Problemas potenciales
        $this->section('⚠️  PROBLEMAS POTENCIALES');
        $this->diagnosticoProblemas();

        // 5. Muestras de datos
        $this->section('📄 MUESTRA DE DATOS');
        $this->muestraDatos();

        $this->info("\n╔═══════════════════════════════════════════════════════╗");
        $this->info("║  ✅ Diagnóstico completado                           ║");
        $this->info("╚═══════════════════════════════════════════════════════╝\n");

        return 0;
    }

    private function diagnosticoTablaOriginal()
    {
        // Conteo total
        $total = DB::table('tabla_original')->count();
        $this->info("Total de registros: {$total}");

        // Campos vacíos críticos
        $sinPedido = DB::table('tabla_original')->whereNull('pedido')->count();
        $sinCliente = DB::table('tabla_original')->whereNull('cliente')->count();
        $sinEstado = DB::table('tabla_original')->whereNull('estado')->count();
        $sinFecha = DB::table('tabla_original')->whereNull('fecha_de_creacion_de_orden')->count();

        $this->info("Sin PEDIDO: {$sinPedido}");
        $this->info("Sin CLIENTE: {$sinCliente}");
        $this->info("Sin ESTADO: {$sinEstado}");
        $this->info("Sin FECHA_CREACION: {$sinFecha}");

        // Pedidos duplicados
        $pedidosDuplicados = DB::table('tabla_original')
            ->select('pedido')
            ->groupBy('pedido')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        $this->info("Pedidos DUPLICADOS: {$pedidosDuplicados}");
        if ($pedidosDuplicados > 0) {
            $this->warn("⚠️  Hay pedidos con ID duplicado");
        }

        // Rango de pedidos
        $minPedido = DB::table('tabla_original')->min('pedido');
        $maxPedido = DB::table('tabla_original')->max('pedido');
        $this->info("Rango de PEDIDO: {$minPedido} a {$maxPedido}");

        // Estados
        $estados = DB::table('tabla_original')
            ->select('estado', DB::raw('COUNT(*) as count'))
            ->groupBy('estado')
            ->get();
        
        $this->info("Estados encontrados:");
        foreach ($estados as $estado) {
            $nombre = $estado->estado ?? 'NULL';
            $this->info("  - {$nombre}: {$estado->count}");
        }

        // Asesoras
        $asesorCount = DB::table('tabla_original')
            ->select('asesora')
            ->distinct()
            ->count();
        $this->info("Número de asesoras: {$asesorCount}");

        // Clientes únicos
        $clienteCount = DB::table('tabla_original')
            ->select('cliente')
            ->distinct()
            ->count();
        $this->info("Número de clientes: {$clienteCount}");

        // Áreas
        $areas = DB::table('tabla_original')
            ->select('area', DB::raw('COUNT(*) as count'))
            ->groupBy('area')
            ->get();
        
        $this->info("Áreas encontradas:");
        foreach ($areas as $area) {
            $nombre = $area->area ?? 'NULL';
            $this->info("  - {$nombre}: {$area->count}");
        }

        // Fechas
        $fechaMin = DB::table('tabla_original')->min('fecha_de_creacion_de_orden');
        $fechaMax = DB::table('tabla_original')->max('fecha_de_creacion_de_orden');
        $this->info("Rango de fechas: {$fechaMin} a {$fechaMax}");
    }

    private function diagnosticoRegistrosPorOrden()
    {
        $total = DB::table('registros_por_orden')->count();
        $this->info("Total de registros: {$total}");

        // Campos vacíos
        $sinPedido = DB::table('registros_por_orden')->whereNull('pedido')->count();
        $sinPrenda = DB::table('registros_por_orden')->whereNull('prenda')->count();
        $sinCantidad = DB::table('registros_por_orden')->whereNull('cantidad')->count();

        $this->info("Sin PEDIDO: {$sinPedido}");
        $this->info("Sin PRENDA: {$sinPrenda}");
        $this->info("Sin CANTIDAD: {$sinCantidad}");

        // Prendas únicas
        $prendasCount = DB::table('registros_por_orden')
            ->select('prenda')
            ->distinct()
            ->count();
        $this->info("Prendas únicas: {$prendasCount}");

        // Promedio de prendas por orden
        $stats = DB::table('registros_por_orden')
            ->select('pedido', DB::raw('COUNT(*) as cant'))
            ->groupBy('pedido')
            ->get();
        
        $promedio = $stats->avg('cant');
        $this->info("Promedio de prendas por pedido: " . round($promedio, 2));

        // Tallas
        $tallasCount = DB::table('registros_por_orden')
            ->select('talla')
            ->distinct()
            ->count();
        $this->info("Tallas únicas: {$tallasCount}");

        // Costureros
        $costureroCount = DB::table('registros_por_orden')
            ->select('costurero')
            ->distinct()
            ->whereNotNull('costurero')
            ->count();
        $this->info("Costureros asignados: {$costureroCount}");
    }

    private function diagnosticoRelaciones()
    {
        // Pedidos en tabla_original que NO existen en registros_por_orden
        $pedidosSinRegistros = DB::table('tabla_original')
            ->whereNotIn('pedido', DB::table('registros_por_orden')->select('pedido'))
            ->count();
        
        if ($pedidosSinRegistros > 0) {
            $this->warn("⚠️  {$pedidosSinRegistros} pedidos sin registros en registros_por_orden");
        } else {
            $this->info("✅ Todos los pedidos tienen registros");
        }

        // Registros que apuntan a pedidos que NO existen
        $registrosSinPedido = DB::table('registros_por_orden')
            ->whereNotIn('pedido', DB::table('tabla_original')->select('pedido'))
            ->count();
        
        if ($registrosSinPedido > 0) {
            $this->warn("⚠️  {$registrosSinPedido} registros apuntan a pedidos que no existen");
        } else {
            $this->info("✅ Todos los registros apuntan a pedidos válidos");
        }

        // Integridad de cantidades
        $registrosZero = DB::table('registros_por_orden')
            ->where('cantidad', 0)
            ->orWhere('cantidad', '')
            ->count();
        
        if ($registrosZero > 0) {
            $this->warn("⚠️  {$registrosZero} registros con cantidad cero o vacía");
        }
    }

    private function diagnosticoProblemas()
    {
        $problemas = [];

        // Problema 1: Pedidos duplicados
        $duplicados = DB::table('tabla_original')
            ->select('pedido')
            ->groupBy('pedido')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('pedido')
            ->toArray();
        
        if (count($duplicados) > 0) {
            $this->warn("❌ CRÍTICO: Pedidos duplicados: " . implode(', ', $duplicados));
            $problemas[] = "Pedidos duplicados en tabla_original";
        }

        // Problema 2: Registros huérfanos
        $huerfanos = DB::table('registros_por_orden')
            ->whereNotIn('pedido', DB::table('tabla_original')->select('pedido'))
            ->select('pedido')
            ->distinct()
            ->pluck('pedido')
            ->toArray();
        
        if (count($huerfanos) > 0) {
            $this->warn("❌ ERROR: Registros sin pedido: " . implode(', ', $huerfanos));
            $problemas[] = "Registros huérfanos en registros_por_orden";
        }

        // Problema 3: Pedidos sin registros
        $sinRegistros = DB::table('tabla_original')
            ->whereNotIn('pedido', DB::table('registros_por_orden')->select('pedido'))
            ->select('pedido')
            ->distinct()
            ->limit(10)
            ->pluck('pedido')
            ->toArray();
        
        if (count($sinRegistros) > 0) {
            $this->warn("⚠️  ADVERTENCIA: Algunos pedidos sin registros: " . implode(', ', $sinRegistros));
            $problemas[] = "Pedidos sin detalles en registros_por_orden";
        }

        // Problema 4: Datos inconsistentes
        $inconsistencias = DB::table('registros_por_orden as r')
            ->join('tabla_original as t', 'r.pedido', '=', 't.pedido')
            ->whereRaw("r.cliente != t.cliente")
            ->count();
        
        if ($inconsistencias > 0) {
            $this->warn("⚠️  {$inconsistencias} registros con cliente inconsistente");
            $problemas[] = "Cliente inconsistente entre tablas";
        }

        if (empty($problemas)) {
            $this->info("✅ No se encontraron problemas críticos");
        }
    }

    private function muestraDatos()
    {
        $this->info("Primeros 5 pedidos:");
        
        $pedidos = DB::table('tabla_original')
            ->select('pedido', 'cliente', 'asesora', 'estado', 'area', 'fecha_de_creacion_de_orden')
            ->limit(5)
            ->get();

        foreach ($pedidos as $pedido) {
            $this->info("  Pedido #{$pedido->pedido}");
            $this->info("    Cliente: {$pedido->cliente}");
            $this->info("    Asesora: {$pedido->asesora}");
            $this->info("    Estado: {$pedido->estado}");
            $this->info("    Área: {$pedido->area}");
            $this->info("    Fecha: {$pedido->fecha_de_creacion_de_orden}");
            
            // Mostrar registros del pedido
            $registros = DB::table('registros_por_orden')
                ->where('pedido', $pedido->pedido)
                ->select('prenda', 'talla', 'cantidad', 'costurero')
                ->get();
            
            $this->info("    Prendas (" . $registros->count() . "):");
            foreach ($registros as $registro) {
                $this->info("      - {$registro->prenda} (Talla: {$registro->talla}, Cantidad: {$registro->cantidad})");
            }
            
            $this->info("");
        }
    }

    private function section($title)
    {
        $this->newLine();
        $this->info("─────────────────────────────────────────────────────");
        $this->info($title);
        $this->info("─────────────────────────────────────────────────────");
    }
}
