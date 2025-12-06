<?php

namespace App\Services\Pedidos;

use App\Models\Cotizacion;
use App\DTOs\CotizacionSearchDTO;
use Illuminate\Support\Collection;

/**
 * Service para búsqueda de cotizaciones
 * Principio SRP: solo responsable de lógica de búsqueda
 * Principio DIP: depende de abstracciones, no de implementaciones concretas
 */
class CotizacionSearchService
{
    /**
     * Obtiene todas las cotizaciones como DTOs
     */
    public function obtenerTodas(): Collection
    {
        return Cotizacion::all()
            ->map(fn($cot) => CotizacionSearchDTO::fromModel($cot));
    }

    /**
     * Obtiene cotizaciones filtradas por asesor
     * ISP: Interface Segregation - método simple y específico
     */
    public function obtenerPorAsesor(string $nombreAsesor): Collection
    {
        $todas = $this->obtenerTodas();
        return CotizacionSearchDTO::filterByAsesor($todas, $nombreAsesor);
    }

    /**
     * Busca cotización por ID
     */
    public function obtenerPorId(int $id): ?Cotizacion
    {
        return Cotizacion::find($id);
    }

    /**
     * Filtra por término de búsqueda
     * OCP: Open for extension - fácil agregar más criterios
     */
    public function filtrarPorTermino(Collection $cotizaciones, string $termino): Collection
    {
        if (empty($termino)) {
            return $cotizaciones;
        }

        $terminoLower = strtolower($termino);

        return $cotizaciones->filter(fn($cot) => 
            str_contains(strtolower($cot->numero), $terminoLower) ||
            str_contains(strtolower($cot->cliente), $terminoLower)
        );
    }
}
