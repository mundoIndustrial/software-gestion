<?php

namespace App\Application\Pedidos\Services;

use App\Models\PrendaPedido;

final class PrendaPostUpdateHydrationService
{
    public function hidratarParaRespuesta(PrendaPedido $prenda): PrendaPedido
    {
        $prenda->refresh();

        if (!$prenda->relationLoaded('procesos')) {
            $prenda->load('procesos');
        }

        if (!$prenda->relationLoaded('fotos')) {
            $prenda->load('fotos');
        }

        return $prenda;
    }
}

