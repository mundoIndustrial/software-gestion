<?php

namespace App\Infrastructure\Repositories\SupervisorPedidos;

use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Domain\SupervisorPedidos\Entities\Order;
use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use App\Domain\SupervisorPedidos\ValueObjects\OrderStatus;
use App\Models\PedidoProduccion;

class EloquentOrderRepository implements OrderRepository
{
    public function findById(OrderId $id): ?Order
    {
        $pedido = PedidoProduccion::find($id->value());

        if (!$pedido) {
            return null;
        }

        return $this->toDomain($pedido);
    }

    public function save(Order $order): void
    {
        $pedido = PedidoProduccion::find($order->getId()->value());

        if (!$pedido) {
            throw new \RuntimeException('Pedido no encontrado');
        }

        $pedido->update([
            'estado' => $order->getStatus()->value(),
            'aprobado_por_supervisor_en' => $order->getApprovedBySupervisorAt(),
            'novedades' => $order->getNotes(),
        ]);

        // Publicar domain events
        foreach ($order->pullDomainEvents() as $event) {
            event($event);
        }
    }

    public function findAllPending(): array
    {
        $pedidos = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')
            ->with(['asesora', 'prendas', 'epps'])
            ->get();

        return $pedidos->map(function(PedidoProduccion $p) {
            return $this->toDomain($p);
        })->values()->toArray();
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        $pedido = PedidoProduccion::where('numero_pedido', $orderNumber)->first();

        if (!$pedido) {
            return null;
        }

        return $this->toDomain($pedido);
    }

    public function findByIdWithRelations(int $id): ?array
    {
        $pedido = PedidoProduccion::with([
            'asesora',
            'prendas',
            'prendas.color',
            'prendas.tela',
            'prendas.tipoManga',
            'prendas.tipoBrocheBoton',
            'cotizacion',
            'cotizacion.tipoCotizacion',
            'epps'
        ])->find($id);

        if (!$pedido) {
            return null;
        }

        return $pedido->toArray();
    }

    public function updateMultiple(int $id, array $data): ?array
    {
        $pedido = PedidoProduccion::find($id);

        if (!$pedido) {
            return null;
        }

        $pedido->update($data);

        return $pedido->fresh()->toArray();
    }

    public function updateStatus(int $id, string $status): ?array
    {
        $pedido = PedidoProduccion::find($id);

        if (!$pedido) {
            return null;
        }

        $pedido->update(['estado' => $status]);

        return $pedido->fresh()->toArray();
    }

    private function toDomain(PedidoProduccion $model): Order
    {
        return new Order(
            new OrderId($model->id),
            new OrderStatus($model->estado),
            $model->cliente ?? '',
            $model->numero_pedido ?? '',
            $model->aprobado_por_supervisor_en,
            $model->novedades ?? ''
        );
    }
}
