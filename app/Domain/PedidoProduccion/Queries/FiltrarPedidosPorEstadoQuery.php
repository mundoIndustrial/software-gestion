<?php

namespace App\Domain\PedidoProduccion\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * FiltrarPedidosPorEstadoQuery
 * 
 * Query para filtrar pedidos por estado
 * Estados válidos: activo, pendiente, cancelado, completado
 * 
 * @param string $estado Estado a filtrar
 * @param int $page Número de página (default 1)
 * @param int $perPage Registros por página (default 15)
 */
class FiltrarPedidosPorEstadoQuery implements Query
{
    private const ESTADOS_VALIDOS = ['activo', 'pendiente', 'cancelado', 'completado'];

    public function __construct(
        private string $estado,
        private int $page = 1,
        private int $perPage = 15,
    ) {
        if (!in_array(strtolower($this->estado), self::ESTADOS_VALIDOS)) {
            throw new \InvalidArgumentException("Estado inválido: {$estado}");
        }
    }

    public function getEstado(): string
    {
        return strtolower($this->estado);
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }
}
