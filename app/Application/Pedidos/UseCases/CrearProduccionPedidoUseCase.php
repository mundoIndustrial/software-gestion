<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Domain\PedidoProduccion\Agregado\PedidoProduccionAggregate;
use Exception;

/**
 * CrearProduccionPedidoUseCase
 * 
 * Use Case para crear un nuevo pedido de producción
 * 
 * Responsabilidades:
 * - Validar datos de entrada
 * - Crear agregado de dominio
 * - Persistir en repositorio
 * - Retornar resultado
 * 
 * Patrón: Command Handler
 */
class CrearProduccionPedidoUseCase
{
    /**
     * Constructor puede inyectar repositorios, servicios, etc.
     * Por ahora está vacío pero vamos a agregar dependencias
     */
    public function __construct()
    {
    }

    /**
     * Ejecutar el use case
     */
    public function ejecutar(CrearProduccionPedidoDTO $dto): PedidoProduccionAggregate
    {
        try {
            // 1. Crear agregado con validaciones de dominio
            $pedido = PedidoProduccionAggregate::crear([
                'numero_pedido' => $dto->numeroPedido,
                'cliente' => $dto->cliente,
            ]);

            // 2. Agregar prendas si vienen en la solicitud
            if (!empty($dto->prendas)) {
                foreach ($dto->prendas as $prenda) {
                    $pedido->agregarPrenda($prenda);
                }
            }

            // 3. TODO: Persistir en repositorio
            // $this->pedidoRepository->guardar($pedido);

            // 4. TODO: Publicar domain events si es necesario
            // $this->eventPublisher->publicar($pedido->eventos());

            return $pedido;

        } catch (Exception $e) {
            throw new Exception("Error al crear pedido de producción: " . $e->getMessage());
        }
    }
}
