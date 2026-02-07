<?php

namespace App\Repositories;

use App\Domain\Pedidos\Entities\ItemPedido;
use App\Domain\Pedidos\Repositories\ItemPedidoRepository as ItemPedidoRepositoryInterface;
use App\Domain\Pedidos\ValueObjects\TipoItem;
use App\Domain\Pedidos\ValueObjects\OrdenItem;
use App\Models\ItemPedido as ItemPedidoModel;
use Illuminate\Support\Collection;

/**
 * Repository: ItemPedidoRepository
 * 
 * Implementación concreta para persistencia de ItemPedido
 * Traduce entre modelos de Eloquent y entidades de Dominio
 */
class EloquentItemPedidoRepository implements ItemPedidoRepositoryInterface
{
    /**
     * Guardar (crear o actualizar) un item
     */
    public function guardar(ItemPedido $item): ItemPedido
    {
        $modelo = $this->obtenerOCrearModelo($item->id());

        $modelo->pedido_id = $item->pedidoId();
        $modelo->referencia_id = $item->referenciaId();
        $modelo->tipo = $item->tipo()->valor();
        $modelo->orden = $item->orden()->valor();
        $modelo->nombre = $item->nombre();
        $modelo->descripcion = $item->descripcion();
        $modelo->datos_presentacion = json_encode($item->datosPresentacion());

        $modelo->save();

        // Reconstruir la entidad con el ID asignado
        return new ItemPedido(
            id: $modelo->id,
            pedidoId: $modelo->pedido_id,
            referenciaId: $modelo->referencia_id,
            tipo: TipoItem::desde($modelo->tipo),
            orden: OrdenItem::desde($modelo->orden),
            nombre: $modelo->nombre,
            descripcion: $modelo->descripcion,
            datosPresentacion: json_decode($modelo->datos_presentacion, true) ?? [],
            fechaCreacion: $modelo->created_at
        );
    }

    /**
     * Encontrar un item por ID
     */
    public function encontrarPorId(int $id): ?ItemPedido
    {
        $modelo = ItemPedidoModel::find($id);

        if (!$modelo) {
            return null;
        }

        return $this->modeloAEntidad($modelo);
    }

    /**
     * Obtener todos los items de un pedido (no ordenados)
     */
    public function obtenerPorPedido(int $pedidoId): Collection
    {
        return ItemPedidoModel::where('pedido_id', $pedidoId)
            ->orderBy('orden', 'asc')
            ->get()
            ->map(fn($modelo) => $this->modeloAEntidad($modelo));
    }

    /**
     * Obtener items ordenados para API
     */
    public function obtenerPorPedidoOrdenados(int $pedidoId): array
    {
        return ItemPedidoModel::where('pedido_id', $pedidoId)
            ->orderBy('orden', 'asc')
            ->get()
            ->map(function ($modelo) {
                return [
                    'id' => $modelo->id,
                    'tipo' => $modelo->tipo,
                    'nombre' => $modelo->nombre,
                    'descripcion' => $modelo->descripcion,
                    'orden' => $modelo->orden,
                    'referencia_id' => $modelo->referencia_id,
                    'datos_presentacion' => json_decode($modelo->datos_presentacion, true) ?? [],
                ];
            })
            ->toArray();
    }

    /**
     * Eliminar un item
     */
    public function eliminar(int $itemId): void
    {
        ItemPedidoModel::destroy($itemId);
    }

    private function modeloAEntidad(ItemPedidoModel $modelo): ItemPedido
    {
        return new ItemPedido(
            id: $modelo->id,
            pedidoId: $modelo->pedido_id,
            referenciaId: $modelo->referencia_id,
            tipo: TipoItem::desde($modelo->tipo),
            orden: OrdenItem::desde($modelo->orden),
            nombre: $modelo->nombre,
            descripcion: $modelo->descripcion,
            datosPresentacion: json_decode($modelo->datos_presentacion, true) ?? [],
            fechaCreacion: $modelo->created_at
        );
    }

    private function obtenerOCrearModelo(?int $id): ItemPedidoModel
    {
        if ($id === null) {
            return new ItemPedidoModel();
        }

        return ItemPedidoModel::findOrFail($id);
    }
}
