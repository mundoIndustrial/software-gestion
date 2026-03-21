<?php

namespace App\Constants;

/**
 * Constantes para los formatos de descripción de prenda
 * Centraliza los strings utilizados en la generación de descripciones
 */
class DescripcionPrendaConstants
{
    // Formato de cabecera
    const PRENDA_HEADER = "PRENDA {prenda_index}: {prenda_nombre}";
    
    // Tela y color
    const TELA_COLOR_FORMAT = "TELA: {tela} / COLOR: {color}";
    const TELA_COLOR_WITH_REF = "TELA: {tela} / COLOR: {color} (REF: {ref})";
    const REF_LABEL = "REF";
    
    // Manga
    const MANGA_LABEL = "MANGA";
    const MANGA_OBS_LABEL = "OBS. MANGA";
    
    // Bolsillos
    const BOLSILLOS_LABEL = "BOLSILLOS";
    
    // Broche
    const BROCHE_LABEL = "BROCHE";
    const BROCHE_OBS_LABEL = "OBS. BROCHE";
    
    // Tallas
    const TALLAS_LABEL = "TALLAS";
    
    // Separadores y valores por defecto
    const LINE_SEPARATOR = " | ";
    const VALUE_SEPARATOR = ": ";
    const TALLA_SEPARATOR = ", ";
    const DEFAULT_VALUE = "-";
    
    // Formatos especiales
    const TALLA_CANTIDAD_COLOR_FORMAT = "{talla}:{cantidad}-{color}";
    const TALLA_CANTIDAD_FORMAT = "{talla}: {cantidad}";
    
    // Mensajes de logging
    const LOG_PREFIX = "[GENERAR-DESCRIPCION]";
    const LOG_DESCRIPCION_GENERADA = "descripcion generada";
    const LOG_ERROR_GENERANDO = "Error generando descripcion";

    /**
     * Métodos helper para formateo de strings
     */
    public static function formatPrendaHeader(int $index, string $nombre): string
    {
        return str_replace(
            ['{prenda_index}', '{prenda_nombre}'],
            [$index, $nombre],
            self::PRENDA_HEADER
        );
    }

    public static function formatTelaColor(string $tela, string $color): string
    {
        return str_replace(
            ['{tela}', '{color}'],
            [$tela, $color],
            self::TELA_COLOR_FORMAT
        );
    }

    public static function formatTelaColorWithRef(string $tela, string $color, string $ref): string
    {
        return str_replace(
            ['{tela}', '{color}', '{ref}'],
            [$tela, $color, $ref],
            self::TELA_COLOR_WITH_REF
        );
    }

    public static function formatTallaCantidadColor(string $talla, int $cantidad, string $color): string
    {
        return str_replace(
            ['{talla}', '{cantidad}', '{color}'],
            [$talla, $cantidad, $color],
            self::TALLA_CANTIDAD_COLOR_FORMAT
        );
    }

    public static function formatTallaCantidad(string $talla, int $cantidad): string
    {
        return str_replace(
            ['{talla}', '{cantidad}'],
            [$talla, $cantidad],
            self::TALLA_CANTIDAD_FORMAT
        );
    }
}
