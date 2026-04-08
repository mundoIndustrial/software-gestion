<?php

namespace App\Application\Services\Asesores;

use App\Models\PrendaPedido;

class PrendaPedidoFinderService
{
    public function findOrFail(int $prendaId): PrendaPedido
    {
        return PrendaPedido::query()->findOrFail($prendaId);
    }

    public function findVarianteOrFail(PrendaPedido $prenda, int $varianteId): object
    {
        return $prenda->variantes()->findOrFail($varianteId);
    }

    public function findProcesoOrFail(PrendaPedido $prenda, int $procesoId): object
    {
        return $prenda->procesos()->findOrFail($procesoId);
    }
}
