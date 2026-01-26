<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\DB;

/**
 * EliminarProcesoUseCase
 * 
 * Caso de uso para eliminar un proceso de un pedido
 * Responsabilidad: Eliminar proceso y su historial asociado
 * 
 * Patrón: Use Case (Application Layer - DDD)
 * Restricción: No se puede eliminar el Ãºnico proceso de una orden
 */
class EliminarProcesoUseCase
{
    use ManejaPedidosUseCase;

    /**
     * Ejecutar caso de uso
     * 
     * @param int $id - ID del proceso a eliminar
     * @param int $numeroPedido - NÃºmero de pedido para validación
     * @return array - Respuesta del resultado
     * @throws \Exception
     */
    public function ejecutar(int $id, int $numeroPedido): array
    {
        $this->validarPositivo($id, 'ID del proceso');
        $this->validarPositivo($numeroPedido, 'NÃºmero de pedido');

        $proceso = ProcesoPrenda::where('id', $id)
            ->where('numero_pedido', $numeroPedido)
            ->first();

        $this->validarObjetoExiste($proceso, 'Proceso', $id);

        $totalProcesos = ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->count();

        if ($totalProcesos <= 1) {
            throw new \DomainException('No se puede eliminar el Ãºltimo proceso de una orden');
        }

        $proceso->delete();

        DB::table('procesos_historial')
            ->where('numero_pedido', $numeroPedido)
            ->where('proceso', $proceso->proceso)
            ->delete();

        \Log::info("Proceso eliminado correctamente", [
            'id' => $id,
            'numero_pedido' => $numeroPedido,
            'proceso' => $proceso->proceso
        ]);

        return [
            'success' => true,
            'message' => 'Proceso eliminado correctamente',
            'proceso_id' => $id
        ];
    }
}

