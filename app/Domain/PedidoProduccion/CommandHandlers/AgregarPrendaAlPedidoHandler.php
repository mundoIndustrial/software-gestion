<?php

namespace App\Domain\PedidoProduccion\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\PedidoProduccion\Commands\AgregarPrendaAlPedidoCommand;
use App\Domain\PedidoProduccion\Services\PrendaCreationService;
use App\Domain\PedidoProduccion\Validators\PrendaValidator;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * AgregarPrendaAlPedidoHandler
 * 
 * Maneja AgregarPrendaAlPedidoCommand
 * Agrega una nueva prenda a un pedido existente
 * Delega a PrendaCreationService
 */
class AgregarPrendaAlPedidoHandler implements CommandHandler
{
    public function __construct(
        private PedidoProduccion $pedidoModel,
        private PrendaCreationService $prendaService,
        private PrendaValidator $validator,
    ) {}

    public function handle(Command $command): mixed
    {
        if (!$command instanceof AgregarPrendaAlPedidoCommand) {
            throw new \InvalidArgumentException('Command debe ser AgregarPrendaAlPedidoCommand');
        }

        try {
            Log::info('👕 [AgregarPrendaAlPedidoHandler] Agregando prenda al pedido', [
                'pedido_id' => $command->getPedidoId(),
                'tipo' => $command->getTipo(),
            ]);

            // Validar que el pedido exista
            $pedido = $this->pedidoModel->find($command->getPedidoId());

            if (!$pedido) {
                throw new \Exception("Pedido no encontrado: {$command->getPedidoId()}");
            }

            // Validar que el pedido esté activo
            if (strtolower($pedido->estado) !== 'activo') {
                throw new \Exception("No se pueden agregar prendas a un pedido {$pedido->estado}");
            }

            // Validar datos de la prenda
            $this->validator->validateAgregarAlPedido(
                $command->getPrendaData(),
                $command->getTipo()
            );

            Log::info('✅ [AgregarPrendaAlPedidoHandler] Validaciones pasadas', []);

            // Enriquecer datos de la prenda con el ID del pedido
            $prendaData = array_merge($command->getPrendaData(), [
                'pedido_id' => $command->getPedidoId(),
            ]);

            // Crear la prenda usando el servicio apropiado
            $prenda = match ($command->getTipo()) {
                'sin_cotizacion' => $this->prendaService->crearPrendaSinCotizacion(
                    $prendaData,
                    $pedido->numero_pedido
                ),
                'reflectivo' => $this->prendaService->crearPrendaReflectivo(
                    $prendaData,
                    $pedido->numero_pedido
                ),
                default => throw new \Exception("Tipo de prenda no soportado: {$command->getTipo()}"),
            };

            // Actualizar cantidad total del pedido
            $pedido->increment('cantidad_total', $prendaData['cantidad_inicial'] ?? 1);

            Log::info('✅ [AgregarPrendaAlPedidoHandler] Prenda agregada', [
                'pedido_id' => $pedido->id,
                'prenda_id' => $prenda->id,
                'tipo' => $command->getTipo(),
            ]);

            // Invalidar cachés
            cache()->forget("pedido_{$command->getPedidoId()}_completo");
            cache()->forget("pedido_{$command->getPedidoId()}_prendas");

            return $prenda;

        } catch (\Exception $e) {
            Log::error('❌ [AgregarPrendaAlPedidoHandler] Error agregando prenda', [
                'pedido_id' => $command->getPedidoId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
