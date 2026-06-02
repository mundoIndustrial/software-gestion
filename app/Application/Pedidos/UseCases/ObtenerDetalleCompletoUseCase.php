<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerDetalleCompletoResponse;
use App\Application\Pedidos\Exceptions\ObtenerDetalleCompletoException;
use App\Application\Pedidos\Services\PedidoAuthorizationService;
use App\Application\Pedidos\Services\PedidoFiltroService;
use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerDetalleCompletoUseCase
 * Caso de uso para obtener datos completos de un pedido para recibos.
 * Con filtrado especial por rol:
 * - Bodeguero: solo procesos COSTURA-BODEGA
 * - Insumos: solo prendas con de_bodega=false
 * Responsabilidades:
 * - Obtener el pedido (búsqueda por ID o número)
 * - Validaciones de autorización
 * - Enriquecimiento de procesos y prendas
 * - Aplicar filtros de rol
 * - Cargar ancho/metraje y consecutivos
 * Orquesta los servicios de autorización y filtrado.
 */
class ObtenerDetalleCompletoUseCase
{
    private ObtenerPedidoUseCase $obtenerPedidoUseCase;
    private PedidoAuthorizationService $authService;
    private PedidoFiltroService $filtroService;
    private PedidoDetalleReadService $readService;

    public function __construct(
        ObtenerPedidoUseCase $obtenerPedidoUseCase,
        PedidoAuthorizationService $authService,
        PedidoFiltroService $filtroService,
        PedidoDetalleReadService $readService
    ) {
        $this->obtenerPedidoUseCase = $obtenerPedidoUseCase;
        $this->authService = $authService;
        $this->filtroService = $filtroService;
        $this->readService = $readService;
    }

