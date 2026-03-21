<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de CrearOrdenUseCase
 * 
 * Responsabilidad: Encapsular datos de entrada para crear una nueva orden
 * Patrón: Transfer Object
 */
class CrearOrdenInput
{
    public function __construct(
        public string $cliente,
        public string $asesora,
        public string $forma_de_pago,
        public string $descripcion,
        public ?string $novedades = null,
        public ?string $area = null,
        public ?int $numero_recibo = null,
        public bool $allow_any_pedido = false,
        public ?array $metadata = null,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            cliente: $request->input('cliente'),
            asesora: $request->input('asesora'),
            forma_de_pago: $request->input('forma_pago'),
            descripcion: $request->input('descripcion'),
            novedades: $request->input('novedades'),
            area: $request->input('area'),
            numero_recibo: $request->input('numero_recibo'),
            allow_any_pedido: $request->boolean('allow_any_pedido', false),
            metadata: $request->input('metadata', []),
        );
    }

    /**
     * Factory: Crear desde array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cliente: $data['cliente'] ?? '',
            asesora: $data['asesora'] ?? '',
            forma_de_pago: $data['forma_pago'] ?? '',
            descripcion: $data['descripcion'] ?? '',
            novedades: $data['novedades'] ?? null,
            area: $data['area'] ?? null,
            numero_recibo: $data['numero_recibo'] ?? null,
            allow_any_pedido: $data['allow_any_pedido'] ?? false,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'cliente' => $this->cliente,
            'asesora' => $this->asesora,
            'forma_de_pago' => $this->forma_de_pago,
            'descripcion' => $this->descripcion,
            'novedades' => $this->novedades,
            'area' => $this->area,
            'numero_recibo' => $this->numero_recibo,
            'allow_any_pedido' => $this->allow_any_pedido,
            'metadata' => $this->metadata,
        ];
    }
}
