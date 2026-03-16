<?php

namespace App\Application\Pedidos\Contracts;

/**
 * PedidoEnricherService
 * 
 * Contrato para enriquecer datos de pedidos con información adicional
 */
interface PedidoEnricherService
{
    /**
     * Enriquecer prendas con datos adicionales (ancho, metraje, consecutivos)
     */
    public function enriquecerPrendas(int $pedidoId, array $prendas): array;

    /**
     * Enriquecer con EPPs transformados
     */
    public function enriquecerEpps(int $pedidoId, array $epps): array;

    /**
     * Enriquecer con datos de entrega
     */
    public function enriquecerEntregas(array $prendas): array;

    /**
     * Enriquecer con recibos parciales
     */
    public function enriquecerRecibosParciales(int $pedidoId, array $prendas): array;
}
