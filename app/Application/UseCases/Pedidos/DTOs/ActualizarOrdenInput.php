<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de ActualizarOrdenUseCase
 * 
 * Responsabilidad: Encapsular datos de entrada para actualizar una orden
 * Patrón: Transfer Object
 */
class ActualizarOrdenInput
{
    public function __construct(
        public int $numero_pedido,
        public ?string $cliente = null,
        public ?string $asesora = null,
        public ?string $forma_de_pago = null,
        public ?string $descripcion = null,
        public ?string $novedades = null,
        public ?string $estado = null,
        public ?string $area = null,
        public ?int $numero_recibo = null,
        public ?int $dia_de_entrega = null,
        public ?string $fecha_estimada_de_entrega = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request, int $numeroPedido): self
    {
        return new self(
            numero_pedido: $numeroPedido,
            cliente: $request->input('cliente'),
            asesora: $request->input('asesora'),
            forma_de_pago: $request->input('forma_pago'),
            descripcion: $request->input('descripcion'),
            novedades: $request->input('novedades'),
            estado: $request->input('estado'),
            area: $request->input('area'),
            numero_recibo: $request->input('numero_recibo'),
            dia_de_entrega: $request->input('dia_de_entrega'),
            fecha_estimada_de_entrega: $request->input('fecha_estimada_de_entrega'),
            metadata: $request->input('metadata'),
        );
    }

    /**
     * Obtener solo los campos que fueron actualizados
     */
    public function getChangedFields(): array
    {
        $data = [];
        if ($this->cliente !== null) $data['cliente'] = $this->cliente;
        if ($this->asesora !== null) $data['asesora'] = $this->asesora;
        if ($this->forma_de_pago !== null) $data['forma_de_pago'] = $this->forma_de_pago;
        if ($this->descripcion !== null) $data['descripcion'] = $this->descripcion;
        if ($this->novedades !== null) $data['novedades'] = $this->novedades;
        if ($this->estado !== null) $data['estado'] = $this->estado;
        if ($this->area !== null) $data['area'] = $this->area;
        if ($this->numero_recibo !== null) $data['numero_recibo'] = $this->numero_recibo;
        if ($this->dia_de_entrega !== null) $data['dia_de_entrega'] = $this->dia_de_entrega;
        if ($this->fecha_estimada_de_entrega !== null) $data['fecha_estimada_de_entrega'] = $this->fecha_estimada_de_entrega;
        if ($this->metadata !== null) $data['metadata'] = $this->metadata;
        return $data;
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return array_filter([
            'numero_pedido' => $this->numero_pedido,
            'cliente' => $this->cliente,
            'asesora' => $this->asesora,
            'forma_de_pago' => $this->forma_de_pago,
            'descripcion' => $this->descripcion,
            'novedades' => $this->novedades,
            'estado' => $this->estado,
            'area' => $this->area,
            'numero_recibo' => $this->numero_recibo,
            'dia_de_entrega' => $this->dia_de_entrega,
            'fecha_estimada_de_entrega' => $this->fecha_estimada_de_entrega,
            'metadata' => $this->metadata,
        ], fn($val) => $val !== null);
    }
}
