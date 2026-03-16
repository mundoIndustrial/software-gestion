<?php

namespace App\Domain\RegistrosOrdenes\Contracts;

/**
 * SeguimientoOrdenService
 * 
 * Contrato para lógica de seguimiento y procesos de órdenes
 * Responsabilidad: Calcular y estructurar datos de seguimiento por prenda/proceso
 */
interface SeguimientoOrdenService
{
    /**
     * Obtener seguimiento completo por prenda
     */
    public function obtenerSeguimientoPorPrenda($registroId): array;
    
    /**
     * Obtener consecutivo de costura
     */
    public function obtenerConsecutivoCostura($registroId, $prendaId = null): ?array;
    
    /**
     * Obtener procesos con fechas calculadas
     */
    public function calcularProcesosConFechas($numeroPedido): array;
    
    /**
     * Obtener último proceso de un área
     */
    public function obtenerUltimoProceso($numeroPedido): ?array;
}
