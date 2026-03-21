<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PedidoProduccion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Domain Service: EnriquecedorRecibosService
 * 
 * Responsabilidad: Enriquecer datos de recibos con información relacionada
 * Patrón: Domain Service
 * 
 * Encapsula la lógica de:
 * - Detectar recibos parciales (anexos)
 * - Calcular días hábiles desde fecha base
 * - Obtener información de prendas/telas/colores/tallas
 * - Calcular cantidad total de prendas
 * - Generar descripción detallada de prendas
 */
class EnriquecedorRecibosService
{
    public function __construct(
        private CalculadorFechaEntregaService $calculadorFecha,
    ) {}

    /**
     * Enriquecer un recibo Individual con toda su información relacionada
     * 
     * @param object $recibo Recibo desde BD (consecutivos_recibos_pedidos)
     * @param array $festivosSet Array de festivos formateados como 'Y-m-d'
     * @return array Recibo enriquecido con toda la información
     */
    public function enriquecerRecibo($recibo, array $festivosSet = []): array
    {
        try {
            // 1. Obtener pedido relacionado
            $pedido = PedidoProduccion::with([
                'prendas.coloresTelas.tela',
                'prendas.coloresTelas.color',
                'prendas.tallas'
            ])->find($recibo->pedido_produccion_id);

            if (!$pedido) {
                Log::warning('[EnriquecedorRecibosService] Pedido no encontrado', [
                    'recibo_id' => $recibo->id,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id
                ]);
                return $this->construirReciboVacio($recibo);
            }

            // 2. Detectar si es recibo parcial (anexo)
            $parcialId = null;
            $esParcial = false;
            $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
            
            if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                $parcialId = (int) $matches[1];
                $esParcial = true;
            }

            // 3. Resolver created_at real (del anexo si aplica)
            $createdAt = $recibo->created_at;
            if ($esParcial && $parcialId) {
                $createdAt = $this->obtenerFechaCreacionAnexo($parcialId) ?? $recibo->created_at;
            }

            // 4. Calcular días hábiles
            $diasCalculados = $this->calcularDiasHabiles($pedido, $createdAt, $festivosSet, $esParcial);

            // 5. Obtener información detallada de la prenda del recibo
            $prendaInfo = $this->obtenerInfoPrenda($pedido, $recibo->prenda_id);

            // 6. Calcular cantidad total de la prenda
            $cantidadTotal = $this->calcularCantidadPrenda($pedido, $recibo->prenda_id);

            // 7. Obtener área (actualizada por Observer)
            $area = $recibo->area ?? 'Insumos';

            // 8. Construir respuesta enriquecida
            return [
                'id' => $recibo->id,
                'consecutivo_actual' => $recibo->consecutivo_actual,
                'pedido_produccion_id' => $recibo->pedido_produccion_id,
                'prenda_id' => $recibo->prenda_id,
                'tipo_recibo' => $recibo->tipo_recibo,
                'notas' => $recibo->notas,
                'estado' => $recibo->estado ?? 'PENDIENTE_INSUMOS',
                'area' => $area,
                'created_at' => $createdAt,
                'updated_at' => $recibo->updated_at,
                'dias_calculados' => $diasCalculados,
                'cantidad_total' => $cantidadTotal,
                'descripcion_detallada' => $prendaInfo['descripcion'],
                'nombre_prenda' => $prendaInfo['nombre'],
                'es_parcial' => $esParcial,
                'pedido_parcial_id' => $parcialId,
                'pedido_info' => [
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'estado' => $pedido->estado,
                    'area' => $area,
                    'dia_de_entrega' => $pedido->dia_de_entrega,
                    'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega ? $pedido->fecha_estimada_de_entrega->format('d/m/Y') : null,
                    'fecha_creacion_orden' => $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('Y-m-d H:i:s') : null,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('[EnriquecedorRecibosService] Error enriqueciendo recibo', [
                'recibo_id' => $recibo->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->construirReciboVacio($recibo);
        }
    }

    /**
     * Obtener fecha de creación de un anexo desde pedidos_parciales
     */
    private function obtenerFechaCreacionAnexo(int $parcialId): ?Carbon
    {
        try {
            $parcial = \DB::table('pedidos_parciales')
                ->select('created_at')
                ->where('id', $parcialId)
                ->whereNull('deleted_at')
                ->first();

            if ($parcial && !empty($parcial->created_at)) {
                return Carbon::parse($parcial->created_at);
            }
        } catch (\Exception $e) {
            Log::warning('[EnriquecedorRecibosService] No se pudo obtener created_at de pedidos_parciales', [
                'pedido_parcial_id' => $parcialId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Calcular días hábiles desde fecha base hasta hoy
     */
    private function calcularDiasHabiles(PedidoProduccion $pedido, $fechaBase, array $festivosSet, bool $esParcial): int
    {
        try {
            $fechaInicio = null;

            if ($esParcial && $fechaBase) {
                $fechaInicio = Carbon::parse($fechaBase);
            } elseif ($pedido && $pedido->fecha_de_creacion_de_orden) {
                $fechaInicio = $pedido->fecha_de_creacion_de_orden;
            }

            if (!$fechaInicio) {
                return 0;
            }

            // Usar el calculador de días hábiles
            $fechaFin = Carbon::now();
            $current = $fechaInicio->copy()->addDay();
            $totalDays = 0;
            $maxIterations = 365;
            $iterations = 0;

            while ($current <= $fechaFin && $iterations < $maxIterations) {
                $dateString = $current->format('Y-m-d');
                $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                $isFestivo = isset($festivosSet[$dateString]);

                if (!$isWeekend && !$isFestivo) {
                    $totalDays++;
                }

                $current->addDay();
                $iterations++;
            }

            return max(0, $totalDays);

        } catch (\Exception $e) {
            Log::warning('[EnriquecedorRecibosService] Error calculando días hábiles', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Obtener información detallada de una prenda específica
     */
    private function obtenerInfoPrenda(PedidoProduccion $pedido, ?int $prendaId): array
    {
        $default = [
            'nombre' => 'Sin prendas',
            'descripcion' => '',
        ];

        if (!$pedido || !$prendaId) {
            return $default;
        }

        try {
            $prenda = $pedido->prendas->where('id', $prendaId)->first();

            if (!$prenda) {
                return $default;
            }

            $nombrePrenda = $prenda->nombre_prenda ?? 'Sin nombre';
            $descripcion = "PRENDA: " . $nombrePrenda;

            // Agregar telas y colores
            if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
                $telasInfo = [];
                foreach ($prenda->coloresTelas as $colorTela) {
                    $telaNombre = $colorTela->tela ? $colorTela->tela->nombre : 'Sin tela';
                    $colorNombre = $colorTela->color ? $colorTela->color->nombre : 'Sin color';
                    $referencia = $colorTela->referencia ?? '';
                    $telasInfo[] = "TELA: {$telaNombre} / COLOR: {$colorNombre}" . ($referencia ? " (REF: {$referencia})" : '');
                }
                if (!empty($telasInfo)) {
                    $descripcion .= " | " . implode(' | ', $telasInfo);
                }
            }

            // Agregar tallas
            if ($prenda->tallas && $prenda->tallas->count() > 0) {
                $tallasInfo = [];
                foreach ($prenda->tallas as $talla) {
                    $cantidad = $talla->cantidad ?? 0;
                    if ($cantidad > 0) {
                        $tallasInfo[] = $talla->talla . ": " . $cantidad;
                    }
                }
                if (!empty($tallasInfo)) {
                    $descripcion .= " | TALLAS: " . implode(', ', $tallasInfo);
                }
            }

            return [
                'nombre' => $nombrePrenda,
                'descripcion' => $descripcion,
            ];

        } catch (\Exception $e) {
            Log::warning('[EnriquecedorRecibosService] Error obteniendo info de prenda', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            return $default;
        }
    }

    /**
     * Calcular cantidad total de una prenda específica
     */
    private function calcularCantidadPrenda(PedidoProduccion $pedido, ?int $prendaId): int
    {
        if (!$pedido || !$prendaId) {
            return 0;
        }

        try {
            $prenda = $pedido->prendas->where('id', $prendaId)->first();

            if (!$prenda || !$prenda->tallas) {
                return 0;
            }

            $cantidad = 0;
            foreach ($prenda->tallas as $talla) {
                if (method_exists($talla, 'obtenerCantidadTotal')) {
                    $cantidad += $talla->obtenerCantidadTotal();
                } else {
                    $cantidad += $talla->cantidad ?? 0;
                }
            }

            return $cantidad;

        } catch (\Exception $e) {
            Log::warning('[EnriquecedorRecibosService] Error calculando cantidad', [
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Construir recibo vacío en caso de error
     */
    private function construirReciboVacio($recibo): array
    {
        return [
            'id' => $recibo->id,
            'consecutivo_actual' => $recibo->consecutivo_actual,
            'pedido_produccion_id' => $recibo->pedido_produccion_id,
            'prenda_id' => $recibo->prenda_id,
            'tipo_recibo' => $recibo->tipo_recibo,
            'notas' => $recibo->notas,
            'estado' => 'ERROR',
            'area' => 'ERROR',
            'created_at' => $recibo->created_at,
            'updated_at' => $recibo->updated_at,
            'dias_calculados' => 0,
            'cantidad_total' => 0,
            'descripcion_detallada' => 'Error al enriquecer datos',
            'nombre_prenda' => 'Error',
            'es_parcial' => false,
            'pedido_parcial_id' => null,
            'pedido_info' => null,
        ];
    }
}
