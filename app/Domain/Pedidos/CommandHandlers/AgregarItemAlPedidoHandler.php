<?php

namespace App\Domain\Pedidos\CommandHandlers;

use App\Domain\Pedidos\Commands\AgregarItemAlPedidoCommand;
use App\Domain\Pedidos\Entities\ItemPedido;
use App\Domain\Pedidos\ValueObjects\TipoItem;
use App\Domain\Pedidos\Repositories\ItemPedidoRepository;
use App\Domain\Pedidos\DomainServices\GestorItemsPedidoDomainService;
use App\Domain\Pedidos\Events\ItemAgregadoAlPedido;

/**
 * Command Handler: AgregarItemAlPedidoHandler
 * 
 * Maneja la agregación de items al pedido
 * Aplica validaciones de dominio y dispara eventos
 */
class AgregarItemAlPedidoHandler
{
    public function __construct(
        private ItemPedidoRepository $itemRepository,
        private GestorItemsPedidoDomainService $gestorItems
    ) {}

    public function ejecutar(AgregarItemAlPedidoCommand $comando): ItemPedido
    {
        // Obtener items actuales del pedido
        $items = $this->itemRepository->obtenerPorPedido($comando->pedidoId);

        // Crear el nuevo item
        $tipoItem = TipoItem::desde($comando->tipo);
        
        if ($tipoItem->esPrenda()) {
            $item = ItemPedido::crearPrenda(
                pedidoId: $comando->pedidoId,
                prendaId: $comando->referenciaId,
                nombre: $comando->nombre,
                descripcion: $comando->descripcion,
                orden: $this->gestorItems->calcularProximaPosicion($items),
                datosPresentacion: $comando->datosPresentacion
            );
        } else {
            $item = ItemPedido::crearEpp(
                pedidoId: $comando->pedidoId,
                eppId: $comando->referenciaId,
                nombre: $comando->nombre,
                descripcion: $comando->descripcion,
                orden: $this->gestorItems->calcularProximaPosicion($items),
                datosPresentacion: $comando->datosPresentacion
            );
        }

        // Validar y agregar al collection
        $items = $this->gestorItems->agregarItemAlFinal($items, $item);

        // Persistir
        $itemGuardado = $this->itemRepository->guardar($item);

        // Disparar evento de dominio
        $itemGuardado->registrarEvento(
            ItemAgregadoAlPedido::desde(
                pedidoId: $comando->pedidoId,
                itemId: $itemGuardado->id(),
                referenciaId: $comando->referenciaId,
                tipo: $tipoItem,
                nombre: $comando->nombre,
                orden: $item->orden()->valor()
            )
        );

        return $itemGuardado;
    }
}
