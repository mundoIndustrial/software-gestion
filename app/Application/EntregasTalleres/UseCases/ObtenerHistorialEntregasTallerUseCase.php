<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\EntregaReciboCostura;

class ObtenerHistorialEntregasTallerUseCase
{
    public function execute(int $id, bool $esParcial)
    {
        $entregas = EntregaReciboCostura::where($esParcial ? 'recibo_parcial_id' : 'consecutivo_recibo_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $entregas->map(function($e) {
            $msgColor = $e->color_nombre ? " - {$e->color_nombre}" : "";
            return [
                'id' => $e->id,
                'cantidad_total' => $e->cantidad_entregada,
                'fecha' => $e->created_at->format('d/m/Y H:i'),
                'encargado' => $e->encargado,
                'detalle' => "{$e->talla} ({$e->genero}){$msgColor}"
            ];
        });
    }
}
