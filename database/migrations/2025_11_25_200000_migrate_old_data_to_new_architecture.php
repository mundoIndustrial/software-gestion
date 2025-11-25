<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migra datos desde la arquitectura antigua (tabla_original + registros_por_orden)
     * a la nueva arquitectura (pedidos_produccion + prendas_pedido)
     * 
     * Proceso:
     * 1. Crea usuarios (asesoras) si no existen
     * 2. Crea clientes si no existen
     * 3. Migra pedidos a pedidos_produccion
     * 4. Migra prendas a prendas_pedido
     */
    public function up(): void
    {
        DB::beginTransaction();

        try {
            // ======================================
            // PASO 1: Crear usuarios (asesoras)
            // ======================================
            $asesoras = DB::table('tabla_original')
                ->distinct()
                ->whereNotNull('asesora')
                ->pluck('asesora')
                ->filter()
                ->unique();

            foreach ($asesoras as $nombreAsesor) {
                $existe = DB::table('users')
                    ->where('name', $nombreAsesor)
                    ->where('email', 'like', '%' . strtolower(str_replace(' ', '_', $nombreAsesor)) . '%')
                    ->exists();

                if (!$existe) {
                    $email = strtolower(str_replace(' ', '_', $nombreAsesor)) . '@mundoindustrial.com';
                    
                    DB::table('users')->insert([
                        'name' => $nombreAsesor,
                        'email' => $email,
                        'password' => bcrypt('password'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // ======================================
            // PASO 2: Crear clientes
            // ======================================
            $clientesNombres = DB::table('tabla_original')
                ->distinct()
                ->whereNotNull('cliente')
                ->pluck('cliente')
                ->filter()
                ->unique();

            foreach ($clientesNombres as $nombreCliente) {
                $existe = DB::table('clientes')
                    ->where('nombre', $nombreCliente)
                    ->exists();

                if (!$existe) {
                    DB::table('clientes')->insert([
                        'nombre' => $nombreCliente,
                        'email' => null,
                        'telefono' => null,
                        'direccion' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // ======================================
            // PASO 3: Migrar pedidos
            // ======================================
            $pedidosAntiguos = DB::table('tabla_original')
                ->whereNotNull('pedido')
                ->get();

            foreach ($pedidosAntiguos as $pedido) {
                // Obtener IDs del usuario y cliente
                $asesorId = DB::table('users')
                    ->where('name', $pedido->asesora)
                    ->value('id');

                $clienteId = DB::table('clientes')
                    ->where('nombre', $pedido->cliente)
                    ->value('id');

                // Verificar si el pedido ya existe en la nueva tabla
                $pedidoYaExiste = DB::table('pedidos_produccion')
                    ->where('numero_pedido', $pedido->pedido)
                    ->exists();

                if (!$pedidoYaExiste) {
                    DB::table('pedidos_produccion')->insert([
                        'numero_pedido' => $pedido->pedido,
                        'cliente' => $pedido->cliente,
                        'asesor_id' => $asesorId,
                        'cliente_id' => $clienteId,
                        'novedades' => $pedido->novedades ?? null,
                        'forma_de_pago' => $pedido->forma_de_pago ?? null,
                        'estado' => $pedido->estado ?? 'No iniciado',
                        'fecha_de_creacion_de_orden' => $pedido->fecha_de_creacion_de_orden,
                        'dia_de_entrega' => $pedido->dia_de_entrega,
                        'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega,
                        'created_at' => $pedido->created_at ?? now(),
                        'updated_at' => $pedido->updated_at ?? now(),
                        'deleted_at' => $pedido->deleted_at,
                    ]);
                }
            }

            // ======================================
            // PASO 4: Migrar prendas desde registros_por_orden
            // ======================================
            $registrosAntiguos = DB::table('registros_por_orden')
                ->get();

            foreach ($registrosAntiguos as $registro) {
                // Obtener el pedido_produccion_id
                $pedidoProduccionId = DB::table('pedidos_produccion')
                    ->where('numero_pedido', $registro->pedido)
                    ->value('id');

                if (!$pedidoProduccionId) {
                    continue; // Saltar si no existe el pedido
                }

                // Verificar si ya existe esta prenda para este pedido
                $prendaYaExiste = DB::table('prendas_pedido')
                    ->where('pedido_produccion_id', $pedidoProduccionId)
                    ->where('nombre_prenda', $registro->prenda)
                    ->exists();

                if (!$prendaYaExiste) {
                    // Crear la prenda
                    $prendaPedidoId = DB::table('prendas_pedido')->insertGetId([
                        'pedido_produccion_id' => $pedidoProduccionId,
                        'nombre_prenda' => $registro->prenda,
                        'cantidad' => $registro->cantidad ?? '1',
                        'descripcion' => $registro->descripcion,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Crear variación de talla en JSON
                    $tallaJson = json_encode([
                        [
                            'talla' => $registro->talla,
                            'cantidad' => (int)$registro->cantidad,
                        ]
                    ]);

                    DB::table('prendas_pedido')
                        ->where('id', $prendaPedidoId)
                        ->update(['cantidad_talla' => $tallaJson]);
                } else {
                    // Si la prenda ya existe, actualizar la cantidad_talla para agregar esta talla
                    $prenda = DB::table('prendas_pedido')
                        ->where('pedido_produccion_id', $pedidoProduccionId)
                        ->where('nombre_prenda', $registro->prenda)
                        ->first();

                    if ($prenda) {
                        $tallaExistentes = json_decode($prenda->cantidad_talla ?? '[]', true) ?? [];

                        // Verificar si la talla ya existe
                        $tallaYaExiste = false;
                        foreach ($tallaExistentes as &$t) {
                            if ($t['talla'] === $registro->talla) {
                                $t['cantidad'] += (int)$registro->cantidad;
                                $tallaYaExiste = true;
                                break;
                            }
                        }

                        // Si la talla no existe, agregarla
                        if (!$tallaYaExiste) {
                            $tallaExistentes[] = [
                                'talla' => $registro->talla,
                                'cantidad' => (int)$registro->cantidad,
                            ];
                        }

                        DB::table('prendas_pedido')
                            ->where('id', $prenda->id)
                            ->update(['cantidad_talla' => json_encode($tallaExistentes)]);
                    }
                }
            }

            DB::commit();

            echo "Migración completada exitosamente.\n";

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer rollback automático de datos migrados
        // Esto es intencional para proteger datos
    }
};
