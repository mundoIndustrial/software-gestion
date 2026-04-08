<?php

namespace App\Infrastructure\Repositories;

use App\Models\TipoPrenda;
use Illuminate\Database\Eloquent\Collection;

class CatalogoRepository
{
    /**
     * Obtener todos los tipos de prenda activos
     */
    public function obtenerTiposPrendas(): Collection
    {
        return TipoPrenda::where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Reconocer tipo de prenda por nombre
     */
    public function reconocerPrendaPorNombre(string $nombre): ?TipoPrenda
    {
        return TipoPrenda::reconocerPorNombre($nombre);
    }
}
