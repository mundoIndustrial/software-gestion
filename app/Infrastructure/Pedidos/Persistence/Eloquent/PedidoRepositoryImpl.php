<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Agregado\PedidoAggregate;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;
use App\Domain\Pedidos\ValueObjects\Estado;
use App\Domain\Pedidos\Entities\PrendaPedido;
use App\Models\Pedido as PedidoModel;
use App\Models\PrendaPedido as PrendaPedidoModel;

/**
 * Repository Implementation para Pedidos
 * 
 * Implementa la persistencia usando Eloquent
 * Convierte entre agregado de dominio y modelo Eloquent
 * 
 * TALLAS: Se guardan en tabla relacional `prenda_pedido_tallas`
 * ========
 * Representa LO QUE PIDIÓ EL CLIENTE para cada prenda
 * - Un registro por cada (genero, talla, cantidad)
 * - NO como JSON en la propia prenda_pedido
 * 
 * Ejemplo:
 *   Prenda: Camiseta (prenda_pedido_id = 10)
 *   Tallas pedidas:
 *   - DAMA XS: 5 unidades
 *   - DAMA S:  5 unidades
 *   - DAMA M:  5 unidades
 */
class PedidoRepositoryImpl implements PedidoRepository
{
    public function guardar(PedidoAggregate $pedido): void
    {
        \DB::transaction(function () use ($pedido) {
            // Determinar si es nuevo o actualización
            $datos = [
                'cliente_id' => $pedido->clienteId(),
                'estado' => $pedido->estado()->valor(),
                'descripcion' => $pedido->descripcion(),
                'observaciones' => $pedido->observaciones(),
                'numero' => (string)$pedido->numero(),
            ];

            if ($pedido->id() === null) {
                // Nuevo: crear
                $pedidoModel = PedidoModel::create($datos);
                $pedido->setId($pedidoModel->id);
            } else {
                // Actualizar existente
                $pedidoModel = PedidoModel::findOrFail($pedido->id());
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
            ->where('numero', (string)$numero)
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
        return PedidoModel::with('prendas')
            ->where('estado', $estado)
            ->get()
            ->map(fn($model) => $this->reconstituir($model))
            ->toArray();
    }

    public function eliminar(int $id): void
    {
        \DB::transaction(function () use ($id) {
            PrendaPedidoModel::where('pedido_id', $id)->delete();
            PedidoModel::destroy($id);
        });
    }

    private function reconstituir(PedidoModel $model): PedidoAggregate
    {
        // Para esta fase, solo reconstruimos el agregado básico sin prendas
        // Las prendas están en otra tabla y requieren otra estrategia
        
        return PedidoAggregate::reconstruir(
            id: $model->id,
            numero: NumeroPedido::desde($model->numero),
            clienteId: $model->cliente_id,
            estado: Estado::desde($model->estado),
            descripcion: $model->descripcion,
            prendas: [], // Fase posterior: integrar prendas_pedido
            fechaCreacion: $model->created_at,
            observaciones: $model->observaciones,
            fechaActualizacion: $model->updated_at
        );
    }

    private function guardarPrendas(PedidoAggregate $pedido, PedidoModel $pedidoModel): void
    {
        // Fase posterior: implementar integración con prendas_pedido
        // Por ahora, no guardamos prendas en el agregado
        // Las prendas se gestionan a través de otro endpoint/use case
    }

    private function guardarTallas(int $prendaPedidoId, array $tallas): void
    {
        // Fase posterior: implementar guardar tallas cuando se integre con prendas
    }

    private function reconstruirTallas(int $prendaPedidoId): array
    {
        // Fase posterior: implementar recuperar tallas cuando se integre con prendas
        return [];
    }
}
