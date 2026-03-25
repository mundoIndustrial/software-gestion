<?php

namespace App\Application\UseCases\Orders;

use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenPrendaService;
use App\Services\RegistroOrdenCacheService;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * UseCase: Actualizar descripción de prendas de una orden
 *
 * Responsabilidades:
 * - Validar datos de entrada
 * - Parsear y reemplazar prendas según la nueva descripción
 * - Invalidar cache de días calculados
 * - Registrar evento de auditoría
 * - Disparar evento de dominio
 */
class UpdateDescripcionPrendasUseCase
{
    public function __construct(
        private RegistroOrdenValidationService $validationService,
        private RegistroOrdenPrendaService $prendaService,
        private RegistroOrdenCacheService $cacheService,
    ) {}

    public function execute(Request $request): array
    {
        $validatedData = $this->validationService->validateUpdateDescripcionRequest($request);

        $pedido = $validatedData['pedido'];
        $nuevaDescripcion = $validatedData['descripcion'];

        $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

        DB::beginTransaction();

        try {
            $prendas = $this->prendaService->parseDescripcionToPrendas($nuevaDescripcion);
            $procesarRegistros = $this->prendaService->isValidParsedPrendas($prendas);

            if ($procesarRegistros) {
                $this->prendaService->replacePrendas($pedido, $prendas);
            }

            $this->cacheService->invalidateDaysCache($pedido);

            News::create([
                'event_type' => 'description_updated',
                'description' => "descripcion y prendas actualizadas para pedido {$pedido}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['prendas_count' => count($prendas)]
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        $orden->load('prendas');

        broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

        $mensaje = $this->prendaService->getParsedPrendasMessage($prendas);

        return [
            'success' => true,
            'message' => $mensaje,
            'prendas_procesadas' => count($prendas),
            'registros_regenerados' => $procesarRegistros,
        ];
    }
}
