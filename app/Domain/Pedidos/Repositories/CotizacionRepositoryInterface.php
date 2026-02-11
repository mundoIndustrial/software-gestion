<?php

namespace App\Domain\Pedidos\Repositories;

/**
 * Interface: CotizacionRepositoryInterface
 * 
 * Define el contrato para el repositorio de cotizaciones
 */
interface CotizacionRepositoryInterface
{
    /**
     * Encuentra una cotización por su ID
     */
    public function findById(int $id): ?object;
    
    /**
     * Encuentra una cotización con sus relaciones
     */
    public function findByIdWithRelations(int $id, array $relations = []): ?object;
    
    /**
     * Obtiene telas de una cotización
     */
    public function obtenerTelas(int $cotizacionId, int $prendaId): array;
    
    /**
     * Obtiene variaciones de una cotización
     */
    public function obtenerVariaciones(int $cotizacionId, int $prendaId): array;
    
    /**
     * Obtiene ubicaciones de una cotización
     */
    public function obtenerUbicaciones(int $cotizacionId, int $prendaId): array;
    
    /**
     * Obtiene descripción de una cotización
     */
    public function obtenerDescripcion(int $cotizacionId, int $prendaId): string;
    
    /**
     * Verifica si una cotización es de tipo específico
     */
    public function esTipo(int $cotizacionId, string $tipo): bool;
    
    /**
     * Obtiene el tipo de cotización
     */
    public function obtenerTipo(int $cotizacionId): ?object;
}
