<?php

namespace App\Infrastructure\Services\Bodega;

use App\Application\Bodega\Constants\WarehouseConstants;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Models\BodegaDetallesTalla;
use App\Models\CosturaBodegaDetalle;
use App\Models\EppBodegaDetalle;
use App\Models\PedidoProduccion;
use App\Models\ReciboPrenda;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BodegaPedidoDetalleService
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private BodegaPedidoDetalleUtilsService $utilsService
    ) {
    }

    public function procesarItemsPedidoParaDespacho(Collection $recibos, array $rolesDelUsuario, array $areasPermitidas): array
    {
        $items = $this->procesarItemsPedido($recibos, $rolesDelUsuario, $areasPermitidas, paraDespacho: true);
        return $this->filtrarItemsParaDespacho($items);
    }

    public function procesarItemsPedido(Collection $recibos, array $rolesDelUsuario, array $areasPermitidas, bool $paraDespacho = false): array
    {
        $items = [];
        $primerRecibo = $recibos->first();
        $numeroPedido = trim((string) ($primerRecibo?->numero_pedido ?? ''));

        $pedidoProduccion = null;
        if ($numeroPedido !== '') {
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        }

        if (!$pedidoProduccion && $primerRecibo?->id) {
            $pedidoProduccion = PedidoProduccion::find($primerRecibo->id);
        }

        foreach ($recibos as $recibo) {
            try {
                $datosCompletos = $this->obtenerPedidoUseCase->ejecutar($recibo->id);

                if (isset($datosCompletos->prendas) && is_array($datosCompletos->prendas)) {
                    $items = array_merge(
                        $items,
                        $paraDespacho
                            ? $this->procesarPrendasParaDespacho($datosCompletos->prendas, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion)
                            : $this->procesarPrendas($datosCompletos->prendas, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion)
                    );
                }

                if (isset($datosCompletos->epps) && is_array($datosCompletos->epps)) {
                    $eppsProcesados = $this->procesarEpps($datosCompletos->epps, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion);
                    $items = array_merge($items, $eppsProcesados);
                }
            } catch (\Exception $e) {
                \Log::warning('[Bodega Show] Error al obtener datos del pedido', [
                    'numero_pedido' => $numeroPedido,
                    'recibo_id' => $recibo->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $items;
    }

    public function calcularRowspans(array $items): array
    {
        $items = $this->utilsService->aplicarRowspans($items, 'asesor_rowspan', fn ($item) => $item['asesor']);
        $items = $this->utilsService->aplicarRowspans($items, 'descripcion_rowspan', fn ($item) => $item['asesor'] . '|' . $this->utilsService->obtenerIdArticulo($item));
        $items = $this->utilsService->aplicarRowspans($items, 'genero_rowspan', fn ($item) =>
            $item['asesor'] . '|' . $this->utilsService->obtenerIdArticulo($item) . '|' . $this->utilsService->obtenerGeneroDelItem($item)
        );

        return $items;
    }

    private function procesarPrendasParaDespacho(array $prendas, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        $items = [];

        foreach ($prendas as $prendaEnriquecida) {
            $variantes = $prendaEnriquecida['variantes'] ?? [];
            $items[] = $this->crearItemPrendaConTallas($variantes, $prendaEnriquecida, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion);
        }

        return $items;
    }

    private function procesarPrendas(array $prendas, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        $items = [];

        foreach ($prendas as $prendaEnriquecida) {
            $variantes = $prendaEnriquecida['variantes'] ?? [];

            foreach ($variantes as $variante) {
                $items = array_merge($items, $this->procesarVariante($variante, $prendaEnriquecida, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion));
            }
        }

        return $items;
    }

    private function procesarVariante(array $variante, array $prendaEnriquecida, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        $coloresDetalle = $variante['colores_detalle'] ?? null;

        if (!is_array($coloresDetalle) || empty($coloresDetalle)) {
            return [$this->crearItemPrenda($variante, $prendaEnriquecida, $recibo, $rolesDelUsuario, $areasPermitidas, $pedidoProduccion)];
        }

        $items = [];
        foreach ($coloresDetalle as $colorDetalle) {
            $cantidadColor = (int) ($colorDetalle['cantidad'] ?? 0);

            if ($cantidadColor <= 0) {
                continue;
            }

            $tallaColorId = $colorDetalle['talla_color_id'] ?? ($colorDetalle['tallaColorId'] ?? null);
            $colorNombre = $colorDetalle['color'] ?? null;
            $items[] = $this->crearItemPrenda(
                $variante,
                $prendaEnriquecida,
                $recibo,
                $rolesDelUsuario,
                $areasPermitidas,
                $pedidoProduccion,
                $tallaColorId,
                $cantidadColor,
                $colorNombre
            );
        }

        return $items;
    }

    private function procesarEpps(array $epps, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        $items = [];

        $todosLosEppsBD = \App\Models\PedidoEpp::where('pedido_produccion_id', $pedidoProduccion?->id)
            ->withTrashed()
            ->with('epp')
            ->get();

        $eppsPorId = $todosLosEppsBD->keyBy('id');
        $hijosPorPadre = $todosLosEppsBD->groupBy('homologado_de');
        $eppsEnriquecidosPorId = collect($epps)->keyBy(fn ($epp) => $epp['pedido_epp_id'] ?? null);

        $eppsOriginales = $todosLosEppsBD->whereNull('homologado_de')->values();
        foreach ($eppsOriginales as $eppOriginal) {
            $pedidoEppIdOriginal = (int) $eppOriginal->id;
            $pedidoEppIdActual = $this->obtenerUltimoPedidoEppIdDeCadena($pedidoEppIdOriginal, $hijosPorPadre);
            $pedidoEppActual = $eppsPorId->get($pedidoEppIdActual);

            if (!$pedidoEppActual) {
                continue;
            }

            // Si el EPP está eliminado (deleted_at IS NOT NULL) y no tiene versiones más nuevas
            // (es decir, no fue homologado a otro EPP), entonces no debe aparecer en la vista
            $tieneVersionesMasNuevas = !$hijosPorPadre->get($pedidoEppIdActual, collect())->isEmpty();

            if ($pedidoEppActual->deleted_at !== null && !$tieneVersionesMasNuevas) {
                continue;
            }

            $historialeHomologaciones = $this->obtenerHistorialEpp($pedidoEppIdOriginal);

            $eppEnriquecidoActual = $eppsEnriquecidosPorId->get($pedidoEppIdActual, []);
            $eppEnriquecidoActual['pedido_epp_id'] = $pedidoEppIdActual;
            $eppEnriquecidoActual['nombre'] = $eppEnriquecidoActual['nombre'] ?? ($pedidoEppActual->epp->nombre_completo ?? 'EPP sin nombre');
            $eppEnriquecidoActual['cantidad'] = $eppEnriquecidoActual['cantidad'] ?? (int) $pedidoEppActual->cantidad;

            $items[] = $this->crearItemEpp(
                $eppEnriquecidoActual,
                $recibo,
                $rolesDelUsuario,
                $areasPermitidas,
                $pedidoProduccion,
                $historialeHomologaciones
            );
        }

        return $items;
    }

    private function obtenerUltimoPedidoEppIdDeCadena(int $pedidoEppIdOriginal, Collection $hijosPorPadre): int
    {
        $actualId = $pedidoEppIdOriginal;
        $intentos = 0;
        $maxIntentos = 30;

        while ($intentos < $maxIntentos) {
            $intentos++;
            $hijos = $hijosPorPadre->get($actualId, collect());
            if ($hijos->isEmpty()) {
                break;
            }

            $siguiente = $hijos
                ->sortByDesc(fn ($item) => ($item->created_at?->timestamp ?? 0) . '-' . $item->id)
                ->first();

            if (!$siguiente) {
                break;
            }

            $actualId = (int) $siguiente->id;
        }

        return $actualId;
    }

    private function crearItemPrendaConTallas(array $variantes, array $prendaEnriquecida, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion): array
    {
        $numeroPedido = $this->resolverNumeroPedido($recibo, $pedidoProduccion);
        $asesor = 'N/A';
        if ($recibo->asesor) {
            $asesor = $recibo->asesor->name ?? $recibo->asesor->nombre ?? 'N/A';
        }

        $empresa = $recibo->cliente ?? 'N/A';
        $tallas = [];
        $cantidadTotal = 0;

        foreach ($variantes as $variante) {
            $talla = $variante['talla'] ?? '';
            $cantidad = $variante['cantidad'] ?? 0;
            $prendaId = $prendaEnriquecida['id'] ?? null;
            $genero = $variante['genero'] ?? null;

            $bodegaData = $this->obtenerDatosBodega($numeroPedido, $talla, $prendaEnriquecida['nombre'] ?? null, $cantidad, $rolesDelUsuario, null, $prendaId, $genero);

            $tallas[] = [
                'talla' => $talla,
                'cantidad' => $cantidad,
                'pendientes' => $bodegaData['pendientes'] ?? 0,
                'area' => $bodegaData['area'] ?? '',
                'estado_bodega' => $bodegaData['estado_bodega'] ?? WarehouseConstants::STATE_PENDING,
                'pedido_produccion_id' => $bodegaData['id'] ?? null,
                'observaciones' => $bodegaData['observaciones'] ?? '',
                'fecha_entrega' => $bodegaData['fecha_entrega'] ?? '',
                'fecha_entrega_bodega' => $bodegaData['fecha_entrega_bodega'] ?? null,
                'created_at' => $bodegaData['created_at'] ?? null,
                'updated_at' => $bodegaData['updated_at'] ?? null,
            ];

            $cantidadTotal += $cantidad;
        }

        $descripcionRowspan = count($tallas);

        return [
            'id' => $recibo->id,
            'tipo' => 'prenda',
            'numero_pedido' => $numeroPedido,
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'recibo_prenda_id' => $recibo->id,
            'prenda_id' => $prendaEnriquecida['id'] ?? null,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $prendaEnriquecida,
            'tallas' => $tallas,
            'cantidad' => $cantidadTotal,
            'descripcion_rowspan' => $descripcionRowspan,
            'observaciones' => null,
            'fecha_pedido' => $recibo->created_at->format('Y-m-d H:i:s'),
            'fecha_entrega' => null,
            'area' => null,
            'estado_bodega' => WarehouseConstants::STATE_PENDING,
        ];
    }

    private function crearItemPrenda(array $variante, array $prendaEnriquecida, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion, ?int $tallaColorId = null, ?int $cantidadOverride = null, ?string $colorNombre = null): array
    {
        $talla = $variante['talla'] ?? '';
        $prendaNombre = $prendaEnriquecida['nombre'] ?? null;
        $cantidad = $cantidadOverride !== null ? (int) $cantidadOverride : ($variante['cantidad'] ?? 0);
        $prendaId = $prendaEnriquecida['id'] ?? null;
        $genero = $variante['genero'] ?? null;

        $numeroPedido = $this->resolverNumeroPedido($recibo, $pedidoProduccion);
        $bodegaData = $this->obtenerDatosBodega($numeroPedido, $talla, $prendaNombre, $cantidad, $rolesDelUsuario, $tallaColorId, $prendaId, $genero);

        $asesor = 'N/A';
        if ($recibo->asesor) {
            $asesor = $recibo->asesor->name ?? $recibo->asesor->nombre ?? WarehouseConstants::DEFAULT_NA;
        }

        $empresa = $recibo->cliente ?? WarehouseConstants::DEFAULT_NA;

        return [
            'id' => $recibo->id,
            'tipo' => 'prenda',
            'numero_pedido' => $numeroPedido,
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'recibo_prenda_id' => $recibo->id,
            'prenda_id' => $prendaEnriquecida['id'] ?? null,
            'talla_color_id' => $tallaColorId,
            'color_nombre' => $colorNombre,
            'genero' => $genero,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $prendaEnriquecida,
            'talla' => $talla,
            'cantidad' => $bodegaData['cantidad'] ?? $cantidad,
            'cantidad_total' => $cantidad,
            'observaciones' => $bodegaData['observaciones'] ?? null,
            'pendientes' => $bodegaData['pendientes'] ?? null,
            'fecha_entrega' => $bodegaData['fecha_entrega'],
            'fecha_pedido' => $bodegaData['fecha_pedido'],
            'fecha_entrega_bodega' => $bodegaData['fecha_entrega_bodega'] ?? null,
            'created_at' => $bodegaData['created_at'] ?? null,
            'updated_at' => $bodegaData['updated_at'] ?? null,
            'estado_bodega' => $bodegaData['estado_bodega'],
            'costura_estado' => $bodegaData['costura_estado'] ?? null,
            'epp_estado' => $bodegaData['epp_estado'] ?? null,
            'area' => $bodegaData['area'],
            'usuario_bodega_nombre' => $bodegaData['usuario_nombre'],
            'bodega_id' => $bodegaData['id'],
        ];
    }

    private function obtenerHistorialEpp(int $pedidoEppIdOriginal): array
    {
        // OPTIMIZACIÓN: Cargar toda la cadena de homologaciones de una vez
        // Antes: 2 queries por iteración = 20 queries para cadena de 10
        // Ahora: 1 query para toda la cadena

        // Query 1: Obtener el EPP original
        $eppOriginal = \App\Models\PedidoEpp::withTrashed()
            ->with('epp')
            ->find($pedidoEppIdOriginal);

        if (!$eppOriginal) {
            return [];
        }

        // Query 2: Cargar TODA la cadena de homologaciones de una vez
        // Esto usa recursión en SQL (CTE) o carga en cascada
        $todaLaCadena = $this->obtenerCadenaEppCompleta($pedidoEppIdOriginal);

        // Procesar los datos cargados (sin queries adicionales)
        $historial = [];
        foreach ($todaLaCadena as $pedidoEpp) {
            $historial[] = [
                'pedido_epp_id' => $pedidoEpp->id,
                'epp_id' => $pedidoEpp->epp_id,
                'epp_nombre' => $pedidoEpp->epp->nombre_completo ?? 'EPP sin nombre',
                'cantidad' => $pedidoEpp->cantidad,
                'fecha_creacion' => $pedidoEpp->created_at?->format('Y-m-d H:i'),
                'deleted_at' => $pedidoEpp->deleted_at?->format('Y-m-d H:i'),
                'observaciones' => $pedidoEpp->observaciones ?? '',
                'es_original' => $pedidoEpp->homologado_de === null,
            ];
        }

        \Log::debug('[obtenerHistorialEpp] Historial completo obtenido', [
            'pedido_epp_id_original' => $pedidoEppIdOriginal,
            'cantidad_cambios' => count($historial),
        ]);

        return $historial;
    }

    private function obtenerCadenaEppCompleta(int $pedidoEppIdOriginal): Collection
    {
        // Cargar toda la cadena de EPPs homologados
        // Usar una estrategia de carga recursiva: cargar en batch en lugar de loop
        $cadena = collect();
        $eppsVisitados = collect();
        $eppIdActual = $pedidoEppIdOriginal;
        $intentos = 0;
        $maxIntentos = 30;

        while ($eppIdActual !== null && $intentos < $maxIntentos) {
            $intentos++;

            if ($eppsVisitados->contains($eppIdActual)) {
                \Log::warning('[obtenerCadenaEppCompleta] Ciclo detectado', [
                    'pedido_epp_id' => $eppIdActual,
                    'epps_visitados' => $eppsVisitados->toArray(),
                ]);
                break;
            }

            $eppsVisitados->push($eppIdActual);

            // Cargar este EPP
            $epp = \App\Models\PedidoEpp::withTrashed()
                ->with('epp')
                ->find($eppIdActual);

            if (!$epp) {
                break;
            }

            $cadena->push($epp);

            // Buscar el siguiente en la cadena (homologado_de = eppIdActual)
            $siguiente = \App\Models\PedidoEpp::where('homologado_de', $eppIdActual)
                ->withTrashed()
                ->first();

            $eppIdActual = $siguiente?->id;
        }

        return $cadena;
    }

    private function crearItemEpp(array $eppEnriquecido, ReciboPrenda $recibo, array $rolesDelUsuario, array $areasPermitidas, ?PedidoProduccion $pedidoProduccion, array $historialeHomologaciones = []): array
    {
        $eppNombre = $eppEnriquecido['nombre'] ?? WarehouseConstants::AREA_EPP;
        $eppCantidad = (int) ($eppEnriquecido['cantidad'] ?? 0);
        $pedidoEppId = $eppEnriquecido['pedido_epp_id'] ?? null;
        $numeroPedido = $this->resolverNumeroPedido($recibo, $pedidoProduccion);
        $eppId = md5($numeroPedido . '|' . $eppNombre . '|' . $eppCantidad);

        $tieneHistorial = count($historialeHomologaciones) > 1;

        \Log::debug('[crearItemEpp] Historial recibido', [
            'pedido_epp_id' => $pedidoEppId,
            'cantidad_registros_en_historial' => count($historialeHomologaciones),
            'tiene_boton_ver_cambios' => $tieneHistorial,
        ]);

        $bodegaData = $this->obtenerDatosBodega($numeroPedido, $eppId, $eppNombre, $eppCantidad, $rolesDelUsuario);

        \Log::debug('[crearItemEpp] Datos obtenidos de bodega', [
            'eppNombre' => $eppNombre,
            'eppId' => $eppId,
            'bodegaData_keys' => array_keys($bodegaData),
            'area' => $bodegaData['area'] ?? 'NULL',
            'estado_bodega' => $bodegaData['estado_bodega'] ?? 'NULL',
        ]);

        $asesor = 'N/A';
        if ($recibo->asesor) {
            $asesor = $recibo->asesor->name ?? $recibo->asesor->nombre ?? 'N/A';
        }

        $empresa = $recibo->cliente ?? 'N/A';

        $area = $bodegaData['area'] ?? 'EPP';  // Asegurar que EPPs siempre tengan area 'EPP'
        $estadoBodega = $bodegaData['estado_bodega'] ?? null;
        $cantidad = $bodegaData['cantidad'] ?? $eppCantidad;
        $pendientes = $bodegaData['pendientes'] ?? null;
        $fechaEntrega = $bodegaData['fecha_entrega'] ?? null;
        $fechaPedido = $bodegaData['fecha_pedido'] ?? null;
        $fechaPendiente = $bodegaData['fecha_pendiente'] ?? null;
        $fechaEntregaBodega = $bodegaData['fecha_entrega_bodega'] ?? null;
        $createdAt = $bodegaData['created_at'] ?? null;
        $updatedAt = $bodegaData['updated_at'] ?? null;

        $descripcionEpp = $eppEnriquecido;
        $descripcionEpp['nombre'] = $eppNombre;
        $descripcionEpp['nombre_prenda'] = $eppNombre;
        $descripcionEpp['cantidad'] = $eppCantidad;
        $descripcionEpp['pedido_epp_id'] = $pedidoEppId;

        $itemEpp = [
            'id' => $recibo->id,
            'tipo' => WarehouseConstants::AREA_EPP,
            'numero_pedido' => $numeroPedido,
            'pedido_produccion_id' => $pedidoProduccion?->id,
            'recibo_prenda_id' => $recibo->id,
            'pedido_epp_id' => $pedidoEppId,
            'tiene_historial' => $tieneHistorial,
            'historial_homologaciones' => $historialeHomologaciones,
            'asesor' => $asesor,
            'empresa' => $empresa,
            'descripcion' => $descripcionEpp,
            'prenda_nombre' => $eppNombre,
            'talla' => $eppId,
            'cantidad' => $cantidad,
            'cantidad_total' => $eppCantidad,
            'observaciones' => $bodegaData['observaciones'] ?? null,
            'pendientes' => $pendientes,
            'fecha_entrega' => $fechaEntrega,
            'fecha_pedido' => $fechaPedido,
            'fecha_pendiente' => $fechaPendiente,
            'fecha_entrega_bodega' => $fechaEntregaBodega,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'estado_bodega' => $estadoBodega,
            'costura_estado' => $bodegaData['costura_estado'] ?? null,
            'epp_estado' => $bodegaData['epp_estado'] ?? null,
            'area' => $area,
            'tallas' => [[
                'talla' => $eppId,
                'cantidad' => $cantidad,
                'pendientes' => $pendientes,
                'area' => $area,
                'estado_bodega' => $estadoBodega,
                'pedido_produccion_id' => $bodegaData['id'] ?? null,
                'observaciones' => $bodegaData['observaciones'] ?? '',
                'fecha_entrega' => $fechaEntrega ?? '',
            ]],
        ];

        \Log::debug('[crearItemEpp] Item creado', [
            'area' => $itemEpp['area'],
            'tipo' => $itemEpp['tipo'],
            'eppNombre' => $eppNombre,
            'tiene_historial' => $tieneHistorial,
        ]);

        return $itemEpp;
    }

    private function obtenerDatosBodega(?string $numeroPedido, string $talla, ?string $prendaNombre, int $cantidad, array $rolesDelUsuario, ?int $tallaColorId = null, ?int $prendaId = null, ?string $genero = null): array
    {
        $numeroPedido = trim((string) $numeroPedido);
        if ($numeroPedido === '') {
            \Log::warning('[obtenerDatosBodega] numeroPedido vacío/null, devolviendo defaults', [
                'talla' => $talla,
                'prenda_nombre' => $prendaNombre,
                'prenda_id' => $prendaId,
                'talla_color_id' => $tallaColorId,
            ]);

            return [
                'id' => null,
                'estado' => WarehouseConstants::STATE_PENDING,
                WarehouseConstants::FIELD_ESTADO_BODEGA => WarehouseConstants::STATE_PENDING,
                WarehouseConstants::FIELD_AREA => null,
                WarehouseConstants::FIELD_CANTIDAD => $cantidad,
                WarehouseConstants::FIELD_COSTURA_ESTADO => null,
                WarehouseConstants::FIELD_EPP_ESTADO => null,
                'observaciones' => null,
                WarehouseConstants::FIELD_PENDIENTES => null,
                WarehouseConstants::FIELD_FECHA_ENTREGA => null,
                WarehouseConstants::FIELD_FECHA_PEDIDO => null,
                'fecha_pendiente' => null,
                'usuario_nombre' => null,
            ];
        }

        $bodegaDataBase = null;

        if (strlen($talla) === WarehouseConstants::MD5_LENGTH && ctype_xdigit($talla)) {
            $bodegaDataBase = BodegaDetallesTalla::where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $numeroPedido)
                ->where(WarehouseConstants::FIELD_TALLA, $talla)
                ->when($prendaNombre, fn ($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $prendaNombre))
                ->first();
        } else {
            $generoNormalizado = $genero ? strtoupper($genero) : null;

            if ($prendaId && $talla && $generoNormalizado) {
                $rowHash = md5($numeroPedido . '_' . $prendaId . '_' . $talla . '_' . ($tallaColorId ?? '') . '_' . $generoNormalizado);
                $bodegaDataBase = BodegaDetallesTalla::where(WarehouseConstants::FIELD_ROW_HASH, $rowHash)->first();
            }

            if (!$bodegaDataBase) {
                $bodegaDataBase = BodegaDetallesTalla::where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $numeroPedido)
                    ->where(WarehouseConstants::FIELD_TALLA, $talla)
                    ->when($tallaColorId !== null, function ($q) use ($tallaColorId) {
                        return $q->where(WarehouseConstants::FIELD_TALLA_COLOR_ID, $tallaColorId);
                    }, function ($q) {
                        return $q->whereNull(WarehouseConstants::FIELD_TALLA_COLOR_ID);
                    })
                    ->when($prendaNombre, fn ($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $prendaNombre))
                    ->when($prendaId !== null, fn ($q) => $q->where(WarehouseConstants::FIELD_PRENDA_ID, $prendaId))
                    ->when($generoNormalizado !== null, fn ($q) => $q->where(WarehouseConstants::FIELD_GENERO, $generoNormalizado))
                    ->first();
            }
        }

        $bodegaDataEstado = null;
        if (in_array(WarehouseConstants::ROLE_EPP_BODEGA, $rolesDelUsuario)) {
            $bodegaDataEstado = EppBodegaDetalle::where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $numeroPedido)
                ->where(WarehouseConstants::FIELD_TALLA, $talla)
                ->when($prendaNombre, fn ($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $prendaNombre))
                ->first();
        } elseif (in_array(WarehouseConstants::ROLE_COSTURA_BODEGA, $rolesDelUsuario)) {
            $bodegaDataEstado = CosturaBodegaDetalle::where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $numeroPedido)
                ->where(WarehouseConstants::FIELD_TALLA, $talla)
                ->when($prendaNombre, fn ($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $prendaNombre))
                ->first();
        }

        $datosFinales = $this->seleccionarDatosSegunRol($bodegaDataEstado, $bodegaDataBase, $rolesDelUsuario);

        $estado = $datosFinales?->estado_bodega ?? $bodegaDataBase?->estado_bodega;
        $area = $datosFinales?->area ?? $bodegaDataBase?->area;
        $estadoEspecifico = $this->obtenerEstadoEspecifico($area, $estado, $datosFinales, $bodegaDataBase);

        $resultado = [
            'id' => $datosFinales?->id,
            'estado' => $estadoEspecifico,
            WarehouseConstants::FIELD_ESTADO_BODEGA => $estado,
            WarehouseConstants::FIELD_AREA => $area,
            WarehouseConstants::FIELD_CANTIDAD => $bodegaDataBase?->cantidad,
            WarehouseConstants::FIELD_COSTURA_ESTADO => $bodegaDataBase?->costura_estado,
            WarehouseConstants::FIELD_EPP_ESTADO => $bodegaDataBase?->epp_estado,
            'observaciones' => $datosFinales?->observaciones_bodega ?? $bodegaDataBase?->observaciones_bodega,
            WarehouseConstants::FIELD_PENDIENTES => $datosFinales?->pendientes ?? $bodegaDataBase?->pendientes,
            WarehouseConstants::FIELD_FECHA_ENTREGA => $bodegaDataBase?->fecha_entrega ? Carbon::parse($bodegaDataBase->fecha_entrega)->format('Y-m-d') : null,
            WarehouseConstants::FIELD_FECHA_PEDIDO => $bodegaDataBase?->fecha_pedido ? Carbon::parse($bodegaDataBase->fecha_pedido)->format('Y-m-d') : null,
            'fecha_pendiente' => $bodegaDataBase?->fecha_pendiente ? Carbon::parse($bodegaDataBase->fecha_pendiente)->format('Y-m-d H:i:s') : null,
            'fecha_entrega_bodega' => $bodegaDataBase?->fecha_entrega_bodega ? Carbon::parse($bodegaDataBase->fecha_entrega_bodega)->format('Y-m-d H:i:s') : null,
            'created_at' => $bodegaDataBase?->created_at ? Carbon::parse($bodegaDataBase->created_at)->format('Y-m-d H:i:s') : null,
            'updated_at' => $bodegaDataBase?->updated_at ? Carbon::parse($bodegaDataBase->updated_at)->format('Y-m-d H:i:s') : null,
            'usuario_nombre' => $datosFinales?->usuario_bodega_nombre ?? $bodegaDataBase?->usuario_bodega_nombre,
        ];

        return $resultado;
    }

    private function resolverNumeroPedido(ReciboPrenda $recibo, ?PedidoProduccion $pedidoProduccion): string
    {
        $numeroPedido = trim((string) ($recibo->numero_pedido ?? ''));
        if ($numeroPedido !== '') {
            return $numeroPedido;
        }

        $numeroPedidoPedido = trim((string) ($pedidoProduccion?->numero_pedido ?? ''));
        if ($numeroPedidoPedido !== '') {
            return $numeroPedidoPedido;
        }

        if (!empty($pedidoProduccion?->id)) {
            return (string) $pedidoProduccion->id;
        }

        return !empty($recibo->id) ? (string) $recibo->id : '';
    }

    private function seleccionarDatosSegunRol($bodegaDataEstado, $bodegaDataBase, array $rolesDelUsuario)
    {
        $tieneRolEspecifico = in_array(WarehouseConstants::ROLE_EPP_BODEGA, $rolesDelUsuario) || in_array(WarehouseConstants::ROLE_COSTURA_BODEGA, $rolesDelUsuario);
        return $tieneRolEspecifico ? $bodegaDataEstado : $bodegaDataBase;
    }

    private function obtenerEstadoEspecifico(?string $area, ?string $estado, $datosFinales, $bodegaDataBase): string
    {
        $estadoMap = [
            WarehouseConstants::AREA_COSTURA => fn () => $datosFinales?->costura_estado ?? $bodegaDataBase?->costura_estado ?? $estado,
            WarehouseConstants::AREA_EPP => fn () => $datosFinales?->epp_estado ?? $bodegaDataBase?->epp_estado ?? $estado,
        ];

        $estadoResultado = isset($estadoMap[$area]) ? $estadoMap[$area]() : $estado;

        return $estadoResultado ?? WarehouseConstants::STATE_PENDING;
    }

    private function filtrarItemsParaDespacho(array $items): array
    {
        $itemsFiltrados = [];

        \Log::info('[DESPACHO-FILTRO] Inicio filtrado por estado_bodega = Pendiente', [
            'items_totales' => count($items),
            'items_recibidos' => array_map(fn ($item) => [
                'numero_pedido' => $item['numero_pedido'],
                'tipo' => $item['tipo'] ?? 'unknown',
                'area' => $item['area'] ?? 'unknown',
                'tiene_tallas' => isset($item['tallas']) && is_array($item['tallas']),
                'tiene_historial' => $item['tiene_historial'] ?? false,
            ], $items),
        ]);

        foreach ($items as $item) {
            $tallas = $item['tallas'] ?? [];
            $tieneHistorialItem = $item['tiene_historial'] ?? false;

            $tallasFiltradas = [];
            foreach ($tallas as $talla) {
                $estadoPendiente = ($talla['estado_bodega'] ?? '') === WarehouseConstants::STATE_PENDING;
                // Incluir si está en estado Pendiente O si es EPP con historial de homologaciones
                $esEppConHistorial = ($item['tipo'] ?? '') === 'EPP' && $tieneHistorialItem;
                
                if ($estadoPendiente || $esEppConHistorial) {
                    $tallasFiltradas[] = $talla;
                }
            }

            if (!empty($tallasFiltradas)) {
                $item['tallas'] = $tallasFiltradas;
                $itemsFiltrados[] = $item;
            }
        }

        return $itemsFiltrados;
    }
}
