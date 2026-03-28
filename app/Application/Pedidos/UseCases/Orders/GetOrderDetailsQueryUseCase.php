<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Application\Pedidos\Services\PedidoDescriptionService;
use App\Application\Orders\Services\OrderDescriptionService;
use App\Infrastructure\QueryServices\OrderDetailsQueryService;
use App\Models\LogoCotizacion;
use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenStatsService;

class GetOrderDetailsQueryUseCase
{
    public function __construct(
        private readonly OrderDetailsQueryService $orderDetailsQueryService,
        private readonly RegistroOrdenStatsService $statsService,
        private readonly PedidoDescriptionService $pedidoDescriptionService,
        private readonly OrderDescriptionService $orderDescriptionService,
    ) {}

    /**
     * Mantiene la respuesta JSON actual del endpoint show().
     *
     * @return array{status:int,data:array}
     */
    public function execute(string $pedido): array
    {
        $logoPedido = null;

        // Verificar si la tabla LogoPedido existe antes de consultarla
        try {
            if (!$this->orderDetailsQueryService->logoPedidosTableExists()) {
                $logoPedido = null;
            } else {
                // Primero, intentar buscar en LogoPedido
                $logoPedido = $this->orderDetailsQueryService->findLogoPedidoByNumeroPedido($pedido);
            }
        } catch (\Exception $e) {
            $logoPedido = null;
        }

        if ($logoPedido) {
            $logoPedidoArray = $logoPedido->toArray();

            // PASO 1: Intentar completar desde PedidoProduccion
            if ($logoPedido->pedido_id) {
                try {
                    $pedidoProd = PedidoProduccion::with('asesora', 'prendas')->find($logoPedido->pedido_id);

                    if ($pedidoProd) {
                        \Log::info(' Encontrado PedidoProduccion, completando datos', [
                            'pedido_id' => $logoPedido->pedido_id,
                            'cliente' => $pedidoProd->cliente,
                            'asesora' => $pedidoProd->asesora?->name,
                            'fecha' => $pedidoProd->created_at,
                        ]);

                        // Completar desde el pedido de producción - SIEMPRE si viene vacío
                        if (empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-') {
                            $logoPedidoArray['cliente'] = $pedidoProd->cliente ?? '-';
                            \Log::info(' [PASO 1] Cliente completado desde PedidoProduccion', ['cliente' => $logoPedidoArray['cliente']]);
                        }
                        if (empty($logoPedidoArray['asesora']) || $logoPedidoArray['asesora'] === '-') {
                            $asesoraName = $pedidoProd->asesora?->name ?? $pedidoProd->asesor?->name ?? '-';
                            $logoPedidoArray['asesora'] = $asesoraName;
                            \Log::info(' [PASO 1] Asesora completada desde PedidoProduccion', ['asesora' => $logoPedidoArray['asesora']]);
                        }
                        if (empty($logoPedidoArray['created_at'])) {
                            $logoPedidoArray['created_at'] = $pedidoProd->created_at;
                            \Log::info(' [PASO 1] Fecha completada desde PedidoProduccion', ['fecha' => $logoPedidoArray['created_at']]);
                        }
                        if (empty($logoPedidoArray['descripcion']) && $pedidoProd->descripcion_prendas) {
                            $logoPedidoArray['descripcion'] = $this->pedidoDescriptionService->generatePrendasDescription($pedidoProd);
                            \Log::info(' [PASO 1] Descripción completada desde PedidoProduccion');
                        }
                    } else {
                        \Log::warning(' [PASO 1] PedidoProduccion no encontrado', ['pedido_id' => $logoPedido->pedido_id]);
                    }
                } catch (\Exception $e) {
                    \Log::error(' [PASO 1] Error al buscar PedidoProduccion', ['error' => $e->getMessage()]);
                }
            }

            // PASO 2: Si aún falta info, intentar desde LogoCotizacion
            if ($logoPedido->logo_cotizacion_id && (empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-')) {
                try {
                    $logoCot = LogoCotizacion::with('cotizacion')->find($logoPedido->logo_cotizacion_id);

                    if ($logoCot && $logoCot->cotizacion) {
                        \Log::info(' Encontrado LogoCotizacion, completando datos', [
                            'cliente' => $logoCot->cotizacion->cliente,
                            'fecha' => $logoCot->cotizacion->fecha_de_creacion,
                        ]);

                        if (empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-') {
                            $logoPedidoArray['cliente'] = $logoCot->cotizacion->cliente ?? '-';
                            \Log::info(' [PASO 2] Cliente completado desde LogoCotizacion', ['cliente' => $logoPedidoArray['cliente']]);
                        }
                        if (empty($logoPedidoArray['created_at'])) {
                            $logoPedidoArray['created_at'] = $logoCot->cotizacion->fecha_de_creacion;
                            \Log::info(' [PASO 2] Fecha completada desde LogoCotizacion', ['fecha' => $logoPedidoArray['created_at']]);
                        }
                        if (empty($logoPedidoArray['asesora']) || $logoPedidoArray['asesora'] === '-') {
                            $logoPedidoArray['asesora'] = $logoCot->cotizacion->asesor?->name ?? '-';
                            \Log::info(' [PASO 2] Asesora completada desde LogoCotizacion', ['asesora' => $logoPedidoArray['asesora']]);
                        }
                        if (empty($logoPedidoArray['descripcion']) && $logoCot->descripcion) {
                            $logoPedidoArray['descripcion'] = $logoCot->descripcion;
                            \Log::info(' [PASO 2] Descripción completada desde LogoCotizacion');
                        }
                    } else {
                        \Log::warning(' [PASO 2] LogoCotizacion no encontrado o sin cotización', ['logo_cotizacion_id' => $logoPedido->logo_cotizacion_id]);
                    }
                } catch (\Exception $e) {
                    \Log::error(' [PASO 2] Error al buscar LogoCotizacion', ['error' => $e->getMessage()]);
                }
            }

            // PASO 3: Asegurar valores finales
            $logoPedidoArray['numero_pedido'] = $logoPedido->numero_pedido ?? $pedido;
            $logoPedidoArray['cliente'] = $logoPedidoArray['cliente'] ?: '-';
            $logoPedidoArray['asesora'] = $logoPedidoArray['asesora'] ?: '-';
            $logoPedidoArray['descripcion'] = $logoPedido->descripcion ?? '';

            //  IMPORTANTE: Si no hay created_at, usar created_at
            if (empty($logoPedidoArray['created_at'])) {
                $logoPedidoArray['created_at'] = $logoPedido->created_at ?? now();
                \Log::info(' [PASO 3] Fecha asignada desde created_at', ['fecha' => $logoPedidoArray['created_at']]);
            }

            $logoPedidoArray['encargado_orden'] = $logoPedido->encargado_orden ?? '-';
            $logoPedidoArray['forma_de_pago'] = $logoPedido->forma_de_pago ?? '-';
            $logoPedidoArray['observaciones'] = $logoPedido->observaciones ?? '';
            $logoPedidoArray['estado'] = $logoPedido->estado ?? '-';
            $logoPedidoArray['area'] = $logoPedido->area ?? '-';
            $logoPedidoArray['tecnicas'] = $logoPedido->tecnicas ?? [];
            $logoPedidoArray['ubicaciones'] = $logoPedido->ubicaciones ?? [];
            $logoPedidoArray['prendas'] = $logoPedido->prendas ?? [];

            // Campos de identificación
            $logoPedidoArray['es_cotizacion'] = false;
            $logoPedidoArray['es_logo_pedido'] = true;

            \Log::info(' [RegistroOrdenQueryController::show] LogoPedido finalizado COMPLETAMENTE', [
                'numero_pedido' => $logoPedidoArray['numero_pedido'],
                'cliente' => $logoPedidoArray['cliente'],
                'asesora' => $logoPedidoArray['asesora'],
                'descripcion' => $logoPedidoArray['descripcion'],
                'created_at' => $logoPedidoArray['created_at'],
                'forma_de_pago' => $logoPedidoArray['forma_de_pago'],
                'encargado_orden' => $logoPedidoArray['encargado_orden'],
            ]);

            return ['status' => 200, 'data' => $logoPedidoArray];
        }

        // Si no es LogoPedido, buscar en PedidoProduccion
        $order = $this->orderDetailsQueryService->findPedidoProduccionByNumeroPedidoOrFail($pedido);

        // Obtener estadísticas mediante servicio
        $stats = $this->statsService->getOrderStats($pedido);
        $order->total_cantidad = $stats['total_cantidad'];
        $order->total_entregado = $stats['total_entregado'];

        $prendasConRelaciones = $this->orderDetailsQueryService->getPrendasConRelaciones((int) $order->id);

        \Log::info(' [show] Prendas cargadas con relaciones', [
            'pedido' => $pedido,
            'total' => $prendasConRelaciones->count(),
            'primera_prenda' => $prendasConRelaciones->first() ? [
                'nombre' => $prendasConRelaciones->first()->nombre_prenda,
                'fotos_loaded' => $prendasConRelaciones->first()->relationLoaded('fotos'),
                'tallas_loaded' => $prendasConRelaciones->first()->relationLoaded('tallas'),
                'variantes_loaded' => $prendasConRelaciones->first()->relationLoaded('variantes'),
                'variantes_count' => $prendasConRelaciones->first()->variantes ? $prendasConRelaciones->first()->variantes->count() : 0,
                'procesos_loaded' => $prendasConRelaciones->first()->relationLoaded('procesos'),
            ] : 'N/A',
        ]);

        // Reemplazar prendas en la orden con las que tienen relaciones
        $order->setRelation('prendas', $prendasConRelaciones);

        //  CONSTRUIR DESCRIPCIÓN MIENTRAS AÚN TENEMOS ACCESO A RELACIONES ELOQUENT
        $descripcionPrendas = $this->orderDescriptionService->buildDescripcionConTallas($order);

        \Log::info(' [show] Descripción construida', [
            'longitud' => strlen($descripcionPrendas),
            'primeras_200_caracteres' => substr($descripcionPrendas, 0, 200),
            'contiene_font_size_15' => strpos($descripcionPrendas, 'font-size: 15px') !== false,
            'contiene_important' => strpos($descripcionPrendas, '!important') !== false,
            'HTML_completo' => $descripcionPrendas,
        ]);

        // Filtrar datos sensibles
        $orderArray = $order->toArray();

        // Verificar si es una cotización
        $esCotizacion = !empty($order->cotizacion_id);
        $orderArray['es_cotizacion'] = $esCotizacion;

        // Campos que se ocultan para todos
        $camposOcultosGlobal = ['created_at', 'updated_at', 'deleted_at', 'asesor_id', 'cliente_id'];

        // Campos que se ocultan para no-asesores
        $camposOcultosNoAsesor = ['cotizacion_id', 'numero_cotizacion'];

        // Agregar nombres en lugar de IDs
        if ($order->asesora) {
            $orderArray['asesor'] = $order->asesora->name ?? '';
            $orderArray['asesora'] = $order->asesora->name ?? '';
        } else {
            $orderArray['asesor'] = '';
            $orderArray['asesora'] = '';
        }

        // Para cliente, usar el campo 'cliente' directo (que es el nombre del cliente en la tabla)
        if (!empty($orderArray['cliente_id'])) {
            $orderArray['cliente_nombre'] = $this->orderDetailsQueryService->findClienteNombreById(
                (int) $orderArray['cliente_id'],
                (string) ($orderArray['cliente'] ?? '')
            );
        } else {
            $orderArray['cliente_nombre'] = $orderArray['cliente'] ?? '';
        }

        // Agregar la descripción ya construida
        $orderArray['descripcion_prendas'] = $descripcionPrendas;

        // Obtener prendas formateadas para el modal
        \Log::info(' [getOrderDetails] Obteniendo prendas para pedido', [
            'pedido' => $pedido,
            'es_cotizacion' => $esCotizacion,
        ]);

        try {
            $prendas = $order->prendas;

            $normalize = $this->normalizer();

            $prendasFormato = [];
            foreach ($prendas as $index => $prenda) {
                //  NUEVO: Normalizar fotos de prenda (WebP)
                $fotosNormalizadas = [];
                if ($prenda->fotos) {
                    foreach ($prenda->fotos as $foto) {
                        $ruta = $foto->ruta_webp ?? $foto->ruta_original;
                        $fotosNormalizadas[] = $normalize($ruta);
                    }
                }

                //  NUEVO: Normalizar fotos de tela (WebP)
                $telaFotosNormalizadas = [];
                try {
                    $fotosTelaDB = $this->orderDetailsQueryService->getFotosTelaByPrendaId((int) $prenda->id);
                    foreach ($fotosTelaDB as $fotoTela) {
                        $ruta = $fotoTela->ruta_webp ?? $fotoTela->ruta_original;
                        $telaFotosNormalizadas[] = $normalize($ruta);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error cargando fotos de tela para prenda ' . $prenda->id . ': ' . $e->getMessage());
                }

                $prendasFormato[] = [
                    'id' => $prenda->id,
                    'prenda_pedido_id' => $prenda->id,
                    'numero' => $index + 1,
                    'nombre' => $prenda->nombre_prenda ?? '-',
                    'nombre_prenda' => $prenda->nombre_prenda ?? '-',
                    'descripcion' => $prenda->descripcion ?? '-',
                    'tallas' => $prenda->tallas ? $prenda->tallas->map(function ($t) {
                        return "{$t->talla}:{$t->cantidad}";
                    })->implode(', ') : '-',
                    'fotos' => $fotosNormalizadas,
                    'tela_fotos' => $telaFotosNormalizadas,
                ];
            }

            \Log::info(' [getOrderDetails] Prendas formateadas', [
                'pedido' => $pedido,
                'total_prendas' => count($prendasFormato),
                'primera_prenda' => $prendasFormato[0] ?? null,
            ]);

            $orderArray['prendas'] = $prendasFormato;
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo prendas: ' . $e->getMessage());
            $orderArray['prendas'] = [];
        }

        foreach ($camposOcultosGlobal as $campo) {
            unset($orderArray[$campo]);
        }

        if (!auth()->user() || !auth()->user()->role || auth()->user()->role->name !== 'asesor') {
            foreach ($camposOcultosNoAsesor as $campo) {
                unset($orderArray[$campo]);
            }
        }

        return ['status' => 200, 'data' => $orderArray];
    }

    private function normalizer(): \Closure
    {
        return function ($ruta) {
            if (empty($ruta)) {
                return null;
            }
            if (str_starts_with($ruta, 'http')) {
                return $ruta;
            }
            if (str_starts_with($ruta, '/storage/')) {
                return $ruta;
            }
            return '/storage/' . ltrim($ruta, '/');
        };
    }
}

