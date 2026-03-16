<?php

namespace App\Application\RegistrosOrdenes\Contracts;

/**
 * TransformacionOrdenService
 * 
 * Contrato para transformación de datos de órdenes para respuestas API
 * Responsabilidad: Convertir modelos a arrays formateados
 */
interface TransformacionOrdenService
{
    /**
     * Transformar orden para listado
     */
    public function transformarParaListado($orden, array $areasMap = [], array $encargadosMap = []): array;
    
    /**
     * Transformar orden para detalle
     */
    public function transformarParaDetalle($orden): array;
    
    /**
     * Transformar prendas
     */
    public function transformarPrendas($prendas): array;
}