    /**
     * Ejecuta el caso de uso
     * @param int $idONumero ID del pedido o número de pedido
     * @param bool $filtrarProcesosPendientes Si true, oculta procesos PENDIENTES
     * @return ObtenerDetalleCompletoResponse
     * @throws ObtenerDetalleCompletoException Si el pedido no existe o el usuario no tiene permisos
     */
    public function ejecutar(int $idONumero, bool $filtrarProcesosPendientes = false): ObtenerDetalleCompletoResponse
    {
        try {
            // 1. Obtener el pedido
            $pedido = $this->obtenerPedido($idONumero);

            // 2. Validar autorización
            $this->validarAutorizacion($pedido);

            // 3. Obtener datos base del pedido
            $response = $this->obtenerPedidoUseCase->ejecutar($pedido->id, $filtrarProcesosPendientes);
            $responseData = $response->toArray();

            // 4. Enriquecer procesos
            $this->enriquecerProcesos($responseData);

            // 5. Aplicar filtro bodeguero si corresponde
            $this->aplicarFiltrosPorRol($pedido, $responseData);

            // 7. Agregar ancho/metraje y consecutivos por prenda
            $this->agregarAnchosMetrajesYConsecutivos($pedido, $responseData);

            // 8. Agregar ancho/metraje general
            $this->agregarAnchoMetrajeGeneral($pedido, $responseData);

            // 9. Agregar datos adicionales del pedido
            $this->agregarDatosAdicionalesPedido($pedido, $responseData);

            return new ObtenerDetalleCompletoResponse($responseData);

        } catch (ObtenerDetalleCompletoException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('[ObtenerDetalleCompletoUseCase] Error inesperado', [
                'id_numero' => $idONumero,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw ObtenerDetalleCompletoException::inesperado($e);
        }
    }

    /**
     * Obtiene el pedido por ID o número de pedido
     */
    private function validarAutorizacion(PedidoProduccion $pedido): void
    {
        $this->lanzarSiHayError($this->authService->validarAccesoBodeguero($pedido));
    }

    private function aplicarFiltrosPorRol(PedidoProduccion $pedido, array &$responseData): void
    {
        if ($this->authService->esBodeguero()) {
            $this->lanzarSiHayError(
                $this->filtroService->filtrarParaBodeguero($pedido->id, $responseData)
            );
        }

        if ($this->authService->debeAplicarFiltroInsumos()) {
            $this->lanzarSiHayError(
                $this->filtroService->filtrarParaInsumos($pedido->id, $responseData)
            );
        }
    }

    private function lanzarSiHayError(?string $error): void
    {
        if ($error) {
            throw ObtenerDetalleCompletoException::validacion($error);
        }
    }

    private function obtenerPedido(int $idONumero): PedidoProduccion
    {
        $pedido = $this->readService->findPedidoByIdOrNumero($idONumero);

        if (!$pedido) {
            throw ObtenerDetalleCompletoException::pedidoNoEncontrado($idONumero);
        }

        return $pedido;
    }

    /**
     * Enriquece los procesos con ubicaciones y observaciones por talla
     */
    private function enriquecerProcesos(array &$responseData): void
    {
        if (!$this->tienePrendas($responseData)) {
            return;
        }

        foreach ($responseData['prendas'] as &$prendaProc) {
            $this->enriquecerProcesosDePrenda($prendaProc);
        }
        unset($prendaProc);
    }

    private function tienePrendas(array $responseData): bool
    {
        return isset($responseData['prendas']) && is_array($responseData['prendas']);
    }

    private function enriquecerProcesosDePrenda(array &$prendaProc): void
    {
        if (!isset($prendaProc['procesos']) || !is_array($prendaProc['procesos'])) {
            return;
        }

        foreach ($prendaProc['procesos'] as &$procesoProc) {
            $this->enriquecerProceso($procesoProc);
        }
        unset($procesoProc);
    }

    private function enriquecerProceso(array &$procesoProc): void
    {
        if (!isset($procesoProc['id'])) {
            return;
        }

        $this->decodificarUbicaciones($procesoProc);
        $this->cargarTallasDetalle($procesoProc);

        if ($this->usaModoTallasGeneral($procesoProc)) {
            $this->cargarObservacionesPorTalla($procesoProc);
        }
    }

    private function decodificarUbicaciones(array &$procesoProc): void
    {
        $ubicaciones = $procesoProc['ubicaciones'] ?? null;

        if (!is_string($ubicaciones)) {
            return;
        }

        $decodedUb = json_decode($ubicaciones, true);

        if (is_array($decodedUb)) {
            $procesoProc['ubicaciones_array'] = $decodedUb;
        }
    }

    private function usaModoTallasGeneral(array $procesoProc): bool
    {
        return ($procesoProc['modo_tallas'] ?? null) === 'general';
    }

    /**
     * Carga tallas_detalle con información completa
     */
    private function cargarTallasDetalle(array &$proceso): void
    {
        try {
            $tallas = $this->readService->getProcesoTallasDetalle((int) $proceso['id']);

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
            $tallasObs = $this->readService->getProcesoTallasConObservaciones((int) $proceso['id']);

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
                // Cargar consecutivos/recibos
                $prenda['recibos'] = $this->obtenerConsecutivosPrenda($pedido->id, $prendaId);
                $prenda['consecutivos'] = $prenda['recibos'];

                $numeroReciboUnico = $this->extraerNumeroReciboUnico($prenda['recibos']);

                // Cargar ancho/metraje solo si hay un recibo inequívoco
                $this->cargarAnchometrajePrenda($pedido->id, $prendaId, $prenda, $numeroReciboUnico);

                // Cargar estado de entrega para reflejar correctamente el boton Entregar/Deshacer.
                $entrega = $this->readService->findPrendaEntrega((int) $prendaId);
                $estadoEntrega = $this->readService->getPrendaEntregaEstado((int) $prendaId);
                $prenda['entrega'] = $entrega ? [
                    'entregado' => (bool) $entrega->entregado,
                    'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                    'usuario' => $entrega->usuario?->name,
                    'estado_entrega' => $estadoEntrega['estado_entrega'],
                    'total_recibos' => $estadoEntrega['total_recibos'],
                    'recibos_entregados' => $estadoEntrega['recibos_entregados'],
                    'completa' => $estadoEntrega['completa'],
                ] : $estadoEntrega;
            } else {
                $prenda['ancho_metraje'] = null;
                $prenda['recibos'] = null;
                $prenda['entrega'] = null;
            }
        }
        unset($prenda);
    }

    /**
     * Carga ancho/metraje para una prenda específica
     */
    private function cargarAnchometrajePrenda(int $pedidoId, int $prendaId, array &$prenda, ?int $numeroRecibo = null): void
    {
        try {
            $ancho = $this->readService->getAnchoPrenda($pedidoId, $prendaId, $numeroRecibo);

            if ($ancho) {
                $metrajes = $this->readService->getMetrajesPrenda($pedidoId, $prendaId, $numeroRecibo);

                $prenda['ancho_metraje'] = [
                    'ancho' => $ancho->ancho,
                    'numero_recibo' => $numeroRecibo,
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
     * Extrae un numero de recibo unico si la prenda tiene un solo recibo inequívoco.
     */
    private function extraerNumeroReciboUnico($recibos): ?int
    {
        if (!$recibos || !is_iterable($recibos)) {
            return null;
        }

        $numeros = [];

        foreach ($recibos as $clave => $recibo) {
            if ($clave === 'parciales' && is_iterable($recibo)) {
                foreach ($recibo as $parcial) {
                    $numero = (int) data_get($parcial, 'consecutivo_actual', data_get($parcial, 'numero_recibo', 0));
                    if ($numero > 0) {
                        $numeros[$numero] = true;
                    }
                }
                continue;
            }

            if (is_array($recibo)) {
                $numero = (int) data_get($recibo, 'consecutivo_actual', data_get($recibo, 'numero_recibo', 0));
                if ($numero > 0) {
                    $numeros[$numero] = true;
                }
            }
        }

        $numeros = array_keys($numeros);

        return count($numeros) === 1 ? (int) $numeros[0] : null;
    }

    /**
     * Obtiene consecutivos y recibos parciales para una prenda
     */
    private function obtenerConsecutivosPrenda(int $pedidoId, int $prendaId): ?array
    {
        try {
            $consecutivos = $this->readService->getConsecutivosPrenda($pedidoId, $prendaId);
            $parciales = $this->readService->getParcialesPrenda($pedidoId, $prendaId);

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
                if (!array_key_exists($c->tipo_recibo, $recibos)) {
                    continue;
                }

                // Si el registro viene marcado como ANEXO, no debe poblar recibos base por tipo.
                // Los anexos se representan por separado en $recibos['parciales'].
                $origen = strtoupper((string) ($c->origen_recibo ?? 'BASE'));
                if ($origen === 'ANEXO') {
                    continue;
                }

                // Conservar el mejor candidato (el primero por orden updated_at desc/id desc).
                if ($recibos[$c->tipo_recibo] === null) {
                    $recibos[$c->tipo_recibo] = [
                        'id' => $c->id,
                        'tipo_recibo' => $c->tipo_recibo,
                        'consecutivo_actual' => $c->consecutivo_actual,
                        'activo' => $c->activo,
                        'estado' => $c->estado,
                        'area' => $c->area,
                        'origen_recibo' => $c->origen_recibo ?? 'BASE',
                        // Fecha de activación visible en recibo dinámico
                        // (consecutivos_recibos_pedidos.created_at).
                        'created_at' => $c->created_at,
                        'updated_at' => $c->updated_at,
                    ];
                }
            }

            $recibos['parciales'] = $parciales->map(function ($parcial) {
                $row = (array) $parcial;
                $tipoRecibo = strtoupper((string) ($row['tipo_recibo'] ?? ''));

                // Normalizar anexos de costura: aunque se persistan como COSTURA-BODEGA,
                // en UI y flujo de recibos deben tratarse como COSTURA.
                if ($tipoRecibo === 'COSTURA-BODEGA') {
                    $row['tipo_recibo_original'] = 'COSTURA-BODEGA';
                    $row['tipo_recibo'] = 'COSTURA';
                }

                return $row;
            })->values()->toArray();

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
     * Agrega datos adicionales del pedido (fecha estimada, área, día entrega).
     *
     * Para el resumen del pedido se debe mostrar la fecha estimada global del pedido
     * (que representa la fecha más lejana entre sus recibos).
     */
    private function agregarDatosAdicionalesPedido(PedidoProduccion $pedido, array &$responseData): void
    {
        $fechaMasLejanaRecibos = $this->readService->getFechaEstimadaMasLejanaByPedidoId((int) $pedido->id);
        if (!isset($responseData['fecha_estimada_de_entrega'])) {
            $responseData['fecha_estimada_de_entrega'] = $fechaMasLejanaRecibos
                ?? $pedido->fecha_estimada_de_entrega;
        }

        if (!isset($responseData['area'])) {
            $responseData['area'] = $pedido->area;
        }

        if (!isset($responseData['dia_de_entrega'])) {
            $responseData['dia_de_entrega'] = $pedido->dia_de_entrega;
        }

        // Datos de aprobacion para resumen en modal (registros)
        $responseData['created_at'] = $pedido->created_at?->format('Y-m-d H:i:s');
        $responseData['aprobado_por_cartera_en'] = $pedido->aprobado_por_cartera_en
            ? \Carbon\Carbon::parse($pedido->aprobado_por_cartera_en)->format('Y-m-d H:i:s')
            : null;
        $responseData['aprobado_por_supervisor_en'] = $pedido->aprobado_por_supervisor_en
            ? \Carbon\Carbon::parse($pedido->aprobado_por_supervisor_en)->format('Y-m-d H:i:s')
            : null;

        $responseData['cartera_nombre'] = null;
        if (!empty($pedido->aprobado_por_usuario_cartera)) {
            $responseData['cartera_nombre'] = User::query()
                ->where('id', (int) $pedido->aprobado_por_usuario_cartera)
                ->value('name');
        }

        Log::info('[ObtenerDetalleCompletoUseCase] Datos del pedido agregados', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'dia_de_entrega' => $responseData['dia_de_entrega'] ?? null,
            'fecha_estimada_de_entrega' => $responseData['fecha_estimada_de_entrega'] ?? null,
            'fecha_mas_lejana_recibos' => $fechaMasLejanaRecibos,
            'aprobado_por_cartera_en' => $responseData['aprobado_por_cartera_en'],
            'aprobado_por_supervisor_en' => $responseData['aprobado_por_supervisor_en'],
            'cartera_nombre' => $responseData['cartera_nombre'],
        ]);
    }
}
