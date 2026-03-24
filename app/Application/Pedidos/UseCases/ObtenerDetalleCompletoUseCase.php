<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerDetalleCompletoResponse;
use App\Application\Pedidos\Services\PedidoAuthorizationService;
use App\Application\Pedidos\Services\PedidoFiltroService;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerDetalleCompletoUseCase
 * 
 * Caso de uso para obtener datos completos de un pedido para recibos.
 * Con filtrado especial por rol:
 * - Bodeguero: solo procesos COSTURA-BODEGA
 * - Insumos: solo prendas con de_bodega=false
 * 
 * Responsabilidades:
 * - Obtener el pedido (búsqueda por ID o número)
 * - Validaciones de autorización
 * - Enriquecimiento de procesos y prendas
 * - Aplicar filtros de rol
 * - Cargar ancho/metraje y consecutivos
 * 
 * Orquesta los servicios de autorización y filtrado.
 */
class ObtenerDetalleCompletoUseCase
{
    private ObtenerPedidoUseCase $obtenerPedidoUseCase;
    private PedidoAuthorizationService $authService;
    private PedidoFiltroService $filtroService;

    public function __construct(
        ObtenerPedidoUseCase $obtenerPedidoUseCase,
        PedidoAuthorizationService $authService,
        PedidoFiltroService $filtroService
    ) {
        $this->obtenerPedidoUseCase = $obtenerPedidoUseCase;
        $this->authService = $authService;
        $this->filtroService = $filtroService;
    }

