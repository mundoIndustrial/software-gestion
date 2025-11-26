<?php

namespace App\Console\Commands;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\TablaOriginal;
use App\Models\RegistrosPorOrden;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTablaOriginalToPedidosProduccion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:tabla-original-to-pedidos-produccion {--dry-run}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Migra todos los datos de tabla_original a pedidos_produccion con sus relaciones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║  Migración: tabla_original → pedidos_produccion       ║');
        $this->info('╚════════════════════════════════════════════════════════╝');
        
        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios en la BD');
        }

        $this->info("\n📊 Analizando datos...\n");

        // Contar registros
        $totalOrdenes = TablaOriginal::count();
        $totalRegistros = DB::table('registros_por_orden')->count();

        $this->info("Total de órdenes en tabla_original: {$totalOrdenes}");
        $this->info("Total de registros en registros_por_orden: {$totalRegistros}");

        if ($totalOrdenes === 0) {
            $this->warn('⚠️  No hay datos para migrar en tabla_original');
            return 0;
        }

        if (!$this->confirm("\n¿Deseas continuar con la migración?")) {
            $this->info('Migración cancelada.');
            return 0;
        }

        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            $bar = $this->output->createProgressBar($totalOrdenes);
            $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %message%');
            $bar->setMessage('Iniciando migración...');

            $migrados = 0;
            $errores = 0;

            // Iterar sobre todas las órdenes
            TablaOriginal::chunk(100, function ($ordenes) use (&$migrados, &$errores, $dryRun, $bar) {
                foreach ($ordenes as $orden) {
                    try {
                        $this->migrateOrden($orden, $dryRun);
                        $migrados++;
                    } catch (\Exception $e) {
                        $errores++;
                        $this->error("\n❌ Error migrando pedido #{$orden->pedido}: {$e->getMessage()}");
                    }
                    $bar->advance();
                }
            });

            $bar->finish();

            $this->newLine();
            $this->info("\n═══════════════════════════════════════════════════════");
            $this->info("✅ Migración completada");
            $this->info("═══════════════════════════════════════════════════════");
            $this->info("Órdenes migradas: {$migrados}");
            $this->info("Errores: {$errores}");

            if (!$dryRun) {
                DB::commit();
                $this->info("\n✅ Cambios confirmados en la base de datos");
            } else {
                $this->warn("⚠️  Modo DRY-RUN: No se realizaron cambios");
            }

            return 0;

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $this->error("❌ Error en la migración: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Migra una orden individual con todos sus datos
     */
    private function migrateOrden($orden, $dryRun = false)
    {
        // Crear pedido de producción
        $numeroPedido = $orden->pedido;

        // Verificar si ya existe
        $pedidoExistente = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        
        if ($pedidoExistente) {
            return; // Ya migrado
        }

        // Crear nuevo pedido (sin cotización, ya que es dato histórico)
        $pedido = [
            'numero_pedido' => $numeroPedido,
            'cliente' => $orden->cliente,
            'novedades' => $orden->novedades,
            'forma_de_pago' => $orden->forma_de_pago,
            'estado' => $orden->estado ?? 'No iniciado',
            'fecha_de_creacion_de_orden' => $orden->fecha_de_creacion_de_orden,
            'dia_de_entrega' => $orden->dia_de_entrega,
            'fecha_estimada_de_entrega' => $orden->fecha_estimada_de_entrega,
            'created_at' => $orden->created_at,
            'updated_at' => $orden->updated_at,
        ];

        if (!$dryRun) {
            // Usar insert directo para evadir observers
            $pedidoProduccion = PedidoProduccion::create($pedido);
        } else {
            $pedidoProduccion = (object) $pedido;
        }

        // Obtener registros por orden (prendas)
        $registros = DB::table('registros_por_orden')
            ->where('pedido', $numeroPedido)
            ->get();

        // Crear prendas del pedido
        $procesos = [];
        foreach ($registros as $registro) {
            $prenda = [
                'pedido_produccion_id' => $pedidoProduccion->id ?? 0,
                'nombre_prenda' => $registro->prenda,
                'cantidad' => $registro->cantidad,
                'descripcion' => $registro->descripcion,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!$dryRun) {
                $prendaCreada = PrendaPedido::create($prenda);
                $prendaId = $prendaCreada->id;
            } else {
                $prendaId = 0;
            }

            // Crear procesos basado en los campos de área y fechas de tabla_original
            $procesosPrenda = $this->generarProcesosDeLaOrden($orden, $prendaId);
            
            if (!$dryRun) {
                foreach ($procesosPrenda as $proceso) {
                    ProcesoPrenda::create($proceso);
                }
            }
        }
    }

    /**
     * Genera los procesos de una prenda basado en los datos históricos de tabla_original
     */
    private function generarProcesosDeLaOrden($orden, $prendaId)
    {
        $procesos = [];
        $areaActual = $orden->area ?? 'Creación Orden';

        // Mapeo de campos de tabla_original a procesos
        $mapaAreas = [
            'corte' => 'Corte',
            'bordado' => 'Bordado',
            'estampado' => 'Estampado',
            'costura' => 'Costura',
            'reflectivo' => 'Reflectivo',
            'lavanderia' => 'Lavandería',
            'arreglos' => 'Arreglos',
            'control_de_calidad' => 'Control Calidad',
            'entrega' => 'Entrega',
            'despacho' => 'Despacho',
        ];

        // Proceso 1: Creación Orden (siempre)
        $procesos[] = [
            'prenda_pedido_id' => $prendaId,
            'proceso' => 'Creación Orden',
            'fecha_inicio' => $orden->fecha_de_creacion_de_orden,
            'fecha_fin' => $orden->fecha_de_creacion_de_orden,
            'encargado' => $orden->encargado_orden,
            'estado_proceso' => 'Completado',
            'dias_duracion' => 0,
            'created_at' => $orden->created_at,
            'updated_at' => $orden->updated_at,
        ];

        // Iterar por cada área y crear procesos
        foreach ($mapaAreas as $campo => $nombreProceso) {
            $fechaProceso = $orden->{$campo} ?? null;
            $encargadoCampo = "encargados_" . str_replace('_', '', $campo);
            $encargado = $orden->{$encargadoCampo} ?? null;

            if ($fechaProceso) {
                $estadoProceso = ($nombreProceso === $areaActual || $orden->estado === 'Entregado') 
                    ? 'Completado' 
                    : 'Pendiente';

                $procesos[] = [
                    'prenda_pedido_id' => $prendaId,
                    'proceso' => $nombreProceso,
                    'fecha_inicio' => $fechaProceso,
                    'fecha_fin' => $fechaProceso,
                    'encargado' => $encargado,
                    'estado_proceso' => $estadoProceso,
                    'created_at' => $orden->updated_at,
                    'updated_at' => $orden->updated_at,
                ];
            }
        }

        return $procesos;
    }
}
