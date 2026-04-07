<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\ValueObjects\Estado;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;
use App\Models\PedidoProduccion as PedidoModel;

/**
 * Implementacion Eloquent del contrato de persistencia de pedidos.
 */
class PedidoRepositoryImpl implements PedidoRepository
{
    private function mapEstadoABD(string $estadoDDD): string
    {
        $mapa = [
            'PENDIENTE' => 'pendiente_cartera',
            'CONFIRMADO' => 'En Ejecución',
            'EN_PRODUCCION' => 'En Ejecución',
            'COMPLETADO' => 'Entregado',
            'CANCELADO' => 'Anulada',
        ];

        return $mapa[$estadoDDD] ?? 'Pendiente';
    }

    private function mapEstadoADDD(string $estadoBD): string
    {
        $mapa = [
            'Pendiente' => 'PENDIENTE',
            'pendiente_cartera' => 'PENDIENTE',
            'PENDIENTE_SUPERVISOR' => 'PENDIENTE',
            'En Ejecucion' => 'EN_PRODUCCION',
            'En Ejecución' => 'EN_PRODUCCION',
            'Entregado' => 'COMPLETADO',
            'Anulada' => 'CANCELADO',
            'No iniciado' => 'PENDIENTE',
        ];

        return $mapa[$estadoBD] ?? 'PENDIENTE';
    }

    public function guardar(PedidoAggregate $pedido): void
    {
        \DB::transaction(function () use ($pedido) {
            $datos = [
                'numero_pedido' => $pedido->numero()->valor(),
                'cliente_id' => $pedido->clienteId(),
                'estado' => $this->mapEstadoABD($pedido->estado()->valor()),
                'novedades' => $pedido->observaciones(),
            ];

            if ($pedido->id() === null) {
                $pedidoModel = PedidoModel::create($datos);
                $pedido->setId($pedidoModel->id);
            } else {
                $pedidoModel = PedidoModel::findOrFail($pedido->id());
                // En actualizaciones generales del pedido no se modifica el estado.
                $datos['estado'] = $pedidoModel->estado;
                $pedidoModel->update($datos);
            }

            $this->guardarPrendas($pedido, $pedidoModel);
        });
    }

    public function porId(int $id): ?PedidoAggregate
    {
        $pedidoModel = PedidoModel::with('prendas')->find($id);
        if (!$pedidoModel) {
            return null;
        }

        return $this->reconstituir($pedidoModel);
    }

    public function porNumero(NumeroPedido $numero): ?PedidoAggregate
    {
        $pedidoModel = PedidoModel::with('prendas')
            ->where('numero_pedido', $numero->valor())
            ->first();

        if (!$pedidoModel) {
            return null;
        }

        return $this->reconstituir($pedidoModel);
    }

    public function porClienteId(int $clienteId): array
    {
        return PedidoModel::with('prendas')
            ->where('cliente_id', $clienteId)
            ->get()
            ->map(fn($model) => $this->reconstituir($model))
            ->toArray();
    }

    public function porEstado(string $estado): array
    {
        $estadoBD = $this->mapEstadoABD($estado);

        return PedidoModel::with('prendas')
            ->where('estado', $estadoBD)
            ->get()
            ->map(fn($model) => $this->reconstituir($model))
            ->toArray();
    }

    public function eliminar(int $id): void
    {
        \DB::transaction(function () use ($id) {
            PedidoModel::destroy($id);
        });
    }

    public function calcularCantidadTotalPrendas(int $pedidoId): int
    {
        try {
            $cantidad = \DB::table('pedidos_procesos_prenda_tallas as pppt')
                ->selectRaw('COALESCE(SUM(pppt.cantidad), 0) as total')
                ->join('pedidos_procesos_prenda_detalles as ppd', 'pppt.proceso_prenda_detalle_id', '=', 'ppd.id')
                ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
                ->where('pp.pedido_produccion_id', $pedidoId)
                ->value('total');

            return (int) $cantidad ?? 0;
        } catch (\Exception $e) {
            \Log::warning('[PEDIDO-REPOSITORY] Error calculando cantidad de prendas', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    public function calcularCantidadTotalEpps(int $pedidoId): int
    {
        try {
            $cantidad = \DB::table('pedido_epp')
                ->where('pedido_produccion_id', $pedidoId)
                ->sum('cantidad');

            return (int) $cantidad ?? 0;
        } catch (\Exception $e) {
            \Log::warning('[PEDIDO-REPOSITORY] Error calculando cantidad de EPPs', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    private function reconstituir(PedidoModel $model): PedidoAggregate
    {
        $estadoDDD = $this->mapEstadoADDD($model->estado);

        return PedidoAggregate::reconstruir(
            id: $model->id,
            numero: NumeroPedido::desde($model->numero_pedido),
            clienteId: $model->cliente_id ?? null,
            estado: Estado::desde($estadoDDD),
            descripcion: $model->novedades ?? '',
            prendas: [],
            fechaCreacion: $model->created_at,
            observaciones: $model->novedades ?? '',
            fechaActualizacion: $model->updated_at
        );
    }

    private function guardarPrendas(PedidoAggregate $pedido, PedidoModel $pedidoModel): void
    {
        // Pendiente: persistir prendas del agregado en una siguiente fase.
    }
}
