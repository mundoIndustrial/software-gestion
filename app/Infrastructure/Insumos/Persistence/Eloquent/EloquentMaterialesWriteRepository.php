<?php

namespace App\Infrastructure\Insumos\Persistence\Eloquent;

use App\Domain\Insumos\Repositories\MaterialesWriteRepository;
use App\Models\MaterialesOrdenInsumos;
use App\Models\PedidoProduccion;

class EloquentMaterialesWriteRepository implements MaterialesWriteRepository
{
    public function guardarMaterialesDetallados(
        string $numeroPedido,
        array $materiales,
        ?int $prendaId = null,
        ?int $prendaBodegaId = null,
        ?int $numeroRecibo = null,
        ?string $tipoRecibo = null
    ): array
    {
        $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
        $esBodega = strtoupper(trim((string) $tipoRecibo)) === 'CORTE-PARA-BODEGA' || ($prendaBodegaId ?? 0) > 0;

        $materialesGuardados = 0;
        $materialesEliminados = 0;

        foreach ($materiales as $material) {
            $isRecibido = ($material['recibido'] ?? false) === true
                || ($material['recibido'] ?? null) === 'true'
                || ($material['recibido'] ?? null) === 1
                || ($material['recibido'] ?? null) === '1';

            if ($isRecibido) {
                $matchCriteria = [
                    'numero_pedido' => $orden->numero_pedido,
                    'nombre_material' => $material['nombre'],
                ];

                if ($esBodega && ($prendaBodegaId ?? 0) > 0) {
                    $matchCriteria['prenda_bodega_id'] = $prendaBodegaId;
                    if (($numeroRecibo ?? 0) > 0) {
                        $matchCriteria['numero_recibo'] = $numeroRecibo;
                    }
                } elseif ($prendaId) {
                    $matchCriteria['prenda_id'] = $prendaId;
                }

                MaterialesOrdenInsumos::updateOrCreate(
                    $matchCriteria,
                    [
                        'prenda_id' => $esBodega ? null : $prendaId,
                        'prenda_bodega_id' => ($esBodega && ($prendaBodegaId ?? 0) > 0) ? $prendaBodegaId : null,
                        'numero_recibo' => ($esBodega && ($numeroRecibo ?? 0) > 0) ? $numeroRecibo : null,
                        'fecha_orden' => $material['fecha_orden'] ?? null,
                        'fecha_pedido' => $material['fecha_pedido'] ?? null,
                        'fecha_pago' => $material['fecha_pago'] ?? null,
                        'fecha_llegada' => $material['fecha_llegada'] ?? null,
                        'fecha_despacho' => $material['fecha_despacho'] ?? null,
                        'observaciones' => $material['observaciones'] ?? null,
                        'recibido' => true,
                    ]
                );

                $materialesGuardados++;
                continue;
            }

            $deleteCriteria = [
                'numero_pedido' => $orden->numero_pedido,
                'nombre_material' => $material['nombre'],
            ];

            if ($esBodega && ($prendaBodegaId ?? 0) > 0) {
                $deleteCriteria['prenda_bodega_id'] = $prendaBodegaId;
                if (($numeroRecibo ?? 0) > 0) {
                    $deleteCriteria['numero_recibo'] = $numeroRecibo;
                }
            } elseif ($prendaId) {
                $deleteCriteria['prenda_id'] = $prendaId;
            }

            $deleted = MaterialesOrdenInsumos::where($deleteCriteria)->delete();

            if ($deleted > 0) {
                $materialesEliminados++;
            }
        }

        $mensajes = [];
        if ($materialesGuardados > 0) {
            $mensajes[] = "Se guardaron {$materialesGuardados} material(es)";
        }
        if ($materialesEliminados > 0) {
            $mensajes[] = "Se eliminaron {$materialesEliminados} material(es)";
        }

        return [
            'success' => true,
            'message' => !empty($mensajes) ? implode(' y ', $mensajes) . ' correctamente' : 'Sin cambios',
        ];
    }

    public function eliminarMaterialPorNombre(string $numeroPedido, string $nombreMaterial, ?int $prendaId = null): array
    {
        $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();

        $criteria = [
            'numero_pedido' => $orden->numero_pedido,
            'nombre_material' => $nombreMaterial,
        ];

        if ($prendaId) {
            $criteria['prenda_id'] = $prendaId;
        }

        $deleted = MaterialesOrdenInsumos::where($criteria)->delete();

        return [
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Material eliminado correctamente' : 'Material no encontrado',
        ];
    }

    public function guardarObservaciones(string $numeroPedido, string $nombreMaterial, ?string $observaciones): array
    {
        $material = MaterialesOrdenInsumos::where('numero_pedido', $numeroPedido)
            ->where('nombre_material', $nombreMaterial)
            ->first();

        if (!$material) {
            return [
                'success' => false,
                'error' => 'Material no encontrado',
            ];
        }

        $material->update([
            'observaciones' => $observaciones,
        ]);

        return [
            'success' => true,
            'message' => 'Observaciones guardadas exitosamente',
            'material_id' => $material->id,
            'observaciones' => $material->observaciones,
        ];
    }
}
