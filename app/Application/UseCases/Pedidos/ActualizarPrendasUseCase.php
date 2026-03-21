<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ActualizarPrendasInput;
use App\Application\UseCases\Pedidos\DTOs\ActualizarPrendasOutput;
use App\Events\OrdenUpdated;
use App\Models\News;
use App\Models\PedidoProduccion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * UseCase: Actualizar Prendas de una Orden
 * 
 * Responsabilidad: Orquestar actualización completa de orden + prendas
 * Patrón: UseCase (Application Service)
 */
class ActualizarPrendasUseCase
{
    public function __construct(
        private \App\Services\RegistroOrdenPrendaService $prendaService,
        private \App\Services\RegistroOrdenCacheService $cacheService,
        private \App\Services\RegistroOrdenValidationService $validationService,
    ) {}

    /**
     * Ejecutar: Actualizar orden y prendas de forma transaccional
     * 
     * @throws ModelNotFoundException Si la orden no existe
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function execute(ActualizarPrendasInput $input): ActualizarPrendasOutput
    {
        try {
            \Log::info('ActualizarPrendasUseCase iniciado', [
                'numero_pedido' => $input->numero_pedido,
            ]);

            // Validar entrada
            if (!$input->isValid()) {
                throw new \InvalidArgumentException('Datos inválidos para actualizar prendas');
            }

            // Iniciar transacción
            DB::beginTransaction();

            try {
                // Obtener orden existente
                $orden = PedidoProduccion::where('numero_pedido', $input->numero_pedido)
                    ->firstOrFail();

                // Preparar datos para actualizar
                $datosActualizacion = [
                    'cliente' => $input->cliente,
                    'estado' => $input->estado,
                    'forma_de_pago' => $input->forma_de_pago,
                ];

                // Solo actualizar fecha_de_creacion si se proporciona
                if ($input->fecha_creacion) {
                    $datosActualizacion['fecha_de_creacion'] = $input->fecha_creacion;
                }

                // Actualizar orden
                $orden->update($datosActualizacion);

                // Reemplazar prendas
                $totalPrendasActualizadas = $this->prendaService->replacePrendas(
                    $input->numero_pedido,
                    $input->prendas
                );

                // Invalidar caché
                $this->cacheService->invalidateDaysCache($input->numero_pedido);

                // Registrar novedad
                News::create([
                    'numero_pedido' => $input->numero_pedido,
                    'novedad' => 'Prendas actualizadas - ' . $totalPrendasActualizadas . ' prendas',
                    'usuario' => auth()?->user()?->email ?? 'sistema',
                ]);

                DB::commit();

                // Recargar orden para broadcast
                $orden->load('prendas');

                // Broadcast del evento (con fallback)
                try {
                    broadcast(new OrdenUpdated($orden, 'updated'));
                } catch (\Exception $broadcastError) {
                    \Log::warning('Error broadcasting OrdenUpdated en ActualizarPrendasUseCase', [
                        'numero_pedido' => $input->numero_pedido,
                        'error' => $broadcastError->getMessage(),
                    ]);
                }

                \Log::info('ActualizarPrendasUseCase completado', [
                    'numero_pedido' => $input->numero_pedido,
                    'prendas_actualizadas' => $totalPrendasActualizadas,
                ]);

                return new ActualizarPrendasOutput(
                    numero_pedido: $input->numero_pedido,
                    mensaje: 'Prendas actualizadas correctamente',
                    total_prendas_actualizado: $totalPrendasActualizadas,
                    orden_actualizada: $orden->toArray(),
                    metadata: [
                        'cliente' => $orden->cliente,
                        'estado' => $orden->estado,
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                    ],
                );
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ModelNotFoundException $e) {
            \Log::error('Orden no encontrada en ActualizarPrendasUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error en ActualizarPrendasUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
