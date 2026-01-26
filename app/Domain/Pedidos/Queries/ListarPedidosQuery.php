<?php

namespace App\Domain\Pedidos\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * ListarPedidosQuery
 * 
 * Query para listar todos los pedidos con paginación
 * 
 * @param int $page NÃºmero de pÃ¡gina (default 1)
 * @param int $perPage Registros por pÃ¡gina (default 15)
 * @param string $ordenar Campo para ordenar (default 'created_at')
 * @param string $direccion Dirección (asc/desc, default desc)
 */
class ListarPedidosQuery implements Query
{
    public function __construct(
        private int $page = 1,
        private int $perPage = 15,
        private string $ordenar = 'created_at',
        private string $direccion = 'desc',
    ) {}

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getOrdenar(): string
    {
        return $this->ordenar;
    }

    public function getDireccion(): string
    {
        return strtolower($this->direccion) === 'asc' ? 'asc' : 'desc';
    }
}

