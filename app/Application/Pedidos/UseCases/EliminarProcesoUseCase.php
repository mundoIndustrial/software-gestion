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
     * @param int $numeroPedido - Número de pedido para validación
     * @return array - Respuesta del resultado
     * @throws \Exception
     */
    public function ejecutar(int $id, int $numeroPedido): array
    {
        $this->validarPositivo($id, 'ID del proceso');
        $this->validarPositivo($numeroPedido, 'Número de pedido');

        // ✅ PRIMERO: Intentar en la tabla nueva (pedidos_procesos_prenda_detalles)
        $proceso = DB::table('pedidos_procesos_prenda_detalles as ppd')
            ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
            ->select('ppd.*', 'pp.id as prenda_pedido_id')
            ->where('ppd.id', $id)
            ->where('pp.pedido_produccion_id', $numeroPedido)
            ->first();

        // ✅ Si no existe en tabla nueva, buscar en tabla antigua
        if (!$proceso) {
            $proceso = ProcesoPrenda::where('id', $id)
                ->where('numero_pedido', $numeroPedido)
                ->first();
        }

        $this->validarObjetoExiste($proceso, 'Proceso', $id);

        // Eliminar de tabla nueva si existe
        $procesoNueva = DB::table('pedidos_procesos_prenda_detalles')
            ->where('id', $id)
            ->first();

        if ($procesoNueva) {
            // Eliminar imágenes asociadas
            DB::table('pedidos_procesos_imagenes')
                ->where('proceso_prenda_detalle_id', $id)
                ->delete();

            // Eliminar tallas asociadas
            DB::table('pedidos_procesos_prenda_tallas')
                ->where('proceso_prenda_detalle_id', $id)
                ->delete();

            // Eliminar el proceso
            DB::table('pedidos_procesos_prenda_detalles')
                ->where('id', $id)
                ->delete();

            return [
                'success' => true,
                'message' => 'Proceso eliminado correctamente',
            ];
        }

        // Eliminar de tabla antigua si existe
        if ($proceso) {
            $totalProcesos = ProcesoPrenda::where('numero_pedido', $numeroPedido)
                ->count();

            if ($totalProcesos <= 1) {
                throw new \DomainException('No se puede eliminar el único proceso de una orden');
            }

            $proceso->delete();

            DB::table('procesos_historial')
                ->where('numero_pedido', $numeroPedido)
                ->where('proceso', $proceso->proceso)
                ->delete();

            return [
                'success' => true,
                'message' => 'Proceso eliminado correctamente',
            ];
        }

        throw new \DomainException('Proceso no encontrado');
    }
}
