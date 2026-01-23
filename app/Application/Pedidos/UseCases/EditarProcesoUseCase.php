<?php

namespace App\Application\Pedidos\UseCases;

use Illuminate\Support\Facades\DB;

/**
 * EditarProcesoUseCase
 * 
 * Caso de uso para editar un proceso existente
 * Responsabilidad: Validar y actualizar un proceso de pedido
 * 
 * Patrón: Use Case (Application Layer - DDD)
 * Autorización: Solo admin o producción
 */
class EditarProcesoUseCase
{
    /**
     * Ejecutar caso de uso
     * 
     * @param int $id - ID del proceso
     * @param array $data - Datos a actualizar
     * @return array - Respuesta del resultado
     * @throws \Exception
     */
    public function ejecutar(int $id, array $data): array
    {
        // Buscar el proceso
        $proceso = \App\Models\ProcesoPrenda::where('id', $id)
            ->where('numero_pedido', $data['numero_pedido'])
            ->first();

        if (!$proceso) {
            throw new \DomainException('Proceso no encontrado');
        }

        // Actualizar usando el modelo (dispara Observer)
        $proceso->update([
            'proceso' => $data['proceso'],
            'fecha_inicio' => $data['fecha_inicio'],
            'encargado' => $data['encargado'] ?? null,
            'estado_proceso' => $data['estado_proceso'],
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        \Log::info('Proceso actualizado correctamente', [
            'proceso_id' => $id,
            'numero_pedido' => $data['numero_pedido'],
            'nuevo_estado' => $data['estado_proceso'],
            'nuevo_proceso' => $data['proceso'],
        ]);

        return [
            'success' => true,
            'message' => 'Proceso actualizado correctamente',
            'id' => $id
        ];
    }
}
