<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\User;
use App\Models\Cliente;
use Carbon\Carbon;

/**
 * 🚀 COMANDO ÚNICO DE MIGRACIÓN COMPLETA
 * 
 * Migra TODA la información de tabla_original + registros_por_orden
 * a la nueva arquitectura normalizada en 6 pasos:
 * 1. Crear usuarios (asesoras)
 * 2. Crear clientes
 * 3. Migrar pedidos
 * 4. Migrar prendas
 * 5. Migrar procesos
 * 6. Calcular área y fecha_ultimo_proceso
 * 
 * Uso:
 * php artisan migrate:tabla-original-completo
 * php artisan migrate:tabla-original-completo --dry-run
 * php artisan migrate:tabla-original-completo --reset
 */
class MigrateTablaOriginalCompleto extends Command
{
    protected $signature = 'migrate:tabla-original-completo {--dry-run : Simular sin guardar} {--reset : Limpiar datos migrados primero}';

    protected $description = '🚀 MIGRACIÓN UNIFICADA COMPLETA: tabla_original → Nueva Arquitectura Normalizada';

    protected $stats = [
        'usuarios_creados' => 0,
        'usuarios_existentes' => 0,
        'clientes_creados' => 0,
        'clientes_existentes' => 0,
        'pedidos_migrados' => 0,
        'prendas_migradas' => 0,
        'procesos_migrados' => 0,
    ];

