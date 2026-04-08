<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerPedidoTransformadoResponse;
use App\Application\Pedidos\Exceptions\ObtenerPedidoTransformadoException;
use App\Application\Pedidos\Services\ProcesoPedidoEnricherService;
use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerPedidoTransformadoUseCase
 * Caso de uso que orquestra:
 * 1. Obtención del pedido base
 * 2. Enriquecimiento de procesos con tallas y observaciones
 * 3. Carga de tallas con colores y estado de entrega
 * 4. Agregación de recibos parciales (ANEXOS)
 * 5. Transformación de EPPs con imágenes
 * Responsabilidades:
 * - Centralizar la lógica de transformación del pedido
 * - Mantener el controller delgado (solo delegación)
 * - Facilitar testing y reutilización
 * Sigue el patrón DDD con separación de responsabilidades.
 */
class ObtenerPedidoTransformadoUseCase
{
    private ObtenerPedidoUseCase $obtenerPedidoUseCase;
    private ProcesoPedidoEnricherService $procesoPedidoEnricherService;
    private PedidoDetalleReadService $readService;

    public function __construct(
        ObtenerPedidoUseCase $obtenerPedidoUseCase,
        ProcesoPedidoEnricherService $procesoPedidoEnricherService,
        PedidoDetalleReadService $readService
    )
    {
        $this->obtenerPedidoUseCase = $obtenerPedidoUseCase;
        $this->procesoPedidoEnricherService = $procesoPedidoEnricherService;
        $this->readService = $readService;
    }

    /**
     * Ejecuta el caso de uso de obtención y transformación del pedido
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
            $this->enriquecerProcesos($datos);

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
            throw ObtenerPedidoTransformadoException::inesperado($e);
        }
    }

    /**
     * Enriquece los procesos con información de tallas y observaciones
     */
    private function enriquecerProcesos(array &$datos): void
    {
        $this->procesoPedidoEnricherService->enriquecer($datos);
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
            $pedido = $this->readService->findPedidoById($pedidoId);
            if ($pedido) {
                $fechaCreacion = $pedido->created_at;
                $fechaFormateada = date('d/m/Y');

                if ($fechaCreacion) {
                    $fechaFormateada = is_string($fechaCreacion)
                        ? $fechaCreacion
                        : $fechaCreacion->format('d/m/Y');
                }

                $datos['fecha_creacion'] = $fechaFormateada;
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

            $tallasColores = $this->readService->getTallasColoresPrenda((int) $prenda['id']);

            if ($tallasColores->isEmpty()) {
                return;
            }

            foreach ($tallasColores as $tallaColor) {
                $genero = $this->normalizarGeneroTallaColor($tallaColor->genero ?? null);
                [$talla, $color, $cantidad] = $this->extraerDatosTallaColor($tallaColor);

                if (!$this->esTallaColorValida($talla, $cantidad)) {
                    continue;
                }

                $tallasPorGenero[$genero][$talla][] = [
                    'cantidad' => $cantidad,
                    'color' => $color !== '' ? $color : null,
                ];
            }

            $prenda['tallas'] = $tallasPorGenero;
        } catch (\Exception $e) {
            Log::warning('[ObtenerPedidoTransformadoUseCase] Error cargando tallas con color', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prenda['id'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normaliza el genero de tallas con color para indexacion de respuesta.
     */
    private function normalizarGeneroTallaColor(?string $genero): string
    {
        $generoNormalizado = strtoupper((string) $genero);
        if (!in_array($generoNormalizado, ['DAMA', 'CABALLERO', 'UNISEX'], true)) {
            return 'CABALLERO';
        }

        return $generoNormalizado;
    }

    /**
     * @return array{0:string,1:string,2:int}
     */
    private function extraerDatosTallaColor(object $tallaColor): array
    {
        return [
            (string) ($tallaColor->talla ?? ''),
            (string) ($tallaColor->color_nombre ?? ''),
            (int) ($tallaColor->cantidad ?? 0),
        ];
    }

    private function esTallaColorValida(string $talla, int $cantidad): bool
    {
        return $talla !== '' && $cantidad > 0;
    }

    /**
     * Carga el estado de entrega de una prenda
     */
    private function cargarEstadoEntrega(array &$prenda, int $pedidoId): void
    {
        try {
            $entrega = $this->readService->findPrendaEntrega((int) $prenda['id']);
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
            $recibosParciales = $this->readService->getRecibosParcialesPrenda($pedidoId, (int) $prenda['id']);

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
                $tallas = $this->readService->getReciboParcialTallas((int) $reciboParcial->id);

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
            $pedido = $this->readService->findPedidoById($pedidoId);
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
            $imagenesData = $this->readService->getPedidoEppImagenes($pedidoEppId);

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
        if ($genero === 'dama') {
            return 'dama';
        }
        if ($genero === 'caballero') {
            return 'caballero';
        }
        return 'unisex';
    }
}
