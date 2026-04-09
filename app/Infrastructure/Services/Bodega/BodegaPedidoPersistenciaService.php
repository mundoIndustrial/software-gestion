<?php

namespace App\Infrastructure\Services\Bodega;

use App\Application\Bodega\Constants\WarehouseConstants;
use App\Application\Services\EntregaService;
use App\Models\BodegaDetallesTalla;
use App\Models\CosturaBodegaDetalle;
use App\Models\EppBodegaDetalle;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BodegaPedidoPersistenciaService
{
    public function guardarDetalles(array $validatedData): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();

        $pedido = PedidoProduccion::where('numero_pedido', $validatedData['numero_pedido'])->first();
        if (!$pedido) {
            throw new \Exception('Pedido no encontrado');
        }

        $this->guardarDatosBasicos($validatedData, $pedido, $usuario, $rolesDelUsuario);
        $detalle = $this->guardarEstadoPorRol($validatedData, $pedido, $usuario, $rolesDelUsuario);
        $this->verificarYActualizarEstadoPedido($pedido);
        $this->dispararEventoTiempoReal($validatedData);

        return [
            'success' => true,
            'message' => 'Detalle guardado correctamente',
            'data' => $detalle,
        ];
    }

    public function registrarEntregaPrenda(array $datosPrenda, int $pedidoProduccionId): array
    {
        try {
            return app(EntregaService::class)->registrarEntregaPrenda($datosPrenda, $pedidoProduccionId);
        } catch (\Exception $e) {
            \Log::error('[BODEGA] Error al registrar entrega de prenda', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'datos_prenda' => $datosPrenda,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function registrarEntregasMasivas(int $pedidoProduccionId, array $prendasEntregadas): array
    {
        try {
            return app(EntregaService::class)->registrarEntregasMasivas($pedidoProduccionId, $prendasEntregadas);
        } catch (\Exception $e) {
            \Log::error('[BODEGA] Error al registrar entregas masivas', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'prendas_entregadas_count' => count($prendasEntregadas),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function guardarDatosBasicos(array $validatedData, PedidoProduccion $pedido, ?User $usuario, array $rolesDelUsuario): BodegaDetallesTalla
    {
        try {
            $datosBasicos = $this->prepararDatosBasicos($validatedData, $usuario);
            $areaFinal = $this->procesarAreaEnDatos($datosBasicos, $validatedData, $pedido);
            $this->procesarEstadosEnDatos($datosBasicos, $validatedData, $areaFinal);
            $detalleExistente = $this->encontrarOCrearDetalle($validatedData, $pedido, $datosBasicos);

            $detalleExistente->fill($datosBasicos);
            $detalleExistente->save();
            $pedido->touch();

            return $detalleExistente;
        } catch (\Throwable $e) {
            \Log::error('[ERROR] Error en guardarDatosBasicos:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function prepararDatosBasicos(array $validatedData, ?User $usuario): array
    {
        return [
            'pedido_produccion_id' => $validatedData['pedido_produccion_id'] ?? null,
            'recibo_prenda_id' => $validatedData['recibo_prenda_id'] ?? null,
            'prenda_id' => $validatedData['prenda_id'] ?? null,
            'pedido_epp_id' => $validatedData['pedido_epp_id'] ?? null,
            'prenda_nombre' => $validatedData['prenda_nombre'] ?? null,
            'talla_color_id' => $validatedData['talla_color_id'] ?? null,
            'asesor' => $validatedData['asesor'] ?? null,
            'empresa' => $validatedData['empresa'] ?? null,
            'cantidad' => $validatedData['cantidad'] ?? 0,
            'pendientes' => $validatedData['pendientes'] ?? null,
            'observaciones_bodega' => $validatedData['observaciones_bodega'] ?? null,
            'fecha_entrega' => $validatedData['fecha_entrega'] ?? null,
            'fecha_pedido' => $validatedData['fecha_pedido'] ?? null,
            'usuario_bodega_id' => $usuario?->id,
            'usuario_bodega_nombre' => $usuario?->name,
        ];
    }

    private function procesarAreaEnDatos(array &$datosBasicos, array $validatedData, PedidoProduccion $pedido): ?string
    {
        $areaInput = $validatedData[WarehouseConstants::FIELD_AREA] ?? null;
        $areaInput = is_string($areaInput) ? trim($areaInput) : $areaInput;

        if (empty($areaInput)) {
            $areaExistente = BodegaDetallesTalla::where(WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID, $pedido->id)
                ->where(WarehouseConstants::FIELD_NUMERO_PEDIDO, $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO])
                ->where(WarehouseConstants::FIELD_TALLA, $validatedData[WarehouseConstants::FIELD_TALLA])
                ->where(WarehouseConstants::FIELD_TALLA_COLOR_ID, $validatedData[WarehouseConstants::FIELD_TALLA_COLOR_ID] ?? null)
                ->when(isset($validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE]), fn ($q) => $q->where(WarehouseConstants::FIELD_PRENDA_NOMBRE, $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE]))
                ->when(isset($validatedData[WarehouseConstants::FIELD_CANTIDAD]), fn ($q) => $q->where(WarehouseConstants::FIELD_CANTIDAD, $validatedData[WarehouseConstants::FIELD_CANTIDAD]))
                ->value(WarehouseConstants::FIELD_AREA);
            $areaFinal = $areaExistente;
        } else {
            $areaFinal = $areaInput;
        }

        if (!empty($areaFinal)) {
            $datosBasicos[WarehouseConstants::FIELD_AREA] = $areaFinal;
        }

        return $areaFinal;
    }

    private function procesarEstadosEnDatos(array &$datosBasicos, array $validatedData, ?string $areaFinal): void
    {
        if (array_key_exists(WarehouseConstants::FIELD_ESTADO_BODEGA, $validatedData)
            && $validatedData[WarehouseConstants::FIELD_ESTADO_BODEGA] !== null) {
            $datosBasicos[WarehouseConstants::FIELD_ESTADO_BODEGA] =
                $validatedData[WarehouseConstants::FIELD_ESTADO_BODEGA] ?: WarehouseConstants::STATE_PENDING;
        }

        if (array_key_exists('estado', $validatedData) && $validatedData['estado'] !== null) {
            $datosBasicos[WarehouseConstants::FIELD_ESTADO_BODEGA] = $validatedData['estado'];

            if (!empty($areaFinal)) {
                if ($areaFinal === WarehouseConstants::AREA_COSTURA) {
                    $datosBasicos[WarehouseConstants::FIELD_COSTURA_ESTADO] = $validatedData['estado'];
                } elseif ($areaFinal === WarehouseConstants::AREA_EPP) {
                    $datosBasicos[WarehouseConstants::FIELD_EPP_ESTADO] = $validatedData['estado'];
                }
            }
        }

        if (array_key_exists(WarehouseConstants::FIELD_COSTURA_ESTADO, $validatedData)) {
            $datosBasicos[WarehouseConstants::FIELD_COSTURA_ESTADO] = $validatedData[WarehouseConstants::FIELD_COSTURA_ESTADO];
            if (!array_key_exists('estado', $validatedData)) {
                $datosBasicos[WarehouseConstants::FIELD_ESTADO_BODEGA] = $validatedData[WarehouseConstants::FIELD_COSTURA_ESTADO];
            }
        }

        if (array_key_exists(WarehouseConstants::FIELD_EPP_ESTADO, $validatedData)) {
            $datosBasicos[WarehouseConstants::FIELD_EPP_ESTADO] = $validatedData[WarehouseConstants::FIELD_EPP_ESTADO];
            if (!array_key_exists('estado', $validatedData)) {
                $datosBasicos[WarehouseConstants::FIELD_ESTADO_BODEGA] = $validatedData[WarehouseConstants::FIELD_EPP_ESTADO];
            }
        }
    }

    private function encontrarOCrearDetalle(array $validatedData, PedidoProduccion $pedido, array $datosBasicos): BodegaDetallesTalla
    {
        $criterios = [
            WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID => $pedido->id,
            WarehouseConstants::FIELD_NUMERO_PEDIDO => $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO],
            WarehouseConstants::FIELD_TALLA => $validatedData[WarehouseConstants::FIELD_TALLA],
            WarehouseConstants::FIELD_TALLA_COLOR_ID => $validatedData[WarehouseConstants::FIELD_TALLA_COLOR_ID] ?? null,
            WarehouseConstants::FIELD_PRENDA_NOMBRE => $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE] ?? null,
            WarehouseConstants::FIELD_CANTIDAD => $validatedData[WarehouseConstants::FIELD_CANTIDAD] ?? 0,
        ];

        $registros = BodegaDetallesTalla::where($criterios)->get();

        $detalle = null;
        if ($registros->count() > 0) {
            $detalle = $registros->first(function ($registro) use ($datosBasicos) {
                if ($datosBasicos['prenda_id'] && $registro->prenda_id == $datosBasicos['prenda_id']) {
                    return true;
                }
                if ($datosBasicos['pedido_epp_id'] && $registro->pedido_epp_id == $datosBasicos['pedido_epp_id']) {
                    return true;
                }
                return $registro->prenda_id || $registro->pedido_epp_id;
            });

            if (!$detalle) {
                $detalle = $registros->first();
            }
        }

        if (!$detalle) {
            $detalle = new BodegaDetallesTalla();
            $detalle->pedido_produccion_id = $pedido->id;
            $detalle->numero_pedido = $validatedData['numero_pedido'];
            $detalle->talla = $validatedData['talla'];
            $detalle->talla_color_id = $validatedData['talla_color_id'] ?? null;
            $detalle->prenda_nombre = $validatedData['prenda_nombre'] ?? null;
            $detalle->cantidad = $validatedData['cantidad'] ?? 0;
        }

        return $detalle;
    }

    private function guardarEstadoPorRol(array $validatedData, PedidoProduccion $pedido, ?User $usuario, array $rolesDelUsuario): ?Model
    {
        if (in_array(WarehouseConstants::ROLE_EPP_BODEGA, $rolesDelUsuario)) {
            $estadoNuevo = $validatedData[WarehouseConstants::FIELD_EPP_ESTADO] ?? ($validatedData[WarehouseConstants::FIELD_ESTADO_BODEGA] ?? WarehouseConstants::STATE_PENDING);
            if (empty($estadoNuevo)) {
                $estadoNuevo = WarehouseConstants::STATE_PENDING;
            }
            return $this->guardarEstadoArea(
                $validatedData,
                $pedido,
                $usuario,
                $estadoNuevo,
                EppBodegaDetalle::class,
                WarehouseConstants::AREA_EPP,
                WarehouseConstants::FIELD_EPP_ESTADO
            );
        } elseif (in_array(WarehouseConstants::ROLE_COSTURA_BODEGA, $rolesDelUsuario)) {
            $estadoNuevo = $validatedData[WarehouseConstants::FIELD_COSTURA_ESTADO] ?? ($validatedData[WarehouseConstants::FIELD_ESTADO_BODEGA] ?? WarehouseConstants::STATE_PENDING);
            if (empty($estadoNuevo)) {
                $estadoNuevo = WarehouseConstants::STATE_PENDING;
            }
            return $this->guardarEstadoArea(
                $validatedData,
                $pedido,
                $usuario,
                $estadoNuevo,
                CosturaBodegaDetalle::class,
                WarehouseConstants::AREA_COSTURA,
                WarehouseConstants::FIELD_COSTURA_ESTADO
            );
        }

        return null;
    }

    private function guardarEstadoArea(
        array $validatedData,
        PedidoProduccion $pedido,
        ?User $usuario,
        string $estadoNuevo,
        string $modelClass,
        string $areaDefault,
        string $stateFieldName
    ) {
        $datosArea = [
            WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID => $pedido->id,
            WarehouseConstants::FIELD_NUMERO_PEDIDO => $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO],
            WarehouseConstants::FIELD_TALLA => $validatedData[WarehouseConstants::FIELD_TALLA],
            WarehouseConstants::FIELD_PRENDA_NOMBRE => $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE] ?? null,
            WarehouseConstants::FIELD_ASESOR => $validatedData[WarehouseConstants::FIELD_ASESOR] ?? null,
            WarehouseConstants::FIELD_EMPRESA => $validatedData[WarehouseConstants::FIELD_EMPRESA] ?? null,
            WarehouseConstants::FIELD_CANTIDAD => $validatedData[WarehouseConstants::FIELD_CANTIDAD] ?? 0,
            WarehouseConstants::FIELD_PENDIENTES => $validatedData[WarehouseConstants::FIELD_PENDIENTES] ?? null,
            WarehouseConstants::FIELD_OBSERVACIONES_BODEGA => $validatedData[WarehouseConstants::FIELD_OBSERVACIONES_BODEGA] ?? null,
            WarehouseConstants::FIELD_FECHA_PEDIDO => $validatedData[WarehouseConstants::FIELD_FECHA_PEDIDO] ?? null,
            WarehouseConstants::FIELD_FECHA_ENTREGA => $validatedData[WarehouseConstants::FIELD_FECHA_ENTREGA] ?? null,
            WarehouseConstants::FIELD_AREA => $validatedData[WarehouseConstants::FIELD_AREA] ?? $areaDefault,
            WarehouseConstants::FIELD_ESTADO_BODEGA => $estadoNuevo,
            WarehouseConstants::FIELD_USUARIO_BODEGA_ID => $usuario->id,
            WarehouseConstants::FIELD_USUARIO_BODEGA_NOMBRE => $usuario->name,
        ];

        if (!array_key_exists(WarehouseConstants::FIELD_ESTADO_BODEGA, $validatedData)) {
            unset($datosArea[WarehouseConstants::FIELD_ESTADO_BODEGA]);
        }

        $guardado = call_user_func(
            [$modelClass, 'updateOrCreate'],
            [
                WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID => $pedido->id,
                WarehouseConstants::FIELD_NUMERO_PEDIDO => $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO],
                WarehouseConstants::FIELD_TALLA => $validatedData[WarehouseConstants::FIELD_TALLA],
                WarehouseConstants::FIELD_PRENDA_NOMBRE => $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE] ?? null,
                WarehouseConstants::FIELD_CANTIDAD => $validatedData[WarehouseConstants::FIELD_CANTIDAD] ?? 0,
            ],
            $datosArea
        );

        $updateBase = [
            $stateFieldName => $validatedData[$stateFieldName] ?? $estadoNuevo,
            WarehouseConstants::FIELD_AREA => $validatedData[WarehouseConstants::FIELD_AREA] ?? $areaDefault,
        ];

        if (array_key_exists(WarehouseConstants::FIELD_ESTADO_BODEGA, $validatedData)) {
            $updateBase[WarehouseConstants::FIELD_ESTADO_BODEGA] = $estadoNuevo;
        }

        if (($updateBase[WarehouseConstants::FIELD_ESTADO_BODEGA] ?? null) === WarehouseConstants::STATE_DELIVERED) {
            $updateBase['fecha_entrega_bodega'] = now();
        } else {
            $updateBase['fecha_entrega_bodega'] = null;
        }

        BodegaDetallesTalla::updateOrCreate(
            [
                WarehouseConstants::FIELD_PEDIDO_PRODUCCION_ID => $pedido->id,
                WarehouseConstants::FIELD_NUMERO_PEDIDO => $validatedData[WarehouseConstants::FIELD_NUMERO_PEDIDO],
                WarehouseConstants::FIELD_TALLA => $validatedData[WarehouseConstants::FIELD_TALLA],
                WarehouseConstants::FIELD_PRENDA_NOMBRE => $validatedData[WarehouseConstants::FIELD_PRENDA_NOMBRE] ?? null,
                WarehouseConstants::FIELD_CANTIDAD => $validatedData[WarehouseConstants::FIELD_CANTIDAD] ?? 0,
            ],
            $updateBase
        );

        $pedido->touch();

        return $guardado;
    }

    private function dispararEventoTiempoReal(array $validatedData)
    {
        try {
            \App\Events\BodegaDetallesActualizados::dispatch(
                $validatedData['numero_pedido'],
                $validatedData['talla'],
                [
                    'pendientes' => $validatedData['pendientes'] ?? null,
                    'observaciones_bodega' => $validatedData['observaciones_bodega'] ?? null,
                    'fecha_entrega' => $validatedData['fecha_entrega'] ?? null,
                    'fecha_pedido' => $validatedData['fecha_pedido'] ?? null,
                    'estado_bodega' => $validatedData['estado_bodega'] ?? null,
                    'area' => $validatedData['area'] ?? null,
                ]
            );
        } catch (\Exception $websocketError) {
            \Log::warning('WebSocket no disponible para tiempo real, pero datos guardados correctamente', [
                'websocket_error' => $websocketError->getMessage(),
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
            ]);
        }
    }

    private function verificarYActualizarEstadoPedido(PedidoProduccion $pedido): void
    {
        try {
            $itemsBodega = \DB::table('bodega_detalles_talla')
                ->where('pedido_produccion_id', $pedido->id)
                ->get();

            if ($itemsBodega->isEmpty()) {
                return;
            }

            $estadosCount = $itemsBodega->groupBy('estado_bodega')->map->count();

            $changedFields = [
                'estado' => $pedido->estado,
                'bodega_items_count' => $itemsBodega->count(),
                'bodega_pendientes_count' => $estadosCount[WarehouseConstants::STATE_PENDING] ?? 0,
                'bodega_entregados_count' => $estadosCount[WarehouseConstants::STATE_DELIVERED] ?? 0,
                'ultima_actualizacion_bodega' => now()->toISOString(),
            ];

            event(new \App\Events\PedidoActualizado($pedido, auth()->user(), $changedFields, 'updated'));
        } catch (\Exception $e) {
            \Log::error('[BodegaPedidoService] Error verificando estado del pedido', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
