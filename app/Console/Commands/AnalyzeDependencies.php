<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeDependencies extends Command
{
    protected $signature = 'analyze:dependencies';
    protected $description = 'Analiza todas las tablas conectadas a tabla_original y registros_por_orden';

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 120));
        $this->info("ANÁLISIS DE DEPENDENCIAS: TABLAS CONECTADAS A tabla_original");
        $this->info(str_repeat("=", 120) . "\n");

        // ============================================
        // 1. TABLAS QUE REFERENCIAN tabla_original
        // ============================================
        $this->info("1️⃣  TABLAS CONECTADAS A tabla_original");
        $this->info(str_repeat("-", 120));

        // registros_por_orden
        $regPorOrdenCount = DB::table('registros_por_orden')->count();
        $this->line("   📊 registros_por_orden: $regPorOrdenCount registros (detalle de prendas por talla)");
        $this->line("      Relaciona: tabla_original.pedido = registros_por_orden.pedido");
        $this->line("      Estructura: pedido → prenda → talla → cantidad");

        // registros_por_orden_bodega
        $regBodegaCount = DB::table('registros_por_orden_bodega')->count();
        $this->line("\n   📊 registros_por_orden_bodega: $regBodegaCount registros");
        $this->line("      Relaciona: tabla_original_bodega.pedido = registros_por_orden_bodega.pedido");

        // tabla_original_bodega
        $tablaOriginalBodegaCount = DB::table('tabla_original_bodega')->count();
        $this->line("\n   📊 tabla_original_bodega: $tablaOriginalBodegaCount registros");
        $this->line("      Parece ser copia/referencia de tabla_original");

        // ============================================
        // 2. ANÁLISIS DE COLUMNAS QUE DEBEN MIGRARSE
        // ============================================
        $this->info("\n\n2️⃣  COLUMNAS DE tabla_original QUE DEBEN MIGRARSE");
        $this->info(str_repeat("-", 120));

        $columnas = DB::select("DESCRIBE tabla_original");
        
        $columnasMapping = [
            'pedido' => 'numero_pedido (PK)',
            'cliente' => 'cliente (string) + cliente_id (FK)',
            'asesora' => 'asesor_id (FK) + relación asesora()',
            'estado' => 'estado (enum)',
            'forma_de_pago' => 'forma_de_pago (string)',
            'novedades' => 'novedades (text)',
            'fecha_de_creacion_de_orden' => 'fecha_de_creacion_de_orden (date)',
            'dia_de_entrega' => 'dia_de_entrega (int)',
            'fecha_estimada_de_entrega' => 'fecha_estimada_de_entrega (date)',
            'descripcion' => '❌ NO migrar (está en registros_por_orden)',
            'cantidad' => '❌ NO migrar (suma de registros_por_orden)',
        ];

        foreach ($columnasMapping as $origen => $destino) {
            $this->line("   $origen → $destino");
        }

        // ============================================
        // 3. ESTRUCTURA DE MIGRACIÓN PROPUESTA
        // ============================================
        $this->info("\n\n3️⃣  ESTRUCTURA DE DATOS POST-MIGRACIÓN");
        $this->info(str_repeat("=", 120));

        $this->line("\n   📋 pedidos_produccion (nueva tabla de pedidos)");
        $this->line("      - id (PK)");
        $this->line("      - numero_pedido (unique) ← tabla_original.pedido");
        $this->line("      - cliente (string) ← tabla_original.cliente");
        $this->line("      - cliente_id (FK) → clientes.id");
        $this->line("      - asesor_id (FK) → users.id ← tabla_original.asesora");
        $this->line("      - estado ← tabla_original.estado");
        $this->line("      - forma_de_pago ← tabla_original.forma_de_pago");
        $this->line("      - novedades ← tabla_original.novedades");
        $this->line("      - fecha_de_creacion_de_orden ← tabla_original.fecha_de_creacion_de_orden");
        $this->line("      - dia_de_entrega ← tabla_original.dia_de_entrega");
        $this->line("      - fecha_estimada_de_entrega ← tabla_original.fecha_estimada_de_entrega");

        $this->line("\n   📋 prendas_pedido (nueva tabla de prendas)");
        $this->line("      - id (PK)");
        $this->line("      - pedido_produccion_id (FK) → pedidos_produccion.id");
        $this->line("      - nombre_prenda ← registros_por_orden.prenda (agrupar)");
        $this->line("      - cantidad (varchar) ← suma de tallas en registros_por_orden");
        $this->line("      - descripcion ← registros_por_orden.descripcion");
        $this->line("      - cantidad_talla (JSON) ← array de {talla, cantidad} desde registros_por_orden");

        // ============================================
        // 4. PROCESOS DETALLADOS
        // ============================================
        $this->info("\n\n4️⃣  PROCESOS DETALLADOS DE MIGRACIÓN");
        $this->info(str_repeat("=", 120));

        $this->line("\n   PROCESO 1: Crear usuarios (asesoras)");
        $asesorasUnicas = DB::table('tabla_original')
            ->distinct()
            ->whereNotNull('asesora')
            ->pluck('asesora')
            ->filter(fn($a) => !empty(trim($a)))
            ->count();
        $this->line("      → Crear $asesorasUnicas usuarios nuevos");
        $this->line("      → Para pedidos sin asesora (527): asignar NULL");

        $this->line("\n   PROCESO 2: Crear clientes");
        $clientesUnicos = DB::table('tabla_original')
            ->distinct()
            ->whereNotNull('cliente')
            ->pluck('cliente')
            ->count();
        $this->line("      → Crear $clientesUnicos clientes nuevos");
        $this->line("      → Para pedidos sin cliente (3): asignar NULL");

        $this->line("\n   PROCESO 3: Migrar 2256 pedidos");
        $this->line("      → Insertar en pedidos_produccion");
        $this->line("      → Validar relaciones con users y clientes");

        $this->line("\n   PROCESO 4: Procesar 6642 registros (prendas)");
        $this->line("      → Agrupar por: pedido + prenda");
        $this->line("      → Crear 1 fila en prendas_pedido por prenda");
        $this->line("      → Agrupar tallas en JSON cantidad_talla");

        // ============================================
        // 5. DATOS PROBLEMÁTICOS
        // ============================================
        $this->info("\n\n5️⃣  DATOS PROBLEMÁTICOS A REVISAR");
        $this->info(str_repeat("-", 120));

        $pedidosSinAsesora = DB::table('tabla_original')->whereNull('asesora')->count();
        $pedidosSinCliente = DB::table('tabla_original')->whereNull('cliente')->count();
        $registrosSinPrenda = DB::table('registros_por_orden')->whereNull('prenda')->count();

        $this->warn("   ❌ Pedidos sin asesora: $pedidosSinAsesora (NULL)");
        $this->warn("   ❌ Pedidos sin cliente: $pedidosSinCliente (NULL)");
        $this->warn("   ❌ Registros sin prenda: $registrosSinPrenda (skip)");

        // Mostrar ejemplos
        $ejemplosSinAsesora = DB::table('tabla_original')
            ->whereNull('asesora')
            ->limit(2)
            ->get(['pedido', 'cliente', 'estado']);
        
        if ($ejemplosSinAsesora->isNotEmpty()) {
            $this->line("\n   Ejemplos de pedidos SIN asesora:");
            foreach ($ejemplosSinAsesora as $ej) {
                $this->line("      - Pedido #{$ej->pedido}: cliente={$ej->cliente}, estado={$ej->estado}");
            }
        }

        // ============================================
        // 6. ORDEN DE EJECUCIÓN
        // ============================================
        $this->info("\n\n6️⃣  ORDEN DE EJECUCIÓN RECOMENDADO");
        $this->info(str_repeat("=", 120));

        $this->line("   1. Crear usuarios (asesoras) - 37 nuevos");
        $this->line("   2. Crear clientes - 964 nuevos");
        $this->line("   3. Migrar pedidos a pedidos_produccion - 2256 pedidos");
        $this->line("   4. Procesar registros → prendas_pedido - 6642 registros → ~1821 prendas");
        $this->line("   5. Verificar integridad referencial");
        $this->line("   6. Vaciar/archivar tablas antiguas (opcional)");

        $this->info("\n" . str_repeat("=", 120));
        $this->info("✅ ANÁLISIS DE DEPENDENCIAS COMPLETADO");
        $this->info(str_repeat("=", 120) . "\n");

        return 0;
    }
}
