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

    public function __construct(
        string $numeroPedido,
        string $cliente,
        array $prendas = []
    ) {
        $this->numeroPedido = trim($numeroPedido);
        $this->cliente = trim($cliente);
        $this->prendas = $prendas;

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
            $datos['prendas'] ?? []
        );
    }

    private function validar(): void
    {
        if (empty($this->numeroPedido)) {
            throw new InvalidArgumentException("Número de pedido es requerido");
        }

        if (empty($this->cliente)) {
            throw new InvalidArgumentException("Cliente es requerido");
        }
    }
}
