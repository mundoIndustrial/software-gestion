<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * RegistroBodegaSearchService
 * 
 * Servicio para búsqueda en tablas de bodega
 */
class RegistroBodegaSearchService
{
    /**
     * Aplicar filtro de búsqueda por pedido o cliente
     */
    public function applySearchFilter(Builder $query, ?string $searchTerm): Builder
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function($q) use ($searchTerm) {
            $q->where('pedido', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('cliente', 'LIKE', '%' . $searchTerm . '%');
        });
    }
}
