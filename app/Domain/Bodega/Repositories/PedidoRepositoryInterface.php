<?php

namespace App\Domain\Bodega\Repositories;

use App\Domain\Bodega\Entities\Pedido;
use App\Domain\Bodega\ValueObjects\EstadoPedido;
use App\Domain\Bodega\ValueObjects\AreaBodega;
use Illuminate\Support\Collection;

/**
 * Interface para Repository de Pedidos
 * Define el contrato de acceso a datos, agnóstico a la implementación
 */
interface PedidoRepositoryInterface
{
    /**
     * Encontrar un pedido por su ID
     */
    public function findById(int $id): ?Pedido;

    /**
     * Encontrar un pedido por su número de pedido
     */
    public function findByNumero(string $numeroPedido): ?Pedido;

    /**
     * Guardar un pedido (crear o actualizar)
     */
    public function save(Pedido $pedido): void;

    /**
     * Eliminar un pedido
     */
    public function delete(Pedido $pedido): void;

    /**
     * Obtener todos los pedidos con un estado específico
     */
    public function findByEstado(EstadoPedido $estado): Collection;

    /**
     * Obtener pedidos por múltiples estados
     */
    public function findByEstados(array $estados): Collection;

    /**
     * Obtener pedidos que pertenecen a áreas específicas
     */
    public function findByAreas(array $areas): Collection;

    /**
     * Obtener pedidos con filtros combinados
     */
    public function findWithFilters(
        ?array $estados = null,
        ?array $areas = null,
        ?string $cliente = null,
        ?string $asesor = null,
        ?\DateTime $fechaDesde = null,
        ?\DateTime $fechaHasta = null
    ): Collection;

    /**
     * Obtener pedidos paginados
     */
    public function findPaginated(
        int $pagina = 1,
        int $porPagina = 20,
        array $filtros = []
    ): array;

    /**
     * Verificar si un pedido existe
     */
    public function exists(int $id): bool;

    /**
     * Verificar si existe un pedido con ese número
     */
    public function existsByNumero(string $numeroPedido): bool;

    /**
     * Contar pedidos por estado
     */
    public function countByEstado(EstadoPedido $estado): int;

    /**
     * Contar pedidos en retraso
     */
    public function countRetrasados(): int;

    /**
     * Obtener estadísticas de pedidos
     */
    public function getEstadisticas(): array;

    /**
     * Obtener el próximo número de pedido disponible
     */
    public function getSiguienteNumeroPedido(): string;
}