    protected $mapeoAsesoras = [];
    protected $mapeoClientes = [];

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 140));
        $this->info("🚀 MIGRACIÓN UNIFICADA COMPLETA: tabla_original + registros_por_orden → Nueva Arquitectura");
        $this->info(str_repeat("=", 140) . "\n");

        $dryRun = $this->option('dry-run');
        $reset = $this->option('reset');

        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: Los datos NO se guardarán en la base de datos\n");
        }

        try {
            // PASO 0: Limpiar SIEMPRE (para evitar duplicados)
            $this->info("🧹 PASO 0: Limpiando datos existentes para evitar duplicados...\n");
            $this->limpiarDatos($dryRun);

            // PASO 1: Crear Usuarios (Asesoras)
            $this->info("👥 PASO 1: Migrando Usuarios (Asesoras)...\n");
            $this->migrarUsuarios($dryRun);

            // PASO 2: Crear Clientes
            $this->info("🏢 PASO 2: Migrando Clientes...\n");
            $this->migrarClientes($dryRun);

            // PASO 3: Migrar Pedidos
            $this->info("📦 PASO 3: Migrando Pedidos...\n");
            $this->migrarPedidos($dryRun);

            // PASO 4: Migrar Prendas
            $this->info("👕 PASO 4: Migrando Prendas...\n");
            $this->migrarPrendas($dryRun);

            // PASO 5: Migrar Procesos
            $this->info("⚙️  PASO 5: Migrando Procesos...\n");
            $this->migrarProcesos($dryRun);

            // MOSTRAR RESUMEN
            $this->mostrarResumen($dryRun);

        } catch (\Exception $e) {
            $this->error("\n❌ Error en la migración: " . $e->getMessage());
            \Log::error('Migración error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * PASO 0: Limpiar datos migrados
     */
    private function limpiarDatos($dryRun)
    {
        if (!$dryRun) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            $countProc = ProcesoPrenda::count();
            ProcesoPrenda::truncate();
            $this->line("   ✓ Procesos eliminados: $countProc");
            
            $countPren = PrendaPedido::count();
            PrendaPedido::truncate();
            $this->line("   ✓ Prendas eliminadas: $countPren");
            
            $countPed = PedidoProduccion::count();
            PedidoProduccion::truncate();
            $this->line("   ✓ Pedidos eliminados: $countPed");
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            $this->line("   [DRY-RUN] Se limpiarían procesos, prendas y pedidos");
        }
        $this->newLine();
    }

    /**
     * PASO 1: Migrar Usuarios (Asesoras)
     */
    private function migrarUsuarios($dryRun)
    {
        $asesorasOriginales = DB::table('tabla_original')
            ->distinct()
            ->whereNotNull('asesora')
            ->pluck('asesora')
            ->toArray();

        $this->line("   📊 Asesoras encontradas: " . count($asesorasOriginales));

        foreach ($asesorasOriginales as $nombreAsesora) {
            $nombreAsesora = trim($nombreAsesora);
            if (empty($nombreAsesora)) continue;

            $usuario = User::where('name', $nombreAsesora)->first();

            if ($usuario) {
                $this->stats['usuarios_existentes']++;
                $this->mapeoAsesoras[$nombreAsesora] = $usuario->id;
            } else {
                if (!$dryRun) {
                    $nuevoUsuario = User::create([
                        'name' => $nombreAsesora,
                        'email' => strtolower(str_replace(' ', '.', $nombreAsesora)) . '@mundoindustrial.local',
                        'password' => bcrypt('password123'),
                        'role_id' => 2, // Asesora por defecto
                    ]);
                    $this->mapeoAsesoras[$nombreAsesora] = $nuevoUsuario->id;
                } else {
                    $this->mapeoAsesoras[$nombreAsesora] = 0; // Placeholder
                }
                $this->stats['usuarios_creados']++;
                $this->line("   ✓ Usuario creado: {$nombreAsesora}");
            }
        }

        $this->newLine();
        $this->line("   Resumen: Creados: {$this->stats['usuarios_creados']}, Existentes: {$this->stats['usuarios_existentes']}");
        $this->newLine();
    }

    /**
     * PASO 2: Migrar Clientes
     */
    private function migrarClientes($dryRun)
    {
        $clientesOriginales = DB::table('tabla_original')
            ->distinct()
            ->whereNotNull('cliente')
            ->pluck('cliente')
            ->toArray();

        $this->line("   📊 Clientes encontrados: " . count($clientesOriginales));

        foreach ($clientesOriginales as $nombreCliente) {
            $nombreCliente = trim($nombreCliente);
            if (empty($nombreCliente)) continue;

            $cliente = Cliente::where('nombre', $nombreCliente)->first();

            if ($cliente) {
                $this->stats['clientes_existentes']++;
                $this->mapeoClientes[$nombreCliente] = $cliente->id;
            } else {
                if (!$dryRun) {
                    $nuevoCliente = Cliente::create([
                        'nombre' => $nombreCliente,
                        'estado' => 'Activo',
                    ]);
                    $this->mapeoClientes[$nombreCliente] = $nuevoCliente->id;
                } else {
                    $this->mapeoClientes[$nombreCliente] = 0; // Placeholder
                }
                $this->stats['clientes_creados']++;
                $this->line("   ✓ Cliente creado: {$nombreCliente}");
            }
        }

        $this->newLine();
        $this->line("   Resumen: Creados: {$this->stats['clientes_creados']}, Existentes: {$this->stats['clientes_existentes']}");
        $this->newLine();
    }

    /**
     * PASO 3: Migrar Pedidos
     */
    private function migrarPedidos($dryRun)
    {
        $pedidosOriginales = DB::table('tabla_original')
            ->select(
                'pedido as numero_pedido',
                'asesora',
                'cliente',
                'fecha_de_creacion_de_orden',
                'dia_de_entrega',
                'fecha_estimada_de_entrega',
                'estado',
                'area',
                'novedades',
                'forma_de_pago'
            )
            ->distinct()
            ->get();

        $this->line("   📊 Pedidos a migrar: " . $pedidosOriginales->count());

        $bar = $this->output->createProgressBar($pedidosOriginales->count());
        $bar->start();

        foreach ($pedidosOriginales as $pedidoOrig) {
            try {
                $asesorId = $this->mapeoAsesoras[$pedidoOrig->asesora] ?? null;
                $clienteId = $this->mapeoClientes[$pedidoOrig->cliente] ?? null;

                if (!$dryRun) {
                    PedidoProduccion::updateOrCreate(
                        ['numero_pedido' => $pedidoOrig->numero_pedido],
                        [
                            'asesor_id' => $asesorId,
                            'cliente_id' => $clienteId,
                            'cliente' => $pedidoOrig->cliente,
                            'estado' => $pedidoOrig->estado ?? 'Pendiente',
                            'fecha_de_creacion_de_orden' => $this->parsearFecha($pedidoOrig->fecha_de_creacion_de_orden),
                            'dia_de_entrega' => $pedidoOrig->dia_de_entrega ?? 0,
                            'fecha_estimada_de_entrega' => $this->parsearFecha($pedidoOrig->fecha_estimada_de_entrega),
                            'area' => $pedidoOrig->area ?? 'Creación Orden',
                            'novedades' => $pedidoOrig->novedades ?? null,
                            'forma_de_pago' => $pedidoOrig->forma_de_pago ?? null,
                        ]
                    );
                }

                $this->stats['pedidos_migrados']++;
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nError migrando pedido {$pedidoOrig->numero_pedido}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->line("   Pedidos migrados: {$this->stats['pedidos_migrados']}");
        $this->newLine();
    }

    /**
     * PASO 4: Migrar Prendas (desde registros_por_orden)
     */
    private function migrarPrendas($dryRun)
    {
        $prendas = DB::table('registros_por_orden')
            ->select(
                'pedido',
                'prenda as nombre_prenda',
                DB::raw('SUM(cantidad) as cantidad'),
                'descripcion',
                'talla'
            )
            ->groupBy('pedido', 'nombre_prenda', 'descripcion', 'talla')
            ->get();

        $this->line("   📊 Prendas a migrar: " . $prendas->count());

        $bar = $this->output->createProgressBar($prendas->count());
        $bar->start();

        foreach ($prendas as $prenda) {
            try {
                $pedido = PedidoProduccion::where('numero_pedido', $prenda->pedido)->first();
                
                if (!$pedido) {
                    $bar->advance();
                    continue;
                }

                // Consolidar tallas
                $tallasRaw = DB::table('registros_por_orden')
                    ->where('pedido', $prenda->pedido)
                    ->where('prenda', $prenda->nombre_prenda)
                    ->select('talla', 'cantidad')
                    ->get()
                    ->toArray();

                if (!$dryRun) {
                    PrendaPedido::updateOrCreate(
                        [
                            'pedido_produccion_id' => $pedido->id,
                            'nombre_prenda' => $prenda->nombre_prenda,
                        ],
                        [
                            'cantidad' => $prenda->cantidad ?? 0,
                            'descripcion' => $prenda->descripcion,
                            'tallas' => json_encode($tallasRaw),
                        ]
                    );
                }

                $this->stats['prendas_migradas']++;
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nError en prenda: {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->line("   Prendas migradas: {$this->stats['prendas_migradas']}");
        $this->newLine();
    }

    /**
     * PASO 5: Migrar Procesos (desde tabla_original)
     */
    private function migrarProcesos($dryRun)
    {
        $pedidos = PedidoProduccion::all();
        $this->line("   📊 Procesando " . $pedidos->count() . " pedidos");

        $bar = $this->output->createProgressBar($pedidos->count());
        $bar->start();

        foreach ($pedidos as $pedido) {
            try {
                $pedidoOriginal = DB::table('tabla_original')
                    ->where('pedido', $pedido->numero_pedido)
                    ->first();

                if (!$pedidoOriginal) {
                    $bar->advance();
                    continue;
                }

                // Crear proceso inicial "Creación Orden"
                $fechaInicio = $this->parsearFecha($pedidoOriginal->fecha_de_creacion_de_orden);
                
                if (!$dryRun && $fechaInicio) {
                    ProcesoPrenda::updateOrCreate(
                        [
                            'numero_pedido' => $pedido->numero_pedido,
                            'proceso' => 'Creación Orden',
                        ],
                        [
                            'fecha_inicio' => $fechaInicio,
                            'estado_proceso' => 'Completado',
                        ]
                    );
                }

                $this->stats['procesos_migrados']++;
                $ultimaFecha = $fechaInicio;
                $ultimoProceso = 'Creación Orden';

                // Mapeo de campos de procesos en tabla_original
                $procesosMap = [
                    'inventario' => 'Inventario',
                    'insumos_y_telas' => 'Insumos y Telas',
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

                // Migrar procesos adicionales
                foreach ($procesosMap as $campoOrig => $nombreProceso) {
                    // Intentar diferentes variaciones de nombres de campo
                    $fechaInicioCampo = $campoOrig . '_inicio' ?: $campoOrig . '_fecha_inicio';
                    $fechaFinCampo = $campoOrig . '_fin' ?: $campoOrig . '_fecha_fin';

                    // Buscar en los atributos del objeto
                    $fechaProceso = null;
                    foreach ((array)$pedidoOriginal as $key => $value) {
                        if (strpos($key, $campoOrig) !== false && strpos($key, 'fecha') !== false && $value) {
                            $fechaProceso = $this->parsearFecha($value);
                            break;
                        }
                    }

                    if ($fechaProceso) {
                        if (!$dryRun) {
                            ProcesoPrenda::updateOrCreate(
                                [
                                    'numero_pedido' => $pedido->numero_pedido,
                                    'proceso' => $nombreProceso,
                                ],
                                [
                                    'fecha_inicio' => $fechaProceso,
                                    'estado_proceso' => 'Completado',
                                ]
                            );
                        }

                        $this->stats['procesos_migrados']++;
                        $ultimaFecha = $fechaProceso;
                        $ultimoProceso = $nombreProceso;
                    }
                }

                // Actualizar área y fecha_ultimo_proceso del pedido
                if (!$dryRun) {
                    $pedido->update([
                        'area' => $ultimoProceso,
                        'fecha_ultimo_proceso' => $ultimaFecha,
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("\nError en procesos pedido {$pedido->numero_pedido}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   Procesos migrados: {$this->stats['procesos_migrados']}");
        $this->newLine();
    }

    /**
     * Mostrar resumen final
     */
    private function mostrarResumen($dryRun)
    {
        $this->info("\n");
        $this->info(str_repeat("=", 140));
        $this->info("📊 RESUMEN DE MIGRACIÓN");
        $this->info(str_repeat("=", 140) . "\n");

        $tabla = [
            ['Usuarios creados', $this->stats['usuarios_creados']],
            ['Usuarios existentes', $this->stats['usuarios_existentes']],
            ['Clientes creados', $this->stats['clientes_creados']],
            ['Clientes existentes', $this->stats['clientes_existentes']],
            ['Pedidos migrados', $this->stats['pedidos_migrados']],
            ['Prendas migradas', $this->stats['prendas_migradas']],
            ['Procesos migrados', $this->stats['procesos_migrados']],
        ];

        $this->table(['Concepto', 'Cantidad'], $tabla);

        if ($dryRun) {
            $this->warn("\n⚠️  MODO DRY-RUN: Los datos NO fueron guardados en la base de datos");
            $this->info("✓ Ejecuta sin --dry-run para realizar la migración real\n");
        } else {
            $this->info("\n✅ MIGRACIÓN COMPLETADA EXITOSAMENTE");
            $this->info("✓ Ejecuta: php artisan validate:migracion-completa");
            $this->info("✓ Para validar la integridad de los datos migrados\n");
        }

        $this->info(str_repeat("=", 140) . "\n");
    }

    /**
     * Parsear fecha de diferentes formatos
     */
    private function parsearFecha($fecha)
    {
        if (!$fecha || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            $parsed = Carbon::parse($fecha);
            // Validar que sea una fecha razonable
            if ($parsed->year < 2000 || $parsed->year > 2100) {
                return null;
            }
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
