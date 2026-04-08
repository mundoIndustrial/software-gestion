<?php

namespace App\Application\Pedidos\Services;

use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Infrastructure\Services\Pedidos\PedidoEppBuilder;
use App\Infrastructure\Services\Pedidos\PedidoItemBuilder;
use App\Infrastructure\Services\Pedidos\PedidoLifecycleService;
use App\Infrastructure\Services\Pedidos\PedidoProcesoBuilder;
use App\Infrastructure\Services\Pedidos\PedidoProcesoImageManager;
use App\Infrastructure\Services\Pedidos\PedidoProcesoTallaBuilder;
use App\Infrastructure\Services\Pedidos\PedidoTallaBuilder;
use App\Infrastructure\Services\Pedidos\PedidoTelaBuilder;
use App\Infrastructure\Services\Pedidos\PedidoVarianteBuilder;
use App\Models\PedidoProduccion;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PedidoCreationCoordinator
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private PedidoEppBuilder $pedidoEppBuilder,
        private PedidoItemBuilder $pedidoItemBuilder,
        private PedidoLifecycleService $pedidoLifecycleService,
        private PedidoProcesoBuilder $pedidoProcesoBuilder,
        private PedidoProcesoImageManager $pedidoProcesoImageManager,
        private PedidoProcesoTallaBuilder $pedidoProcesoTallaBuilder,
        private PedidoTallaBuilder $pedidoTallaBuilder,
        private PedidoTelaBuilder $pedidoTelaBuilder,
        private PedidoVarianteBuilder $pedidoVarianteBuilder,
    ) {}

    public function crearPedidoCompleto(array $datosValidados, int $asesorId): PedidoProduccion
    {
        $tiempoInicio = microtime(true);

        return $this->transactionManager->run(function () use ($datosValidados, $asesorId, &$tiempoInicio) {
            $pedido = $this->crearPedidoBaseConLog(
                fn () => $this->pedidoLifecycleService->crearPedidoBase($datosValidados, $asesorId),
                '[PedidoCreationCoordinator] Pedido base creado'
            );

            $this->crearContenidoPedido($pedido, $datosValidados);

            Log::info('[PedidoCreationCoordinator] Pedido completo creado', [
                'pedido_id' => $pedido->id,
                'cantidad_prendas' => $pedido->prendas()->count(),
                'cantidad_epps' => $pedido->epps()->count(),
                'area_final' => $pedido->area,
                'tiempo_total_ms' => round((microtime(true) - $tiempoInicio) * 1000, 2),
            ]);

            return $pedido;
        });
    }

    public function crearPedidoCompletoDentroTransaccion(array $datosValidados, int $asesorId): PedidoProduccion
    {
        $tiempoInicio = microtime(true);

        $pedido = $this->crearPedidoBaseConLog(
            fn () => $this->pedidoLifecycleService->crearPedidoBase($datosValidados, $asesorId),
            '[PedidoCreationCoordinator] Pedido base creado'
        );

        $this->crearContenidoPedido($pedido, $datosValidados);

        Log::info('[PedidoCreationCoordinator] Pedido completo creado', [
            'pedido_id' => $pedido->id,
            'cantidad_prendas' => $pedido->prendas()->count(),
            'cantidad_epps' => $pedido->epps()->count(),
            'area_final' => $pedido->area,
            'tiempo_total_ms' => round((microtime(true) - $tiempoInicio) * 1000, 2),
        ]);

        return $pedido;
    }

    public function crearPedidoBorrador(array $datosValidados, int $asesorId): PedidoProduccion
    {
        $tiempoInicio = microtime(true);

        return $this->transactionManager->run(function () use ($datosValidados, $asesorId, &$tiempoInicio) {
            $pedido = $this->crearPedidoBaseConLog(
                fn () => $this->pedidoLifecycleService->crearPedidoBaseBorrador($datosValidados, $asesorId),
                '[PedidoCreationCoordinator] Pedido borrador base creado'
            );

            $this->crearContenidoPedido($pedido, $datosValidados);

            Log::info('[PedidoCreationCoordinator] Pedido borrador completo creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido ?? 'NULL (Borrador)',
                'cantidad_prendas' => $pedido->prendas()->count(),
                'cantidad_epps' => $pedido->epps()->count(),
                'estado' => $pedido->estado,
                'tiempo_total_ms' => round((microtime(true) - $tiempoInicio) * 1000, 2),
            ]);

            return $pedido;
        });
    }

    public function convertirBorradorEnPedido(PedidoProduccion $borrador, array $datosValidados): PedidoProduccion
    {
        return $this->pedidoLifecycleService->convertirBorradorEnPedido($borrador, $datosValidados);
    }

    public function agregarItemAPedido(PedidoProduccion $pedido, array $itemData, int $index): PrendaPedido
    {
        return $this->crearItemCompleto($pedido, $itemData, $index);
    }

    private function crearPedidoBaseConLog(callable $factory, string $logMessage): PedidoProduccion
    {
        $tiempoInicioBase = microtime(true);
        $pedido = $factory();
        $tiempoBase = (microtime(true) - $tiempoInicioBase) * 1000;

        Log::info($logMessage, [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido ?? 'NULL (Borrador)',
            'area_guardada' => $pedido->area,
            'estado' => $pedido->estado,
            'tiempo_base_ms' => round($tiempoBase, 2),
        ]);

        return $pedido;
    }

    private function crearContenidoPedido(PedidoProduccion $pedido, array $datosValidados): void
    {
        if (isset($datosValidados['items']) && is_array($datosValidados['items'])) {
            foreach ($datosValidados['items'] as $itemIndex => $itemData) {
                $this->crearItemCompleto($pedido, $itemData, $itemIndex);
            }
        }

        if (isset($datosValidados['epps']) && is_array($datosValidados['epps'])) {
            foreach ($datosValidados['epps'] as $eppIndex => $eppData) {
                $this->pedidoEppBuilder->crear($pedido, $eppData, $eppIndex);
            }
        }
    }

    private function crearItemCompleto(PedidoProduccion $pedido, array $itemData, int $itemIndex): PrendaPedido
    {
        $prenda = $this->pedidoItemBuilder->crearBase($pedido, $itemData);

        if (isset($itemData['cantidad_talla']) && is_array($itemData['cantidad_talla'])) {
            $asignacionesColores = $itemData['asignacionesColoresPorTalla'] ?? [];
            $flujoTallas = $itemData['flujo'] ?? 'simple';

            Log::info('[PedidoCreationCoordinator] Diagnostico de tallas', [
                'asignaciones_exists' => isset($itemData['asignacionesColoresPorTalla']),
                'asignaciones_count' => count($asignacionesColores),
                'flujo' => $flujoTallas,
            ]);

            $this->pedidoTallaBuilder->crear($prenda, $itemData['cantidad_talla'], $asignacionesColores, $flujoTallas);

            if (Schema::hasColumn('prendas_pedido', 'tipo_flujo_tallas')) {
                $tieneAsignaciones = is_array($asignacionesColores) && !empty($asignacionesColores);
                $tieneTallas = !empty($itemData['cantidad_talla']);
                $tipoFlujo = $tieneAsignaciones ? 'talla_color' : ($tieneTallas ? 'normal' : 'sin_tallas');

                if (($prenda->tipo_flujo_tallas ?? null) !== $tipoFlujo) {
                    $prenda->tipo_flujo_tallas = $tipoFlujo;
                    $prenda->save();
                }
            }
        }

        if (isset($itemData['variaciones']) && is_array($itemData['variaciones'])) {
            $this->pedidoVarianteBuilder->crear($prenda, $itemData['variaciones']);
        }

        $flujo = $itemData['flujo'] ?? 'simple';
        $esWizard = $flujo === 'wizard';

        if (!$esWizard) {
            if (isset($itemData['prenda_pedido_colores_telas']) && is_array($itemData['prenda_pedido_colores_telas'])) {
                $this->pedidoTelaBuilder->crearColoresTelas($prenda, $itemData['prenda_pedido_colores_telas']);
            } elseif (isset($itemData['telas']) && is_array($itemData['telas'])) {
                $this->pedidoTelaBuilder->crearDesdeFormulario($prenda, $itemData['telas']);
            }
        }

        if (isset($itemData['procesos']) && is_array($itemData['procesos'])) {
            $this->crearProcesosCompletos(
                $prenda,
                $itemData['procesos'],
                $itemData['asignacionesColoresPorTalla'] ?? [],
                $itemData['flujo'] ?? 'simple'
            );
        }

        return $prenda;
    }

    private function crearProcesosCompletos(PrendaPedido $prenda, array $procesos, array $asignacionesColores = [], string $flujo = 'simple'): void
    {
        Log::info('[PedidoCreationCoordinator] crearProcesosCompletos', [
            'prenda_id' => $prenda->id,
            'procesos_count' => count($procesos),
        ]);

        foreach ($procesos as $tipoProceso => $procesoData) {
            if (!is_array($procesoData)) {
                Log::warning('[PedidoCreationCoordinator] Datos de proceso no es array', [
                    'tipo' => $tipoProceso,
                    'tipo_datos' => gettype($procesoData),
                ]);
                continue;
            }

            if (is_numeric($tipoProceso)) {
                $tipoProceso = strtolower(trim($procesoData['tipo'] ?? $procesoData['nombre'] ?? (string) $tipoProceso));
            }

            $datosProceso = $procesoData['datos'] ?? $procesoData;
            if (!is_array($datosProceso)) {
                continue;
            }

            $tipoProcesoId = $this->pedidoProcesoBuilder->resolverTipoProcesoId($tipoProceso);
            if (!$tipoProcesoId) {
                continue;
            }

            $ubicaciones = $datosProceso['ubicaciones'] ?? $procesoData['ubicaciones'] ?? [];
            $observaciones = $datosProceso['observaciones'] ?? $procesoData['observaciones'] ?? null;
            if (!is_array($ubicaciones)) {
                $ubicaciones = is_string($ubicaciones) ? [$ubicaciones] : [];
            }
            if (is_string($observaciones)) {
                $observaciones = trim($observaciones);
                $observaciones = $observaciones === '' ? null : $observaciones;
            }

            $procesoUID = $procesoData['uid'] ?? $datosProceso['uid'] ?? null;
            if ($procesoUID && !isset($datosProceso['uid'])) {
                $datosProceso['uid'] = $procesoUID;
            }

            $modoTallas = $datosProceso['modo_tallas'] ?? $procesoData['modo_tallas'] ?? 'generico';
            $datosExtendidos = $datosProceso['datos_extendidos'] ?? $procesoData['datos_extendidos'] ?? null;
            if (is_string($datosExtendidos)) {
                $datosExtendidos = json_decode($datosExtendidos, true);
            }

            $this->pedidoProcesoBuilder->eliminarDuplicado($prenda, $tipoProcesoId);

            $procesoPrenda = $this->pedidoProcesoBuilder->crearBase(
                $prenda,
                $tipoProcesoId,
                $ubicaciones,
                $observaciones,
                $modoTallas,
                $datosProceso,
                'PENDIENTE'
            );

            $this->crearTallasProceso($procesoPrenda, $datosProceso, $asignacionesColores, $datosExtendidos, $tipoProceso, $modoTallas, $flujo);
            $this->guardarImagenesProceso($procesoPrenda, $datosProceso, $prenda);
        }
    }

    private function crearTallasProceso(
        PedidosProcesosPrendaDetalle $procesoPrenda,
        array $datosProceso,
        array $asignacionesColores,
        mixed $datosExtendidos,
        string $tipoProceso,
        string $modoTallas,
        string $flujo
    ): void {
        if ($modoTallas === 'especifico' && !empty($datosExtendidos)) {
            $this->pedidoProcesoTallaBuilder->crearDesdeDatosExtendidosPorTallas(
                $procesoPrenda,
                $datosExtendidos,
                $datosProceso['tallas'] ?? []
            );
            return;
        }

        if (!isset($datosProceso['tallas']) || !is_array($datosProceso['tallas'])) {
            Log::warning('[PedidoCreationCoordinator] No hay tallas para proceso', [
                'tipo' => $tipoProceso,
                'modo_tallas' => $modoTallas,
                'flujo' => $flujo,
            ]);
            return;
        }

        $datosExtendidos = $datosProceso['datos_extendidos'] ?? $datosProceso['datosExtendidos'] ?? [];
        $this->pedidoProcesoTallaBuilder->crearDesdeMapaConAsignaciones(
            $procesoPrenda,
            $datosProceso['tallas'],
            $asignacionesColores,
            $datosExtendidos
        );
    }

    private function guardarImagenesProceso(PedidosProcesosPrendaDetalle $procesoPrenda, array $datosProceso, PrendaPedido $prenda): void
    {
        if (!isset($datosProceso['imagenes']) || !is_array($datosProceso['imagenes']) || empty($datosProceso['imagenes'])) {
            return;
        }

        $this->pedidoProcesoImageManager->guardarImagenesGenerales(
            $procesoPrenda,
            $prenda->pedidoProduccion,
            $datosProceso['imagenes']
        );
    }
}
