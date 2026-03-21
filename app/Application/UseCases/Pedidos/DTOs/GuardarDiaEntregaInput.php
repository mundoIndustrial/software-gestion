<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de GuardarDiaEntregaUseCase
 * 
 * Responsabilidad: Encapsular datos para guardar día de entrega
 * Patrón: Transfer Object
 */
class GuardarDiaEntregaInput
{
    public function __construct(
        public int $numero_pedido,
        public ?int $dia_de_entrega = null,
        public bool $calcular_fecha_estimada = true,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request, int $numeroPedido): self
    {
        return new self(
            numero_pedido: $numeroPedido,
            dia_de_entrega: $request->input('dia_de_entrega') ? intval($request->input('dia_de_entrega')) : null,
            calcular_fecha_estimada: $request->input('calcular_fecha_estimada', true),
        );
    }

    /**
     * Validar entrada
     */
    public function isValid(): bool
    {
        // Si se proporciona día de entrega, debe estar entre 1 y 35
        if ($this->dia_de_entrega !== null && ($this->dia_de_entrega < 1 || $this->dia_de_entrega > 35)) {
            return false;
        }
        return true;
    }

    /**
     * Obtener mensaje de validación
     */
    public function getValidationMessage(): ?string
    {
        if ($this->dia_de_entrega !== null && ($this->dia_de_entrega < 1 || $this->dia_de_entrega > 35)) {
            return 'Día de entrega inválido. Debe ser entre 1 y 35';
        }
        return null;
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'dia_de_entrega' => $this->dia_de_entrega,
            'calcular_fecha_estimada' => $this->calcular_fecha_estimada,
        ];
    }
}
