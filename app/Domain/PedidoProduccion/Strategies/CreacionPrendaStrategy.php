<?php

namespace App\Domain\PedidoProduccion\Strategies;

use App\Models\PrendaPedido;
use Illuminate\Http\Request;

/**
 * Strategy Pattern para Creación de Prendas
 * 
 * Define el contrato que deben cumplir todas las estrategias de creación de prendas
 * Permite diferentes algoritmos sin cambiar el código cliente
 * 
 * Implementaciones:
 * - CreacionPrendaSinCtaStrategy: Para prendas sin cotización (con tallas/géneros)
 * - CreacionPrendaReflectivoStrategy: Para reflectivos sin cotización
 */
interface CreacionPrendaStrategy
{
    /**
     * Procesar y crear prenda según la estrategia específica
     * 
     * @param array $prendaData Datos de la prenda del request
     * @param int $pedidoProduccionId ID del pedido a vincular
     * @param array $servicios Array con servicios inyectados: [
     *     'descripcionService' => DescripcionService,
     *     'imagenService' => ImagenService,
     * ]
     * @return PrendaPedido Prenda creada y persistida
     * @throws \Exception Si hay error en procesamiento
     */
    public function procesar(
        array $prendaData,
        int $pedidoProduccionId,
        array $servicios
    ): PrendaPedido;

    /**
     * Validar datos antes de procesar
     * Cada estrategia puede tener validaciones diferentes
     * 
     * @param array $prendaData Datos a validar
     * @return bool
     * @throws \InvalidArgumentException Si validación falla
     */
    public function validar(array $prendaData): bool;

    /**
     * Obtener nombre descriptivo de la estrategia
     * Útil para logging y debugging
     * 
     * @return string
     */
    public function getNombre(): string;
}
