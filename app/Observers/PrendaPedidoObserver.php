<?php

namespace App\Observers;

use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

class PrendaPedidoObserver
{
    public function deleting(PrendaPedido $prenda): void
    {
        Log::info("Prenda eliminada: {$prenda->nombre_prenda} (ID: {$prenda->id})");
    }
}
