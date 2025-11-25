<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeDataMigration extends Command
{
    protected $signature = 'analyze:migration';
    protected $description = 'Analiza la estructura de datos antigua vs nueva para planificar migración';

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 100));
        $this->info("ANÁLISIS PRE-MIGRACIÓN: ARQUITECTURA ANTIGUA vs NUEVA");
        $this->info(str_repeat("=", 100) . "\n");

        // ============================================
        // 1. ANALIZAR tabla_original
        // ============================================
        $this->info("1️⃣  TABLA ANTIGUA: tabla_original");
        $this->info(str_repeat("-", 100));

        $totalPedidosAntiguos = DB::table('tabla_original')->count();
        $pedidosConAsesora = DB::table('tabla_original')->whereNotNull('asesora')->count();
        $pedidosConCliente = DB::table('tabla_original')->whereNotNull('cliente')->count();

        $this->line("   Total pedidos: $totalPedidosAntiguos");
        $this->line("   Pedidos con asesora: $pedidosConAsesora");
        $this->line("   Pedidos con cliente: $pedidosConCliente\n");

        // Obtener muestra de datos
        $muestraPedidos = DB::table('tabla_original')->limit(3)->get();
        $this->line("   MUESTRA de datos:");
        foreach ($muestraPedidos as $i => $ped) {
            $this->line("\n   Pedido $i:");
            $this->line("     - Pedido #: {$ped->pedido}");
            $this->line("     - Cliente: {$ped->cliente}");
            $this->line("     - Asesora: {$ped->asesora}");
            $this->line("     - Estado: {$ped->estado}");
            $this->line("     - Descripción: " . substr($ped->descripcion ?? '', 0, 60) . "...");
            $this->line("     - Cantidad: {$ped->cantidad}");
        }

        // ============================================
        // 2. ANALIZAR registros_por_orden
        // ============================================
        $this->info("\n\n2️⃣  TABLA ANTIGUA: registros_por_orden");
        $this->info(str_repeat("-", 100));

        $totalRegistrosAntiguos = DB::table('registros_por_orden')->count();
        $totalPrendas = DB::table('registros_por_orden')->distinct()->pluck('prenda')->count();
        $totalTallas = DB::table('registros_por_orden')->distinct()->pluck('talla')->count();

        $this->line("   Total registros: $totalRegistrosAntiguos");
        $this->line("   Prendas únicas: $totalPrendas");
        $this->line("   Tallas únicas: $totalTallas\n");

        // Agrupar por pedido
        $registrosPorPedido = DB::table('registros_por_orden')
            ->selectRaw('pedido, COUNT(*) as total_registros, COUNT(DISTINCT prenda) as prendas_unicas')
            ->groupBy('pedido')
            ->limit(3)
            ->get();

        $this->line("   MUESTRA de registros por pedido:");
        foreach ($registrosPorPedido as $reg) {
            $this->line("     Pedido #{$reg->pedido}: {$reg->total_registros} registros, {$reg->prendas_unicas} prendas");
            
            $detalles = DB::table('registros_por_orden')
                ->where('pedido', $reg->pedido)
                ->limit(2)
                ->get();
            
            foreach ($detalles as $det) {
                $this->line("       - {$det->prenda} (Talla: {$det->talla}, Cant: {$det->cantidad})");
            }
        }

        // ============================================
        // 3. DATOS EXISTENTES EN NUEVA TABLA
        // ============================================
        $this->info("\n\n3️⃣  TABLA NUEVA: pedidos_produccion (estado actual)");
        $this->info(str_repeat("-", 100));

        $totalPedidosNuevos = DB::table('pedidos_produccion')->count();
        $this->line("   Pedidos existentes en tabla nueva: $totalPedidosNuevos\n");

        if ($totalPedidosNuevos > 0) {
            $muestraNuevos = DB::table('pedidos_produccion')->limit(2)->get();
            $this->line("   MUESTRA de pedidos nuevos:");
            foreach ($muestraNuevos as $ped) {
                $this->line("     - Pedido #{$ped->numero_pedido}: cliente_id={$ped->cliente_id}, asesor_id={$ped->asesor_id}");
            }
        }

        // ============================================
        // 4. ANÁLISIS DE USUARIOS Y CLIENTES
        // ============================================
        $this->info("\n\n4️⃣  USUARIOS Y CLIENTES EXISTENTES");
        $this->info(str_repeat("-", 100));

        $totalUsuarios = DB::table('users')->count();
        $totalClientes = DB::table('clientes')->count();
        $this->line("   Usuarios existentes: $totalUsuarios");
        $this->line("   Clientes existentes: $totalClientes\n");

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

        $this->line("   Asesoras únicas en tabla_original: $asesorasUnicas");
        $this->line("   Clientes únicos en tabla_original: $clientesUnicos");

        // ============================================
        // 5. PLAN DE MIGRACIÓN
        // ============================================
        $this->info("\n\n5️⃣  PLAN DE MIGRACIÓN PROPUESTO");
        $this->info(str_repeat("=", 100));

        $this->line("   PASO 1: Crear usuarios (asesoras)");
        $this->line("           - Crear $asesorasUnicas usuarios si no existen");
        $this->line("           - Basados en: tabla_original.asesora\n");

        $this->line("   PASO 2: Crear clientes");
        $this->line("           - Crear $clientesUnicos clientes si no existen");
        $this->line("           - Basados en: tabla_original.cliente\n");

        $this->line("   PASO 3: Migrar pedidos");
        $this->line("           - Insertar $totalPedidosAntiguos pedidos a pedidos_produccion");
        $this->line("           - Relacionar con asesor_id y cliente_id");
        $this->line("           - Copiar: numero_pedido, cliente (string), asesor_id, cliente_id, estado, etc.\n");

        $this->line("   PASO 4: Migrar prendas");
        $this->line("           - Agrupar $totalRegistrosAntiguos registros por pedido + prenda");
        $this->line("           - Crear prendas en prendas_pedido");
        $this->line("           - Guardar tallas en JSON (cantidad_talla)\n");

        // ============================================
        // 6. VERIFICACIONES PREVIAS
        // ============================================
        $this->info("\n6️⃣  VERIFICACIONES PREVIAS NECESARIAS");
        $this->info(str_repeat("-", 100));

        $pedidosSinAsesora = DB::table('tabla_original')->whereNull('asesora')->count();
        $pedidosSinCliente = DB::table('tabla_original')->whereNull('cliente')->count();
        $registrosSinPrenda = DB::table('registros_por_orden')->whereNull('prenda')->count();

        $this->line("   ⚠️  Pedidos sin asesora: $pedidosSinAsesora");
        $this->line("   ⚠️  Pedidos sin cliente: $pedidosSinCliente");
        $this->line("   ⚠️  Registros sin prenda: $registrosSinPrenda\n");

        if ($pedidosSinAsesora > 0 || $pedidosSinCliente > 0 || $registrosSinPrenda > 0) {
            $this->warn("   ⚠️  ATENCIÓN: Hay datos incompletos que necesitan revisión manual\n");
        }

        // ============================================
        // 7. RESUMEN
        // ============================================
        $this->info("\n7️⃣  RESUMEN DE MIGRACIÓN");
        $this->info(str_repeat("=", 100));
        $this->line("   - Usuarios a crear: $asesorasUnicas");
        $this->line("   - Clientes a crear: $clientesUnicos");
        $this->line("   - Pedidos a migrar: $totalPedidosAntiguos");
        $this->line("   - Registros a procesar: $totalRegistrosAntiguos");
        $this->line("   - Pedidos ya en nueva tabla: $totalPedidosNuevos\n");

        $this->info(str_repeat("=", 100));
        $this->info("✅ ANÁLISIS COMPLETADO");
        $this->info(str_repeat("=", 100) . "\n");

        return 0;
    }
}
