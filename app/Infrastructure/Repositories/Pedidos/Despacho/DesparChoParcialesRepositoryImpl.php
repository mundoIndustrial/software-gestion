<?php

namespace App\Infrastructure\Repositories\Pedidos\Despacho;

use App\Domain\Pedidos\Despacho\Entities\DesparChoParcial;
use App\Domain\Pedidos\Despacho\Repositories\DesparChoParcialesRepository;
use App\Models\DesparChoParcialesModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * DesparChoParcialesRepositoryImpl (Infrastructure Implementation)
 * 
 * Implementa la persistencia de DesparChoParcial en la BD
 * usando Eloquent.
 * 
 * Responsable de:
 * - Convertir entre entidades Domain y modelos Eloquent
 * - Realizar operaciones CRUD
 * - Mantener transacciones
 */
class DesparChoParcialesRepositoryImpl implements DesparChoParcialesRepository
{
    /**
     * Guardar un despacho parcial
     */
    public function guardar(DesparChoParcial $despacho): void
    {
        $data = $this->entidadAArray($despacho);
        
        DesparChoParcialesModel::create($data);
    }

    /**
     * Guardar múltiples despachos parciales (transacción)
     */
    public function guardarMultiples(array $despachos): void
    {
        DB::transaction(function () use ($despachos) {
            foreach ($despachos as $despacho) {
                $this->guardar($despacho);
            }
        });
    }

    /**
     * Obtener por ID
     */
    public function obtenerPorId(int $id): ?DesparChoParcial
    {
        $modelo = DesparChoParcialesModel::find($id);
        
        return $modelo ? $this->modeloAEntidad($modelo) : null;
    }

    /**
     * Obtener todos los despachos de un pedido
     */
    public function obtenerPorPedidoId(int $pedidoId): Collection
    {
        return DesparChoParcialesModel::porPedido($pedidoId)
            ->activo()
            ->get()
            ->map(fn($modelo) => $this->modeloAEntidad($modelo));
    }

    /**
     * Obtener despachos de un ítem específico
     */
    public function obtenerPorItem(string $tipoItem, int $itemId): Collection
    {
        return DesparChoParcialesModel::porItem($tipoItem, $itemId)
            ->activo()
            ->get()
            ->map(fn($modelo) => $this->modeloAEntidad($modelo));
    }

    /**
     * Obtener despachos de un pedido filtrados por tipo de ítem
     */
    public function obtenerPorPedidoYTipo(int $pedidoId, string $tipoItem): Collection
    {
        return DesparChoParcialesModel::porPedido($pedidoId)
            ->porTipo($tipoItem)
            ->activo()
            ->get()
            ->map(fn($modelo) => $this->modeloAEntidad($modelo));
    }

    /**
     * Actualizar un despacho parcial
     */
    public function actualizar(DesparChoParcial $despacho): void
    {
        $data = $this->entidadAArray($despacho);
        
        DesparChoParcialesModel::find($despacho->id())->update($data);
    }

    /**
     * Eliminar un despacho parcial (soft delete)
     */
    public function eliminar(int $id): void
    {
        DesparChoParcialesModel::find($id)->delete();
    }

    /**
     * Verificar si existe un despacho para un ítem
     */
    public function existeParaItem(string $tipoItem, int $itemId): bool
    {
        return DesparChoParcialesModel::porItem($tipoItem, $itemId)
            ->activo()
            ->exists();
    }

    // ============ HELPERS DE CONVERSIÓN ============

    /**
     * Convertir modelo Eloquent a entidad Domain
     */
    private function modeloAEntidad(DesparChoParcialesModel $modelo): DesparChoParcial
    {
        return DesparChoParcial::reconstruir(
            id: $modelo->id,
            pedidoId: $modelo->pedido_id,
            tipoItem: $modelo->tipo_item,
            itemId: $modelo->item_id,
            tallaId: $modelo->talla_id,
            genero: $modelo->genero,        // ✅ Agregar género
            pendienteInicial: $modelo->pendiente_inicial,
            parcial1: $modelo->parcial_1,
            pendiente1: $modelo->pendiente_1,
            parcial2: $modelo->parcial_2,
            pendiente2: $modelo->pendiente_2,
            parcial3: $modelo->parcial_3,
            pendiente3: $modelo->pendiente_3,
            observaciones: $modelo->observaciones,
            usuarioId: $modelo->usuario_id,
            fechaDespacho: $modelo->fecha_despacho,
            createdAt: $modelo->created_at,
            updatedAt: $modelo->updated_at,
            deletedAt: $modelo->deleted_at,
        );
    }

    /**
     * Convertir entidad Domain a array para persistencia
     */
    private function entidadAArray(DesparChoParcial $despacho): array
    {
        return [
            'pedido_id' => $despacho->pedidoId(),
            'tipo_item' => $despacho->tipoItem(),
            'item_id' => $despacho->itemId(),
            'talla_id' => $despacho->tallaId(),
            'genero' => $despacho->genero(),  // ✅ Agregar género
            'pendiente_inicial' => $despacho->pendienteInicial(),
            'parcial_1' => $despacho->parcial1(),
            'pendiente_1' => $despacho->pendiente1(),
            'parcial_2' => $despacho->parcial2(),
            'pendiente_2' => $despacho->pendiente2(),
            'parcial_3' => $despacho->parcial3(),
            'pendiente_3' => $despacho->pendiente3(),
            'observaciones' => $despacho->observaciones(),
            'fecha_despacho' => $despacho->fechaDespacho(),
            'usuario_id' => $despacho->usuarioId(),
        ];
    }
}
