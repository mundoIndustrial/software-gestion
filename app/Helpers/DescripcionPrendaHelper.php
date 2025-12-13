<?php

namespace App\Helpers;

class DescripcionPrendaHelper
{
    // Constante para el viñeta UTF-8
    private const BULLET = '•';
    
    /**
     * Genera descripción formateada de una prenda según template especificado
     * 
     * @param array $prenda Array con estructura: [
     *      'numero' => int,
     *      'tipo' => string (nombre_prenda),
     *      'color' => string,
     *      'tela' => string,
     *      'ref' => string (referencia tela),
     *      'manga' => string,
     *      'obs_manga' => string (observación de manga),
     *      'logo' => string,
     *      'bolsillos' => array de strings,
     *      'broche' => string,
     *      'reflectivos' => array de strings,
     *      'otros' => array de strings,
     *      'tallas' => array ['talla' => cantidad]
     * ]
     * @return string
     */
    public static function generarDescripcion(array $prenda): string
    {
        // Validar que tengamos los datos mínimos
        $numero = $prenda['numero'] ?? 1;
        $tipo = strtoupper($prenda['tipo'] ?? '');
        $color = $prenda['color'] ?? '';
        $tela = $prenda['tela'] ?? '';
        $ref = $prenda['ref'] ?? '';
        $manga = $prenda['manga'] ?? '';
        $obsManga = $prenda['obs_manga'] ?? '';
        $logo = $prenda['logo'] ?? '';
        
        // Procesar listas
        $bolsillos = $prenda['bolsillos'] ?? [];
        $broche = $prenda['broche'] ?? '';
        $reflectivos = $prenda['reflectivos'] ?? [];
        $otros = $prenda['otros'] ?? [];
        $tallas = $prenda['tallas'] ?? [];

        // Formatear bolsillos
        $bolsillosFormato = '';
        if (!empty($bolsillos)) {
            $bolsillosLista = array_map(function($b) {
                return self::BULLET . " " . trim($b);
            }, $bolsillos);
            $bolsillosFormato = implode("\n", $bolsillosLista);
        }

        // Formatear reflectivos
        $reflectivosFormato = '';
        if (!empty($reflectivos)) {
            $reflectivosLista = array_map(function($r) {
                return self::BULLET . " " . trim($r);
            }, $reflectivos);
            $reflectivosFormato = implode("\n", $reflectivosLista);
        }

        // Formatear otros
        $otrosFormato = '';
        if (!empty($otros)) {
            $otrosLista = array_map(function($o) {
                return self::BULLET . " " . trim($o);
            }, $otros);
            $otrosFormato = implode("\n", $otrosLista);
        }

        // Formatear tallas
        $tallasFormato = '';
        if (!empty($tallas) && is_array($tallas)) {
            $tallasList = [];
            foreach ($tallas as $talla => $cant) {
                if ($cant > 0) {
                    $tallasList[] = "- {$talla}: {$cant}";
                }
            }
            if (!empty($tallasList)) {
                $tallasFormato = implode("\n", $tallasList);
            }
        }

        // Construir referencia de tela
        $telaRef = $tela;
        if ($ref) {
            $telaRef .= " {$ref}";
        }

        // Construir descripción completa
        $descripcion = "PRENDA {$numero}: {$tipo}";
        
        if ($color || $tela || $manga) {
            $atributos = [];
            if ($color) $atributos[] = "Color: {$color}";
            if ($telaRef) $atributos[] = "Tela: {$telaRef}";
            if ($manga) {
                $mangaConObs = "Manga: {$manga}";
                if ($obsManga) {
                    $mangaConObs .= " ({$obsManga})";
                }
                $atributos[] = $mangaConObs;
            }
            $descripcion .= "\n" . implode(" | ", $atributos);
        }

        // Solo agregar sección DESCRIPCIÓN si hay logo o si hay bolsillos/reflectivos/otros
        if ($logo || $bolsillosFormato || $reflectivosFormato || $otrosFormato) {
            $descripcion .= "\n*** DESCRIPCIÓN: ***";
            if ($logo) {
                $descripcion .= "\n- Logo: {$logo}";
            }
        }

        if ($bolsillosFormato) {
            $descripcion .= "\n*** Bolsillos: ***\n{$bolsillosFormato}";
        }

        if ($prenda['broche'] ?? false) {
            $descripcion .= "\n*** Broche: ***\n{$prenda['broche']}";
        }

        if ($reflectivosFormato) {
            $descripcion .= "\n*** Reflectivo: ***\n{$reflectivosFormato}";
        }

        if ($otrosFormato) {
            $descripcion .= "\n*** Otros detalles: ***\n{$otrosFormato}";
        }

        if ($tallasFormato) {
            $descripcion .= "\n*** TALLAS: ***\n{$tallasFormato}";
        }

        return trim($descripcion);
    }

