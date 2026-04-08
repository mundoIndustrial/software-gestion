<?php

namespace App\Domain\Prendas\Repositories;

use App\Models\TipoPrenda;
use Illuminate\Database\Eloquent\Collection;

/**
 * TipoPrendaRepository
 * 
 * RESPONSABILIDAD ÚNICA:
 * - Encapsular todas las queries relacionadas con tipos de prenda
 * - Eliminar lógica de BD del Controller
 * - Punto único de cambio para queries de prendas
 * 
 * ANTES (ANTIPATRÓN - Controller):
 * ```php
 * $busquedaUpper = strtoupper($busqueda);
 * $query->where(function($q) use ($busquedaUpper) {
 *     $q->whereRaw('UPPER(nombre) LIKE ?', ["%{$busquedaUpper}%"])
 *       ->orWhereRaw('UPPER(codigo) LIKE ?', ["%{$busquedaUpper}%"]);
 * });
 * ```
 * 
 * AHORA (CORRECTO - Repository):
 * ```php
 * $prendas = $this->tipoPrendaRepository->buscarActivas($busqueda, 50);
 * ```
 */
class TipoPrendaRepository
{
    /**
     * Obtener todas las prendas activas (sin búsqueda)
     * 
     * @param int $limit
     * @return Collection
     */
    public function obtenerActivas(int $limit = 50): Collection
    {
        return TipoPrenda::where('activo', true)
            ->orderBy('nombre', 'asc')
            ->limit($limit)
            ->get(['id', 'nombre', 'codigo', 'descripcion']);
    }

    /**
     * Buscar prendas activas por nombre o código
     * 
     * Búsqueda case-insensitive en:
     * - nombre
     * - código
     * 
     * @param string $busqueda Término de búsqueda
     * @param int $limit Máximo de resultados (por defecto 50)
     * @return Collection [id, nombre, codigo, descripcion]
     */
    public function buscarActivas(string $busqueda, int $limit = 50): Collection
    {
        // Limpiar y normalizar búsqueda
        $busqueda = trim($busqueda);
        
        // Si no hay búsqueda, retornar todas las activas
        if (empty($busqueda)) {
            return $this->obtenerActivas($limit);
        }

        // Convertir a mayúsculas para búsqueda case-insensitive
        $busquedaUpper = strtoupper($busqueda);

        return TipoPrenda::where('activo', true)
            ->where(function($query) use ($busquedaUpper) {
                $query->whereRaw('UPPER(nombre) LIKE ?', ["%{$busquedaUpper}%"])
                      ->orWhereRaw('UPPER(codigo) LIKE ?', ["%{$busquedaUpper}%"]);
            })
            ->orderBy('nombre', 'asc')
            ->limit($limit)
            ->get(['id', 'nombre', 'codigo', 'descripcion']);
    }

    /**
     * Obtener prenda por ID
     * 
     * @param int $id
     * @return TipoPrenda|null
     */
    public function obtenerPorId(int $id): ?TipoPrenda
    {
        return TipoPrenda::find($id);
    }

    /**
     * Obtener prenda por nombre (búsqueda exacta)
     * 
     * @param string $nombre
     * @return TipoPrenda|null
     */
    public function obtenerPorNombre(string $nombre): ?TipoPrenda
    {
        return TipoPrenda::where('nombre', $nombre)
            ->where('activo', true)
            ->first();
    }

    /**
     * Contar prendas activas
     * 
     * @return int
     */
    public function contarActivas(): int
    {
        return TipoPrenda::where('activo', true)->count();
    }
}
