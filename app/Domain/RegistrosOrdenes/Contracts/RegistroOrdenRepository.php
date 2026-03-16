<?php

namespace App\Domain\RegistrosOrdenes\Contracts;

use App\Models\PedidoProduccion;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * RegistroOrdenRepository
 * 
 * Contrato para acceso a datos de órdenes/registros
 * Responsabilidad: Abstraer queries complejas de la BD
 */
interface RegistroOrdenRepository
{
    /**
     * Construir query base con relaciones
     */
    public function buildBaseQuery();
    
    /**
     * Obtener registro por ID o número
     */
    public function obtenerPorId($id): ?PedidoProduccion;
    public function obtenerPorNumero($numero): ?PedidoProduccion;
    
    /**
     * Obtener registros paginados con búsqueda y filtros
     */
    public function listarConFiltros(array $filters, $search = null, $page = 1, $perPage = 25): LengthAwarePaginator;
    
    /**
     * Obtener valores únicos para una columna
     */
    public function obtenerValoresUnicos($column): array;
    
    /**
     * Obtener prendas del registro
     */
    public function obtenerPrendas($registroId, $conRelaciones = true);
    
    /**
     * Obtener procesos de una prenda
     */
    public function obtenerProcesosPrenda($prendaId);
    
    /**
     * Obtener datos de ancho y metraje
     */
    public function obtenerAnchoMetraje($registroId);
}
