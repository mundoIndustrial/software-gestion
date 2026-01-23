<?php

namespace App\Application\Pedidos\UseCases\Base;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * AbstractEstadoTransicionUseCase
 * 
 * Clase base reutilizable para todos los Use Cases que realizan transiciones de estado
 * 
 * PATRÓN: Template Method + Strategy
 * 
 * Ventajas:
 * - Elimina duplicación en validación y obtención (4 lineas)
 * - Centraliza respuesta estándar (10 lineas)
 * - Cada subclase solo implementa aplicarTransicion() (1 línea)
 * - Reduces LOC de 28 a 8 por Use Case (71% menos código)
 * 
 * Uso:
 *   class ConfirmarPedidoUseCase extends AbstractEstadoTransicionUseCase {
 *       protected function aplicarTransicion($pedido): void { $pedido->confirmar(); }
 *       protected function obtenerMensaje(): string { return 'Confirmado'; }
 *   }
 */
abstract class AbstractEstadoTransicionUseCase
{
    protected PedidoRepository $pedidoRepository;

    public function __construct(PedidoRepository $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Template Method - Define el flujo común para todas las transiciones
     * 
     * Flujo:
     * 1. Obtener pedido
     * 2. Validar existencia
     * 3. Aplicar transición (strategy específica)
     * 4. Persistir
     * 5. Retornar respuesta
     */
    final public function ejecutar(int $pedidoId): PedidoResponseDTO
    {
        // LINEA COMÚN 1: Obtener pedido
        $pedido = $this->pedidoRepository->porId($pedidoId);
        
        // LINEA COMÚN 2: Validar existencia
        if (!$pedido) {
            throw new \DomainException("Pedido $pedidoId no encontrado");
        }

        // LINEA VARIABLE 3: Aplicar transición (strategy específica por subclase)
        $this->aplicarTransicion($pedido);
        
        // LINEA COMÚN 4: Persistir
        $this->pedidoRepository->guardar($pedido);

        // LINEA COMÚN 5: Retornar respuesta
        return $this->crearRespuesta($pedido);
    }

    /**
     * Método abstracto - Cada subclase implementa su transición de estado
     * 
     * Ejemplos:
     * - $pedido->confirmar()
     * - $pedido->cancelar()
     * - $pedido->completar()
     * - $pedido->anular()
     * - $pedido->iniciarProduccion()
     */
    abstract protected function aplicarTransicion($pedido): void;

    /**
     * Método abstracto - Cada subclase proporciona su mensaje personalizado
     * 
     * Ejemplos:
     * - 'Pedido confirmado exitosamente'
     * - 'Pedido cancelado exitosamente'
     */
    abstract protected function obtenerMensaje(): string;

    /**
     * Método reutilizable - Construye respuesta estándar
     * 
     * Encapsula la construcción del DTO de respuesta
     * Consistente en todas las transiciones
     */
    protected function crearRespuesta($pedido): PedidoResponseDTO
    {
        return new PedidoResponseDTO(
            id: $pedido->id(),
            numero: (string)$pedido->numero(),
            clienteId: $pedido->clienteId(),
            estado: $pedido->estado()->valor(),
            descripcion: $pedido->descripcion(),
            totalPrendas: $pedido->totalPrendas(),
            totalArticulos: $pedido->totalArticulos(),
            mensaje: $this->obtenerMensaje()
        );
    }
}
