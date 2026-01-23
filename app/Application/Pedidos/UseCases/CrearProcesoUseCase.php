<?php

namespace App\Application\Pedidos\UseCases;

use Illuminate\Support\Facades\DB;

/**
 * CrearProcesoUseCase
 * 
 * Caso de uso para crear o actualizar un proceso de pedido
 * Responsabilidad: Crear nuevos procesos y guardar cambios en historial
 * 
 * Patrón: Use Case (Application Layer - DDD)
 * Lógica: Si el proceso ya existe, guardar el anterior en historial y actualizar
 */
class CrearProcesoUseCase
{
    /**
     * Ejecutar caso de uso
     * 
     * @param array $data - Datos del proceso a crear
     * @return array - Respuesta con datos del proceso creado
     */
    public function ejecutar(array $data): array
    {
        $numeroPedido = $data['numero_pedido'];
        $nombreProceso = $data['proceso'];

        // Verificar si ya existe este proceso para este pedido
        $procesoDuplicado = DB::table('procesos_prenda')
            ->where('numero_pedido', $numeroPedido)
            ->where('proceso', $nombreProceso)
            ->first();

        if ($procesoDuplicado) {
            return $this->actualizarProcesoExistente($procesoDuplicado, $data, $numeroPedido);
        }

        return $this->crearNuevoProceso($data, $numeroPedido, $nombreProceso);
    }

    /**
     * Actualizar un proceso que ya existe
     */
    private function actualizarProcesoExistente($procesoDuplicado, array $data, int $numeroPedido): array
    {
        // Guardar el estado anterior en historial
        DB::table('procesos_historial')->insert([
            'numero_pedido' => $procesoDuplicado->numero_pedido,
            'proceso' => $procesoDuplicado->proceso,
            'fecha_inicio' => $procesoDuplicado->fecha_inicio,
            'encargado' => $procesoDuplicado->encargado,
            'estado_proceso' => $procesoDuplicado->estado_proceso,
            'created_at' => $procesoDuplicado->created_at,
            'updated_at' => $procesoDuplicado->updated_at
        ]);

        // Actualizar el proceso
        DB::table('procesos_prenda')
            ->where('id', $procesoDuplicado->id)
            ->update([
                'fecha_inicio' => $data['fecha_inicio'],
                'encargado' => $data['encargado'] ?? null,
                'estado_proceso' => $data['estado_proceso'],
                'updated_at' => now()
            ]);

        return [
            'success' => true,
            'message' => 'Proceso actualizado correctamente',
            'id' => $procesoDuplicado->id,
            'proceso' => $data['proceso'],
            'duplicado' => true
        ];
    }

    /**
     * Crear un nuevo proceso
     */
    private function crearNuevoProceso(array $data, int $numeroPedido, string $nombreProceso): array
    {
        // Crear proceso nuevo
        $id = DB::table('procesos_prenda')->insertGetId([
            'numero_pedido' => $numeroPedido,
            'proceso' => $nombreProceso,
            'fecha_inicio' => $data['fecha_inicio'],
            'encargado' => $data['encargado'] ?? null,
            'estado_proceso' => $data['estado_proceso'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Guardar en historial
        DB::table('procesos_historial')->insert([
            'numero_pedido' => $numeroPedido,
            'proceso' => $nombreProceso,
            'fecha_inicio' => $data['fecha_inicio'],
            'encargado' => $data['encargado'] ?? null,
            'estado_proceso' => $data['estado_proceso'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return [
            'success' => true,
            'message' => 'Proceso creado correctamente',
            'id' => $id,
            'proceso' => $nombreProceso
        ];
    }
}
