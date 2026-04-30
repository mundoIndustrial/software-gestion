<?php

namespace App\Infrastructure\Insumos\ReadModels;

use Carbon\Carbon;

class RecibosViewTransformer
{
    private function extraerMotivoAnulacion(?string $notas): ?string
    {
        $texto = trim((string) $notas);
        if ($texto === '') {
            return null;
        }

        $lineas = preg_split('/\R+/', $texto) ?: [];
        $lineasAnulacion = [];

        foreach ($lineas as $linea) {
            $lineaLimpia = trim((string) $linea);
            if ($lineaLimpia === '') {
                continue;
            }

            if (preg_match('/^ANULACION\b/iu', $lineaLimpia)) {
                $lineasAnulacion[] = $lineaLimpia;
            }
        }

        if (empty($lineasAnulacion)) {
            return null;
        }

        return end($lineasAnulacion) ?: null;
    }

    private function extraerUltimaNovedadAsesora(?string $novedades): ?string
    {
        $texto = trim((string) $novedades);
        if ($texto === '') {
            return null;
        }

        $lineas = preg_split('/\R+/', $texto) ?: [];
        $lineasAsesora = [];

        foreach ($lineas as $linea) {
            $lineaLimpia = trim((string) $linea);
            if ($lineaLimpia === '') {
                continue;
            }

            if (preg_match('/^Asesor-/iu', $lineaLimpia)) {
                $lineasAsesora[] = $lineaLimpia;
            }
        }

        if (empty($lineasAsesora)) {
            return null;
        }

        return end($lineasAsesora) ?: null;
    }

    public function transform($recibos, array $parcialCreatedAtMap, callable $calcularDiasCallback, array $materialesMap = [])
    {
        return $recibos->map(function ($recibo) use ($parcialCreatedAtMap, $calcularDiasCallback, $materialesMap) {
            $tipoRecibo = strtoupper(trim((string) ($recibo->tipo_recibo ?? '')));
            $fechaRecibo = $recibo->recibo_created_at ?? null;
            $fechaPedido = $recibo->pedido_created_at ?? null;
            $fechaBaseInicio = $tipoRecibo === 'REFLECTIVO'
                ? ($fechaRecibo ?? $fechaPedido ?? ($recibo->created_at ?? null))
                : ($fechaPedido ?? ($recibo->created_at ?? null) ?? $fechaRecibo);

            $diasCalculados = 0;
            if ($fechaBaseInicio) {
                $fechaInicio = Carbon::parse($fechaBaseInicio);
                $diasCalculados = $calcularDiasCallback($fechaInicio);
            }

            $parcialId = null;
            $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
            if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                $parcialId = (int) $matches[1];
            }

            $esParcial = $parcialId !== null;
            $fechaInicioOrden = $fechaBaseInicio;
            if ($esParcial && isset($parcialCreatedAtMap[$parcialId]) && $parcialCreatedAtMap[$parcialId]) {
                $fechaInicioOrden = $parcialCreatedAtMap[$parcialId];
            }

            $materialesKey = $recibo->numero_pedido . '_' . $recibo->prenda_id;
            $cantidadMateriales = $materialesMap[$materialesKey] ?? 0;
            $estadoRecibo = (string) ($recibo->recibo_estado ?? $recibo->pedido_estado ?? '');
            $motivoDevolucion = null;
            $ultimaNovedadAsesora = $this->extraerUltimaNovedadAsesora((string) ($recibo->pedido_novedades ?? ''));

            if (in_array($estadoRecibo, ['DEVUELTO_ASESOR', 'Devuelto_Asesor'], true)) {
                $motivoDevolucion = trim((string) ($recibo->notas ?? ''));
            } elseif ($estadoRecibo === 'Anulada') {
                $motivoDevolucion = $this->extraerMotivoAnulacion((string) ($recibo->notas ?? ''))
                    ?? trim((string) ($recibo->notas ?? ''));
            }

            return (object) [
                'id' => $recibo->id,
                'numero_pedido' => $recibo->consecutivo_actual,
                'numero_pedido_original' => $recibo->numero_pedido_original,
                'cliente' => $recibo->cliente,
                'estado' => $recibo->recibo_estado ?? $recibo->pedido_estado,
                'area' => $recibo->recibo_area ?? $recibo->pedido_area,
                'recibo_estado' => $recibo->recibo_estado,
                'recibo_area' => $recibo->recibo_area,
                'pedido_estado' => $recibo->pedido_estado,
                'pedido_area' => $recibo->pedido_area,
                'created_at' => $fechaInicioOrden,
                'dia_de_entrega' => $recibo->dia_de_entrega,
                'fecha_estimada_de_entrega' => !empty($recibo->fecha_estimada_de_entrega)
                    ? Carbon::parse($recibo->fecha_estimada_de_entrega)->format('d/m/Y')
                    : null,
                'dias_calculados' => $diasCalculados,
                'pedido_produccion_id' => $recibo->pedido_produccion_id,
                'prenda_id' => $recibo->prenda_id,
                'consecutivo_actual' => $recibo->consecutivo_actual,
                'tipo_recibo' => $recibo->tipo_recibo,
                'notas' => (string) ($recibo->notas ?? ''),
                'motivo_devolucion' => $motivoDevolucion,
                'ultima_novedad_asesora' => $ultimaNovedadAsesora,
                'marcar_plooter' => $recibo->marcar_plooter ?? false,
                'es_parcial' => $esParcial,
                'pedido_parcial_id' => $parcialId,
                'updated_at' => $recibo->updated_at,
                'tiene_materiales' => $cantidadMateriales > 0,
                'cantidad_materiales' => $cantidadMateriales,
                'esta_completado' => (int) ($recibo->esta_completado ?? 0) === 1,
            ];
        });
    }
}
