<?php

namespace App\Traits;

use App\Services\CalculadorDiasService;

/**
 * Trait para agregar funcionalidades de cálculo de días a controllers
 */
trait CalculaDiasHelper
{
    /**
     * Obtener información de días para un pedido
     */
    public function getInfoDiasPedido($pedido)
    {
        return [
            'total_dias' => $pedido->getTotalDias(),
            'total_dias_numero' => $pedido->getTotalDiasNumero(),
            'desglose' => $pedido->getDesgloseDiasPorProceso(),
            'en_retraso' => $pedido->estaEnRetraso(),
            'dias_retraso' => $pedido->getDiasDeRetraso(),
            'fecha_creacion' => $pedido->fecha_de_creacion_de_orden,
            'fecha_entrega_estimada' => $pedido->fecha_estimada_de_entrega,
        ];
    }

    /**
     * Obtener información de días para un proceso
     */
    public function getInfoDiasProceso($proceso)
    {
        return [
            'dias_duracion' => $proceso->dias_duracion,
            'dias_numero' => $proceso->getDiasNumero(),
            'dias_hasta_hoy' => $proceso->estáEnProgreso() ? $proceso->getDiasHastaHoy() : null,
            'fecha_inicio' => $proceso->fecha_inicio,
            'fecha_fin' => $proceso->fecha_fin,
            'estado' => $proceso->estado_proceso,
        ];
    }

    /**
     * Formatear información de días para respuesta JSON
     */
    public function formatearRespuestaDias($pedido, $incluirDesglose = true)
    {
        $respuesta = [
            'total_dias' => $pedido->getTotalDias(),
            'total_dias_numero' => $pedido->getTotalDiasNumero(),
            'fecha_creacion' => $pedido->fecha_de_creacion_de_orden?->format('Y-m-d'),
            'fecha_estimada' => $pedido->fecha_estimada_de_entrega?->format('Y-m-d'),
            'estado' => $pedido->estado,
            'en_retraso' => $pedido->estaEnRetraso(),
        ];

        if ($pedido->estaEnRetraso()) {
            $respuesta['dias_retraso'] = $pedido->getDiasDeRetraso();
        }

        if ($incluirDesglose) {
            $respuesta['desglose_procesos'] = $pedido->getDesgloseDiasPorProceso();
        }

        return $respuesta;
    }
}
