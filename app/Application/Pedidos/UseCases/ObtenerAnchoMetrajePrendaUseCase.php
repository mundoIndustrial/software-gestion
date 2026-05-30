<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\ObtenerAnchoMetrajePrendaUseCaseContract;

use App\Application\Pedidos\DTOs\ObtenerAnchoMetrajePrendaResponse;
use App\Models\PedidoProduccion;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerAnchoMetrajePrendaUseCase
 * Caso de uso para obtener ancho, metraje y contenido de mano de una prenda en un pedido.
 * Responsabilidades:
 * - Obtener ancho general del pedido-prenda
 * - Obtener metrajes por color
 * - Determinar tipo de modo (general o por color)
 * - Preparar respuesta para catálogo público
 * Nota: Este es un catálogo público, sin autenticación requerida.
 */
class ObtenerAnchoMetrajePrendaUseCase implements ObtenerAnchoMetrajePrendaUseCaseContract
{
    /**
     * Ejecuta el caso de uso
     * @param int $pedidoId
     * @param int $prendaId
     * @param int|null $numeroRecibo Filtro opcional para obtener datos de un recibo específico
     * @return ObtenerAnchoMetrajePrendaResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si el pedido no existe
     */
    public function ejecutar(int $pedidoId, int $prendaId, ?int $numeroRecibo = null): ObtenerAnchoMetrajePrendaResponse
    {
        try {
            // Validar que el pedido exista
            PedidoProduccion::findOrFail($pedidoId);

            // Obtener ancho general
            $query = PedidoAnchoGeneral::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId);
            
            // Si hay numero_recibo, filtrar por él (importante para mostrar datos del recibo correcto)
            if (!is_null($numeroRecibo)) {
                $query->where('numero_recibo', $numeroRecibo);
            }
            
            $anchoGeneral = $query->latest('created_at')->first();

            // Obtener metrajes por color
            $queryMetrajes = PedidoMetrajeColor::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId);
            
            // Si hay numero_recibo, filtrar por él
            if (!is_null($numeroRecibo)) {
                $queryMetrajes->where('numero_recibo', $numeroRecibo);
            }
            
            $metrajesPorColor = $queryMetrajes->latest('created_at')->get();

            // Determinar tipo de modo
            $tipoModo = $this->determinarTipoModo($anchoGeneral, $metrajesPorColor);

            // Preparar data de metrajes por color
            $data = $this->prepararMetrajesPorColor($metrajesPorColor);

            Log::info('[ObtenerAnchoMetrajePrendaUseCase] Ancho/metraje obtenido', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'numero_recibo' => $numeroRecibo,
                'tiene_ancho_general' => !is_null($anchoGeneral),
                'metrajes_count' => count($data)
            ]);

            return new ObtenerAnchoMetrajePrendaResponse(
                ancho: !is_null($anchoGeneral?->ancho) ? (string) $anchoGeneral->ancho : null,
                metraje: !is_null($anchoGeneral?->metraje) ? (string) $anchoGeneral->metraje : null,
                contenidoMano: $anchoGeneral?->contenido_mano,
                tipoModo: $tipoModo,
                data: $data
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ObtenerAnchoMetrajePrendaUseCase] Pedido no encontrado', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('[ObtenerAnchoMetrajePrendaUseCase] Error inesperado', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Determina el tipo de modo (general o por color)
     */
    private function determinarTipoModo($anchoGeneral, $metrajesPorColor): ?string
    {
        if ($anchoGeneral && $anchoGeneral->tipo_modo) {
            return $anchoGeneral->tipo_modo;
        }

        if ($metrajesPorColor->isNotEmpty() && $metrajesPorColor->first()->tipo_modo) {
            return $metrajesPorColor->first()->tipo_modo;
        }

        return null;
    }

    /**
     * Prepara el array de metrajes por color
     */
    private function prepararMetrajesPorColor($metrajesPorColor): array
    {
        if ($metrajesPorColor->isEmpty()) {
            return [];
        }

        return $metrajesPorColor->map(fn($item) => [
            'color' => $item->color,
            'metraje' => $item->metraje,
            'tipo_modo' => $item->tipo_modo ?? 'color'
        ])->toArray();
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {ObtenerAnchoMetrajePrendaUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}



