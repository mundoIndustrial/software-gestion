<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Domain\Pedidos\Agregado\PedidosAggregate;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Events\Dispatcher;
use Exception;

/**
 * CrearProduccionPedidoUseCase
 * 
 * COMPLETADO: Fue refactorizado en FASE 1
 * 
 * Use Case para crear un nuevo pedido de producciÃ³n
 * 
 * Responsabilidades:
 * - Validar datos de entrada (delegado a agregado)
 * - Crear agregado de dominio
 * - Persistir en repositorio âœ… AHORA FUNCIONA
 * - Publicar domain events âœ… AHORA FUNCIONA
 * - Retornar resultado
 * 
 * PatrÃ³n: Command Handler
 */
class CrearProduccionPedidoUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository,
        private Dispatcher $eventDispatcher
    ) {}

    /**
     * Ejecutar el use case
     */
    public function ejecutar(CrearProduccionPedidoDTO $dto): PedidosAggregate
    {
        try {
            // 1. Crear agregado con validaciones de dominio
            $pedido = PedidosAggregate::crear([
                'numero_pedido' => $dto->numeroPedido,
                'cliente' => $dto->cliente,
            ]);

            // 2. Agregar prendas si vienen en la solicitud
            if (!empty($dto->prendas)) {
                foreach ($dto->prendas as $prenda) {
                    $pedido->agregarPrenda($prenda);
                }
            }

            // 3. âœ… PERSISTIR EN REPOSITORIO (ANTES ERA TODO)
            $this->pedidoRepository->guardar($pedido);

            // 4. âœ… PUBLICAR DOMAIN EVENTS (ANTES ERA TODO)
            foreach ($pedido->eventos() as $evento) {
                $this->eventDispatcher->dispatch($evento);
            }

            return $pedido;

        } catch (Exception $e) {
            throw new Exception("Error al crear pedido de producciÃ³n: " . $e->getMessage());
        }
    }
}


