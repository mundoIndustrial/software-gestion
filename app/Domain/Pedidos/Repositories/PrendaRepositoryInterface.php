<?php

namespace App\Domain\Pedidos\Repositories;

/**
 * Interface: PrendaRepositoryInterface
 * 
 * Define el contrato para el repositorio de prendas
 * Seguimos el principio de inversión de dependencias
 */
interface PrendaRepositoryInterface
{
    /**
     * Encuentra una prenda por su ID
     */
    public function findById(int $id): ?object;
    
    /**
     * Encuentra una prenda con todas sus relaciones
     */
    public function findByIdWithRelations(int $id, array $relations = []): ?object;
    
    /**
     * Obtiene tipos de manga disponibles
     */
    public function obtenerTiposManga(): array;
    
    /**
     * Guarda una prenda
     */
    public function save(object $prenda): object;
    
    /**
     * Actualiza una prenda
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Elimina una prenda
     */
    public function delete(int $id): bool;
    
    /**
     * Busca prendas por criterios
     */
    public function findBy(array $criteria): array;
    
    /**
     * Obtiene prendas de una cotización
     */
    public function obtenerPorCotizacion(int $cotizacionId): array;
    
    /**
     * Obtiene variantes de una prenda
     */
    public function obtenerVariantes(int $prendaId): array;
    
    /**
     * Obtiene telas de una prenda
     */
    public function obtenerTelas(int $prendaId): array;
    
    /**
     * Obtiene imágenes de una prenda
     */
    public function obtenerImagenes(int $prendaId): array;
    
    /**
     * Obtiene procesos de una prenda
     */
    public function obtenerProcesos(int $prendaId): array;
}
