<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de ActualizarPrendasUseCase
 * 
 * Responsabilidad: Encapsular datos para actualizar prendas de una orden
 * Patrón: Transfer Object
 */
class ActualizarPrendasInput
{
    public function __construct(
        public int $numero_pedido,
        public string $cliente,
        public string $estado = 'No iniciado',
        public string $forma_de_pago = '',
        public ?string $fecha_creacion = null,
        public array $prendas = [],
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request, int $numeroPedido): self
    {
        return new self(
            numero_pedido: $numeroPedido,
            cliente: $request->input('cliente', ''),
            estado: $request->input('estado', 'No iniciado'),
            forma_de_pago: $request->input('forma_pago', ''),
            fecha_creacion: $request->input('fecha_creacion'),
            prendas: $request->input('prendas', []),
        );
    }

    /**
     * Validar entrada
     */
    public function isValid(): bool
    {
        return !empty($this->cliente) && !empty($this->prendas);
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'cliente' => $this->cliente,
            'estado' => $this->estado,
            'forma_de_pago' => $this->forma_de_pago,
            'fecha_creacion' => $this->fecha_creacion,
            'prendas' => $this->prendas,
        ];
    }
}
