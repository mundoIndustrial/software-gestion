<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * RegistroOrdenSearchService
 * 
 * Servicio para aplicar filtros de búsqueda al query builder
 */
class RegistroOrdenSearchService
{
    /**
     * Aplicar filtro de búsqueda por número de pedido o cliente
     * 
     * @param Builder $query
     * @param string|null $searchTerm
     * @return Builder
     */
    public function applySearchFilter(Builder $query, ?string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function($q) use ($searchTerm) {
            $q->where('numero_pedido', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('cliente', 'LIKE', '%' . $searchTerm . '%');
        });
    }
}
