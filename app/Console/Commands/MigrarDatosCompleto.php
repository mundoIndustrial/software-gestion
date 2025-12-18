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
 * 🚀 COMANDO DE MIGRACIÓN COMPLETA BASADO EN ANÁLISIS DE BASE DE DATOS
 * 
 * Este comando migra TODA la información histórica desde:
 * - tabla_original (pedidos y procesos)
 * - registros_por_orden (prendas y tallas)
 * 
 * Hacia la nueva arquitectura normalizada:
 * - pedidos_produccion
 * - prendas_pedido
 * - procesos_prenda
 * 
 * RESPETA pedidos que ya tienen cotizacion_id (creados desde el nuevo sistema)
 * 
 * Uso:
 * php artisan migrar:datos-completo                    # Migración real
 * php artisan migrar:datos-completo --dry-run          # Simulación
 * php artisan migrar:datos-completo --analyze          # Solo análisis
 * php artisan migrar:datos-completo --validate         # Validar después de migrar
 */
class MigrarDatosCompleto extends Command
{
    protected $signature = 'migrar:datos-completo 
                            {--dry-run : Simular sin guardar cambios}
                            {--analyze : Solo analizar datos sin migrar}
                            {--validate : Validar integridad después de migración}
                            {--force : Forzar limpieza completa (incluye pedidos con cotizacion_id)}';

    protected $description = '🚀 Migración completa de datos históricos: tabla_original + registros_por_orden → Nueva Arquitectura';

    protected $stats = [
        'usuarios_creados' => 0,
        'usuarios_existentes' => 0,
        'clientes_creados' => 0,
        'clientes_existentes' => 0,
        'pedidos_migrados' => 0,
        'pedidos_saltados' => 0,
        'prendas_migradas' => 0,
        'prendas_saltadas' => 0,
        'procesos_migrados' => 0,
        'procesos_saltados' => 0,
        'errores' => 0,
    ];

    protected $mapeoAsesoras = [];
    protected $mapeoClientes = [];

