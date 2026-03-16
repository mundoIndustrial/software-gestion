<?php

namespace App\Application\RegistrosOrdenes\Contracts;

/**
 * FiltrosOrdenService
 * 
 * Contrato para aplicación de filtros dinámicos a órdenes
 * Responsabilidad: Extraer y aplicar filtros desde request
 */
interface FiltrosOrdenService
{
    /**
     * Extraer filtros desde request
     */
    public function extraerDelRequest($request): array;
    
    /**
     * Aplicar filtros a query
     */
    public function aplicar(&$query, array $filters);
    
    /**
     * Aplicar filtro de total_dias
     */
    public function aplicarFiltroTotalDias(&$ordenes, array $diasFiltro, array $festivos): \Illuminate\Support\Collection;
}
