<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;

class MigrateTablaOriginalCompleto extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'migrate:tabla-original-completo {--dry-run} {--skip-validation}';

    /**
     * The console command description.
     */
    protected $description = 'Migra TODOS los datos de tabla_original + registros_por_orden a las nuevas tablas normalizadas.';

    protected $stats = [
        'pedidos_creados' => 0,
        'prendas_creadas' => 0,
        'procesos_creados' => 0,
        'errores' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $skipValidation = $this->option('skip-validation');
        
        $this->info('╔════════════════════════════════════════════════════╗');
        $this->info('║  MIGRACIÓN COMPLETA: tabla_original → nuevas tablas ║');
        $this->info('╚════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: Sin cambios en base de datos');
        }

        if (!$skipValidation) {
            $this->info('Paso 1️⃣ : Validar datos fuente...');
            if (!$this->validarDatos()) {
                return;
            }
        }

        $this->info('Paso 2️⃣ : Obtener datos de tabla_original...');
        $pedidosOriginales = $this->obtenerPedidos();
        $this->line("   └─ Encontrados: " . count($pedidosOriginales) . " pedidos");

        $this->info('Paso 3️⃣ : Migrar pedidos...');
        $this->migrarPedidos($pedidosOriginales, $dryRun);

        $this->info('Paso 4️⃣ : Migrar prendas...');
        $this->migrarPrendas($pedidosOriginales, $dryRun);

        $this->info('Paso 5️⃣ : Migrar procesos...');
        $this->migrarProcesos($pedidosOriginales, $dryRun);

        $this->mostrarResumen($dryRun);
    }

    /**
     * Validar que los datos están listos para migración
     */
    protected function validarDatos(): bool
    {
        $this->line("   Validando tabla_original...");
        $countOriginal = DB::table('tabla_original')->count();
        $this->line("   └─ $countOriginal registros");

        $this->line("   Validando registros_por_orden...");
        $countRegistros = DB::table('registros_por_orden')->count();
        $this->line("   └─ $countRegistros registros");

        // Validar que tabla_original tiene user_id y cliente_id_nuevo
        $sinMapeo = DB::table('tabla_original')
            ->whereNull('asesora_id')
            ->whereNull('cliente_id_nuevo')
            ->count();

        if ($sinMapeo > 50) {
            $this->warn("\n⚠️  Advertencia: $sinMapeo registros sin mapeo de asesora/cliente");
            $this->warn("   Recomendación: Ejecutar primero:");
            $this->warn("   php artisan mapear:asesoras-clientes-tabla-original");
            return false;
        }

        $this->line("   ✓ Validación completada");
        $this->newLine();
        return true;
    }

    /**
     * Obtener todos los pedidos con sus detalles DE tabla_original
     */
    protected function obtenerPedidos()
    {
        // Obtener todos los registros de tabla_original con sus detalles de registros_por_orden
        $datos = DB::table('tabla_original')
            ->leftJoin('registros_por_orden', 'tabla_original.pedido', '=', 'registros_por_orden.pedido')
            ->select(
                'tabla_original.pedido',
                'tabla_original.cliente',
                'tabla_original.cliente_id_nuevo',
                'tabla_original.asesora',
                'tabla_original.asesora_id',
                'tabla_original.novedades',
                'tabla_original.forma_de_pago',
                'tabla_original.estado',
                'tabla_original.area',
                'tabla_original.fecha_de_creacion_de_orden',
                'tabla_original.dia_de_entrega',
                'tabla_original.fecha_estimada_de_entrega',
                'registros_por_orden.prenda',
                'registros_por_orden.cantidad',
                'registros_por_orden.talla'
            )
            ->get()
            ->groupBy('pedido');

        return $datos;
    }

    /**
     * Migrar pedidos a pedidos_produccion
     */
    protected function migrarPedidos($pedidosOriginales, $dryRun): void
    {
        $progreso = $this->output->createProgressBar(count($pedidosOriginales));

        foreach ($pedidosOriginales as $numeroPedido => $registros) {
            $primeraFila = $registros->first();

            if (!$dryRun) {
                try {
                    $pedido = PedidoProduccion::updateOrCreate(
                        ['numero_pedido' => $numeroPedido],
                        [
                            'cliente' => $primeraFila->cliente ?? null,
                            'cliente_id' => $primeraFila->cliente_id_nuevo ?? null,
                            'asesora' => $primeraFila->asesora ?? null,
                            'user_id' => $primeraFila->asesora_id ?? null,
                            'novedades' => $primeraFila->novedades ?? null,
                            'forma_de_pago' => $primeraFila->forma_de_pago ?? null,
                            'estado' => $primeraFila->estado ?? 'No iniciado',
                            'fecha_de_creacion_de_orden' => $primeraFila->fecha_de_creacion_de_orden ?? now()->toDateString(),
                            'dia_de_entrega' => $primeraFila->dia_de_entrega ?? null,
                            'fecha_estimada_de_entrega' => $primeraFila->fecha_estimada_de_entrega ?? null,
                        ]
                    );
                    $this->stats['pedidos_creados']++;
                } catch (\Exception $e) {
                    $this->stats['errores'][] = "Pedido $numeroPedido: " . $e->getMessage();
                }
            } else {
                $this->stats['pedidos_creados']++;
            }

            $progreso->advance();
        }

        $progreso->finish();
        $this->newLine();
    }

    /**
     * Migrar prendas a prendas_pedido
     */
    protected function migrarPrendas($pedidosOriginales, $dryRun): void
    {
        $progreso = $this->output->createProgressBar(count($pedidosOriginales));

        foreach ($pedidosOriginales as $numeroPedido => $registros) {
            // Obtener el pedido creado
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

            if (!$pedido) {
                $progreso->advance();
                continue;
            }

            // Agrupar prendas por nombre (para consolidar cantidades)
            $prendasAgrupadas = [];
            foreach ($registros as $registro) {
                $nombrePrenda = trim($registro->prenda ?? 'SIN ESPECIFICAR');
                
                if (!isset($prendasAgrupadas[$nombrePrenda])) {
                    $prendasAgrupadas[$nombrePrenda] = [
                        'cantidad' => 0,
                        'talla' => $registro->talla,
                    ];
                }
                
                $cantidad = (int) ($registro->cantidad ?? 1);
                $prendasAgrupadas[$nombrePrenda]['cantidad'] += $cantidad;
            }

            // Crear prendas
            foreach ($prendasAgrupadas as $nombrePrenda => $datos) {
                if (!$dryRun) {
                    try {
                        PrendaPedido::updateOrCreate(
                            [
                                'pedido_produccion_id' => $pedido->id,
                                'nombre_prenda' => $nombrePrenda,
                            ],
                            [
                                'cantidad' => $datos['cantidad'],
                                'descripcion' => "Migrado de tabla_original",
                            ]
                        );
                        $this->stats['prendas_creadas']++;
                    } catch (\Exception $e) {
                        $this->stats['errores'][] = "Prenda en pedido $numeroPedido: " . $e->getMessage();
                    }
                } else {
                    $this->stats['prendas_creadas']++;
                }
            }

            $progreso->advance();
        }

        $progreso->finish();
        $this->newLine();
    }

    /**
     * Migrar procesos (área actual) a procesos_prenda
     * Ahora incluye el cálculo automático de días desde tabla_original
     */
    protected function migrarProcesos($pedidosOriginales, $dryRun): void
    {
        $progreso = $this->output->createProgressBar(count($pedidosOriginales));

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

        foreach ($pedidosOriginales as $numeroPedido => $registros) {
            // Obtener el pedido
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

            if (!$pedido) {
                $progreso->advance();
                continue;
            }

            // Obtener la primera fila para extraer datos
            $primeraFila = is_array($registros) ? reset($registros) : $registros->first();
            
            // Crear proceso inicial: Creación Orden (siempre)
            $prendas = $pedido->prendas;

            foreach ($prendas as $prenda) {
                if (!$dryRun) {
                    try {
                        // Proceso 1: Creación Orden
                        ProcesoPrenda::firstOrCreate(
                            [
                                'prenda_pedido_id' => $prenda->id,
                                'proceso' => 'Creación Orden',
                            ],
                            [
                                'fecha_inicio' => $primeraFila->fecha_de_creacion_de_orden ?? now()->toDateString(),
                                'fecha_fin' => $primeraFila->fecha_de_creacion_de_orden ?? now()->toDateString(),
                                'encargado' => $primeraFila->encargado_orden ?? null,
                                'estado_proceso' => 'Completado',
                                'observaciones' => "Migrado de tabla_original",
                                // dias_duracion se calcula automáticamente en el modelo
                            ]
                        );

                        // Crear procesos para cada área que tenga fecha registrada
                        foreach ($mapaAreas as $campoFecha => $nombreProceso) {
                            $fechaProceso = $primeraFila->$campoFecha ?? null;
                            
                            if ($fechaProceso) {
                                $campoEncargado = "encargados_" . str_replace("_", "_", $campoFecha);
                                
                                ProcesoPrenda::firstOrCreate(
                                    [
                                        'prenda_pedido_id' => $prenda->id,
                                        'proceso' => $nombreProceso,
                                    ],
                                    [
                                        'fecha_inicio' => $fechaProceso,
                                        'fecha_fin' => $fechaProceso,
                                        'encargado' => $primeraFila->$campoEncargado ?? null,
                                        'estado_proceso' => 'Completado',
                                        'observaciones' => "Migrado de tabla_original.$campoFecha",
                                        // dias_duracion se calcula automáticamente en el modelo
                                    ]
                                );
                            }
                        }

                        $this->stats['procesos_creados']++;
                    } catch (\Exception $e) {
                        $this->stats['errores'][] = "Proceso en prenda: " . $e->getMessage();
                    }
                } else {
                    $this->stats['procesos_creados']++;
                }
            }

            $progreso->advance();
        }

        $progreso->finish();
        $this->newLine();
    }

    /**
     * Mostrar resumen final
     */
    protected function mostrarResumen($dryRun): void
    {
        $this->newLine(2);
        $this->info('╔════════════════════════════════╗');
        $this->info('║  RESUMEN DE MIGRACIÓN          ║');
        $this->info('╚════════════════════════════════╝');
        $this->newLine();

        $this->line("📊 Estadísticas:");
        $this->line("   ✓ Pedidos migrados: " . $this->stats['pedidos_creados']);
        $this->line("   ✓ Prendas migradas: " . $this->stats['prendas_creadas']);
        $this->line("   ✓ Procesos creados: " . $this->stats['procesos_creados']);

        if (!empty($this->stats['errores'])) {
            $this->newLine();
            $this->error("⚠️  Errores encontrados: " . count($this->stats['errores']));
            foreach (array_slice($this->stats['errores'], 0, 10) as $error) {
                $this->error("   - $error");
            }
            if (count($this->stats['errores']) > 10) {
                $this->error("   ... y " . (count($this->stats['errores']) - 10) . " más");
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn('⚠️  Este fue un DRY-RUN. Sin cambios en la base de datos.');
            $this->info('Para ejecutar la migración real, ejecuta:');
            $this->info('   php artisan migrate:tabla-original-completo');
        } else {
            $this->info('✅ Migración completada exitosamente!');
            $this->info('Los datos están ahora en las nuevas tablas normalizadas.');
        }
    }
}
