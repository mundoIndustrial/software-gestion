<?php

namespace App\Domain\Pedidos\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * FiltrarPedidosPorEstadoQuery
 * 
 * Query para filtrar pedidos por estado
 * Estados vÃ¡lidos: activo, pendiente, cancelado, completado
 * 
 * @param string $estado Estado a filtrar
 * @param int $page NÃºmero de pÃ¡gina (default 1)
 * @param int $perPage Registros por pÃ¡gina (default 15)
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
            throw new \InvalidArgumentException("Estado invÃ¡lido: {$estado}");
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

