<?php

namespace App\Domain\Pedidos\Commands;

use App\Domain\Shared\Command;

/**
 * Command: AgregarItemAlPedidoCommand
 * 
 * Instrucción para agregar un item (Prenda o EPP) a un pedido
 */
class AgregarItemAlPedidoCommand extends Command
{
    public function __construct(
        public readonly int $pedidoId,
        public readonly string $tipo,  // 'prenda' o 'epp'
        public readonly int $referenciaId,  // ID de la Prenda o EPP
        public readonly string $nombre,
        public readonly ?string $descripcion = null,
        public readonly array $datosPresentacion = []
    ) {}
}
