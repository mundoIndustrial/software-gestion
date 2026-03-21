<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de AgregarNoveadUseCase
 * 
 * Responsabilidad: Encapsular nueva novedad a agregar a una orden
 * Patrón: Transfer Object
 */
class AgregarNoveadInput
{
    public function __construct(
        public int $numero_pedido,
        public string $novedad,
        public ?string $usuario = null,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request, int $numeroPedido): self
    {
        return new self(
            numero_pedido: $numeroPedido,
            novedad: $request->input('novedad', ''),
            usuario: auth()?->user()?->name ?? auth()?->user()?->email,
        );
    }

    /**
     * Validar entrada
     */
    public function isValid(): bool
    {
        return !empty($this->novedad);
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'novedad' => $this->novedad,
            'usuario' => $this->usuario,
        ];
    }
}
