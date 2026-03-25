<?php

namespace App\Application\UseCases\Orders;

use App\Models\PedidoProduccion;
use App\Events\OrdenUpdated;
use Illuminate\Http\Request;

class UpdateNovedadesUseCase
{
    /**
     * Actualiza el campo de novedades (observaciones) de una orden
     *
     * @param Request $request
     * @param string $numeroPedido
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(Request $request, $numeroPedido): array
    {
        $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
        
        $orden->update([
            'novedades' => $request->input('novedades', '')
        ]);

        // Emit event for real-time updates
        broadcast(new OrdenUpdated($orden->fresh(), 'updated', ['novedades']));

        return [
            'success' => true,
            'message' => 'Novedades actualizadas correctamente',
            'data' => [
                'numero_pedido' => $orden->numero_pedido,
                'novedades' => $orden->novedades
            ]
        ];
    }
}
