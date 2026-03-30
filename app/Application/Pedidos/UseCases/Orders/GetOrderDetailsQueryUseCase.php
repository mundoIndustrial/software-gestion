<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Application\Orders\Services\OrderDescriptionService;
use App\Application\Pedidos\Services\PedidoDescriptionService;
use App\Infrastructure\QueryServices\OrderDetailsQueryService;
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
        $logoPedido = $this->tryGetLogoPedido($pedido);
        if ($logoPedido) {
            return ['status' => 200, 'data' => $this->buildLogoPedidoData($logoPedido, $pedido)];
        }

        return ['status' => 200, 'data' => $this->buildPedidoProduccionData($pedido)];
    }

    private function tryGetLogoPedido(string $pedido): mixed
    {
        try {
            if (!$this->orderDetailsQueryService->logoPedidosTableExists()) {
                return null;
            }

            return $this->orderDetailsQueryService->findLogoPedidoByNumeroPedido($pedido);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function buildLogoPedidoData(mixed $logoPedido, string $pedido): array
    {
        $logoPedidoArray = $logoPedido->toArray();

        $this->completeLogoPedidoFromPedidoProduccion($logoPedido, $logoPedidoArray);
        $this->completeLogoPedidoFromLogoCotizacion($logoPedido, $logoPedidoArray);

        return $this->finalizeLogoPedidoData($logoPedido, $logoPedidoArray, $pedido);
    }

    private function completeLogoPedidoFromPedidoProduccion(mixed $logoPedido, array &$logoPedidoArray): void
    {
        if (!$logoPedido->pedido_id) {
            return;
        }

        try {
            $pedidoProd = $this->orderDetailsQueryService->findPedidoProduccionByIdWithRelations((int) $logoPedido->pedido_id);

            if (!$pedidoProd) {
                \Log::warning(' [PASO 1] PedidoProduccion no encontrado', ['pedido_id' => $logoPedido->pedido_id]);
                return;
            }

            \Log::info(' Encontrado PedidoProduccion, completando datos', [
                'pedido_id' => $logoPedido->pedido_id,
                'cliente' => $pedidoProd->cliente,
                'asesora' => $pedidoProd->asesora?->name,
                'fecha' => $pedidoProd->created_at,
            ]);

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
                \Log::info(' [PASO 1] Descripcion completada desde PedidoProduccion');
            }
        } catch (\Exception $e) {
            \Log::error(' [PASO 1] Error al buscar PedidoProduccion', ['error' => $e->getMessage()]);
        }
    }

    private function completeLogoPedidoFromLogoCotizacion(mixed $logoPedido, array &$logoPedidoArray): void
    {
        $clienteVacio = empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-';
        if (!$logoPedido->logo_cotizacion_id || !$clienteVacio) {
            return;
        }

        try {
            $logoCot = $this->orderDetailsQueryService->findLogoCotizacionByIdWithCotizacion((int) $logoPedido->logo_cotizacion_id);

            if (!$logoCot || !$logoCot->cotizacion) {
                \Log::warning(' [PASO 2] LogoCotizacion no encontrado o sin cotizacion', ['logo_cotizacion_id' => $logoPedido->logo_cotizacion_id]);
                return;
            }

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
                \Log::info(' [PASO 2] Descripcion completada desde LogoCotizacion');
            }
        } catch (\Exception $e) {
            \Log::error(' [PASO 2] Error al buscar LogoCotizacion', ['error' => $e->getMessage()]);
        }
    }

    private function finalizeLogoPedidoData(mixed $logoPedido, array $logoPedidoArray, string $pedido): array
    {
        $logoPedidoArray['numero_pedido'] = $logoPedido->numero_pedido ?? $pedido;
        $logoPedidoArray['cliente'] = $logoPedidoArray['cliente'] ?: '-';
        $logoPedidoArray['asesora'] = $logoPedidoArray['asesora'] ?: '-';
        $logoPedidoArray['descripcion'] = $logoPedido->descripcion ?? '';

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

        return $logoPedidoArray;
    }

    private function buildPedidoProduccionData(string $pedido): array
    {
        $order = $this->orderDetailsQueryService->findPedidoProduccionByNumeroPedidoOrFail($pedido);

        $stats = $this->statsService->getOrderStats($pedido);
        $order->total_cantidad = $stats['total_cantidad'];
        $order->total_entregado = $stats['total_entregado'];

        $prendasConRelaciones = $this->orderDetailsQueryService->getPrendasConRelaciones((int) $order->id);
        $this->logPrendasConRelaciones($pedido, $prendasConRelaciones);

        $order->setRelation('prendas', $prendasConRelaciones);

        $descripcionPrendas = $this->orderDescriptionService->buildDescripcionConTallas($order);
        \Log::info(' [show] Descripcion construida', [
            'longitud' => strlen($descripcionPrendas),
            'primeras_200_caracteres' => substr($descripcionPrendas, 0, 200),
            'contiene_font_size_15' => strpos($descripcionPrendas, 'font-size: 15px') !== false,
            'contiene_important' => strpos($descripcionPrendas, '!important') !== false,
            'HTML_completo' => $descripcionPrendas,
        ]);

        $orderArray = $this->mapOrderData($order, $descripcionPrendas);
        $orderArray['prendas'] = $this->formatPrendasForModal($order, $pedido, (bool) $orderArray['es_cotizacion']);
        $this->hideSensitiveFields($orderArray);

        return $orderArray;
    }

    private function logPrendasConRelaciones(string $pedido, mixed $prendasConRelaciones): void
    {
        $primeraPrenda = $prendasConRelaciones->first();
        $variantesCount = 0;
        if ($primeraPrenda && $primeraPrenda->variantes) {
            $variantesCount = $primeraPrenda->variantes->count();
        }

        $primeraPrendaLog = 'N/A';
        if ($primeraPrenda) {
            $primeraPrendaLog = [
                'nombre' => $primeraPrenda->nombre_prenda,
                'fotos_loaded' => $primeraPrenda->relationLoaded('fotos'),
                'tallas_loaded' => $primeraPrenda->relationLoaded('tallas'),
                'variantes_loaded' => $primeraPrenda->relationLoaded('variantes'),
                'variantes_count' => $variantesCount,
                'procesos_loaded' => $primeraPrenda->relationLoaded('procesos'),
            ];
        }

        \Log::info(' [show] Prendas cargadas con relaciones', [
            'pedido' => $pedido,
            'total' => $prendasConRelaciones->count(),
            'primera_prenda' => $primeraPrendaLog,
        ]);
    }

    private function mapOrderData(mixed $order, string $descripcionPrendas): array
    {
        $orderArray = $order->toArray();
        $orderArray['es_cotizacion'] = !empty($order->cotizacion_id);

        if ($order->asesora) {
            $orderArray['asesor'] = $order->asesora->name ?? '';
            $orderArray['asesora'] = $order->asesora->name ?? '';
        } else {
            $orderArray['asesor'] = '';
            $orderArray['asesora'] = '';
        }

        if (!empty($orderArray['cliente_id'])) {
            $orderArray['cliente_nombre'] = $this->orderDetailsQueryService->findClienteNombreById(
                (int) $orderArray['cliente_id'],
                (string) ($orderArray['cliente'] ?? '')
            );
        } else {
            $orderArray['cliente_nombre'] = $orderArray['cliente'] ?? '';
        }

        $orderArray['descripcion_prendas'] = $descripcionPrendas;

        return $orderArray;
    }

    private function formatPrendasForModal(mixed $order, string $pedido, bool $esCotizacion): array
    {
        \Log::info(' [getOrderDetails] Obteniendo prendas para pedido', [
            'pedido' => $pedido,
            'es_cotizacion' => $esCotizacion,
        ]);

        try {
            $normalize = $this->normalizer();
            $prendasFormato = [];

            foreach ($order->prendas as $index => $prenda) {
                $prendasFormato[] = $this->formatSinglePrenda($prenda, (int) $index, $normalize);
            }

            \Log::info(' [getOrderDetails] Prendas formateadas', [
                'pedido' => $pedido,
                'total_prendas' => count($prendasFormato),
                'primera_prenda' => $prendasFormato[0] ?? null,
            ]);

            return $prendasFormato;
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo prendas: ' . $e->getMessage());
            return [];
        }
    }

    private function formatSinglePrenda(mixed $prenda, int $index, \Closure $normalize): array
    {
        $fotosNormalizadas = [];
        if ($prenda->fotos) {
            foreach ($prenda->fotos as $foto) {
                $ruta = $foto->ruta_webp ?? $foto->ruta_original;
                $fotosNormalizadas[] = $normalize($ruta);
            }
        }

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

        return [
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

    private function hideSensitiveFields(array &$orderArray): void
    {
        $camposOcultosGlobal = ['created_at', 'updated_at', 'deleted_at', 'asesor_id', 'cliente_id'];
        foreach ($camposOcultosGlobal as $campo) {
            unset($orderArray[$campo]);
        }

        if (!auth()->user() || !auth()->user()->role || auth()->user()->role->name !== 'asesor') {
            $camposOcultosNoAsesor = ['cotizacion_id', 'numero_cotizacion'];
            foreach ($camposOcultosNoAsesor as $campo) {
                unset($orderArray[$campo]);
            }
        }
    }

    private function normalizer(): \Closure
    {
        return function ($ruta) {
            $rutaNormalizada = null;

            if (!empty($ruta)) {
                if (str_starts_with($ruta, 'http') || str_starts_with($ruta, '/storage/')) {
                    $rutaNormalizada = $ruta;
                } else {
                    $rutaNormalizada = '/storage/' . ltrim($ruta, '/');
                }
            }

            return $rutaNormalizada;
        };
    }
}
