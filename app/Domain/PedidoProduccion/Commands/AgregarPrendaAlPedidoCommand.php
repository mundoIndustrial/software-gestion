<?php

namespace App\Domain\PedidoProduccion\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * AgregarPrendaAlPedidoCommand
 * 
 * Command para agregar una nueva prenda a un pedido
 * Utiliza PrendaCreationService internamente
 * 
 * @param int|string $pedidoId ID del pedido
 * @param array $prendaData Datos de la prenda (nombre, cantidad, color, etc)
 * @param string $tipo Tipo: 'sin_cotizacion' o 'reflectivo'
 */
class AgregarPrendaAlPedidoCommand implements Command
{
    private const TIPOS_VALIDOS = ['sin_cotizacion', 'reflectivo'];

    public function __construct(
        private int|string $pedidoId,
        private array $prendaData,
        private string $tipo = 'sin_cotizacion',
    ) {
        if (!in_array($tipo, self::TIPOS_VALIDOS)) {
            throw new \InvalidArgumentException("Tipo de prenda inválido: {$tipo}");
        }
    }

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }

    public function getPrendaData(): array
    {
        return $this->prendaData;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }
}
