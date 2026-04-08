<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Exceptions\ActualizarBorradorInputException;
use Illuminate\Http\Request;

/**
 * ActualizarBorradorInput
 * DTO para encapsular los datos de entrada para actualizar un borrador de pedido
 * Datos necesarios:
 * - Pedido ID a actualizar
 * - Request con JSON de pedido + archivos FormData
 * - User ID (asesor - para validación de seguridad)
 * @package App\Application\UseCases\Pedidos
 */
class ActualizarBorradorInput
{
    public function __construct(
        public int $pedidoId,
        public int $asesorId,
        public Request $request,
        public string $pedidoJSON,
        public array $datosFrontend,
    ) {}

    /**
     * Factory: Construir desde un Request HTTP
     * @param Request $request
     * @param int $pedidoId
     * @param int $asesorId
     * @return self
     * @throws ActualizarBorradorInputException
     */
    public static function fromRequest(Request $request, int $pedidoId, int $asesorId): self
    {
        // Obtener y validar JSON
        $pedidoJSON = $request->input('pedido');
        if (!$pedidoJSON) {
            throw ActualizarBorradorInputException::campoPedidoRequerido();
        }

        $datosFrontend = json_decode($pedidoJSON, true);
        if (!$datosFrontend) {
            throw ActualizarBorradorInputException::jsonInvalido();
        }

        return new self(
            pedidoId: $pedidoId,
            asesorId: $asesorId,
            request: $request,
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
