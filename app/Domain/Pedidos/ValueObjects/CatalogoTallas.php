<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * CatalogoTallas — ValueObject de dominio
 *
 * Centraliza el catálogo de tallas disponibles por género.
 * Uso: CatalogoTallas::todos(), CatalogoTallas::porGenero('DAMA')
 */
final class CatalogoTallas
{
    private const CATALOGO = [
        'DAMA'             => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
        'CABALLERO'        => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
        'NUMEROS_DAMA'     => ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
        'NUMEROS_CABALLERO'=> ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
        'UNISEX'           => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
    ];

    /** Devuelve el catálogo completo. */
    public static function todos(): array
    {
        return self::CATALOGO;
    }

    /**
     * Devuelve las tallas de un género específico, o null si no existe.
     *
     * @param  string $genero  Ej: 'DAMA', 'CABALLERO', 'UNISEX'
     */
    public static function porGenero(string $genero): ?array
    {
        return self::CATALOGO[strtoupper($genero)] ?? null;
    }

    /** Comprueba si un género está en el catálogo. */
    public static function tieneGenero(string $genero): bool
    {
        return isset(self::CATALOGO[strtoupper($genero)]);
    }
}
