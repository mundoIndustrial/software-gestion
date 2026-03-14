<?php

namespace App\Application\Services\Pedidos\Contracts;

use App\Models\User;

/**
 * CargarDatosCompartidosServiceInterface
 * 
 * Contrato para servicios que cargan datos compartidos entre vistas
 * Permite implementar diferentes estrategias de carga de datos
 */
interface CargarDatosCompartidosServiceInterface
{
    /**
     * Cargar datos necesarios para vistas de creación de pedido
     * 
     * @param User $user
     * @return array [
     *   'tallas' => Collection,
     *   'formas_pago' => array,
     *   'tecnicas' => array,
     *   'pedidos' => Collection,
     *   'clientes' => Collection,
     *   'tiempos' => array,
     * ]
     */
    public function ejecutar(User $user): array;
}
