<?php

namespace App\Domain\Epp\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * BuscarEppQuery
 * 
 * Query para buscar EPP por término (código o nombre)
 */
class BuscarEppQuery implements Query
{
    public function __construct(
        private string $termino,
    ) {}

    public function getTermino(): string
    {
        return $this->termino;
    }
}
