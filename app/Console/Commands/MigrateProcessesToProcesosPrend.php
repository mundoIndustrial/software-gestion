<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PrendaPedido;

class MigrateProcessesToProcesosPrend extends Command
{
    protected $signature = 'migrate:procesos-prenda {--dry-run} {--reset}';
    protected $description = 'Migra los procesos de tabla_original a procesos_prenda - Ejecuta toda la migración completa';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $reset = $this->option('reset');
        
        $this->info("\n");
        $this->info(str_repeat("=", 120));
        $this->info("MIGRACIÓN COMPLETA: tabla_original → nueva arquitectura");
        $this->info(str_repeat("=", 120) . "\n");

        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: No se harán cambios en la BD\n");
        }

        if ($reset) {
            $this->resetMigration();
        }

        // PASO 1: Crear usuarios (asesoras)
        $this->migrateUsuarios();

        // PASO 2: Crear clientes
        $this->migrateClientes();

        // PASO 3: Migrar pedidos
        $this->migratePedidos($dryRun);

        // PASO 4: Migrar prendas
        $this->migratePrendas($dryRun);

        // PASO 5: Migrar procesos
        $this->migrateProcesos($dryRun);

        $this->info("\n" . str_repeat("=", 120));
        $this->info("✅ MIGRACIÓN COMPLETA EXITOSA");
        $this->info(str_repeat("=", 120) . "\n");

        return 0;
    }

    /**
     * PASO 1: Crear usuarios (asesoras) si no existen
     */
    private function migrateUsuarios()
    {
        $this->info("📋 PASO 1: Creando usuarios (asesoras)...");

        $asesoras = DB::table('tabla_original')
            ->distinct()
            ->whereNotNull('asesora')
            ->pluck('asesora')
            ->filter()
            ->unique();

        $creados = 0;
        $existentes = 0;

        foreach ($asesoras as $nombreAsesor) {
            $existe = DB::table('users')
                ->where('name', $nombreAsesor)
                ->exists();

            if (!$existe) {
                $email = strtolower(str_replace([' ', '.'], '_', $nombreAsesor)) . '@mundoindustrial.com';
                
                DB::table('users')->insert([
                    'name' => $nombreAsesor,
                    'email' => $email,
                    'password' => bcrypt('password123'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $creados++;
                $this->line("   ✅ Creado usuario: $nombreAsesor ($email)");
            } else {
                $existentes++;
            }
        }

        $this->info("   ✅ Usuarios creados: $creados | Existentes: $existentes\n");
    }

    /**
     * PASO 2: Crear clientes si no existen
     */
    private function migrateClientes()
    {
        $this->info("📋 PASO 2: Creando clientes...");

        $clientesNombres = DB::table('tabla_original')
            ->distinct()
            ->whereNotNull('cliente')
            ->pluck('cliente')
            ->filter()
            ->unique();

        $creados = 0;
        $existentes = 0;

        foreach ($clientesNombres as $nombreCliente) {
            $existe = DB::table('clientes')
                ->where('nombre', $nombreCliente)
                ->exists();

            if (!$existe) {
                // Obtener un user_id válido (tomar el primero disponible)
                $userId = DB::table('users')->value('id') ?? 1;

                DB::table('clientes')->insert([
                    'user_id' => $userId,
                    'nombre' => $nombreCliente,
                    'email' => null,
                    'telefono' => null,
                    'ciudad' => null,
                    'notas' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $creados++;
                $this->line("   ✅ Creado cliente: $nombreCliente");
            } else {
                $existentes++;
            }
        }

        $this->info("   ✅ Clientes creados: $creados | Existentes: $existentes\n");
    }

    /**
     * PASO 3: Migrar pedidos
     */
    private function migratePedidos($dryRun)
    {
        $this->info("📋 PASO 3: Migrando pedidos...");

        $pedidosAntiguos = DB::table('tabla_original')
            ->whereNotNull('pedido')
            ->distinct('pedido')
            ->get();

        $migrados = 0;
        $saltados = 0;

        foreach ($pedidosAntiguos as $pedido) {
            try {
                // Obtener IDs
                $asesorId = DB::table('users')
                    ->where('name', $pedido->asesora)
                    ->value('id');

                $clienteId = DB::table('clientes')
                    ->where('nombre', $pedido->cliente)
                    ->value('id');

                // Obtener cotización ID
                $cotizacionId = DB::table('cotizaciones')
                    ->value('id');

                // Verificar si ya existe
                $existe = DB::table('pedidos_produccion')
                    ->where('numero_pedido', $pedido->pedido)
                    ->exists();

                if (!$existe) {
                    if (!$dryRun) {
                        DB::table('pedidos_produccion')->insert([
                            'cotizacion_id' => $cotizacionId,
                            'asesor_id' => $asesorId,
                            'cliente_id' => $clienteId,
                            'numero_pedido' => $pedido->pedido,
                            'cliente' => $pedido->cliente,
                            'asesora' => $pedido->asesora,
                            'novedades' => $pedido->novedades ?? null,
                            'forma_de_pago' => $pedido->forma_de_pago ?? null,
                            'estado' => $pedido->estado ?? 'No iniciado',
                            'fecha_de_creacion_de_orden' => $pedido->fecha_de_creacion_de_orden,
                            'dia_de_entrega' => $pedido->dia_de_entrega,
                            'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $migrados++;
                    $this->line("   ✅ Pedido #{$pedido->pedido}: {$pedido->cliente}");
                } else {
                    $saltados++;
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Error en pedido {$pedido->pedido}: " . $e->getMessage());
            }
        }

        $this->info("   ✅ Pedidos migrados: $migrados | Saltados: $saltados\n");
    }

    /**
     * PASO 4: Migrar prendas
     */
    private function migratePrendas($dryRun)
    {
        $this->info("📋 PASO 4: Migrando prendas...");

        $registrosAntiguos = DB::table('registros_por_orden')
            ->whereNotNull('pedido')
            ->get();

        $migrados = 0;
        $actualizados = 0;

        foreach ($registrosAntiguos as $registro) {
            try {
                // Obtener pedido_produccion_id
                $pedidoProduccionId = DB::table('pedidos_produccion')
                    ->where('numero_pedido', $registro->pedido)
                    ->value('id');

                if (!$pedidoProduccionId) {
                    continue;
                }

                // Verificar si ya existe esta prenda
                $prenda = DB::table('prendas_pedido')
                    ->where('pedido_produccion_id', $pedidoProduccionId)
                    ->where('nombre_prenda', $registro->prenda)
                    ->first();

                if (!$prenda) {
                    // Crear nueva prenda
                    if (!$dryRun) {
                        DB::table('prendas_pedido')->insert([
                            'pedido_produccion_id' => $pedidoProduccionId,
                            'nombre_prenda' => $registro->prenda,
                            'cantidad' => $registro->cantidad ?? '1',
                            'descripcion' => $registro->descripcion,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $migrados++;
                } else {
                    $actualizados++;
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Error: " . $e->getMessage());
            }
        }

        $this->info("   ✅ Prendas migradas: $migrados | Actualizadas: $actualizados\n");
    }

    /**
     * PASO 5: Migrar procesos de tabla_original a procesos_prenda
     * LÓGICA: Cada proceso tiene UNA FECHA
     * Los días en área = fecha_actual - fecha_anterior
     */
    private function migrateProcesos($dryRun)
    {
        $this->info("📋 PASO 5: Migrando procesos...");

        // Mapeo de procesos EN ORDEN SECUENCIAL (orden de flujo)
        $procesosMap = [
            'pedido_recibido' => [
                'proceso' => 'Pedido Recibido',
                'fecha_field' => 'fecha_de_creacion_de_orden',
                'encargado_field' => 'encargado_orden',
            ],
            'insumos_telas' => [
                'proceso' => 'Insumos y Telas',
                'fecha_field' => 'insumos_y_telas',
                'encargado_field' => 'encargados_insumos',
            ],
            'corte' => [
                'proceso' => 'Corte',
                'fecha_field' => 'corte',
                'encargado_field' => 'encargados_de_corte',
            ],
            'bordado' => [
                'proceso' => 'Bordado',
                'fecha_field' => 'bordado',
                'encargado_field' => 'bordado',
            ],
            'estampado' => [
                'proceso' => 'Estampado',
                'fecha_field' => 'estampado',
                'encargado_field' => 'encargados_estampado',
            ],
            'costura' => [
                'proceso' => 'Costura',
                'fecha_field' => 'costura',
                'encargado_field' => 'modulo',
            ],
            'lavanderia' => [
                'proceso' => 'Lavandería',
                'fecha_field' => 'lavanderia',
                'encargado_field' => 'encargado_lavanderia',
            ],
            'arreglos' => [
                'proceso' => 'Arreglos',
                'fecha_field' => 'arreglos',
                'encargado_field' => 'encargado_arreglos',
            ],
            'control_calidad' => [
                'proceso' => 'Control Calidad',
                'fecha_field' => 'control_de_calidad',
                'encargado_field' => 'encargados_calidad',
            ],
            'entrega' => [
                'proceso' => 'Entrega',
                'fecha_field' => 'entrega',
                'encargado_field' => 'encargados_entrega',
            ],
            'despacho' => [
                'proceso' => 'Despacho',
                'fecha_field' => 'despacho',
                'encargado_field' => 'column_52',
            ]
        ];

        $totalProcesos = 0;
        $errores = 0;

        // Obtener todos los pedidos de tabla_original
        $pedidosOriginales = DB::table('tabla_original')
            ->whereNotNull('pedido')
            ->get();

        foreach ($pedidosOriginales as $pedidoOriginal) {
            try {
                // Obtener el id del pedido migrado
                $pedidoMigrado = DB::table('pedidos_produccion')
                    ->where('numero_pedido', $pedidoOriginal->pedido)
                    ->first();

                if (!$pedidoMigrado) {
                    continue;
                }

                // Colectar todos los procesos que tienen fecha (en orden)
                foreach ($procesosMap as $key => $config) {
                    $fechaField = $config['fecha_field'];
                    $fecha = $pedidoOriginal->$fechaField ?? null;
                    
                    if ($fecha && $fecha !== '' && $fecha !== '0000-00-00') {
                        if (!$dryRun) {
                            DB::table('procesos_prenda')->insert([
                                'numero_pedido' => $pedidoMigrado->numero_pedido,
                                'proceso' => $config['proceso'],
                                'fecha_inicio' => $fecha,  // Única fecha guardada
                                'fecha_fin' => null,             // Null: el frontend lo calcula
                                'dias_duracion' => null,         // Null: el frontend lo calcula dinámicamente
                                'encargado' => $pedidoOriginal->{$config['encargado_field']} ?? null,
                                'estado_proceso' => $this->determinarEstado($fecha, $pedidoOriginal->estado),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                        $totalProcesos++;
                    }
                }
            } catch (\Exception $e) {
                $errores++;
                $this->error("   ❌ Error en pedido {$pedidoOriginal->pedido}: " . $e->getMessage());
            }
        }

        $this->info("   ✅ Procesos migrados: $totalProcesos | Errores: $errores\n");
    }

    /**
     * Determinar estado del proceso
     */
    private function determinarEstado($fecha, $estadoPedido)
    {
        if (!$fecha) {
            return 'Pendiente';
        }

        if ($estadoPedido === 'Entregado') {
            return 'Completado';
        }

        if ($estadoPedido === 'En Ejecución') {
            return 'En Progreso';
        }

        if ($estadoPedido === 'No iniciado') {
            return 'Pendiente';
        }

        if ($estadoPedido === 'Anulada') {
            return 'Pausado';
        }

        return 'Pendiente';
    }

    /**
     * Resetear la migración (eliminar datos migrados)
     */
    private function resetMigration()
    {
        if (!$this->confirm('¿Deseas eliminar todos los datos migrados? Esta acción es irreversible.')) {
            $this->info("Reset cancelado.");
            return;
        }

        $this->info("Eliminando datos migrados...\n");

        try {
            DB::beginTransaction();

            // Eliminar procesos
            DB::table('procesos_prenda')->delete();
            $this->line("✅ Procesos eliminados");

            // Eliminar prendas
            DB::table('prendas_pedido')->delete();
            $this->line("✅ Prendas eliminadas");

            // Eliminar pedidos
            DB::table('pedidos_produccion')->delete();
            $this->line("✅ Pedidos eliminados");

            // Eliminar clientes
            DB::table('clientes')->delete();
            $this->line("✅ Clientes eliminados");

            // Eliminar usuarios (excepto admin)
            DB::table('users')->where('email', '!=', 'admin@mundoindustrial.com')->delete();
            $this->line("✅ Usuarios eliminados");

            DB::commit();
            $this->info("\n✅ Reset completado\n");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error en reset: " . $e->getMessage());
        }
    }
}
