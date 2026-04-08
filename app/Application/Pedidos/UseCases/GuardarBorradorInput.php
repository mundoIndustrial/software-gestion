<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Exceptions\GuardarBorradorInputException;
use Illuminate\Http\Request;

/**
 * GuardarBorradorInput
 * DTO para encapsular los datos de entrada para guardar un borrador de pedido
 * Datos necesarios:
 * - Request con JSON de pedido + archivos FormData
 * - User ID (asesor)
 * @package App\Application\UseCases\Pedidos
 */
class GuardarBorradorInput
{
    public function __construct(
        public Request $request,
        public int $asesorId,
        public string $pedidoJSON,
        public array $datosFrontend,
    ) {}

    /**
     * Factory: Construir desde un Request HTTP
     * @param Request $request
     * @param int $asesorId
     * @return self
     * @throws GuardarBorradorInputException
     */
    public static function fromRequest(Request $request, int $asesorId): self
    {
        // Obtener y validar JSON
        $pedidoJSON = $request->input('pedido');
        if (!$pedidoJSON) {
            throw GuardarBorradorInputException::campoPedidoRequerido();
        }

        $datosFrontend = json_decode($pedidoJSON, true);
        if (!$datosFrontend) {
            throw GuardarBorradorInputException::jsonInvalido();
        }

        return new self(
            request: $request,
            asesorId: $asesorId,
            pedidoJSON: $pedidoJSON,
            datosFrontend: $datosFrontend,
        );
    }

    /**
     * Get orden de compra
     */
    public function getOrdenCompra(): ?string
    {
        return trim($this->datosFrontend['orden_compra'] ?? '') ?: null;
    }
}
