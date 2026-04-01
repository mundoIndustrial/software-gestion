<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeCotizacionesVsPedidos extends Command
{
    protected $signature = 'analyze:cotizaciones-vs-pedidos';
    protected $description = 'Analizar diferencia entre estructura de cotizaciones vs pedidos';

    public function handle()
    {
        $this->info('════════════════════════════════════════════════════════');
        $this->info(' ANÁLISIS: COTIZACIONES vs PEDIDOS');
        $this->info('════════════════════════════════════════════════════════');
        $this->newLine();

        // ESTRUCTURA DE COTIZACIONES (DDD Normalizado)
        $this->line('═════════════════════════════════════════════════════════');
        $this->info(' ESTRUCTURA PARA COTIZACIONES (DDD - Normalizado)');
        $this->line('═════════════════════════════════════════════════════════');
        $this->newLine();

        $this->showTableInfo('cotizaciones');
        $this->showTableInfo('prendas_cot');
        $this->showTableInfo('prenda_fotos_cot');
        $this->showTableInfo('prenda_telas_cot');
        $this->showTableInfo('prenda_tallas_cot');
        $this->showTableInfo('prenda_variantes_cot');

        $this->newLine();

        // ESTRUCTURA DE PEDIDOS (Legacy)
        $this->line('═════════════════════════════════════════════════════════');
        $this->info(' ESTRUCTURA PARA PEDIDOS (Legacy)');
        $this->line('═════════════════════════════════════════════════════════');
        $this->newLine();

        $this->showTableInfo('pedidos_produccion');
        $this->showTableInfo('prendas_pedido');
        $this->showTableInfo('prenda_fotos_pedido');
        $this->showTableInfo('prenda_fotos_tela_pedido');

        $this->newLine();

        // DIAGNÓSTICO DE COTIZACIÓN 2
        $this->line('═════════════════════════════════════════════════════════');
        $this->info(' DIAGNÓSTICO: ¿Dónde se guardó la Cotización 2?');
        $this->line('═════════════════════════════════════════════════════════');
        $this->newLine();

        $cotizacion = DB::table('cotizaciones')->where('id', 2)->first();

        if ($cotizacion) {
            $this->info(' Cotización 2 ENCONTRADA:');
            $this->line("   ID: {$cotizacion->id}");
            $this->line("   Cliente ID: {$cotizacion->cliente_id}");
            $this->line("   Tipo: " . ($cotizacion->tipo ?? 'NULL'));
            $this->line("   Tipo Venta: {$cotizacion->tipo_venta}");
            $this->newLine();

            // Buscar en prendas_cot
            $prendasCot = DB::table('prendas_cot')->where('cotizacion_id', 2)->count();
            $this->line(" En tabla `prendas_cot` (Estructura de COTIZACIONES):");
            $this->line("   ├─ prendas_cot con cotizacion_id=2: $prendasCot");

            if ($prendasCot > 0) {
                $prend = DB::table('prendas_cot')->where('cotizacion_id', 2)->get();
                foreach ($prend as $p) {
                    $this->line("   │  └─ ID {$p->id}: {$p->nombre_producto}");
                }
            }

            $this->newLine();

            // Buscar en prendas_pedido
            $prendasPedido = DB::table('prendas_pedido')->count();
            $this->line(" En tabla `prendas_pedido` (Estructura de PEDIDOS):");
            $this->line("   ├─ TOTAL prendas_pedido: $prendasPedido");
            $this->line("   └─   NO tiene cotizacion_id, solo numero_pedido");

            $this->newLine();

            // Diagnóstico
            $this->line('═════════════════════════════════════════════════════════');
            $this->warn('  PROBLEMA IDENTIFICADO:');
            $this->line('═════════════════════════════════════════════════════════');
            $this->newLine();

            if ($prendasCot === 0) {
                $this->error(' NO hay prendas en prendas_cot para esta cotización');
                $this->newLine();
                $this->line(' CAUSA RAÍZ:');
                $this->line('   El controlador está usando la estructura INCORRECTA');
                $this->line('   para guardar COTIZACIONES.');
                $this->newLine();
                $this->line(' Está usando:');
                $this->line('   └─ prendas_pedido (estructura para PEDIDOS)');
                $this->newLine();
                $this->line(' Debería usar:');
                $this->line('   └─ prendas_cot (estructura para COTIZACIONES)');
                $this->newLine();
            } else {
                $this->info(' Prendas guardadas en la estructura correcta');
            }
        } else {
            $this->error(' Cotización 2 no encontrada');
        }

        $this->newLine();
    }

    protected function showTableInfo($tableName)
    {
        try {
            $columns = DB::select("
                SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ", [$tableName]);

            if (empty($columns)) {
                $this->line(" Tabla: $tableName (NO EXISTE)");
                return;
            }

            $count = DB::table($tableName)->count();
            $this->line(" Tabla: $tableName");
            $this->line("   Columnas:");

            foreach ($columns as $col) {
                $this->line("      • {$col->COLUMN_NAME}");
            }

            $this->line("    Registros: $count");
            $this->newLine();

        } catch (\Exception $e) {
            $this->line(" Tabla: $tableName (ERROR)");
            $this->newLine();
        }
    }
}