    /**
     * Ejecuta el caso de uso
     * 
     * @param int $idONumero ID del pedido o número de pedido
     * @param bool $filtrarProcesosPendientes Si true, oculta procesos PENDIENTES
     * @return ObtenerDetalleCompletoResponse
     * @throws \DomainException Si el pedido no existe o el usuario no tiene permisos
     */
    public function ejecutar(int $idONumero, bool $filtrarProcesosPendientes = false): ObtenerDetalleCompletoResponse
    {
        try {
            // 1. Obtener el pedido
            $pedido = $this->obtenerPedido($idONumero);

            // 2. Validar autorización
            $errorAutorizacion = $this->authService->validarAccesoBodeguero($pedido);
            if ($errorAutorizacion) {
                throw new \DomainException($errorAutorizacion);
            }

            // 3. Obtener datos base del pedido
            $response = $this->obtenerPedidoUseCase->ejecutar($pedido->id, $filtrarProcesosPendientes);
            $responseData = $response->toArray();

            // 4. Enriquecer procesos
            $this->enriquecerProcesos($responseData);

            // 5. Aplicar filtro bodeguero si corresponde
            if ($this->authService->esBodeguero()) {
                $errorFiltro = $this->filtroService->filtrarParaBodeguero($pedido->id, $responseData);
                if ($errorFiltro) {
                    throw new \DomainException($errorFiltro);
                }
            }

            // 6. Aplicar filtro insumos si corresponde
            if ($this->authService->debeAplicarFiltroInsumos()) {
                $errorFiltro = $this->filtroService->filtrarParaInsumos($pedido->id, $responseData);
                if ($errorFiltro) {
                    throw new \DomainException($errorFiltro);
                }
            }

            // 7. Agregar ancho/metraje y consecutivos por prenda
            $this->agregarAnchosMetrajesYConsecutivos($pedido, $responseData);

            // 8. Agregar ancho/metraje general
            $this->agregarAnchoMetrajeGeneral($pedido, $responseData);

            // 9. Agregar datos adicionales del pedido
            $this->agregarDatosAdicionalesPedido($pedido, $responseData);

            return new ObtenerDetalleCompletoResponse($responseData);

        } catch (\DomainException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('[ObtenerDetalleCompletoUseCase] Error inesperado', [
                'id_numero' => $idONumero,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Error al obtener detalle completo del pedido: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtiene el pedido por ID o número de pedido
     */
    private function obtenerPedido(int $idONumero): PedidoProduccion
    {
        $pedido = PedidoProduccion::find($idONumero);

        if (!$pedido) {
            $pedido = PedidoProduccion::where('numero_pedido', $idONumero)->first();
        }

        if (!$pedido) {
            throw new \DomainException("Pedido {$idONumero} no encontrado");
        }

        return $pedido;
    }

    /**
     * Enriquece los procesos con ubicaciones y observaciones por talla
     */
    private function enriquecerProcesos(array &$responseData): void
    {
        if (!isset($responseData['prendas']) || !is_array($responseData['prendas'])) {
            return;
        }

        foreach ($responseData['prendas'] as &$prendaProc) {
            if (!isset($prendaProc['procesos']) || !is_array($prendaProc['procesos'])) {
                continue;
            }

            foreach ($prendaProc['procesos'] as &$procesoProc) {
                if (!isset($procesoProc['id'])) {
                    continue;
                }

                // Decodificar ubicaciones
                if (isset($procesoProc['ubicaciones']) && is_string($procesoProc['ubicaciones'])) {
                    $decodedUb = json_decode($procesoProc['ubicaciones'], true);
                    if (is_array($decodedUb)) {
                        $procesoProc['ubicaciones_array'] = $decodedUb;
                    }
                }

                // Cargar tallas con detalle
                $this->cargarTallasDetalle($procesoProc);

                // Cargar observaciones por talla si modo='general'
                if (($procesoProc['modo_tallas'] ?? null) === 'general') {
                    $this->cargarObservacionesPorTalla($procesoProc);
                }
            }
            unset($procesoProc);
        }
        unset($prendaProc);
    }

    /**
     * Carga tallas_detalle con información completa
     */
    private function cargarTallasDetalle(array &$proceso): void
    {
        try {
            $tallas = DB::table('pedidos_procesos_prenda_tallas')
                ->where('proceso_prenda_detalle_id', $proceso['id'])
                ->get(['genero', 'talla', 'cantidad', 'es_sobremedida', 'ubicaciones', 'observaciones']);

            if ($tallas->count() > 0) {
                $proceso['tallas_detalle'] = $tallas->map(function ($t) {
                    return [
                        'genero' => strtoupper((string)($t->genero ?? '')),
                        'talla' => $t->talla,
                        'cantidad' => (int)($t->cantidad ?? 0),
                        'es_sobremedida' => (int)($t->es_sobremedida ?? 0),
                        'ubicaciones' => $t->ubicaciones,
                        'observaciones' => $t->observaciones,
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {
            Log::debug('[ObtenerDetalleCompletoUseCase] Error cargando tallas_detalle', [
                'proceso_id' => $proceso['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Carga observaciones agrupadas por talla y género
     */
    private function cargarObservacionesPorTalla(array &$proceso): void
    {
        try {
            $tallasObs = DB::table('pedidos_procesos_prenda_tallas')
                ->where('proceso_prenda_detalle_id', $proceso['id'])
                ->whereNotNull('observaciones')
                ->where('observaciones', '!=', '')
                ->get(['genero', 'talla', 'observaciones']);

            $obsPorTalla = [
                'dama' => [],
                'caballero' => [],
                'unisex' => [],
            ];

            foreach ($tallasObs as $row) {
                $obs = trim((string)($row->observaciones ?? ''));
                if ($obs === '') {
                    continue;
                }

                $genero = strtolower((string)($row->genero ?? ''));
                if ($genero !== 'dama' && $genero !== 'caballero' && $genero !== 'unisex') {
                    $genero = 'caballero';
                }

                $tallaKey = $row->talla !== null ? (string)$row->talla : 'SOBREMEDIDA';
                $obsPorTalla[$genero][$tallaKey] = $obs;
            }

            $proceso['observaciones_por_talla'] = $obsPorTalla;

        } catch (\Exception $e) {
            Log::debug('[ObtenerDetalleCompletoUseCase] Error cargando observaciones por talla', [
                'proceso_id' => $proceso['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Agrega ancho/metraje y consecutivos a cada prenda
     */
    private function agregarAnchosMetrajesYConsecutivos(PedidoProduccion $pedido, array &$responseData): void
    {
        if (!isset($responseData['prendas']) || !is_array($responseData['prendas'])) {
            return;
        }

        foreach ($responseData['prendas'] as &$prenda) {
            if (!isset($prenda['id'])) {
                continue;
            }

            $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;

            if ($prendaId) {
                // Cargar ancho/metraje
                $this->cargarAnchometrajePrenda($pedido->id, $prendaId, $prenda);

                // Cargar consecutivos/recibos
                $prenda['recibos'] = $this->obtenerConsecutivosPrenda($pedido->id, $prendaId);
                $prenda['consecutivos'] = $prenda['recibos'];
            } else {
                $prenda['ancho_metraje'] = null;
                $prenda['recibos'] = null;
            }
        }
        unset($prenda);
    }

    /**
     * Carga ancho/metraje para una prenda específica
     */
    private function cargarAnchometrajePrenda(int $pedidoId, int $prendaId, array &$prenda): void
    {
        try {
            $ancho = DB::table('pedido_ancho_general')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->latest('created_at')
                ->first();

            if ($ancho) {
                $metrajes = DB::table('pedido_metraje_color')
                    ->where('pedido_produccion_id', $pedidoId)
                    ->where('prenda_pedido_id', $prendaId)
                    ->latest('created_at')
                    ->get();

                $prenda['ancho_metraje'] = [
                    'ancho' => $ancho->ancho,
                    'metrajes_por_color' => $metrajes->map(fn($m) => [
                        'color' => $m->color,
                        'metraje' => $m->metraje
                    ])->toArray()
                ];

                Log::info('[ObtenerDetalleCompletoUseCase] Ancho/Metraje encontrado', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'ancho' => $ancho->ancho,
                    'metrajes_count' => count($metrajes)
                ]);
            } else {
                $prenda['ancho_metraje'] = null;
            }
        } catch (\Exception $e) {
            Log::warning('[ObtenerDetalleCompletoUseCase] Error cargando ancho/metraje', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            $prenda['ancho_metraje'] = null;
        }
    }

    /**
     * Obtiene consecutivos y recibos parciales para una prenda
     */
    private function obtenerConsecutivosPrenda(int $pedidoId, int $prendaId): ?array
    {
        try {
            $consecutivos = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where(function ($query) use ($prendaId) {
                    $query->where('prenda_id', $prendaId)
                        ->orWhereNull('prenda_id');
                })
                ->select([
                    'id', 'tipo_recibo', 'consecutivo_actual', 'consecutivo_inicial',
                    'activo', 'created_at'
                ])
                ->get();

            $parciales = DB::table('pedidos_parciales')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->whereNull('deleted_at')
                ->select([
                    'id', 'tipo_recibo', 'consecutivo_actual', 'consecutivo_inicial',
                    'activo', 'estado', 'created_at'
                ])
                ->get();

            if ($consecutivos->isEmpty() && $parciales->isEmpty()) {
                return null;
            }

            $recibos = [
                'COSTURA' => null,
                'ESTAMPADO' => null,
                'BORDADO' => null,
                'DTF' => null,
                'SUBLIMADO' => null,
                'REFLECTIVO' => null,
                'COSTURA-BODEGA' => null
            ];

            // Procesar consecutivos
            foreach ($consecutivos as $c) {
                if (array_key_exists($c->tipo_recibo, $recibos)) {
                    $recibos[$c->tipo_recibo] = [
                        'id' => $c->id,
                        'tipo_recibo' => $c->tipo_recibo,
                        'consecutivo_actual' => $c->consecutivo_actual,
                        'activo' => $c->activo,
                    ];
                }
            }

            $recibos['parciales'] = $parciales->toArray();

            return $recibos;

        } catch (\Exception $e) {
            Log::error('[ObtenerDetalleCompletoUseCase] Error obteniendo consecutivos', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Agrega ancho/metraje general del pedido
     */
    private function agregarAnchoMetrajeGeneral(PedidoProduccion $pedido, array &$responseData): void
    {
        try {
            $responseData['ancho_metraje'] = [
                'ancho' => $pedido->ancho ?? null,
                'metraje' => $pedido->metraje ?? null,
                'fecha_actualizacion' => $pedido->updated_at ?? null
            ];
        } catch (\Exception $e) {
            Log::debug('[ObtenerDetalleCompletoUseCase] Error ancho/metraje general', [
                'error' => $e->getMessage()
            ]);
            $responseData['ancho_metraje'] = null;
        }
    }

    /**
     * Agrega datos adicionales del pedido (fecha estimada, área, día entrega)
     */
    private function agregarDatosAdicionalesPedido(PedidoProduccion $pedido, array &$responseData): void
    {
        if (!isset($responseData['fecha_estimada_de_entrega'])) {
            $responseData['fecha_estimada_de_entrega'] = $pedido->fecha_estimada_de_entrega;
        }

        if (!isset($responseData['area'])) {
            $responseData['area'] = $pedido->area;
        }

        if (!isset($responseData['dia_de_entrega'])) {
            $responseData['dia_de_entrega'] = $pedido->dia_de_entrega;
        }

        Log::info('[ObtenerDetalleCompletoUseCase] Datos del pedido agregados', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
        ]);
    }
}
