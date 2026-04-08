<?php

namespace App\Application\Pedidos\Services;

use App\Models\PrendaPedido;

class PrendaPedidoVariantSummaryFormatter
{
    public function format(PrendaPedido $prenda): string
    {
        if ($prenda->variantes->isEmpty()) {
            return 'Sin variantes';
        }

        $colores = $prenda->variantes
            ->pluck('color.nombre')
            ->filter()
            ->unique()
            ->implode(', ');

        $telas = $prenda->variantes
            ->pluck('tela.nombre')
            ->filter()
            ->unique()
            ->implode(', ');

        $mangas = $prenda->variantes
            ->pluck('tipoManga.nombre')
            ->filter()
            ->unique()
            ->implode(', ');

        $partes = array_filter([
            $colores ? "Colores: {$colores}" : null,
            $telas ? "Telas: {$telas}" : null,
            $mangas ? "Mangas: {$mangas}" : null,
        ]);

        return implode('; ', $partes) ?: 'Sin detalles';
    }
}
