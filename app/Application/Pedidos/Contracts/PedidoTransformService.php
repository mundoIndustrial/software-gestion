<?php

namespace App\Application\Pedidos\Contracts;

/**
 * PedidoTransformService
 * 
 * Contrato para transformar datos de pedidos a formatos de salida
 */
interface PedidoTransformService
{
    /**
     * Transformar pedido a DTO detallado
     */
    public function transformarDetalleCompleto(array $datos): array;

    /**
     * Transformar pedido para edición
     */
    public function transformarDatosEdicion(array $datos): array;

    /**
     * Transformar procesos de prenda
     */
    public function transformarProcesos(array $procesos): array;

    /**
     * Transformar tallas con colores
     */
    public function transformarTalasConColores(array $tallas): array;
}
