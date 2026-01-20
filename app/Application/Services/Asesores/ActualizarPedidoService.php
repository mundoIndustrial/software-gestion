<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActualizarPedidoService
{
    /**
     * Actualizar datos de un pedido existente
     * 
     * @param int|string $pedidoIdentifier Número de pedido o ID
     * @param array $datos Datos a actualizar
     * @return PedidoProduccion
     * @throws \Exception
     */
    public function actualizar($pedidoIdentifier, array $datos): PedidoProduccion
    {
        Log::info(' [ACTUALIZAR] Actualizando pedido', [
            'identificador' => $pedidoIdentifier,
            'campos' => array_keys($datos)
        ]);

        // Obtener el pedido
        $pedido = $this->obtenerPedido($pedidoIdentifier);

        // Verificar permisos
        if ($pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para actualizar este pedido', 403);
        }

        DB::beginTransaction();
        try {
            // Separar datos de prendas del resto
            $prendas = $datos['prendas'] ?? [];
            $updateData = collect($datos)->except('prendas')->toArray();

            // Actualizar datos del pedido
            if (!empty($updateData)) {
                $pedido->update($updateData);
                Log::info(' [ACTUALIZAR] Datos del pedido actualizados');
            }

            // Actualizar prendas si se enviaron
            if (!empty($prendas)) {
                $this->actualizarPrendas($pedido, $prendas);
            }

            DB::commit();

            Log::info(' [ACTUALIZAR] Pedido actualizado completamente', ['pedido_id' => $pedido->id]);

            return $pedido;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(' [ACTUALIZAR] Error al actualizar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar solo campos específicos
     */
    public function actualizarCampos($pedidoIdentifier, array $campos): PedidoProduccion
    {
        Log::info(' [ACTUALIZAR-CAMPOS] Actualizando campos específicos', [
            'campos' => array_keys($campos)
        ]);

        $pedido = $this->obtenerPedido($pedidoIdentifier);

        if ($pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para actualizar este pedido', 403);
        }

        try {
            $pedido->update($campos);
            Log::info(' [ACTUALIZAR-CAMPOS] Campos actualizados', ['pedido_id' => $pedido->id]);
            return $pedido;
        } catch (\Exception $e) {
            Log::error(' [ACTUALIZAR-CAMPOS] Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Actualizar prendas de un pedido
     */
    private function actualizarPrendas(PedidoProduccion $pedido, array $prendas): void
    {
        Log::info(' [ACTUALIZAR-PRENDAS] Actualizando ' . count($prendas) . ' prendas');

        try {
            // Eliminar prendas antiguas
            $pedido->prendas()->delete();

            // Crear nuevas prendas
            foreach ($prendas as $prendaData) {
                $pedido->prendas()->create([
                    'nombre_prenda' => $prendaData['nombre_prenda'],
                    'talla' => $prendaData['talla'] ?? null,
                    'cantidad' => $prendaData['cantidad'],
                    'precio_unitario' => $prendaData['precio_unitario'] ?? null,
                ]);
            }

            Log::info(' [ACTUALIZAR-PRENDAS] Prendas actualizadas');
        } catch (\Exception $e) {
            Log::error(' [ACTUALIZAR-PRENDAS] Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obtener el pedido (por número o ID)
     */
    private function obtenerPedido($pedidoIdentifier): PedidoProduccion
    {
        // Si es número (numérico > 1000 usualmente)
        if (is_numeric($pedidoIdentifier) && $pedidoIdentifier > 100) {
            $pedido = PedidoProduccion::where('numero_pedido', $pedidoIdentifier)->first();
            if ($pedido) {
                return $pedido;
            }
        }

        // Intentar por ID
        $pedido = PedidoProduccion::find($pedidoIdentifier);
        if ($pedido) {
            return $pedido;
        }

        throw new \Exception('Pedido no encontrado', 404);
    }

    /**
     * Cambiar estado de un pedido
     */
    public function cambiarEstado($pedidoIdentifier, string $nuevoEstado): PedidoProduccion
    {
        Log::info('🔄 [CAMBIAR-ESTADO] Cambiando a estado: ' . $nuevoEstado);

        $pedido = $this->obtenerPedido($pedidoIdentifier);

        if ($pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para cambiar el estado de este pedido', 403);
        }

        try {
            $pedidoAnterior = $pedido->estado;
            $pedido->update(['estado' => $nuevoEstado]);
            
            Log::info(' [CAMBIAR-ESTADO] Estado cambiado', [
                'pedido_id' => $pedido->id,
                'estado_anterior' => $pedidoAnterior,
                'estado_nuevo' => $nuevoEstado
            ]);

            return $pedido;
        } catch (\Exception $e) {
            Log::error(' [CAMBIAR-ESTADO] Error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Actualizar novedades del pedido
     */
    public function actualizarNovedades($pedidoIdentifier, string $novedades): PedidoProduccion
    {
        Log::info('📌 [NOVEDADES] Actualizando novedades del pedido');

        return $this->actualizarCampos($pedidoIdentifier, ['novedades' => $novedades]);
    }
}
