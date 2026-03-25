<?php

namespace App\Application\UseCases\Orders;

use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenNumberService;
use Illuminate\Http\Request;

class UpdatePedidoNumberUseCase
{
    protected $numberService;

    public function __construct(RegistroOrdenNumberService $numberService)
    {
        $this->numberService = $numberService;
    }

    /**
     * Actualiza el número de un pedido (orden)
     *
     * @param Request $request
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(Request $request): array
    {
        $validatedData = $request->validate([
            'old_pedido' => 'required|integer',
            'new_pedido' => 'required|integer|min:1',
        ]);

        $this->numberService->updatePedidoNumber(
            $validatedData['old_pedido'],
            $validatedData['new_pedido']
        );

        $orden = PedidoProduccion::where('numero_pedido', $validatedData['new_pedido'])->first();
        if ($orden) {
            $this->numberService->broadcastPedidoUpdated($orden);
        }

        return [
            'success' => true,
            'message' => 'Número de pedido actualizado correctamente',
            'old_pedido' => $validatedData['old_pedido'],
            'new_pedido' => $validatedData['new_pedido']
        ];
    }
}
