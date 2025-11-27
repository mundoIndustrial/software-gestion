<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use Carbon\Carbon;

class MigrarProcesosCorrectamente extends Command
{
    protected $signature = 'migrate:procesos-correctamente {--dry-run : Simular sin guardar}';
    protected $description = 'Migra TODOS los procesos de tabla_original a procesos_prenda con áreas correctas';

    protected $procesosMap = [
        'creacion_de_orden' => [
            'proceso' => 'Creación de Orden',
            'fecha' => 'fecha_de_creacion_de_orden',  // ← La fecha real de inicio
            'encargado' => 'encargado_orden',          // ← El encargado asignado
            'dias' => 'dias_orden',
        ],
        'insumos_y_telas' => [
            'proceso' => 'Insumos y Telas',
            'fecha' => 'insumos_y_telas',
            'encargado' => 'encargados_insumos',
            'dias' => 'dias_insumos',
        ],
        'corte' => [
            'proceso' => 'Corte',
            'fecha' => 'corte',
            'encargado' => 'encargados_de_corte',
            'dias' => 'dias_corte',
        ],
        'bordado' => [
            'proceso' => 'Bordado',
            'fecha' => 'bordado',
            'encargado' => 'codigo_de_bordado',
            'dias' => 'dias_bordado',
        ],
        'estampado' => [
            'proceso' => 'Estampado',
            'fecha' => 'estampado',
            'encargado' => 'encargados_estampado',
            'dias' => 'dias_estampado',
        ],
        'costura' => [
            'proceso' => 'Costura',
            'fecha' => 'costura',
            'encargado' => 'modulo',
            'dias' => 'dias_costura',
        ],
        'reflectivo' => [
            'proceso' => 'Reflectivo',
            'fecha' => 'reflectivo',
            'encargado' => 'encargado_reflectivo',
            'dias' => 'total_de_dias_reflectivo',
        ],
        'lavanderia' => [
            'proceso' => 'Lavandería',
            'fecha' => 'lavanderia',
            'encargado' => 'encargado_lavanderia',
            'dias' => 'dias_lavanderia',
        ],
        'arreglos' => [
            'proceso' => 'Arreglos',
            'fecha' => 'arreglos',
            'encargado' => 'encargado_arreglos',
            'dias' => 'total_de_dias_arreglos',
        ],
        'control_de_calidad' => [
            'proceso' => 'Control Calidad',
            'fecha' => 'control_de_calidad',
            'encargado' => 'encargados_calidad',
            'dias' => 'dias_c_c',
        ],
        'entrega' => [
            'proceso' => 'Entrega',
            'fecha' => 'entrega',
            'encargado' => 'encargados_entrega',
            'dias' => null,
        ],
        'despacho' => [
            'proceso' => 'Despacho',
            'fecha' => 'despacho',
            'encargado' => 'column_52',
            'dias' => null,
        ],
    ];

    protected $stats = [
        'procesos_migrados' => 0,
        'pedidos_actualizados' => 0,
        'areas_asignadas' => 0,
    ];

    public function handle()
    {
        $this->info("\n" . str_repeat("=", 140));
        $this->info("🚀 MIGRACIÓN CORRECTA DE PROCESOS: tabla_original → procesos_prenda");
        $this->info(str_repeat("=", 140) . "\n");

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: Los datos NO se guardarán\n");
        }

