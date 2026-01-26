<?php

namespace App\Application\Pedidos\DTOs;

use InvalidArgumentException;

/**
 * CrearProduccionPedidoDTO
 * 
 * Data Transfer Object para crear un nuevo pedido de producción
 * Encapsula los datos del request y los valida
 */
class CrearProduccionPedidoDTO
{
    public string $numeroPedido;
    public string $cliente;
    public array $prendas;
    public array $epps;
    public ?string $area;
    public ?string $estado;
    public ?int $asesorId;
    public ?int $clienteId;
    public ?string $formaDePago;

    public function __construct(
        string $numeroPedido,
        string $cliente,
        array $prendas = [],
        array $epps = [],
        ?string $area = null,
        ?string $estado = null,
        ?int $asesorId = null,
        ?int $clienteId = null,
        ?string $formaDePago = null
    ) {
        $this->numeroPedido = trim($numeroPedido);
        $this->cliente = trim($cliente);
        $this->prendas = $prendas;
        $this->epps = $epps;
        $this->area = $area ?? 'creacion de pedido';
        $this->estado = $estado ?? 'Pendiente';
        $this->asesorId = $asesorId;
        $this->clienteId = $clienteId;
        $this->formaDePago = $formaDePago;

        $this->validar();
    }

    /**
     * Factory desde Request HTTP
     */
    public static function fromRequest(array $datos): self
    {
        return new self(
            $datos['numero_pedido'] ?? '',
            $datos['cliente'] ?? '',
            $datos['prendas'] ?? [],
            $datos['epps'] ?? [],
            $datos['area'] ?? 'creacion de pedido',
            $datos['estado'] ?? 'Pendiente',
            $datos['asesor_id'] ?? null,
            $datos['cliente_id'] ?? null,
            $datos['forma_pago'] ?? null
        );
    }

    private function validar(): void
    {
        if (empty($this->numeroPedido)) {
            throw new InvalidArgumentException("NÃºmero de pedido es requerido");
        }

        if (empty($this->cliente)) {
            throw new InvalidArgumentException("Cliente es requerido");
        }
    }
}

