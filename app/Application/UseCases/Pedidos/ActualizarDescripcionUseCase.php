<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ActualizarDescripcionInput;
use App\Application\UseCases\Pedidos\DTOs\ActualizarDescripcionOutput;
use App\Events\OrdenUpdated;
use App\Models\News;
use App\Models\PedidoProduccion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * UseCase: Actualizar Descripción y Generar Prendas
 * 
 * Responsabilidad: Orquestar parseo de descripción, validación y generación de prendas
 * Patrón: UseCase (Application Service)
 */
class ActualizarDescripcionUseCase
{
    public function __construct(
        private \App\Services\RegistroOrdenPrendaService $prendaService,
        private \App\Services\RegistroOrdenCacheService $cacheService,
        private \App\Services\RegistroOrdenValidationService $validationService,
    ) {}

    /**
     * Ejecutar: Parsear descripción, validar y actualizar prendas
     * 
     * @throws ModelNotFoundException Si la orden no existe
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function execute(ActualizarDescripcionInput $input): ActualizarDescripcionOutput
    {
        try {
            \Log::info('ActualizarDescripcionUseCase iniciado', [
                'numero_pedido' => $input->numero_pedido,
            ]);

            // Validar entrada
            if (!$input->isValid()) {
                throw new \InvalidArgumentException('Número de pedido o descripción inválida');
            }

            // Iniciar transacción
            DB::beginTransaction();

            try {
                // Obtener orden existente
                $orden = PedidoProduccion::where('numero_pedido', $input->numero_pedido)
                    ->firstOrFail();

                // Parsear descripción en prendas estructuradas
                $prendasParseadas = $this->prendaService->parseDescripcionToPrendas(
                    $input->descripcion
                );

                // Validar prendas parseadas
                $sonPrendasValidas = $this->prendaService->isValidParsedPrendas(
                    $prendasParseadas
                );

                // Variable para rastrear si se regeneraron registros
                $registrosRegenerados = false;

                // Si las prendas son válidas, reemplazarlas
                if ($sonPrendasValidas) {
                    $this->prendaService->replacePrendas(
                        $input->numero_pedido,
                        $prendasParseadas
                    );
                    $registrosRegenerados = true;
                }

                // Invalidar caché
                $this->cacheService->invalidateDaysCache($input->numero_pedido);

                // Registrar novedad
                News::create([
                    'numero_pedido' => $input->numero_pedido,
                    'novedad' => 'Descripción actualizada - ' . count($prendasParseadas) . ' prendas procesadas',
                    'usuario' => auth()?->user()?->email ?? 'sistema',
                ]);

                DB::commit();

                // Recargar orden para broadcast
                $orden->load('prendas');

                // Broadcast del evento (con fallback)
                try {
                    broadcast(new OrdenUpdated($orden, 'updated'));
                } catch (\Exception $broadcastError) {
                    \Log::warning('Error broadcasting OrdenUpdated en ActualizarDescripcionUseCase', [
                        'numero_pedido' => $input->numero_pedido,
                        'error' => $broadcastError->getMessage(),
                    ]);
                }

                // Generar mensaje informativo
                $mensaje = $this->prendaService->getParsedPrendasMessage($prendasParseadas);

                \Log::info('ActualizarDescripcionUseCase completado', [
                    'numero_pedido' => $input->numero_pedido,
                    'prendas_procesadas' => count($prendasParseadas),
                    'registros_regenerados' => $registrosRegenerados,
                ]);

                return new ActualizarDescripcionOutput(
                    numero_pedido: $input->numero_pedido,
                    mensaje: $mensaje,
                    prendas_procesadas: count($prendasParseadas),
                    registros_regenerados: $registrosRegenerados,
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
            \Log::error('Orden no encontrada en ActualizarDescripcionUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error en ActualizarDescripcionUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
