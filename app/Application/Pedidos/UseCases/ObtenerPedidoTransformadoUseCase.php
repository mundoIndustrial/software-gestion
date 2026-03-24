<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerPedidoTransformadoResponse;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ObtenerPedidoTransformadoUseCase
 * 
 * Caso de uso que orquestra:
 * 1. Obtención del pedido base
 * 2. Enriquecimiento de procesos con tallas y observaciones
 * 3. Carga de tallas con colores y estado de entrega
 * 4. Agregación de recibos parciales (ANEXOS)
 * 5. Transformación de EPPs con imágenes
 * 
 * Responsabilidades:
 * - Centralizar la lógica de transformación del pedido
 * - Mantener el controller delgado (solo delegación)
 * - Facilitar testing y reutilización
 * 
 * Sigue el patrón DDD con separación de responsabilidades.
 */
class ObtenerPedidoTransformadoUseCase
{
    private ObtenerPedidoUseCase $obtenerPedidoUseCase;

    public function __construct(ObtenerPedidoUseCase $obtenerPedidoUseCase)
    {
        $this->obtenerPedidoUseCase = $obtenerPedidoUseCase;
    }

    /**
     * Ejecuta el caso de uso de obtención y transformación del pedido
     * 
     * @param int $pedidoId ID del pedido a obtener y transformar
     * @return ObtenerPedidoTransformadoResponse Respuesta con el pedido transformado
     * @throws \DomainException Si el pedido no existe
     */
    public function ejecutar(int $pedidoId): ObtenerPedidoTransformadoResponse
    {
        try {
            // 1. Obtener el pedido base con el UseCase existente
            $response = $this->obtenerPedidoUseCase->ejecutar($pedidoId);
            $datos = $response->toArray();

            // 2. Enriquecer procesos con tallas y observaciones
            $this->enriquecerProcesos($datos, $pedidoId);

            // 3. Cargar fecha de creación si no existe
            $this->agregarFechaCreacion($datos, $pedidoId);

            // 4. Cargar tallas con colores y estado de entrega de cada prenda
            $this->cargarTallasYEntregas($datos, $pedidoId);

            // 5. Transformar EPPs con imágenes
            $datos['epps_transformados'] = $this->transformarEpps($pedidoId);

            return new ObtenerPedidoTransformadoResponse($datos);

        } catch (\DomainException $e) {
            Log::warning('[ObtenerPedidoTransformadoUseCase] Domain error', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('[ObtenerPedidoTransformadoUseCase] Error inesperado', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \RuntimeException('Error al obtener y transformar pedido: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Enriquece los procesos con información de tallas y observaciones
     */
    private function enriquecerProcesos(array &$datos, int $pedidoId): void
    {
        if (!isset($datos['prendas']) || !is_array($datos['prendas'])) {
            return;
        }

        foreach ($datos['prendas'] as &$prenda) {
            if (!isset($prenda['procesos']) || !is_array($prenda['procesos'])) {
                continue;
            }

            foreach ($prenda['procesos'] as &$proceso) {
                if (!isset($proceso['id'])) {
                    continue;
                }

                // Decodificar ubicaciones si es JSON
                if (isset($proceso['ubicaciones']) && is_string($proceso['ubicaciones'])) {
                    $decodedUb = json_decode($proceso['ubicaciones'], true);
                    if (is_array($decodedUb)) {
                        $proceso['ubicaciones_array'] = $decodedUb;
                    }
                }

                // Cargar tallas del proceso
                $tallas = DB::table('pedidos_procesos_prenda_tallas')
                    ->where('proceso_prenda_detalle_id', $proceso['id'])
                    ->get();

                Log::debug('[ObtenerPedidoTransformadoUseCase] Tallas obtenidas para proceso', [
                    'proceso_id' => $proceso['id'],
                    'tallas_count' => $tallas->count(),
                ]);

                // Transformar tallas y cargar colores
                $talasTransformadas = [
                    'dama' => [],
                    'caballero' => [],
                    'unisex' => []
                ];
                $tallasDetalle = [];
                $obsPorTalla = [
                    'dama' => [],
                    'caballero' => [],
                    'unisex' => [],
                ];

                foreach ($tallas as $talla) {
                    $genero = $this->normalizarGenero($talla->genero ?? 'caballero');

                    // Agregar a tallas_detalle
                    $tallasDetalle[] = [
                        'genero' => strtoupper((string)($talla->genero ?? '')),
                        'talla' => $talla->talla,
                        'cantidad' => (int)($talla->cantidad ?? 0),
                        'es_sobremedida' => (int)($talla->es_sobremedida ?? 0),
                    ];

                    // Cargar colores para esta talla
                    $colores = DB::table('pedidos_procesos_prenda_talla_colores')
                        ->where('pedidos_procesos_prenda_talla_id', $talla->id)
                        ->get();

                    if ($colores->count() > 0) {
                        $talasTransformadas[$genero][$talla->talla] = $colores->map(fn($color) => [
                            'color' => $color->color_nombre,
                            'cantidad' => $color->cantidad
                        ])->toArray();
                    } else {
                        $talasTransformadas[$genero][$talla->talla] = $talla->cantidad;
                    }

                    // Agregar observaciones por talla
                    $obs = trim((string)($talla->observaciones ?? ''));
                    if ($obs !== '') {
                        $tallaKey = $talla->talla !== null ? (string)$talla->talla : 'SOBREMEDIDA';
                        $obsPorTalla[$genero][$tallaKey] = $obs;
                    }
                }

                $proceso['tallas'] = $talasTransformadas;
                $proceso['tallas_detalle'] = $tallasDetalle;

                // Agregar observaciones_por_talla solo si modo_tallas es 'general'
                if (($proceso['modo_tallas'] ?? null) === 'general') {
                    $proceso['observaciones_por_talla'] = $obsPorTalla;
                }
            }
            unset($proceso);
        }
        unset($prenda);
    }

    /**
     * Agrega la fecha de creación del pedido si no existe
     */
    private function agregarFechaCreacion(array &$datos, int $pedidoId): void
    {
        if (isset($datos['fecha_creacion'])) {
            return;
        }

        try {
            $pedido = PedidoProduccion::find($pedidoId);
            if ($pedido) {
                $fechaCreacion = $pedido->fecha_de_creacion_de_orden ?? $pedido->created_at;
                $datos['fecha_creacion'] = $fechaCreacion
                    ? (is_string($fechaCreacion) ? $fechaCreacion : $fechaCreacion->format('d/m/Y'))
                    : date('d/m/Y');
            }
        } catch (\Exception $e) {
            Log::warning('[ObtenerPedidoTransformadoUseCase] Error cargando fecha creación', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Carga tallas con colores y estado de entrega para cada prenda
     */
    private function cargarTallasYEntregas(array &$datos, int $pedidoId): void
    {
        if (!isset($datos['prendas']) || !is_array($datos['prendas'])) {
            return;
        }

        foreach ($datos['prendas'] as &$prenda) {
            if (!isset($prenda['id'])) {
                continue;
            }

            // Cargar tallas con colores
            $this->cargarTallasConColoresPrenda($prenda, $pedidoId);

            // Cargar estado de entrega
            $this->cargarEstadoEntrega($prenda, $pedidoId);

            // Agregar recibos parciales (ANEXOS)
            $this->agregarRecibosParciales($prenda, $pedidoId);
        }
        unset($prenda);
    }

    /**
     * Carga las tallas con colores para una prenda específica
     */
    private function cargarTallasConColoresPrenda(array &$prenda, int $pedidoId): void
    {
        try {
            $tallasPorGenero = [
                'DAMA' => [],
                'CABALLERO' => [],
                'UNISEX' => []
            ];

            $tallasColores = DB::table('prenda_pedido_talla_colores as pptc')
                ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                ->where('ppt.prenda_pedido_id', $prenda['id'])
                ->select(['ppt.genero', 'ppt.talla', 'pptc.color_nombre', 'pptc.cantidad'])
                ->get();

            if ($tallasColores->count() > 0) {
                foreach ($tallasColores as $tallaColor) {
                    $genero = strtoupper((string)($tallaColor->genero ?? ''));
                    if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
                        $genero = 'CABALLERO';
                    }
                    $talla = (string)($tallaColor->talla ?? '');
                    $color = (string)($tallaColor->color_nombre ?? '');
                    $cantidad = (int)($tallaColor->cantidad ?? 0);

                    if ($talla === '' || $cantidad <= 0) {
                        continue;
                    }

                    if (!isset($tallasPorGenero[$genero][$talla])) {
                        $tallasPorGenero[$genero][$talla] = [];
                    }

                    $tallasPorGenero[$genero][$talla][] = [
                        'cantidad' => $cantidad,
                        'color' => $color !== '' ? $color : null,
                    ];
                }

                $prenda['tallas'] = $tallasPorGenero;
            }
        } catch (\Exception $e) {
            Log::warning('[ObtenerPedidoTransformadoUseCase] Error cargando tallas con color', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prenda['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Carga el estado de entrega de una prenda
     */
    private function cargarEstadoEntrega(array &$prenda, int $pedidoId): void
    {
        try {
            $entrega = \App\Models\PrendaEntrega::where('prenda_pedido_id', $prenda['id'])->first();
            $prenda['entrega'] = $entrega ? [
                'entregado' => $entrega->entregado,
                'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                'usuario' => $entrega->usuario?->name,
            ] : null;
        } catch (\Exception $e) {
            Log::warning('[ObtenerPedidoTransformadoUseCase] Error cargando estado entrega', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prenda['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Agrega recibos parciales (ANEXOS) a una prenda
     */
    private function agregarRecibosParciales(array &$prenda, int $pedidoId): void
    {
        try {
            $recibosParciales = DB::table('pedidos_parciales')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prenda['id'])
                ->orderBy('tipo_recibo', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($recibosParciales->isEmpty()) {
                return;
            }

            $anexosPorTipo = [];
            $procesosAdicionales = [];

            foreach ($recibosParciales as $reciboParcial) {
                $tipoRecibo = $reciboParcial->tipo_recibo;

                if (!isset($anexosPorTipo[$tipoRecibo])) {
                    $anexosPorTipo[$tipoRecibo] = 0;
                }
                $anexosPorTipo[$tipoRecibo]++;

                $numeroReciboAnexo = $reciboParcial->consecutivo_actual ?? $reciboParcial->numero_recibo ?? null;

                // Cargar tallas para el parcial
                $tallas = DB::table('pedidos_parciales_tallas')
                    ->where('pedido_parcial_id', $reciboParcial->id)
                    ->get();

                $tallasList = [];
                $talasTransformadas = [
                    'dama' => [],
                    'caballero' => [],
                    'unisex' => []
                ];

                foreach ($tallas as $talla) {
                    $tallasList[] = [
                        'talla' => $talla->talla,
                        'cantidad' => $talla->cantidad,
                        'genero' => $talla->genero ?? 'General'
                    ];

                    $genero = $this->normalizarGenero($talla->genero ?? 'caballero');
                    $talasTransformadas[$genero][$talla->talla] = $talla->cantidad;
                }

                $procesosAdicionales[] = [
                    'tipo_proceso' => $tipoRecibo,
                    'nombre_proceso' => $tipoRecibo . ' ANEXO ' . $anexosPorTipo[$tipoRecibo],
                    'estado' => $reciboParcial->estado ?? 'PENDIENTE',
                    'numero_recibo' => $numeroReciboAnexo,
                    'es_parcial' => true,
                    'numero_anexo' => $anexosPorTipo[$tipoRecibo],
                    'pedido_parcial_id' => $reciboParcial->id,
                    'tallas' => $tallasList,
                    'tallas_transformadas' => $talasTransformadas,
                    'created_at' => $reciboParcial->created_at,
                ];
            }

            if (!isset($prenda['procesos'])) {
                $prenda['procesos'] = [];
            }
            $prenda['procesos'] = array_merge($prenda['procesos'], $procesosAdicionales);

        } catch (\Exception $e) {
            Log::error('[ObtenerPedidoTransformadoUseCase] Error cargando recibos parciales', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prenda['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Transforma los EPPs con sus imágenes
     */
    private function transformarEpps(int $pedidoId): array
    {
        try {
            $pedido = PedidoProduccion::find($pedidoId);
            if (!$pedido || !$pedido->epps) {
                return [];
            }

            $eppsList = [];
            foreach ($pedido->epps as $pedidoEpp) {
                $epp = $pedidoEpp->epp;

                if (!$epp) {
                    Log::warning('[ObtenerPedidoTransformadoUseCase] EPP sin relación válida', [
                        'pedido_epp_id' => $pedidoEpp->id,
                    ]);
                    continue;
                }

                $imagenes = $this->obtenerImagenesEpp($pedidoEpp->id);

                $eppsList[] = [
                    'id' => $pedidoEpp->id,
                    'epp_id' => $pedidoEpp->epp_id,
                    'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                    'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                    'cantidad' => $pedidoEpp->cantidad ?? 0,
                    'observaciones' => $pedidoEpp->observaciones ?? '',
                    'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                    'imagenes' => $imagenes,
                ];
            }

            return $eppsList;

        } catch (\Exception $e) {
            Log::error('[ObtenerPedidoTransformadoUseCase] Error procesando EPPs', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtiene imágenes de un pedido_epp con rutas normalizadas
     */
    private function obtenerImagenesEpp(int $pedidoEppId): array
    {
        try {
            $imagenesData = DB::table('pedido_epp_imagenes')
                ->where('pedido_epp_id', $pedidoEppId)
                ->orderBy('orden', 'asc')
                ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);

            if ($imagenesData->isEmpty()) {
                return [];
            }

            $imagenes = [];
            foreach ($imagenesData as $img) {
                $ruta = $img->ruta_web ?? $img->ruta_original;

                if (empty($ruta)) {
                    continue;
                }

                if (!str_starts_with($ruta, '/storage/')) {
                    $ruta = str_starts_with($ruta, 'storage/') ? '/' . $ruta : '/storage/' . $ruta;
                }

                $imagenes[] = [
                    'ruta_webp' => $ruta,
                    'ruta_original' => $ruta,
                    'ruta_web' => $ruta,
                    'principal' => $img->principal ?? false,
                    'orden' => $img->orden ?? 0,
                ];
            }

            return $imagenes;

        } catch (\Exception $e) {
            Log::error('[ObtenerPedidoTransformadoUseCase] Error obtener imágenes de EPP', [
                'pedido_epp_id' => $pedidoEppId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Normaliza el género a una de las tres opciones válidas
     */
    private function normalizarGenero(?string $genero): string
    {
        $genero = strtolower($genero ?? 'caballero');
        if ($genero === 'dama') return 'dama';
        if ($genero === 'caballero') return 'caballero';
        return 'unisex';
    }
}
