<?php

namespace App\Models\Concerns;

trait HasPrendaPedidoCompatibilityAttributes
{
    public function getColorAttribute()
    {
        $colorTela = $this->coloresTelas()->first();
        return $colorTela ? $colorTela->color : null;
    }

    public function getTelaAttribute()
    {
        $colorTela = $this->coloresTelas()->first();
        return $colorTela ? $colorTela->tela : null;
    }

    public function getTipoMangaAttribute()
    {
        $variante = $this->variantes()->first();
        return $variante ? $variante->tipo_manga : null;
    }

    public function getTipoBrocheAttribute()
    {
        $variante = $this->variantes()->first();
        return $variante ? $variante->tipo_broche_boton : null;
    }
}
