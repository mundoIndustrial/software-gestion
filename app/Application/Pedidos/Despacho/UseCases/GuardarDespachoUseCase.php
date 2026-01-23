<?php

namespace App\Application\Pedidos\Despacho\UseCases;

use App\Models\PedidoProduccion;
use App\Domain\Pedidos\Despacho\Services\DespachoValidadorService;
use App\Application\Pedidos\Despacho\DTOs\ControlEntregasDTO;
use App\Application\Pedidos\Despacho\DTOs\DespachoParcialesDTO;
use Illuminate\Support\Facades\DB;

/**
 * GuardarDespachoUseCase
 * 
 * Use Case (Application Service) para guardar/procesar despachos
 * 
 * Coordina:
 * - Validación de despachos (Domain Service)
 * - Transacciones DB
 * - Auditoría/Logs
 */
class GuardarDespachoUseCase
{
    public function __construct(
        private DespachoValidadorService $validador,
    ) {}

    /**
     * Ejecutar: Guardar control de entregas
     * 
     * @param ControlEntregasDTO $control
     * @return array
     * @throws \Exception
     */
    public function ejecutar(ControlEntregasDTO $control): array
    {
        try {
            // Validar que el pedido existe
            $pedido = PedidoProduccion::find($control->pedidoId);
            if (!$pedido) {
                throw new \Exception("Pedido con ID {$control->pedidoId} no encontrado");
            }

            DB::beginTransaction();

            // Validar todos los despachos
            $despachos = array_map(
                fn($d) => new DespachoParcialesDTO(...$d),
                $control->despachos
            );
            $this->validador->validarMultiplesDespachos($despachos);

            // Procesar cada despacho
            foreach ($despachos as $despacho) {
                $this->validador->procesarDespacho($despacho, $control->clienteEmpresa);
            }

            DB::commit();

            \Log::info('Control de entregas guardado', [
                'pedido_id' => $control->pedidoId,
                'numero_pedido' => $control->numeroPedido,
                'cantidad_items' => count($despachos),
                'fecha_hora' => $control->fechaHora,
                'cliente_empresa' => $control->clienteEmpresa,
            ]);

            return [
                'success' => true,
                'message' => 'Control de entregas guardado correctamente',
                'pedido_id' => $control->pedidoId,
                'despachos_procesados' => count($despachos),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error al guardar control de entregas', [
                'pedido_id' => $control->pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
