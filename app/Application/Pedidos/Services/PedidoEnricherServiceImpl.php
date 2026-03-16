<?php

namespace App\Application\Pedidos\Services;

use App\Application\Pedidos\Contracts\PedidoEnricherService;
use App\Domain\Pedidos\Contracts\ConsecutivosService;
use App\Domain\Pedidos\Contracts\ImagenesEppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PedidoEnricherServiceImpl
 * 
 * Implementación de enriquecimiento de datos de pedidos
 */
class PedidoEnricherServiceImpl implements PedidoEnricherService
{
    public function __construct(
        private ConsecutivosService $consecutivosService,
        private ImagenesEppService $imagenesService,
    ) {}

    public function enriquecerPrendas(int $pedidoId, array $datos): array
    {
        if (!isset($datos['prendas']) || !is_array($datos['prendas'])) {
            return $datos;
        }

        foreach ($datos['prendas'] as &$prenda) {
            $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;

            if (!$prendaId) {
                continue;
            }

            // Agregar ancho/metraje
            $anchoMetraje = $this->obtenerAnchoMetrajePrenda($pedidoId, $prendaId);
            $prenda['ancho_metraje'] = $anchoMetraje;

            // Agregar consecutivos
            $consecutivos = $this->consecutivosService->obtenerConsecutivosPrenda($pedidoId, $prendaId);
            $prenda['recibos'] = $consecutivos;
            $prenda['consecutivos'] = $consecutivos;
        }
        unset($prenda);

        // Agregar ancho/metraje general
        $anchoMetrajeGeneral = $this->obtenerAnchoMetrajeGeneral($pedidoId);
        $datos['ancho_metraje'] = $anchoMetrajeGeneral;

        return $datos;
    }

    public function enriquecerEpps(int $pedidoId, array $epps): array
    {
        if (!is_array($epps) || empty($epps)) {
            return [];
        }

        $eppsEnriquecidos = [];

        foreach ($epps as $pedidoEpp) {
            $epp = $pedidoEpp['epp'] ?? null;

            if (!$epp) {
                continue;
            }

            $pedidoEppId = $pedidoEpp['id'] ?? null;

            if (!$pedidoEppId) {
                $eppsEnriquecidos[] = $pedidoEpp;
                continue;
            }

            $imagenes = $this->imagenesService->obtenerImagenesEpp($pedidoEppId);

            $eppsEnriquecidos[] = [
                'id' => $pedidoEppId,
                'epp_id' => $pedidoEpp['epp_id'] ?? null,
                'nombre' => $epp['nombre_completo'] ?? $epp['nombre'] ?? '',
                'nombre_completo' => $epp['nombre_completo'] ?? $epp['nombre'] ?? '',
                'cantidad' => $pedidoEpp['cantidad'] ?? 0,
                'observaciones' => $pedidoEpp['observaciones'] ?? '',
                'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                'imagenes' => $imagenes,
            ];
        }

        return $eppsEnriquecidos;
    }

    public function enriquecerEntregas(array $prendas): array
    {
        foreach ($prendas as &$prenda) {
            $prendaId = $prenda['id'] ?? null;

            if (!$prendaId) {
                continue;
            }

            try {
                $entrega = DB::table('prenda_entregas')
                    ->where('prenda_pedido_id', $prendaId)
                    ->first();

                $prenda['entrega'] = $entrega ? [
                    'entregado' => $entrega->entregado,
                    'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                    'usuario' => $entrega->usuario?->name ?? null,
                ] : null;

            } catch (\Exception $e) {
                Log::debug('[PedidoEnricherService] Error enriquecer entregas', [
                    'prenda_id' => $prendaId,
                    'error' => $e->getMessage()
                ]);
                $prenda['entrega'] = null;
            }
        }
        unset($prenda);

        return $prendas;
    }

    public function enriquecerRecibosParciales(int $pedidoId, array $prendas): array
    {
        foreach ($prendas as &$prenda) {
            $prendaId = $prenda['id'] ?? null;

            if (!$prendaId) {
                continue;
            }

            try {
                $recibosParciales = DB::table('pedidos_parciales')
                    ->where('pedido_produccion_id', $pedidoId)
                    ->where('prenda_pedido_id', $prendaId)
                    ->orderBy('tipo_recibo', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($recibosParciales->count() > 0) {
                    $procesosAdicionales = [];
                    $anexosPorTipo = [];

                    foreach ($recibosParciales as $reciboParcial) {
                        $tipoRecibo = $reciboParcial->tipo_recibo;

                        if (!isset($anexosPorTipo[$tipoRecibo])) {
                            $anexosPorTipo[$tipoRecibo] = 0;
                        }
                        $anexosPorTipo[$tipoRecibo]++;

                        $procesosAdicionales[] = [
                            'tipo_proceso' => $tipoRecibo,
                            'nombre_proceso' => $tipoRecibo . ' ANEXO ' . $anexosPorTipo[$tipoRecibo],
                            'estado' => $reciboParcial->estado ?? 'PENDIENTE',
                            'numero_recibo' => $reciboParcial->consecutivo_actual ?? $reciboParcial->numero_recibo ?? null,
                            'es_parcial' => true,
                            'numero_anexo' => $anexosPorTipo[$tipoRecibo],
                            'pedido_parcial_id' => $reciboParcial->id,
                            'created_at' => $reciboParcial->created_at,
                        ];
                    }

                    if (!isset($prenda['procesos'])) {
                        $prenda['procesos'] = [];
                    }
                    $prenda['procesos'] = array_merge($prenda['procesos'], $procesosAdicionales);
                }

            } catch (\Exception $e) {
                Log::debug('[PedidoEnricherService] Error enriquecer recibos parciales', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        unset($prenda);

        return $prendas;
    }

    private function obtenerAnchoMetrajePrenda(int $pedidoId, int $prendaId): ?array
    {
        try {
            $anchoGeneral = DB::table('pedido_ancho_general')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->first();

            $metrajesPorColor = DB::table('pedido_metraje_color')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->get();

            if (!$anchoGeneral && $metrajesPorColor->isEmpty()) {
                return null;
            }

            return [
                'prenda_id' => $prendaId,
                'ancho' => $anchoGeneral?->ancho ?? null,
                'metraje' => $anchoGeneral?->metraje ?? null,
                'tipo_modo' => $anchoGeneral?->tipo_modo ?? null,
                'contenido_mano' => $anchoGeneral?->contenido_mano ?? null,
                'metrajes_por_color' => $metrajesPorColor->map(fn($m) => [
                    'color' => $m->color,
                    'metraje' => $m->metraje
                ])->toArray()
            ];

        } catch (\Exception $e) {
            Log::debug('[PedidoEnricherService] Error obtener ancho/metraje prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function obtenerAnchoMetrajeGeneral(int $pedidoId): ?array
    {
        try {
            $pedido = DB::table('pedidos_produccion')
                ->where('id', $pedidoId)
                ->first();

            if (!$pedido) {
                return null;
            }

            return [
                'ancho' => $pedido->ancho ?? null,
                'metraje' => $pedido->metraje ?? null,
                'fecha_actualizacion' => $pedido->updated_at ?? null
            ];

        } catch (\Exception $e) {
            Log::debug('[PedidoEnricherService] Error obtener ancho/metraje general', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
