<?php

namespace App\Domain\Epp\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * ObtenerEppPorIdQuery
 * 
 * Query para obtener un EPP por su ID
 */
class ObtenerEppPorIdQuery implements Query
{
    public function __construct(
        private int $id,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }
}