    // Mapeo completo de procesos desde tabla_original
    protected $procesosMap = [
        'creacion_de_orden' => [
            'proceso' => 'Creación de Orden',
            'fecha' => 'fecha_de_creacion_de_orden',
            'encargado' => 'encargado_orden',
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
        $this->mostrarBanner();

        $dryRun = $this->option('dry-run');
        $analyze = $this->option('analyze');
        $validate = $this->option('validate');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: Los datos NO se guardarán\n");
        }

        if ($force) {
            $this->error("⚠️  MODO FORCE: Se eliminarán TODOS los datos, incluyendo pedidos con cotizacion_id\n");
            if (!$this->confirm('¿Estás seguro? Esta acción NO se puede deshacer.')) {
                $this->info("Operación cancelada.");
                return 0;
            }
        }

        try {
            // Verificar que existan las tablas fuente
            if (!$this->verificarTablasOrigen()) {
                return 1;
            }

            if ($analyze) {
                return $this->analizarDatos();
            }

            if ($validate) {
                return $this->validarMigracion();
            }

            // PROCESO DE MIGRACIÓN COMPLETO
            $this->info("🚀 INICIANDO MIGRACIÓN COMPLETA\n");

            // PASO 0: Limpiar datos existentes
            $this->info("🧹 PASO 0: Limpiando datos existentes...\n");
            $this->limpiarDatos($dryRun, $force);

            // PASO 1: Migrar Usuarios (Asesoras)
            $this->info("👥 PASO 1: Migrando Usuarios (Asesoras)...\n");
            $this->migrarUsuarios($dryRun);

            // PASO 2: Migrar Clientes
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

            // PASO 6: Actualizar áreas y fechas
            $this->info("🔄 PASO 6: Actualizando áreas y fechas...\n");
            $this->actualizarAreasYFechas($dryRun);

            // Mostrar resumen
            $this->mostrarResumen($dryRun);

        } catch (\Exception $e) {
            $this->error("\n❌ Error en la migración: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            \Log::error('Error en migración completa: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function mostrarBanner()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 100));
        $this->info("║  🚀 MIGRACIÓN COMPLETA DE DATOS - MUNDO INDUSTRIAL");
        $this->info("║  📊 Basado en análisis exhaustivo de base de datos");
        $this->info(str_repeat("=", 100));
        $this->info("\n");
    }

    private function verificarTablasOrigen()
    {
        $this->info("🔍 Verificando tablas de origen...\n");

        $tablasRequeridas = ['tabla_original', 'registros_por_orden'];
        $faltantes = [];

        foreach ($tablasRequeridas as $tabla) {
            if (!Schema::hasTable($tabla)) {
                $faltantes[] = $tabla;
                $this->error("   ❌ Tabla '$tabla' NO EXISTE");
            } else {
                $count = DB::table($tabla)->count();
                $this->line("   ✅ Tabla '$tabla' existe ($count registros)");
            }
        }

        if (!empty($faltantes)) {
            $this->error("\n❌ Faltan tablas requeridas. No se puede continuar.");
            return false;
        }

        $this->info("\n");
        return true;
    }

    private function analizarDatos()
    {
        $this->info("📊 ANÁLISIS DE DATOS A MIGRAR\n");
        $this->info(str_repeat("=", 100) . "\n");

        // Análisis de tabla_original
        $this->info("📋 TABLA_ORIGINAL:");
        $totalPedidos = DB::table('tabla_original')->count();
        $pedidosUnicos = DB::table('tabla_original')->distinct('pedido')->count('pedido');
        $asesorasUnicas = DB::table('tabla_original')->distinct('asesora')->whereNotNull('asesora')->count('asesora');
        $clientesUnicos = DB::table('tabla_original')->distinct('cliente')->whereNotNull('cliente')->count('cliente');

        $this->line("   Total registros: " . number_format($totalPedidos));
        $this->line("   Pedidos únicos: " . number_format($pedidosUnicos));
        $this->line("   Asesoras únicas: " . number_format($asesorasUnicas));
        $this->line("   Clientes únicos: " . number_format($clientesUnicos));

        // Análisis de registros_por_orden
        $this->info("\n📋 REGISTROS_POR_ORDEN:");
        $totalRegistros = DB::table('registros_por_orden')->count();
        $pedidosConPrendas = DB::table('registros_por_orden')->distinct('pedido')->count('pedido');
        $prendasUnicas = DB::table('registros_por_orden')
            ->select('pedido', 'prenda', 'descripcion')
            ->distinct()
            ->count();

        $this->line("   Total registros: " . number_format($totalRegistros));
        $this->line("   Pedidos con prendas: " . number_format($pedidosConPrendas));
        $this->line("   Prendas únicas: " . number_format($prendasUnicas));

        // Análisis de pedidos existentes
        $this->info("\n📋 PEDIDOS_PRODUCCION (ACTUALES):");
        $pedidosExistentes = DB::table('pedidos_produccion')->count();
        $pedidosConCotizacion = DB::table('pedidos_produccion')->whereNotNull('cotizacion_id')->count();
        $pedidosSinCotizacion = DB::table('pedidos_produccion')->whereNull('cotizacion_id')->count();

        $this->line("   Total pedidos: " . number_format($pedidosExistentes));
        $this->line("   Con cotizacion_id: " . number_format($pedidosConCotizacion) . " (NO se tocarán)");
        $this->line("   Sin cotizacion_id: " . number_format($pedidosSinCotizacion) . " (serán reemplazados)");

        // Análisis de procesos por tipo
        $this->info("\n📋 ANÁLISIS DE PROCESOS EN TABLA_ORIGINAL:");
        foreach ($this->procesosMap as $key => $info) {
            $count = DB::table('tabla_original')
                ->whereNotNull($info['fecha'])
                ->where($info['fecha'], '!=', '0000-00-00')
                ->where($info['fecha'], '!=', '0000-00-00 00:00:00')
                ->count();
            $this->line("   {$info['proceso']}: " . number_format($count) . " registros");
        }

        $this->info("\n" . str_repeat("=", 100));
        $this->info("✅ ANÁLISIS COMPLETADO\n");

        return 0;
    }

    private function limpiarDatos($dryRun, $force)
    {
        if ($dryRun) {
            $this->line("   [DRY-RUN] Se limpiarían datos existentes");
            $this->newLine();
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if ($force) {
            // MODO FORCE: Eliminar TODO
            $this->warn("   ⚠️  MODO FORCE: Eliminando TODOS los datos...");
            
            $countProc = DB::table('procesos_prenda')->count();
            DB::table('procesos_prenda')->truncate();
            $this->line("   ✓ Procesos eliminados: $countProc");

            $countPren = DB::table('prendas_pedido')->count();
            DB::table('prendas_pedido')->truncate();
            $this->line("   ✓ Prendas eliminadas: $countPren");

            $countPed = DB::table('pedidos_produccion')->count();
            DB::table('pedidos_produccion')->truncate();
            $this->line("   ✓ Pedidos eliminados: $countPed");

        } else {
            // MODO NORMAL: Respetar pedidos con cotizacion_id
            $numerosPedidosConCotizacion = DB::table('pedidos_produccion')
                ->whereNotNull('cotizacion_id')
                ->pluck('numero_pedido')
                ->toArray();

            $this->line("   ℹ️  Pedidos con cotizacion_id: " . count($numerosPedidosConCotizacion) . " (NO se tocarán)");

            // Eliminar procesos
            if (!empty($numerosPedidosConCotizacion)) {
                $countProc = DB::table('procesos_prenda')
                    ->whereNotIn('numero_pedido', $numerosPedidosConCotizacion)
                    ->count();
                DB::table('procesos_prenda')
                    ->whereNotIn('numero_pedido', $numerosPedidosConCotizacion)
                    ->delete();
            } else {
                $countProc = DB::table('procesos_prenda')->count();
                DB::table('procesos_prenda')->truncate();
            }
            $this->line("   ✓ Procesos eliminados: $countProc");

            // Eliminar prendas
            if (!empty($numerosPedidosConCotizacion)) {
                $countPren = DB::table('prendas_pedido')
                    ->whereNotIn('numero_pedido', $numerosPedidosConCotizacion)
                    ->count();
                DB::table('prendas_pedido')
                    ->whereNotIn('numero_pedido', $numerosPedidosConCotizacion)
                    ->delete();
            } else {
                $countPren = DB::table('prendas_pedido')->count();
                DB::table('prendas_pedido')->truncate();
            }
            $this->line("   ✓ Prendas eliminadas: $countPren");

            // Eliminar pedidos sin cotizacion_id
            $countPed = DB::table('pedidos_produccion')
                ->whereNull('cotizacion_id')
                ->count();
            DB::table('pedidos_produccion')
                ->whereNull('cotizacion_id')
                ->delete();
            $this->line("   ✓ Pedidos eliminados: $countPed");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->newLine();
    }

    private function migrarUsuarios($dryRun)
    {
        $asesorasOriginales = DB::table('tabla_original')
            ->distinct()
            ->whereNotNull('asesora')
            ->where('asesora', '!=', '')
            ->pluck('asesora')
            ->toArray();

        $this->line("   📊 Asesoras encontradas: " . count($asesorasOriginales));

        $bar = $this->output->createProgressBar(count($asesorasOriginales));
        $bar->start();

        foreach ($asesorasOriginales as $nombreAsesora) {
            $nombreAsesora = trim($nombreAsesora);
            if (empty($nombreAsesora)) {
                $bar->advance();
                continue;
            }

            $usuario = User::where('name', $nombreAsesora)->first();

            if ($usuario) {
                $this->stats['usuarios_existentes']++;
                $this->mapeoAsesoras[$nombreAsesora] = $usuario->id;
            } else {
                if (!$dryRun) {
                    try {
                        $nuevoUsuario = User::create([
                            'name' => $nombreAsesora,
                            'email' => strtolower(str_replace(' ', '.', $nombreAsesora)) . '@mundoindustrial.local',
                            'password' => bcrypt('password123'),
                            'role_id' => 2,
                        ]);
                        $this->mapeoAsesoras[$nombreAsesora] = $nuevoUsuario->id;
                        $this->stats['usuarios_creados']++;
                    } catch (\Exception $e) {
                        $this->stats['errores']++;
                    }
                } else {
                    $this->mapeoAsesoras[$nombreAsesora] = 0;
                    $this->stats['usuarios_creados']++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✅ Creados: {$this->stats['usuarios_creados']}, Existentes: {$this->stats['usuarios_existentes']}");
        $this->newLine();
    }

    private function migrarClientes($dryRun)
    {
        $clientesOriginales = DB::table('tabla_original')
            ->distinct()
            ->whereNotNull('cliente')
            ->where('cliente', '!=', '')
            ->pluck('cliente')
            ->toArray();

        $this->line("   📊 Clientes encontrados: " . count($clientesOriginales));

        $bar = $this->output->createProgressBar(count($clientesOriginales));
        $bar->start();

        foreach ($clientesOriginales as $nombreCliente) {
            $nombreCliente = trim($nombreCliente);
            if (empty($nombreCliente)) {
                $bar->advance();
                continue;
            }

            $cliente = Cliente::where('nombre', $nombreCliente)->first();

            if ($cliente) {
                $this->stats['clientes_existentes']++;
                $this->mapeoClientes[$nombreCliente] = $cliente->id;
            } else {
                if (!$dryRun) {
                    try {
                        $nuevoCliente = Cliente::create([
                            'nombre' => $nombreCliente,
                            'estado' => 'Activo',
                        ]);
                        $this->mapeoClientes[$nombreCliente] = $nuevoCliente->id;
                        $this->stats['clientes_creados']++;
                    } catch (\Exception $e) {
                        $this->stats['errores']++;
                    }
                } else {
                    $this->mapeoClientes[$nombreCliente] = 0;
                    $this->stats['clientes_creados']++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✅ Creados: {$this->stats['clientes_creados']}, Existentes: {$this->stats['clientes_existentes']}");
        $this->newLine();
    }

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
                // Verificar si el pedido ya existe con cotizacion_id
                $pedidoExistente = PedidoProduccion::where('numero_pedido', $pedidoOrig->numero_pedido)->first();

                if ($pedidoExistente && $pedidoExistente->cotizacion_id !== null) {
                    $this->stats['pedidos_saltados']++;
                    $bar->advance();
                    continue;
                }

                $asesorId = $this->mapeoAsesoras[$pedidoOrig->asesora] ?? null;
                $clienteId = $this->mapeoClientes[$pedidoOrig->cliente] ?? null;

                if (!$dryRun) {
                    PedidoProduccion::create([
                        'numero_pedido' => $pedidoOrig->numero_pedido,
                        'asesor_id' => $asesorId,
                        'cliente_id' => $clienteId,
                        'cliente' => $pedidoOrig->cliente,
                        'estado' => $pedidoOrig->estado ?? 'Pendiente',
                        'fecha_de_creacion_de_orden' => $this->parsearFecha($pedidoOrig->fecha_de_creacion_de_orden),
                        'dia_de_entrega' => $pedidoOrig->dia_de_entrega ?? 0,
                        'fecha_estimada_de_entrega' => $this->parsearFecha($pedidoOrig->fecha_estimada_de_entrega),
                        'area' => $pedidoOrig->area ?? 'Creación Orden',
                        'novedades' => $pedidoOrig->novedades,
                        'forma_de_pago' => $pedidoOrig->forma_de_pago,
                    ]);
                }

                $this->stats['pedidos_migrados']++;
            } catch (\Exception $e) {
                $this->stats['errores']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✅ Migrados: {$this->stats['pedidos_migrados']}, Saltados: {$this->stats['pedidos_saltados']}");
        $this->newLine();
    }

    private function migrarPrendas($dryRun)
    {
        $prendas = DB::table('registros_por_orden')
            ->select(
                'pedido',
                'prenda as nombre_prenda',
                DB::raw('SUM(cantidad) as cantidad_total'),
                'descripcion'
            )
            ->groupBy('pedido', 'nombre_prenda', 'descripcion')
            ->get();

        $this->line("   📊 Prendas a migrar: " . $prendas->count());

        $bar = $this->output->createProgressBar($prendas->count());
        $bar->start();

        foreach ($prendas as $prenda) {
            try {
                if (empty(trim($prenda->nombre_prenda ?? ''))) {
                    $this->stats['prendas_saltadas']++;
                    $bar->advance();
                    continue;
                }

                $pedido = PedidoProduccion::where('numero_pedido', $prenda->pedido)->first();

                if (!$pedido) {
                    $this->stats['prendas_saltadas']++;
                    $bar->advance();
                    continue;
                }

                if ($pedido->cotizacion_id !== null) {
                    $this->stats['prendas_saltadas']++;
                    $bar->advance();
                    continue;
                }

                // Consolidar tallas
                $registrosTallas = DB::table('registros_por_orden')
                    ->where('pedido', $prenda->pedido)
                    ->where('prenda', $prenda->nombre_prenda)
                    ->select('talla', 'cantidad')
                    ->get();

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
                    DB::table('prendas_pedido')->insert([
                        'nombre_prenda' => $prenda->nombre_prenda,
                        'numero_pedido' => $pedido->numero_pedido,
                        'cantidad' => $prenda->cantidad_total ?? 0,
                        'descripcion' => $prenda->descripcion,
                        'cantidad_talla' => !empty($cantidadTalla) ? json_encode($cantidadTalla) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->stats['prendas_migradas']++;
            } catch (\Exception $e) {
                $this->stats['errores']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✅ Migradas: {$this->stats['prendas_migradas']}, Saltadas: {$this->stats['prendas_saltadas']}");
        $this->newLine();
    }

    private function migrarProcesos($dryRun)
    {
        $pedidos = PedidoProduccion::all();
        $this->line("   📊 Procesando " . $pedidos->count() . " pedidos");

        $bar = $this->output->createProgressBar($pedidos->count());
        $bar->start();

        foreach ($pedidos as $pedido) {
            try {
                if ($pedido->cotizacion_id !== null) {
                    $this->stats['procesos_saltados']++;
                    $bar->advance();
                    continue;
                }

                $pedidoOriginal = DB::table('tabla_original')
                    ->where('pedido', $pedido->numero_pedido)
                    ->first();

                if (!$pedidoOriginal) {
                    $bar->advance();
                    continue;
                }

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
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->stats['errores']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✅ Migrados: {$this->stats['procesos_migrados']}, Saltados: {$this->stats['procesos_saltados']}");
        $this->newLine();
    }

    private function actualizarAreasYFechas($dryRun)
    {
        $pedidos = PedidoProduccion::whereNull('cotizacion_id')->get();
        $this->line("   📊 Actualizando " . $pedidos->count() . " pedidos");

        $bar = $this->output->createProgressBar($pedidos->count());
        $bar->start();

        foreach ($pedidos as $pedido) {
            try {
                $ultimoProceso = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                    ->orderBy('fecha_inicio', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->first();

                if ($ultimoProceso && !$dryRun) {
                    $pedido->update([
                        'area' => $ultimoProceso->proceso,
                        'fecha_ultimo_proceso' => $ultimoProceso->fecha_inicio,
                    ]);
                }
            } catch (\Exception $e) {
                $this->stats['errores']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✅ Áreas actualizadas");
        $this->newLine();
    }

    private function validarMigracion()
    {
        $this->info("🔍 VALIDANDO INTEGRIDAD DE LA MIGRACIÓN\n");
        $this->info(str_repeat("=", 100) . "\n");

        $errores = [];

        // Validar pedidos
        $pedidosSinAsesor = DB::table('pedidos_produccion')->whereNull('asesor_id')->whereNull('cotizacion_id')->count();
        if ($pedidosSinAsesor > 0) {
            $errores[] = "⚠️  $pedidosSinAsesor pedidos sin asesor_id";
        }

        $pedidosSinCliente = DB::table('pedidos_produccion')->whereNull('cliente_id')->whereNull('cotizacion_id')->count();
        if ($pedidosSinCliente > 0) {
            $errores[] = "⚠️  $pedidosSinCliente pedidos sin cliente_id";
        }

        // Validar prendas huérfanas
        $prendasHuerfanas = DB::table('prendas_pedido as pp')
            ->leftJoin('pedidos_produccion as ped', 'pp.numero_pedido', '=', 'ped.numero_pedido')
            ->whereNull('ped.numero_pedido')
            ->count();
        if ($prendasHuerfanas > 0) {
            $errores[] = "❌ $prendasHuerfanas prendas sin pedido asociado";
        }

        // Validar procesos huérfanos
        $procesosHuerfanos = DB::table('procesos_prenda as proc')
            ->leftJoin('pedidos_produccion as ped', 'proc.numero_pedido', '=', 'ped.numero_pedido')
            ->whereNull('ped.numero_pedido')
            ->count();
        if ($procesosHuerfanos > 0) {
            $errores[] = "❌ $procesosHuerfanos procesos sin pedido asociado";
        }

        // Mostrar resultados
        if (empty($errores)) {
            $this->info("✅ VALIDACIÓN EXITOSA: No se encontraron problemas de integridad\n");
        } else {
            $this->warn("⚠️  SE ENCONTRARON PROBLEMAS:\n");
            foreach ($errores as $error) {
                $this->line("   $error");
            }
            $this->newLine();
        }

        // Estadísticas finales
        $totalPedidos = DB::table('pedidos_produccion')->count();
        $totalPrendas = DB::table('prendas_pedido')->count();
        $totalProcesos = DB::table('procesos_prenda')->count();

        $this->info("📊 ESTADÍSTICAS FINALES:");
        $this->line("   Total pedidos: " . number_format($totalPedidos));
        $this->line("   Total prendas: " . number_format($totalPrendas));
        $this->line("   Total procesos: " . number_format($totalProcesos));

        $this->info("\n" . str_repeat("=", 100) . "\n");

        return empty($errores) ? 0 : 1;
    }

    private function mostrarResumen($dryRun)
    {
        $this->info("\n");
        $this->info(str_repeat("=", 100));
        $this->info("📊 RESUMEN DE MIGRACIÓN COMPLETA");
        $this->info(str_repeat("=", 100) . "\n");

        $tabla = [
            ['Usuarios creados', number_format($this->stats['usuarios_creados'])],
            ['Usuarios existentes', number_format($this->stats['usuarios_existentes'])],
            ['Clientes creados', number_format($this->stats['clientes_creados'])],
            ['Clientes existentes', number_format($this->stats['clientes_existentes'])],
            ['Pedidos migrados', number_format($this->stats['pedidos_migrados'])],
            ['Pedidos saltados', number_format($this->stats['pedidos_saltados'])],
            ['Prendas migradas', number_format($this->stats['prendas_migradas'])],
            ['Prendas saltadas', number_format($this->stats['prendas_saltadas'])],
            ['Procesos migrados', number_format($this->stats['procesos_migrados'])],
            ['Procesos saltados', number_format($this->stats['procesos_saltados'])],
            ['Errores', number_format($this->stats['errores'])],
        ];

        $this->table(['Concepto', 'Cantidad'], $tabla);

        if ($dryRun) {
            $this->warn("\n⚠️  MODO DRY-RUN: Los datos NO fueron guardados");
            $this->info("✓ Ejecuta sin --dry-run para realizar la migración real\n");
        } else {
            $this->info("\n✅ MIGRACIÓN COMPLETADA EXITOSAMENTE");
            $this->info("✓ Ejecuta: php artisan migrar:datos-completo --validate");
            $this->info("✓ Para validar la integridad de los datos migrados\n");
        }

        $this->info(str_repeat("=", 100) . "\n");
    }

    private function parsearFecha($fecha)
    {
        if (!$fecha || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            $parsed = Carbon::parse($fecha);
            if ($parsed->year < 2000 || $parsed->year > 2100) {
                return null;
            }
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
