<?php

namespace App\Helpers;

/**
 * DescripcionPrendaLegacyFormatter
 * 
 * Genera descripciones de prendas en el FORMATO LEGACY EXACTO del 45452
 * 
 * Formato correcto:
 * PRENDA 1: CAMISA DRILL
 * Color: NARANJA | Tela: DRILL BORNEO REF:REF-DB-001 | Manga: LARGA
 * DESCRIPCION: LOGO BORDADO EN ESPALDA
 *    . Reflectivo: [DETALLES]
 *    . Bolsillos: [DETALLES]
 * Tallas: L: 50, M: 50, S: 50, XL: 50
 */
class DescripcionPrendaLegacyFormatter
{
    /**
     * Generar descripción en formato legacy EXACTO como 45452
     * 
     * @param array $prenda Array con estructura:
     *      'numero' => int,
     *      'tipo' => string,
     *      'descripcion' => string (logo/detalles),
     *      'tela' => string,
     *      'ref' => string,
     *      'color' => string,
     *      'manga' => string,
     *      'tiene_bolsillos' => bool,
     *      'bolsillos_obs' => string,
     *      'tiene_reflectivo' => bool,
     *      'reflectivo_obs' => string,
     *      'tallas' => array ['talla' => cantidad]
     * @return string Descripción formateada en formato legacy exacto
     */
    public static function generar(array $prenda): string
    {
        $partes = [];
        
        // Línea 1: PRENDA X: [tipo]
        if (isset($prenda['numero']) && isset($prenda['tipo'])) {
            $partes[] = "PRENDA {$prenda['numero']}: {$prenda['tipo']}";
        }
        
        // Línea 2: Color: [color] | Tela: [tela] REF:[ref] | Manga: [manga]
        $lineaDos = [];
        if (!empty($prenda['color'])) {
            $lineaDos[] = "Color: {$prenda['color']}";
        }
        if (!empty($prenda['tela'])) {
            $tela = $prenda['tela'];
            if (!empty($prenda['ref'])) {
                $tela .= " REF:{$prenda['ref']}";
            }
            $lineaDos[] = "Tela: {$tela}";
        }
        if (!empty($prenda['manga'])) {
            $lineaDos[] = "Manga: " . strtoupper($prenda['manga']);
        }
        if (!empty($lineaDos)) {
            $partes[] = implode(" | ", $lineaDos);
        }
        
        // Línea 3: DESCRIPCION: [detalles]
        if (!empty($prenda['descripcion'])) {
            $partes[] = "DESCRIPCION: {$prenda['descripcion']}";
        }
        
        // Líneas 4+: Detalles con bullets (solo si existen)
        if (isset($prenda['tiene_reflectivo']) && $prenda['tiene_reflectivo'] && !empty($prenda['reflectivo_obs'])) {
            $partes[] = "   . Reflectivo: {$prenda['reflectivo_obs']}";
        }
        
        if (isset($prenda['tiene_bolsillos']) && $prenda['tiene_bolsillos'] && !empty($prenda['bolsillos_obs'])) {
            $partes[] = "   . Bolsillos: {$prenda['bolsillos_obs']}";
        }
        
        // Agregar botón/broche si existe observación
        if (!empty($prenda['broche_obs'])) {
            $partes[] = "   . Botón: {$prenda['broche_obs']}";
        }
        
        // Última línea: Tallas: [talla]: [cant], [talla]: [cant], ...
        if (!empty($prenda['tallas']) && is_array($prenda['tallas'])) {
            $tallasList = [];
            foreach ($prenda['tallas'] as $talla => $cant) {
                if ($cant > 0) {
                    $tallasList[] = "{$talla}: {$cant}";
                }
            }
            if (!empty($tallasList)) {
                $partes[] = "Tallas: " . implode(", ", $tallasList);
            }
        }
        
        return implode("\n", $partes);
    }
}
