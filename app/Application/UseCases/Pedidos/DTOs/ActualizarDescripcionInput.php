<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de ActualizarDescripcionUseCase
 * 
 * Responsabilidad: Encapsular descripción para parsear y actualizar prendas
 * Patrón: Transfer Object
 */
class ActualizarDescripcionInput
{
    public function __construct(
        public int $numero_pedido,
        public string $descripcion,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            numero_pedido: $request->input('pedido'),
            descripcion: $request->input('descripcion', ''),
        );
    }

    /**
     * Validar entrada
     */
    public function isValid(): bool
    {
        return $this->numero_pedido > 0 && !empty($this->descripcion);
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'descripcion' => $this->descripcion,
        ];
    }
}
