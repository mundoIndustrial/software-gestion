<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * Servicio para búsqueda de RegistroOrden
 * 
 * Encapsula lógica de búsqueda de órdenes por término de búsqueda
 * Responsabilidades:
 * - Búsqueda por numero_pedido o cliente
 * - Búsqueda con patrón LIKE
 * - Aplicación condicional (solo si hay término)
 */
class RegistroOrdenSearchExtendedService
{
    /**
     * Aplicar filtro de búsqueda a query
     * 
     * Busca en:
     * - numero_pedido: Coincidencia exacta con LIKE
     * - cliente: Coincidencia parcial con LIKE
     * 
     * @param Builder $query Query a filtrar
     * @param string|null $searchTerm Término de búsqueda
     * @return Builder Query filtrada
     */
    public function applySearchFilter(Builder $query, ?string $searchTerm): Builder
    {
        if ($searchTerm && !empty(trim($searchTerm))) {
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('numero_pedido', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('cliente', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $query;
    }
}
