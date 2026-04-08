<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\ObtenerCotizacionesUseCaseContract;

use App\Models\Cotizacion;
use App\Domain\Pedidos\PedidoConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerCotizacionesUseCase
 *  RESPONSABILIDAD ÚNICA: Obtener cotizaciones del usuario para crear pedidos
 * Query específica con:
 * - Eager loading optimizado
 * - Filtro por asesor
 * - Filtro por estado (solo válidas para pedido)
 * - Ordenamiento
 * 
 * ¿Por qué como UseCase?
 *  Query centralizada (no en controller)
 *  Lógica de filtrado de estados
 *  Reutilizable
 *  Testeable
 *  Cacheable (si necesario)
 */
class ObtenerCotizacionesUseCase implements ObtenerCotizacionesUseCaseContract
{
    /**
     * Ejecutar use case
     * @param int $usuarioId ID del asesor
     * @return Collection
     */
    public function ejecutar(int $usuarioId): Collection
    {
        Log::info('[ObtenerCotizacionesUseCase] Iniciado', [
            'usuario_id' => $usuarioId,
        ]);

        try {
            // Obtener cotizaciones con eager loading optimizado
            $cotizaciones = Cotizacion::with([
                'cliente',
                'tipoCotizacion',
                'prendas' => function ($query) {
                    $query->with([
                        'fotos',
                        'telaFotos',
                        'tallas.genero',
                        'variantes',
                        'logoCotizacionTelasPrenda'
                    ]);
                },
                'logoCotizacion.fotos',
                'logoCotizacion.telasPrendas'
            ])
                ->where('asesor_id', $usuarioId)
                ->whereIn('estado', PedidoConstants::COTIZACIONES_PARA_PEDIDO)
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('[ObtenerCotizacionesUseCase] Completado', [
                'usuario_id' => $usuarioId,
                'cotizaciones' => $cotizaciones->count(),
            ]);

            return $cotizaciones;

        } catch (\Exception $e) {
            Log::error('[ObtenerCotizacionesUseCase] Error', [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {ObtenerCotizacionesUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}