    /**
     * Extrae los datos de una prenda para el template
     * Analiza descripcion_variaciones para extraer bolsillos, reflectivos, etc.
     * 
     * @param \App\Models\PrendaPedido $prenda
     * @param int $index Número de prenda para la descripción
     * @return array
     */
    public static function extraerDatosPrenda($prenda, int $index = 1): array
    {
        $datos = [
            'numero' => $index,
            'tipo' => $prenda->nombre_prenda ?? '',
            'color' => '',
            'tela' => '',
            'ref' => '',
            'manga' => '',
            'obs_manga' => '',
            'logo' => '',
            'bolsillos' => [],
            'broche' => '',
            'reflectivos' => [],
            'otros' => [],
            'tallas' => [],
        ];

        // Extraer Color
        if ($prenda->relationLoaded('color') && $prenda->color) {
            $datos['color'] = $prenda->color->nombre;
        } elseif ($prenda->color_id) {
            $color = \App\Models\ColorPrenda::find($prenda->color_id);
            if ($color) {
                $datos['color'] = $color->nombre;
            }
        }

        // Extraer Tela y Referencia
        if ($prenda->relationLoaded('tela') && $prenda->tela) {
            $datos['tela'] = $prenda->tela->nombre;
            $datos['ref'] = $prenda->tela->referencia ? "REF:{$prenda->tela->referencia}" : '';
        } elseif ($prenda->tela_id) {
            $tela = \App\Models\TelaPrenda::find($prenda->tela_id);
            if ($tela) {
                $datos['tela'] = $tela->nombre;
                $datos['ref'] = $tela->referencia ? "REF:{$tela->referencia}" : '';
            }
        }

        // Extraer Manga de descripcion_variaciones o tipo_manga_id
        if ($prenda->descripcion_variaciones) {
            if (preg_match('/Manga:\s*([^|]+?)(?:\||$)/i', $prenda->descripcion_variaciones, $matches)) {
                $datos['manga'] = trim($matches[1]);
            }
            // Extraer obs_manga
            if (preg_match('/obs_manga["\']?\s*[:=]\s*["\']?([^"\'|]+)["\']?/i', $prenda->descripcion_variaciones, $matches)) {
                $datos['obs_manga'] = trim($matches[1]);
            }
        }
        if (!$datos['manga'] && $prenda->relationLoaded('tipoManga') && $prenda->tipoManga) {
            $datos['manga'] = $prenda->tipoManga->nombre;
        } elseif (!$datos['manga'] && $prenda->tipo_manga_id) {
            $manga = \App\Models\TipoManga::find($prenda->tipo_manga_id);
            if ($manga) {
                $datos['manga'] = $manga->nombre;
            }
        }

        // Extraer descripción principal (logo, especificaciones)
        if ($prenda->descripcion) {
            $desc = $prenda->descripcion;
            
            // Buscar Logo
            if (preg_match('/Logo:\s*(.+?)(?:Bolsillos?:|Reflectivo?s?:|Otros:|$)/is', $desc, $matches)) {
                $logoText = trim($matches[1]);
                // Limpiar "SI -" si existe
                $logoText = preg_replace('/^(SI|NO)\s*-\s*/i', '', $logoText);
                if ($logoText) {
                    $datos['logo'] = trim($logoText);
                }
            }
        }

        // Extraer Bolsillos, Reflectivos, Otros de descripcion_variaciones (formato prioritario)
        if ($prenda->descripcion_variaciones) {
            $varDesc = $prenda->descripcion_variaciones;
            
            // Buscar Bolsillos
            if (preg_match('/Bolsillos?:\s*(.+?)(?:Reflectivo?s?:|Otros:|Broche:|$)/is', $varDesc, $matches)) {
                $bolsillosText = trim($matches[1]);
                $bolsilloParsed = self::parsearListaItems($bolsillosText);
                if (!empty($bolsilloParsed)) {
                    $datos['bolsillos'] = $bolsilloParsed;
                }
            }

            // Buscar Broche
            if (preg_match('/Broche:\s*(.+?)(?:Reflectivo?s?:|Otros:|Bolsillos?:|$)/is', $varDesc, $matches)) {
                $brocheText = trim($matches[1]);
                // Limpiar pipes y caracteres especiales
                $brocheText = str_replace('|', '', $brocheText);
                $brocheText = trim($brocheText);
                $datos['broche'] = $brocheText;
            }

            // Buscar Reflectivos
            if (preg_match('/Reflectivo?s?:\s*(.+?)(?:Otros:|Bolsillos?:|Broche:|$)/is', $varDesc, $matches)) {
                $reflectivosText = trim($matches[1]);
                $reflectivoParsed = self::parsearListaItems($reflectivosText);
                if (!empty($reflectivoParsed)) {
                    $datos['reflectivos'] = $reflectivoParsed;
                }
            }

            // Buscar Otros detalles
            if (preg_match('/Otros\s+detalles?:\s*(.+?)(?:Bolsillos?:|Reflectivo?s?:|Broche:|$)/is', $varDesc, $matches)) {
                $otrosText = trim($matches[1]);
                $otrosParsed = self::parsearListaItems($otrosText);
                if (!empty($otrosParsed)) {
                    $datos['otros'] = $otrosParsed;
                }
            }
        }

        // Fallback: Extraer Bolsillos y Reflectivos de descripcion si no están en variaciones
        if ($prenda->descripcion && (empty($datos['bolsillos']) || empty($datos['reflectivos']))) {
            $desc = $prenda->descripcion;
            
            if (empty($datos['bolsillos'])) {
                if (preg_match('/Bolsillos?:\s*(.+?)(?:Reflectivo?s?:|Otros:|$)/is', $desc, $matches)) {
                    $bolsillosText = trim($matches[1]);
                    $bolsilloParsed = self::parsearListaItems($bolsillosText);
                    if (!empty($bolsilloParsed)) {
                        $datos['bolsillos'] = $bolsilloParsed;
                    }
                }
            }

            if (empty($datos['reflectivos'])) {
                if (preg_match('/Reflectivo?s?:\s*(.+?)(?:Otros:|Bolsillos?:|$)/is', $desc, $matches)) {
                    $reflectivosText = trim($matches[1]);
                    $reflectivoParsed = self::parsearListaItems($reflectivosText);
                    if (!empty($reflectivoParsed)) {
                        $datos['reflectivos'] = $reflectivoParsed;
                    }
                }
            }

            if (empty($datos['otros'])) {
                if (preg_match('/Otros\s+detalles?:\s*(.+?)(?:Bolsillos?:|Reflectivo?s?:|$)/is', $desc, $matches)) {
                    $otrosText = trim($matches[1]);
                    $otrosParsed = self::parsearListaItems($otrosText);
                    if (!empty($otrosParsed)) {
                        $datos['otros'] = $otrosParsed;
                    }
                }
            }
        }

        // Extraer Tallas (sin duplicados)
        if ($prenda->cantidad_talla && is_array($prenda->cantidad_talla)) {
            foreach ($prenda->cantidad_talla as $talla => $cantidad) {
                if ($cantidad > 0) {
                    $datos['tallas'][$talla] = $cantidad;
                }
            }
        }

        return $datos;
    }

