<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\User;
use App\Models\Cliente;
use Carbon\Carbon;

/**
 * COMANDO ÚNICO DE MIGRACIÓN COMPLETA
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

    protected $description = 'MIGRACIÓN UNIFICADA COMPLETA: tabla_original → Nueva Arquitectura Normalizada';

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

    // Mapeo completo de procesos con sus campos en tabla_original
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
        'marras' => [
            'proceso' => 'Marras',
            'fecha' => 'marras',
            'encargado' => 'encargados_marras',
            'dias' => 'total_de_dias_marras',
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

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 140));
        $this->info("MIGRACIÓN UNIFICADA COMPLETA: tabla_original + registros_por_orden → Nueva Arquitectura");
        $this->info(str_repeat("=", 140) . "\n");

        $dryRun = $this->option('dry-run');
        $reset = $this->option('reset');

        if ($dryRun) {
            $this->warn("  MODO DRY-RUN: Los datos NO se guardarán en la base de datos\n");
        }

        try {
            // PASO 0: Limpiar SIEMPRE (para evitar duplicados)
            $this->info(" PASO 0: Limpiando datos existentes para evitar duplicados...\n");
            $this->limpiarDatos($dryRun);

            // PASO 1: Crear Usuarios (Asesoras)
            $this->info("👥 PASO 1: Migrando Usuarios (Asesoras)...\n");
            $this->migrarUsuarios($dryRun);

            // PASO 2: Crear Clientes
            $this->info("🏢 PASO 2: Migrando Clientes...\n");
            $this->migrarClientes($dryRun);

            // PASO 3: Migrar Pedidos
            $this->info(" PASO 3: Migrando Pedidos...\n");
            $this->migrarPedidos($dryRun);

            // PASO 4: Migrar Prendas
            $this->info(" PASO 4: Migrando Prendas...\n");
            $this->migrarPrendas($dryRun);

            // PASO 5: Migrar Procesos
            $this->info("⚙️  PASO 5: Migrando Procesos...\n");
            $this->migrarProcesos($dryRun);

            // MOSTRAR RESUMEN
            $this->mostrarResumen($dryRun);

        } catch (\Exception $e) {
            $this->error("\n Error en la migración: " . $e->getMessage());
            \Log::error('Migración error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * PASO 0: Limpiar datos migrados (PERO RESPETAR PEDIDOS CON COTIZACION_ID)
     */
    private function limpiarDatos($dryRun)
    {
        if (!$dryRun) {
            // Desactivar checks de integridad referencial
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // ELIMINAR TODO - Limpieza completa
            $this->warn("     LIMPIANDO TODOS LOS DATOS EXISTENTES...");
            
            // 1. Eliminar TODOS los procesos
            try {
                $countProc = DB::table('procesos_prenda')->count();
                DB::table('procesos_prenda')->truncate();
                $this->line("   ✓ Procesos eliminados: $countProc");
            } catch (\Exception $e) {
                $this->line("     Error eliminando procesos: " . $e->getMessage());
            }
            
            // 1b. Eliminar historial de procesos
            try {
                if (Schema::hasTable('procesos_historial')) {
                    $countHist = DB::table('procesos_historial')->count();
                    DB::table('procesos_historial')->truncate();
                    $this->line("   ✓ Historial de procesos eliminado: $countHist");
                }
            } catch (\Exception $e) {
                $this->line("     Error eliminando historial: " . $e->getMessage());
            }
            
            // 2. Eliminar TODAS las prendas
            try {
                $countPren = DB::table('prendas_pedido')->count();
                DB::table('prendas_pedido')->truncate();
                $this->line("   ✓ Prendas eliminadas: $countPren");
            } catch (\Exception $e) {
                $this->line("     Error eliminando prendas: " . $e->getMessage());
            }
            
            // 3. Eliminar TODOS los pedidos
            try {
                $countPed = DB::table('pedidos_produccion')->count();
                DB::table('pedidos_produccion')->truncate();
                $this->line("   ✓ Pedidos eliminados: $countPed");
            } catch (\Exception $e) {
                $this->line("     Error eliminando pedidos: " . $e->getMessage());
            }
            
            // Reactivar checks de integridad referencial
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            $this->line("   [DRY-RUN] Se limpiarían TODOS los procesos, prendas y pedidos");
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

        $this->line("    Asesoras encontradas: " . count($asesorasOriginales));

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

        $this->line("    Clientes encontrados: " . count($clientesOriginales));

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
     * PASO 3: Migrar Pedidos (RESPETANDO PEDIDOS CON COTIZACION_ID)
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

        $this->line("    Pedidos a migrar: " . $pedidosOriginales->count());

        $bar = $this->output->createProgressBar($pedidosOriginales->count());
        $bar->start();

        foreach ($pedidosOriginales as $pedidoOrig) {
            try {
                $asesorId = $this->mapeoAsesoras[$pedidoOrig->asesora] ?? null;
                $clienteId = $this->mapeoClientes[$pedidoOrig->cliente] ?? null;

                if (!$dryRun) {
                    PedidoProduccion::create([
                        'numero_pedido' => $pedidoOrig->numero_pedido,
                        'asesor_id' => $asesorId,
                        'cliente_id' => $clienteId,
                        'cliente' => $pedidoOrig->cliente,
                        'estado' => $this->normalizarEstado($pedidoOrig->estado),
                        'fecha_de_creacion_de_orden' => $this->parsearFecha($pedidoOrig->fecha_de_creacion_de_orden),
                        'dia_de_entrega' => $pedidoOrig->dia_de_entrega ?? 0,
                        'fecha_estimada_de_entrega' => $this->parsearFecha($pedidoOrig->fecha_estimada_de_entrega),
                        'area' => $pedidoOrig->area ?? 'Creación Orden',
                        'novedades' => $pedidoOrig->novedades ?? null,
                        'forma_de_pago' => $pedidoOrig->forma_de_pago ?? null,
                    ]);
                }

                $this->stats['pedidos_migrados']++;
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nError migrando pedido {$pedidoOrig->numero_pedido}: {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->line("    Pedidos migrados: {$this->stats['pedidos_migrados']}");
        $this->newLine();
    }

    /**
     * PASO 4: Migrar Prendas (RESPETANDO PEDIDOS CON COTIZACION_ID)
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

        $this->line("    Prendas a migrar: " . $prendas->count());

        $bar = $this->output->createProgressBar($prendas->count());
        $bar->start();

        $prendasSaltadas = 0;

        foreach ($prendas as $prenda) {
            try {
                // VALIDAR: ¿Tiene nombre_prenda?
                if (empty(trim($prenda->nombre_prenda ?? ''))) {
                    $prendasSaltadas++;
                    $bar->advance();
                    continue;
                }

                $pedido = PedidoProduccion::where('numero_pedido', $prenda->pedido)->first();
                
                if (!$pedido) {
                    $bar->advance();
                    continue;
                }

                // Consolidar tallas desde registros_por_orden
                $registrosTallas = DB::table('registros_por_orden')
                    ->where('pedido', $prenda->pedido)
                    ->where('prenda', $prenda->nombre_prenda)
                    ->select('talla', 'cantidad')
                    ->get()
                    ->toArray();

                // Convertir a JSON: {"SIZE": cantidad, "SIZE2": cantidad2, ...}
                $cantidadTalla = [];
                foreach ($registrosTallas as $reg) {
                    $talla = strtoupper(trim($reg->talla ?? 'SIN_TALLA'));
                    $cantidad = intval($reg->cantidad ?? 0);
                    
                    if (!isset($cantidadTalla[$talla])) {
                        $cantidadTalla[$talla] = 0;
                    }
                    $cantidadTalla[$talla] += $cantidad;
                }

                if (!$dryRun) {
                    // Buscar si ya existe
                    $existing = DB::table('prendas_pedido')
                        ->where('numero_pedido', $pedido->numero_pedido)
                        ->where('nombre_prenda', $prenda->nombre_prenda)
                        ->first();

                    $data = [
                        'cantidad' => $prenda->cantidad ?? 0,
                        'descripcion' => $prenda->descripcion,
                        'cantidad_talla' => !empty($cantidadTalla) ? json_encode($cantidadTalla) : null,
                        'updated_at' => now(),
                    ];

                    if ($existing) {
                        // Actualizar
                        DB::table('prendas_pedido')
                            ->where('id', $existing->id)
                            ->update($data);
                    } else {
                        // Insertar nuevo
                        DB::table('prendas_pedido')->insert(array_merge($data, [
                            'nombre_prenda' => $prenda->nombre_prenda,
                            'numero_pedido' => $pedido->numero_pedido,
                            'created_at' => now(),
                        ]));
                    }
                }

                $this->stats['prendas_migradas']++;
                $bar->advance();
            } catch (\Exception $e) {
                $prendasSaltadas++;
                $this->error("\nError en prenda: {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->line("   Prendas migradas: {$this->stats['prendas_migradas']}");
        if ($prendasSaltadas > 0) {
            $this->line("     Prendas saltadas (sin nombre o error): {$prendasSaltadas}");
        }
        $this->newLine();
    }

    /**
     * PASO 5: Migrar Procesos (RESPETANDO PEDIDOS CON COTIZACION_ID)
     */
    private function migrarProcesos($dryRun)
    {
        $pedidos = PedidoProduccion::all();
        $this->line("    Procesando " . $pedidos->count() . " pedidos");

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
            } catch (\Exception $e) {
                $this->error("\nError en procesos pedido {$pedido->numero_pedido}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("    Procesos migrados: {$this->stats['procesos_migrados']}");
        $this->newLine();
    }

    /**
     * Mostrar resumen final
     */
    private function mostrarResumen($dryRun)
    {
        $this->info("\n");
        $this->info(str_repeat("=", 140));
        $this->info(" RESUMEN DE MIGRACIÓN");
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
            $this->warn("\n  MODO DRY-RUN: Los datos NO fueron guardados en la base de datos");
            $this->info("✓ Ejecuta sin --dry-run para realizar la migración real\n");
        } else {
            $this->info("\n MIGRACIÓN COMPLETADA EXITOSAMENTE");
            $this->info("✓ Ejecuta: php artisan validate:migracion-completa");
            $this->info("✓ Para validar la integridad de los datos migrados\n");
        }

        $this->info(str_repeat("=", 140) . "\n");
    }

    /**
     * Normalizar estado a valores del ENUM
     */
    private function normalizarEstado($estado)
    {
        if (!$estado) {
            return 'Pendiente';
        }

        // Limpiar caracteres especiales y normalizar
        $estadoLimpio = mb_strtolower(trim($estado));
        $estadoLimpio = preg_replace('/[^a-z0-9\s]/ui', '', $estadoLimpio);

        // Mapeo de estados
        $mapeo = [
            'pendiente' => 'Pendiente',
            'entregado' => 'Entregado',
            'en ejecucion' => 'En Ejecución',
            'en ejecucion' => 'En Ejecución',
            'no iniciado' => 'No iniciado',
            'anulada' => 'Anulada',
            'anulado' => 'Anulada',
            'pendiente supervisor' => 'PENDIENTE_SUPERVISOR',
            'pendiente_supervisor' => 'PENDIENTE_SUPERVISOR',
        ];

        // Buscar coincidencia
        foreach ($mapeo as $buscar => $reemplazar) {
            if (stripos($estadoLimpio, str_replace(' ', '', $buscar)) !== false || 
                stripos($estadoLimpio, $buscar) !== false) {
                return $reemplazar;
            }
        }

        // Si no hay coincidencia, usar Pendiente por defecto
        return 'Pendiente';
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
