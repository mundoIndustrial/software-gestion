<?php

namespace App\Domain\Epp\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * ObtenerEppPorCategoriaQuery
 * 
 * Query para obtener EPP filtrados por categoría
 */
class ObtenerEppPorCategoriaQuery implements Query
{
    public function __construct(
        private string $categoria,
    ) {}

    public function getCategoria(): string
    {
        return $this->categoria;
    }
}