    /**
     * Parsea texto con items separados por viñetas o líneas
     * Limpia formatos redundantes como "SI -", "NO -", etc.
     * Ej: "• SI - Pecho" → "Pecho" o "• Pecho" → "Pecho"
     * 
     * @param string $text
     * @return array
     */
    private static function parsearListaItems(string $text): array
    {
        $items = [];
        $text = trim($text);
        
        if (empty($text)) {
            return $items;
        }
        
        // Limpiar caracteres problemáticos (saltos de línea anidados, espacios extras, pipes)
        $text = str_replace(["\r\n", "\r", "|"], "\n", $text);
        $text = preg_replace('/\s+/', ' ', $text); // Normalizar espacios múltiples
        
        // Dividir por viñeta o guion seguido de espacios
        $lineas = preg_split('/[•\-\n]/', $text);
        
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) {
                continue;
            }
            
            // Limpiar prefijos redundantes: "SI -", "NO -", etc. (incluyendo espacios)
            $linea = preg_replace('/^\s*(SI|NO)\s*[\-:\s]+/i', '', $linea);
            $linea = trim($linea);
            
            if ($linea) {
                // Verificar que no sea un duplicado
                if (!in_array($linea, $items)) {
                    $items[] = $linea;
                }
            }
        }
        
        return $items;
    }
}
