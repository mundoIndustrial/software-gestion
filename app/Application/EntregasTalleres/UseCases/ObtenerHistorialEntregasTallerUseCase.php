<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\EntregaReciboCostura;
use App\Models\NovedadEntrega;

class ObtenerHistorialEntregasTallerUseCase
{
    public function execute(int $id, bool $esParcial)
    {
        // Obtener entregas normales
        $entregas = EntregaReciboCostura::where($esParcial ? 'recibo_parcial_id' : 'consecutivo_recibo_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener novedades
        $novedades = NovedadEntrega::where($esParcial ? 'recibo_parcial_id' : 'consecutivo_recibo_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $items = [];

        // Procesar entregas
        foreach ($entregas as $e) {
            $items[] = [
                'id' => $e->id,
                'cantidad_total' => $e->cantidad_entregada,
                'fecha' => $e->created_at->format('d/m/Y H:i'),
                'encargado' => $e->encargado,
                'talla' => $e->talla,
                'genero' => $e->genero,
                'color' => $e->color_nombre,
                'observaciones' => $e->observaciones,
                'es_novedad' => false,
                'created_at' => $e->created_at
            ];
        }

        // Procesar novedades
        foreach ($novedades as $n) {
            $items[] = [
                'id' => $n->id,
                'cantidad_total' => '📝 NOVEDAD',
                'fecha' => $n->created_at->format('d/m/Y H:i'),
                'encargado' => $n->encargado,
                'talla' => '-',
                'genero' => '-',
                'color' => null,
                'observaciones' => $n->observaciones,
                'es_novedad' => true,
                'created_at' => $n->created_at
            ];
        }

        // Ordenar por fecha descendente
        usort($items, function($a, $b) {
            return $b['created_at']->timestamp - $a['created_at']->timestamp;
        });

        // Remover el campo created_at que usamos solo para ordenar
        return array_map(function($item) {
            unset($item['created_at']);
            return $item;
        }, $items);
    }
}
