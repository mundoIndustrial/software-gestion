<?php

namespace App\Domain\Pedidos\Services;

use App\Domain\Pedidos\ValueObjects\TallaPrenda;
use App\Domain\Pedidos\DTOs\TallaPrendaDTO;

/**
 * Servicio de dominio para procesamiento de tallas de prendas
 * 
 * Centraliza toda la lógica compleja de manejo de tallas que antes
 * estaba dispersa en el frontend.
 */
class TallaProcessorService
{
    /**
     * Procesar tallas de una prenda completa
     */
    public function procesarTallasPrenda(array $prendaData): TallaPrendaDTO
    {
        $tallaPrenda = new TallaPrenda($prendaData);
        
        // Detectar tipo de talla automáticamente
        $tipoTalla = $this->detectarTipoTalla($tallaPrenda->getTallasPorGenero());
        
        // Validar cantidades si hay total especificado
        $totalEsperado = $prendaData['cantidad'] ?? null;
        $validacion = null;
        
        if ($totalEsperado) {
            $validacion = $this->validarCantidades($tallaPrenda, $totalEsperado);
        }
        
        return new TallaPrendaDTO([
            'tallas_por_genero' => $tallaPrenda->getTallasPorGenero(),
            'sobremedida' => $tallaPrenda->getSobremedida(),
            'genero_principal' => $tallaPrenda->getGeneroPrincipal(),
            'tipo_talla' => $tipoTalla,
            'total_por_genero' => $this->calcularTotalesPorGenero($tallaPrenda->getTallasPorGenero()),
            'total_general' => $this->calcularTotalGeneral($tallaPrenda->getTallasPorGenero()),
            'validacion' => $validacion,
            'tallas_desde_cotizacion' => $this->extraerTallasDesdeCotizacion($tallaPrenda->getTallasPorGenero()),
            'tiene_sobremedida' => !empty($tallaPrenda->getSobremedida()),
            'generos_activos' => $this->obtenerGenerosActivos($tallaPrenda->getTallasPorGenero())
        ]);
    }
    
    /**
     * Detectar tipo de talla basado en el contenido
     */
    private function detectarTipoTalla(array $tallasPorGenero): string
    {
        $todasLasTallas = [];
        
        foreach ($tallasPorGenero as $genero => $tallas) {
            if ($genero !== 'SOBREMEDIDA' && is_array($tallas)) {
                $todasLasTallas = array_merge($todasLasTallas, array_keys($tallas));
            }
        }
        
        return \App\Domain\Pedidos\Enums\TipoTalla::detectarTipo($todasLasTallas)->value;
    }
    
    /**
     * Validar cantidades totales
     */
    private function validarCantidades(TallaPrenda $tallaPrenda, int $totalEsperado): array
    {
        $totalCalculado = $this->calcularTotalGeneral($tallaPrenda->getTallasPorGenero());
        $isValid = $totalCalculado === $totalEsperado;
        
        return [
            'valid' => $isValid,
            'total_esperado' => $totalEsperado,
            'total_calculado' => $totalCalculado,
            'diferencia' => $totalCalculado - $totalEsperado,
            'errores' => $isValid ? [] : [
                "La suma de tallas ({$totalCalculado}) no coincide con la cantidad total ({$totalEsperado})"
            ]
        ];
    }
    
    /**
     * Calcular totales por género
     */
    private function calcularTotalesPorGenero(array $tallasPorGenero): array
    {
        $totales = [];
        
        foreach ($tallasPorGenero as $genero => $tallas) {
            if ($genero !== 'SOBREMEDIDA' && is_array($tallas)) {
                $totales[$genero] = array_sum($tallas);
            } else {
                $totales[$genero] = 0;
            }
        }
        
        return $totales;
    }
    
    /**
     * Calcular total general
     */
    private function calcularTotalGeneral(array $tallasPorGenero): int
    {
        $total = 0;
        
        foreach ($tallasPorGenero as $genero => $tallas) {
            if ($genero !== 'SOBREMEDIDA' && is_array($tallas)) {
                $total += array_sum($tallas);
            }
        }
        
        return $total;
    }
    
    /**
     * Extraer tallas desde cotización para pre-selección
     */
    private function extraerTallasDesdeCotizacion(array $tallasPorGenero): array
    {
        $tallasDesdeCotizacion = [];
        
        foreach ($tallasPorGenero as $genero => $tallas) {
            if ($genero !== 'SOBREMEDIDA' && !empty($tallas)) {
                $tallasDesdeCotizacion[$genero] = array_keys($tallas);
            }
        }
        
        return $tallasDesdeCotizacion;
    }
    
    /**
     * Obtener géneros activos (que tienen tallas)
     */
    private function obtenerGenerosActivos(array $tallasPorGenero): array
    {
        $activos = [];
        
        foreach ($tallasPorGenero as $genero => $tallas) {
            if ($genero !== 'SOBREMEDIDA' && !empty($tallas)) {
                $activos[] = $genero;
            }
        }
        
        return $activos;
    }
    
    /**
     * Procesar tallas para guardar en base de datos
     */
    public function procesarParaGuardar(array $tallasData): array
    {
        $tallaPrenda = new TallaPrenda($tallasData);
        
        return [
            'cantidad_talla' => $this->formatearParaCantidadTalla($tallaPrenda->getTallasPorGenero()),
            'sobremedida' => $tallaPrenda->getSobremedida(),
            'tipo_talla' => $this->detectarTipoTalla($tallaPrenda->getTallasPorGenero()),
            'genero_principal' => $tallaPrenda->getGeneroPrincipal()
        ];
    }
    
    /**
     * Formatear tallas para el campo cantidad_talla de la BD
     */
    private function formatearParaCantidadTalla(array $tallasPorGenero): array
    {
        $formateado = [];
        
        foreach ($tallasPorGenero as $genero => $tallas) {
            if (!empty($tallas) && is_array($tallas)) {
                // Filtrar solo valores mayores a cero
                $formateado[$genero] = array_filter($tallas, fn($cantidad) => $cantidad > 0);
            }
        }
        
        return $formateado;
    }
    
    /**
     * Generar resumen de tallas para logging/debug
     */
    public function generarResumen(array $prendaData): array
    {
        $tallaPrenda = new TallaPrenda($prendaData);
        
        return [
            'genero_principal' => $tallaPrenda->getGeneroPrincipal(),
            'tipo_talla' => $this->detectarTipoTalla($tallaPrenda->getTallasPorGenero()),
            'generos_con_tallas' => $this->obtenerGenerosActivos($tallaPrenda->getTallasPorGenero()),
            'total_general' => $this->calcularTotalGeneral($tallaPrenda->getTallasPorGenero()),
            'tiene_sobremedida' => !empty($tallaPrenda->getSobremedida()),
            'cantidad_tallas_por_genero' => array_map(
                fn($tallas) => count($tallas),
                $tallaPrenda->getTallasPorGenero()
            )
        ];
    }
}
