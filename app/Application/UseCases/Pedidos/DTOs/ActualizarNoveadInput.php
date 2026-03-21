<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de ActualizarNoveadUseCase
 * 
 * Responsabilidad: Encapsular datos para actualizar novedades de una orden
 * Patrón: Transfer Object
 */
class ActualizarNoveadInput
{
    public function __construct(
        public int $numero_pedido,
        public ?string $novedades = null,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request, int $numeroPedido): self
    {
        return new self(
            numero_pedido: $numeroPedido,
            novedades: $request->input('novedades'),
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'novedades' => $this->novedades,
        ];
    }
}
