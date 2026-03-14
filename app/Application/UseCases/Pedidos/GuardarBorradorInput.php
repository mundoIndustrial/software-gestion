<?php

namespace App\Application\UseCases\Pedidos;

use Illuminate\Http\Request;

/**
 * GuardarBorradorInput
 * 
 * DTO para encapsular los datos de entrada para guardar un borrador de pedido
 * 
 * Datos necesarios:
 * - Request con JSON de pedido + archivos FormData
 * - User ID (asesor)
 * 
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
     * 
     * @param Request $request
     * @param int $asesorId
     * @return self
     * @throws \Exception
     */
    public static function fromRequest(Request $request, int $asesorId): self
    {
        // Obtener y validar JSON
        $pedidoJSON = $request->input('pedido');
        if (!$pedidoJSON) {
            throw new \Exception('Campo "pedido" JSON requerido');
        }

        $datosFrontend = json_decode($pedidoJSON, true);
        if (!$datosFrontend) {
            throw new \Exception('JSON inválido en campo "pedido"');
        }

        return new self(
            request: $request,
            asesorId: $asesorId,
            pedidoJSON: $pedidoJSON,
            datosFrontend: $datosFrontend,
        );
    }
}