        try {
            // PASO 1: Limpiar procesos
            $this->info("🧹 PASO 1: Limpiando procesos_prenda existentes...\n");
            if (!$dryRun) {
                ProcesoPrenda::truncate();
                $this->line("   ✅ Tabla procesos_prenda limpiada");
            } else {
                $this->line("   [DRY-RUN] Se limpiaría tabla procesos_prenda");
            }

            // PASO 2: Migrar procesos
            $this->info("\n📦 PASO 2: Migrando procesos desde tabla_original...\n");
            $this->migrarProcesos($dryRun);

            // PASO 3: Actualizar áreas en pedidos_produccion
            $this->info("\n🗺️  PASO 3: Actualizando áreas basadas en último proceso...\n");
            $this->actualizarAreas($dryRun);

            // Mostrar resumen
            $this->mostrarResumen($dryRun);

        } catch (\Exception $e) {
            $this->error("\n❌ Error: " . $e->getMessage());
            \Log::error('Error en migración de procesos: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function migrarProcesos($dryRun)
    {
        $pedidos = DB::table('tabla_original')->get();
        $this->line("   📊 Pedidos a procesar: " . $pedidos->count());

        $bar = $this->output->createProgressBar($pedidos->count());
        $bar->start();

        $procesos_saltados = 0;

        foreach ($pedidos as $pedidoOriginal) {
            try {
                $pedido = PedidoProduccion::where('numero_pedido', $pedidoOriginal->pedido)->first();

                if (!$pedido) {
                    $bar->advance();
                    continue;
                }

                // VERIFICAR: ¿Este pedido tiene cotizacion_id?
                if ($pedido->cotizacion_id !== null) {
                    // ⚠️ SALTAR: Este pedido tiene cotizacion_id
                    $procesos_saltados++;
                    $bar->advance();
                    continue;
                }

                $ultimaFecha = null;
                $ultimoProceso = null;

                // Migrar todos los procesos usando el mapeo
                foreach ($this->procesosMap as $key => $info) {
                    $fechaValue = $pedidoOriginal->{$info['fecha']} ?? null;

                    if ($fechaValue && $fechaValue !== '0000-00-00' && $fechaValue !== '0000-00-00 00:00:00') {
                        $fecha = $this->parsearFecha($fechaValue);
                        
                        if ($fecha) {
                            $encargado = $pedidoOriginal->{$info['encargado']} ?? null;
                            $dias = null;
                            
                            if ($info['dias']) {
                                $diasValue = $pedidoOriginal->{$info['dias']} ?? null;
                                $dias = is_numeric($diasValue) ? intval($diasValue) : null;
                            }

                            if (!$dryRun) {
                                ProcesoPrenda::updateOrCreate(
                                    [
                                        'numero_pedido' => $pedido->numero_pedido,
                                        'proceso' => $info['proceso'],
                                    ],
                                    [
                                        'fecha_inicio' => $fecha,
                                        'fecha_fin' => $fecha,
                                        'encargado' => $encargado,
                                        'dias_duracion' => $dias,
                                        'estado_proceso' => 'Completado',
                                    ]
                                );
                            }

                            $this->stats['procesos_migrados']++;
                            $ultimaFecha = $fecha;
                            $ultimoProceso = $info['proceso'];
                        }
                    }
                }

                // Actualizar área y fecha_ultimo_proceso del pedido
                if (!$dryRun && $ultimaFecha && $ultimoProceso) {
                    $pedido->update([
                        'area' => $ultimoProceso,
                        'fecha_ultimo_proceso' => $ultimaFecha,
                    ]);
                }

                $bar->advance();

            } catch (\Exception $e) {
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✅ Procesos migrados: {$this->stats['procesos_migrados']}\n");
        if ($procesos_saltados > 0) {
            $this->line("   ⚠️  Procesos saltados (pedido con cotizacion_id): {$procesos_saltados}\n");
        }
    }

    private function actualizarAreas($dryRun)
    {
        $pedidos = PedidoProduccion::all();
        $this->line("   📊 Actualizando áreas en " . $pedidos->count() . " pedidos");

        $bar = $this->output->createProgressBar($pedidos->count());
        $bar->start();

        foreach ($pedidos as $pedido) {
            try {
                // Obtener el proceso con la fecha más reciente
                // Si hay empate de fechas, tomar el que se registró después (id más alto)
                $ultimoProceso = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                    ->orderBy('fecha_inicio', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->first();

                if ($ultimoProceso) {
                    $areaNueva = $ultimoProceso->proceso;
                    
                    if (!$dryRun) {
                        DB::table('pedidos_produccion')
                            ->where('id', $pedido->id)
                            ->update([
                                'area' => $areaNueva,
                                'fecha_ultimo_proceso' => $ultimoProceso->fecha_inicio,
                                'updated_at' => now(),
                            ]);
                    }

                    $this->stats['areas_asignadas']++;
                } else {
                    // Si no hay procesos, asignar "Creación Orden" con fecha de creación
                    $areaNueva = 'Creación Orden';
                    
                    if (!$dryRun) {
                        DB::table('pedidos_produccion')
                            ->where('id', $pedido->id)
                            ->update([
                                'area' => $areaNueva,
                                'fecha_ultimo_proceso' => $pedido->fecha_de_creacion_de_orden,
                                'updated_at' => now(),
                            ]);
                    }

                    $this->stats['areas_asignadas']++;
                }

                $bar->advance();

            } catch (\Exception $e) {
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✅ Áreas actualizadas: {$this->stats['areas_asignadas']}\n");
    }

    private function parsearFecha($fecha)
    {
        if (!$fecha || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', substr($fecha, 0, 10))->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function mostrarResumen($dryRun)
    {
        $this->info("\n" . str_repeat("=", 140));
        $this->info("📊 RESUMEN DE MIGRACIÓN DE PROCESOS");
        $this->info(str_repeat("=", 140));

        $tabla = $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Procesos migrados', $this->stats['procesos_migrados']],
                ['Áreas asignadas', $this->stats['areas_asignadas']],
            ]
        );

        if ($dryRun) {
            $this->warn("\n⚠️  MODO DRY-RUN: Los datos NO fueron guardados");
        } else {
            $this->info("\n✅ MIGRACIÓN DE PROCESOS COMPLETADA EXITOSAMENTE");
        }

        $this->info(str_repeat("=", 140) . "\n");
    }
}
