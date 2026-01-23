<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Domain\PedidoProduccion\Agregado\PedidoProduccionAggregate;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Events\Dispatcher;
use Exception;

/**
 * CrearProduccionPedidoUseCase
 * 
 * COMPLETADO: Fue refactorizado en FASE 1
 * 
 * Use Case para crear un nuevo pedido de producción
 * 
 * Responsabilidades:
 * - Validar datos de entrada (delegado a agregado)
 * - Crear agregado de dominio
 * - Persistir en repositorio ✅ AHORA FUNCIONA
 * - Publicar domain events ✅ AHORA FUNCIONA
 * - Retornar resultado
 * 
 * Patrón: Command Handler
 */
class CrearProduccionPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
        private Dispatcher $eventDispatcher
    ) {}

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

            // 3. ✅ PERSISTIR EN REPOSITORIO (ANTES ERA TODO)
            $this->pedidoRepository->guardar($pedido);

            // 4. ✅ PUBLICAR DOMAIN EVENTS (ANTES ERA TODO)
            foreach ($pedido->eventos() as $evento) {
                $this->eventDispatcher->dispatch($evento);
            }

            return $pedido;

        } catch (Exception $e) {
            throw new Exception("Error al crear pedido de producción: " . $e->getMessage());
        }
    }
}
