<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeFullSchema extends Command
{
    protected $signature = 'analyze:full-schema';
    protected $description = 'Análisis completo de todas las tablas relacionadas a pedidos_produccion';

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 140));
        $this->info("ANÁLISIS COMPLETO: TODAS LAS TABLAS RELACIONADAS A pedidos_produccion");
        $this->info(str_repeat("=", 140) . "\n");

        // ============================================
        // 1. TABLAS QUE YA APUNTAN A pedidos_produccion
        // ============================================
        $this->info("1️⃣  TABLAS YA CONECTADAS A pedidos_produccion (Nueva arquitectura)");
        $this->info(str_repeat("-", 140));

        $tablasConectadas = [
            'prendas_pedido' => [
                'fk' => 'pedido_produccion_id',
                'count' => DB::table('prendas_pedido')->count(),
                'description' => 'Prendas del pedido con variaciones (tallas, colores, etc.)',
            ],
            'procesos_prenda' => [
                'fk' => 'prenda_pedido_id → prendas_pedido → pedido_produccion_id',
                'count' => DB::table('procesos_prenda')->count(),
                'description' => 'Procesos de producción (corte, costura, etc.)',
            ],
            'cotizaciones' => [
                'fk' => 'Inversa: cotizacion_id en pedidos_produccion',
                'count' => DB::table('cotizaciones')->count(),
                'description' => 'Cotizaciones que generan pedidos',
            ],
        ];

        foreach ($tablasConectadas as $tabla => $info) {
            $this->line("   📋 $tabla");
            $this->line("      FK: {$info['fk']}");
            $this->line("      Registros: {$info['count']}");
            $this->line("      {$info['description']}\n");
        }

        // ============================================
        // 2. TABLAS ANTIGUAS QUE DEBEN MIGRARSE
        // ============================================
        $this->info("\n2️⃣  TABLAS ANTIGUAS RELACIONADAS (Que deben migrarse)");
        $this->info(str_repeat("-", 140));

        $tablasAntiguas = [
            'tabla_original' => [
                'count' => DB::table('tabla_original')->count(),
                'pk' => 'pedido',
                'description' => '✅ TABLA PADRE - Pedidos principales (SE MIGRA)',
                'destino' => 'pedidos_produccion',
            ],
            'registros_por_orden' => [
                'count' => DB::table('registros_por_orden')->count(),
                'pk' => 'pedido + prenda + talla',
                'description' => '✅ Detalles de prendas por talla (SE MIGRA)',
                'destino' => 'prendas_pedido (con JSON)',
            ],
            'tabla_original_bodega' => [
                'count' => DB::table('tabla_original_bodega')->count(),
                'pk' => 'pedido',
                'description' => '⏸️  Copia de tabla_original para bodega (NO SE TOCA POR AHORA)',
                'destino' => 'Se maneja por separado después',
            ],
            'registros_por_orden_bodega' => [
                'count' => DB::table('registros_por_orden_bodega')->count(),
                'pk' => 'pedido + prenda + talla',
                'description' => '⏸️  Detalles de bodega (NO SE TOCA POR AHORA)',
                'destino' => 'Se maneja por separado después',
            ],
        ];

        foreach ($tablasAntiguas as $tabla => $info) {
            $this->line("   📊 $tabla");
            $this->line("      Registros: {$info['count']}");
            $this->line("      PK: {$info['pk']}");
            $this->line("      {$info['description']}");
            $this->line("      → Migrar a: {$info['destino']}\n");
        }

        // ============================================
        // 3. TABLAS DE REFERENCIA (Deben existir)
        // ============================================
        $this->info("\n3️⃣  TABLAS DE REFERENCIA (Foreign Keys)");
        $this->info(str_repeat("-", 140));

        $tablasReferencia = [
            'users' => [
                'count' => DB::table('users')->count(),
                'uso' => 'asesor_id en pedidos_produccion',
                'referencia' => 'Usuarios (asesoras)',
            ],
            'clientes' => [
                'count' => DB::table('clientes')->count(),
                'uso' => 'cliente_id en pedidos_produccion',
                'referencia' => 'Clientes',
            ],
            'cotizaciones' => [
                'count' => DB::table('cotizaciones')->count(),
                'uso' => 'cotizacion_id en pedidos_produccion',
                'referencia' => 'Cotizaciones que generan pedidos',
            ],
            'prendas_cotizaciones' => [
                'count' => DB::table('prendas_cotizaciones')->count(),
                'uso' => 'Referencias a prendas en cotizaciones',
                'referencia' => 'Detalles de prendas en cotizaciones',
            ],
        ];

        foreach ($tablasReferencia as $tabla => $info) {
            $this->line("   🔗 $tabla: {$info['count']} registros");
            $this->line("      Uso: {$info['uso']}");
            $this->line("      {$info['referencia']}\n");
        }

        // ============================================
        // 4. MAPA DE MIGRACION COMPLETO
        // ============================================
        $this->info("\n4️⃣  MAPA DE MIGRACIÓN COMPLETO");
        $this->info(str_repeat("=", 140));

        $this->line("\n   🔄 FLUJO DE DATOS:\n");

        $this->line("   ANTIGUA (tabla_original)");
        $this->line("   ├── pedido");
        $this->line("   ├── cliente");
        $this->line("   ├── asesora");
        $this->line("   ├── estado");
        $this->line("   └── otros campos");
        $this->line("            ↓");
        $this->line("   NUEVA (pedidos_produccion)");
        $this->line("   ├── numero_pedido");
        $this->line("   ├── cliente_id (FK)");
        $this->line("   ├── asesor_id (FK)");
        $this->line("   ├── estado");
        $this->line("   └── otros campos\n");

        $this->line("   ANTIGUA (registros_por_orden)");
        $this->line("   ├── pedido");
        $this->line("   ├── prenda");
        $this->line("   ├── talla");
        $this->line("   ├── cantidad");
        $this->line("   └── descripcion");
        $this->line("            ↓");
        $this->line("   NUEVA (prendas_pedido)");
        $this->line("   ├── pedido_produccion_id (FK)");
        $this->line("   ├── nombre_prenda");
        $this->line("   ├── cantidad");
        $this->line("   ├── descripcion");
        $this->line("   └── cantidad_talla (JSON: [{talla, cantidad}])\n");

        // ============================================
        // 5. DEPENDENCIAS A CREAR ANTES DE MIGRAR
        // ============================================
        $this->info("\n5️⃣  ORDEN DE CREACIÓN DE DEPENDENCIAS");
        $this->info(str_repeat("-", 140));

        $this->line("   PASO 1: Crear/Verificar usuarios (asesoras)");
        $this->line("           → 37 usuarios nuevos basados en tabla_original.asesora");
        $this->line("           → Crear usuario 'SIN_ASESORA' para NULL\n");

        $this->line("   PASO 2: Crear/Verificar clientes");
        $this->line("           → 964 clientes nuevos basados en tabla_original.cliente");
        $this->line("           → Crear cliente 'SIN_CLIENTE' para NULL\n");

        $this->line("   PASO 3: Verificar cotizaciones (ya deben existir)");
        $totalCotizaciones = DB::table('cotizaciones')->count();
        $this->line("           → Cotizaciones existentes: $totalCotizaciones\n");

        // ============================================
        // 6. PLAN DE MIGRACIÓN PASO A PASO
        // ============================================
        $this->info("\n6️⃣  PLAN DE MIGRACIÓN PASO A PASO");
        $this->info(str_repeat("=", 140));

        $this->line("\n   FASE 1: PREPARACIÓN");
        $this->line("   ├─ Crear 37 usuarios (asesoras)");
        $this->line("   ├─ Crear 964 clientes");
        $this->line("   └─ Crear usuarios/clientes NULL si necesario\n");

        $this->line("   FASE 2: MIGRACIÓN DE PEDIDOS");
        $this->line("   ├─ Leer 2256 registros de tabla_original");
        $this->line("   ├─ Validar integridad referencial");
        $this->line("   ├─ Insertar en pedidos_produccion con:");
        $this->line("   │  ├─ numero_pedido");
        $this->line("   │  ├─ cliente (string)");
        $this->line("   │  ├─ cliente_id (FK)");
        $this->line("   │  ├─ asesor_id (FK)");
        $this->line("   │  ├─ estado, forma_de_pago, etc.");
        $this->line("   │  └─ timestamps");
        $this->line("   └─ Guardar mapeo: tabla_original.pedido → pedidos_produccion.id\n");

        $this->line("   FASE 3: MIGRACIÓN DE PRENDAS");
        $this->line("   ├─ Leer 6642 registros de registros_por_orden");
        $this->line("   ├─ Agrupar por: pedido + prenda");
        $this->line("   ├─ Para cada prenda:");
        $this->line("   │  ├─ Obtener pedido_produccion_id del mapeo");
        $this->line("   │  ├─ Crear fila en prendas_pedido");
        $this->line("   │  └─ Guardar tallas en JSON cantidad_talla");
        $this->line("   └─ Total prendas esperadas: ~1821\n");

        $this->line("   FASE 4: VALIDACIÓN");
        $this->line("   ├─ Verificar todas las FKs");
        $this->line("   ├─ Validar cantidades totales");
        $this->line("   ├─ Contar registros antes/después");
        $this->line("   └─ Generar reporte de migración\n");

        $this->line("   ⏸️  TABLAS DE BODEGA (NO SE TOCAN POR AHORA)");
        $this->line("   ├─ tabla_original_bodega: 185 registros");
        $this->line("   ├─ registros_por_orden_bodega: 770 registros");
        $this->line("   └─ Se procesan por separado después\n");

        // ============================================
        // 7. ESTADÍSTICAS PRE-MIGRACIÓN
        // ============================================
        $this->info("\n7️⃣  ESTADÍSTICAS PRE-MIGRACIÓN");
        $this->info(str_repeat("=", 140));

        $totalPedidosAntiguos = DB::table('tabla_original')->count();
        $totalRegistrosAntiguos = DB::table('registros_por_orden')->count();
        $totalPrendas = DB::table('registros_por_orden')->distinct()->pluck('prenda')->count();
        $totalTallas = DB::table('registros_por_orden')->distinct()->pluck('talla')->count();

        $this->line("   📈 VOLUMEN DE DATOS");
        $this->line("      Pedidos a migrar: $totalPedidosAntiguos");
        $this->line("      Registros (prendas) a procesar: $totalRegistrosAntiguos");
        $this->line("      Prendas únicas: $totalPrendas");
        $this->line("      Tallas únicas: $totalTallas");
        $this->line("      Usuarios a crear: 37");
        $this->line("      Clientes a crear: 964\n");

        $this->line("   ⚠️  DATOS INCOMPLETOS");
        $pedidosSinAsesora = DB::table('tabla_original')->whereNull('asesora')->count();
        $pedidosSinCliente = DB::table('tabla_original')->whereNull('cliente')->count();
        $registrosSinPrenda = DB::table('registros_por_orden')->whereNull('prenda')->count();
        $this->line("      Pedidos sin asesora: $pedidosSinAsesora (será NULL)");
        $this->line("      Pedidos sin cliente: $pedidosSinCliente (será NULL)");
        $this->line("      Registros sin prenda: $registrosSinPrenda (se skippean)\n");

        $this->info(str_repeat("=", 140));
        $this->info("✅ ANÁLISIS COMPLETO DEL ESQUEMA COMPLETADO");
        $this->info(str_repeat("=", 140) . "\n");

        return 0;
    }
}
