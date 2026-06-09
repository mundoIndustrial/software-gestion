<?php

namespace App\Helpers;

class EntradaCosturaHelper
{
    public static function formatearEtiquetaTalla(string $talla, string $genero, string $color): string
    {
        $partes = array_filter([$genero, $talla, $color], fn ($valor) => trim((string) $valor) !== '');

        return !empty($partes) ? implode(' - ', $partes) : 'Sin talla';
    }

    public static function ordenarTalla(string $genero, string $talla): array
    {
        $generoNormalizado = strtoupper(trim($genero));
        $generoOrden = match ($generoNormalizado) {
            'DAMA' => 1,
            'UNISEX' => 2,
            'CABALLERO' => 3,
            default => 4,
        };

        $tallaNormalizada = strtoupper(trim($talla));
        $ordenTallas = [
            'XXXS' => 1,
            'XXS' => 2,
            'XS' => 3,
            'S' => 4,
            'M' => 5,
            'L' => 6,
            'XL' => 7,
            'XXL' => 8,
            'XXXL' => 9,
        ];

        if (preg_match('/^\d+$/', $tallaNormalizada)) {
            $tallaOrden = 100 + (int) $tallaNormalizada;
        } else {
            $tallaOrden = $ordenTallas[$tallaNormalizada] ?? 999;
        }

        return [$generoOrden, $tallaOrden, $tallaNormalizada];
    }
}
