<?php

namespace App\Domain\Pedidos\CommandHandlers;

use App\Domain\Shared\CQRS\Command;
use App\Domain\Shared\CQRS\CommandHandler;
use App\Domain\Pedidos\Commands\AgregarPrendaAlPedidoCommand;
use App\Domain\Pedidos\Services\PrendaCreationService;
use App\Domain\Pedidos\Validators\PrendaValidator;
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
            Log::info(' [AgregarPrendaAlPedidoHandler] Agregando prenda al pedido', [
                'pedido_id' => $command->getPedidoId(),
                'tipo' => $command->getTipo(),
            ]);

            // Validar que el pedido exista
            $pedido = $this->pedidoModel->find($command->getPedidoId());

            if (!$pedido) {
                throw new \Exception("Pedido no encontrado: {$command->getPedidoId()}");
            }

            // Validar que el pedido estÃ© activo o en ediciÃ³n (permite agregar prendas a Pendiente, Activo, etc)
            $estadosPermitidos = ['activo', 'pendiente', 'no iniciado'];
            if (!in_array(strtolower($pedido->estado), $estadosPermitidos)) {
                throw new \Exception("No se pueden agregar prendas a un pedido {$pedido->estado}");
            }

            // Validar datos de la prenda
            $this->validator->validateAgregarAlPedido(
                $command->getPrendaData(),
                $command->getTipo()
            );

            Log::info(' [AgregarPrendaAlPedidoHandler] Validaciones pasadas', []);

            // Enriquecer datos de la prenda con el ID del pedido
            $prendaData = array_merge($command->getPrendaData(), [
                'pedido_id' => $command->getPedidoId(),
            ]);

            // Crear la prenda usando CreacionPrendaSinCtaStrategy
            // Los procesos (reflectivo, bordado, etc.) se manejan dentro de la estrategia
            $prenda = $this->prendaService->crearPrendaSinCotizacion(
                $prendaData,
                $command->getPedidoId()
            );

            // Actualizar cantidad total del pedido con la suma de todas las tallas
            $cantidadTotal = $this->calcularCantidadTotal($prendaData['cantidad_talla'] ?? []);
            $pedido->increment('cantidad_total', $cantidadTotal);

            Log::info(' [AgregarPrendaAlPedidoHandler] Prenda agregada', [
                'pedido_id' => $pedido->id,
                'prenda_id' => $prenda->id,
                'tipo' => $command->getTipo(),
            ]);

            // Invalidar cachÃ©s
            cache()->forget("pedido_{$command->getPedidoId()}_completo");
            cache()->forget("pedido_{$command->getPedidoId()}_prendas");

            return $prenda;

        } catch (\Exception $e) {
            Log::error(' [AgregarPrendaAlPedidoHandler] Error agregando prenda', [
                'pedido_id' => $command->getPedidoId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calcular cantidad total sumando todas las tallas de todos los géneros
     * 
     * @param array $cantidadTalla Estructura {DAMA: {S: 20, M: 10}, CABALLERO: {}, UNISEX: {}}
     * @return int Suma total de todas las tallas
     */
    private function calcularCantidadTotal(array $cantidadTalla): int
    {
        $total = 0;
        
        foreach ($cantidadTalla as $genero => $tallas) {
            if (is_array($tallas)) {
                foreach ($tallas as $talla => $cantidad) {
                    $total += (int) $cantidad;
                }
            }
        }
        
        return $total;
    }
}

