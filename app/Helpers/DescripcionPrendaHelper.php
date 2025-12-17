<?php

namespace App\Helpers;

class DescripcionPrendaHelper
{
    // Constante para el viñeta UTF-8
    private const BULLET = '•';
    
    /**
     * Genera descripción formateada de una prenda según template especificado
     * REPLICANDO EXACTAMENTE LA LÓGICA DEL MODAL (order-detail-modal-manager.js líneas 183-303)
     * 
     * Formato:
     * 1. PRENDA X: NOMBRE
     * 2. Color: X | Tela: X REF:X | Manga: X
     * 3. DESCRIPCION:
     *    • Reflectivo: X
     *    • Bolsillos: X
     *    • Broche/Botón: X
     * 4. Tallas: X: Y, Z: W
     * 
     * @param array $prenda Array con estructura: [
     *      'numero' => int,
     *      'tipo' => string (nombre_prenda),
     *      'color' => string,
     *      'tela' => string,
     *      'ref' => string (referencia tela),
     *      'manga' => string,
     *      'obs_manga' => string (observación de manga),
     *      'tipo_broche' => string (nombre del tipo: Botón, Broche, etc.),
     *      'broche' => string (observación del broche),
     *      'bolsillos' => array de strings,
     *      'reflectivos' => array de strings,
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
        $tipoBroche = $prenda['tipo_broche'] ?? '';
        $broche = $prenda['broche'] ?? '';
        
        // Procesar listas
        $bolsillos = $prenda['bolsillos'] ?? [];
        $reflectivos = $prenda['reflectivos'] ?? [];
        $tallas = $prenda['tallas'] ?? [];

        // Limpiar bolsillos de "SI" si existe como primer item
        $bolsillos = self::limpiarListaItem($bolsillos);
        
        // Limpiar reflectivos de "SI" si existe como primer item
        $reflectivos = self::limpiarListaItem($reflectivos);

        // 1. Nombre de la prenda
        $descripcion = "PRENDA {$numero}: {$tipo}";
        
        // 2. Línea de atributos: Color | Tela | Manga (con observación de manga si existe)
        $atributos = [];
        
        if ($color) {
            $atributos[] = "Color: " . strtoupper($color);
        }
        
        if ($tela) {
            $telaTexto = strtoupper($tela);
            if ($ref) {
                $telaTexto .= " " . strtoupper($ref);
            }
            $atributos[] = "Tela: {$telaTexto}";
        }
        
        if ($manga) {
            $mangaTexto = strtoupper($manga);
            // Agregar observación de manga si existe y es diferente al tipo
            if ($obsManga && strtoupper($obsManga) !== strtoupper($manga)) {
                $mangaTexto .= " ({$obsManga})";
            }
            $atributos[] = "Manga: {$mangaTexto}";
        }
        
        if (!empty($atributos)) {
            $descripcion .= "\n" . implode(' | ', $atributos);
        }
        
        // 3. DESCRIPCION con viñetas (sin manga, solo reflectivo, bolsillos y broche si existe)
        $partes = [];
        
        // Reflectivo
        if (!empty($reflectivos)) {
            $reflectivoTexto = implode(', ', array_map('strtoupper', $reflectivos));
            $partes[] = self::BULLET . " Reflectivo: {$reflectivoTexto}";
        }
        
        // Bolsillos
        if (!empty($bolsillos)) {
            $bolsillosTexto = implode(', ', array_map('strtoupper', $bolsillos));
            $partes[] = self::BULLET . " Bolsillos: {$bolsillosTexto}";
        }
        
        // Broche/Botón - SOLO si existe tipo_broche (label dinámico según el tipo)
        if ($tipoBroche && $broche) {
            $tipoLabel = strtoupper($tipoBroche);
            $observacion = strtoupper($broche);
            $partes[] = self::BULLET . " {$tipoLabel}: {$observacion}";
        }
        
        if (!empty($partes)) {
            $descripcion .= "\nDESCRIPCION:\n" . implode("\n", $partes);
        }
        
        // 4. Tallas
        if (!empty($tallas) && is_array($tallas)) {
            $tallasList = [];
            foreach ($tallas as $talla => $cant) {
                if ($cant > 0) {
                    $tallasList[] = "{$talla}: {$cant}";
                }
            }
            if (!empty($tallasList)) {
                $descripcion .= "\nTallas: " . implode(', ', $tallasList);
            }
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
            'tipo_broche' => '',
            'broche' => '',
            'bolsillos' => [],
            'reflectivos' => [],
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

        // Extraer Tipo de Broche desde la relación
        if ($prenda->relationLoaded('tipoBroche') && $prenda->tipoBroche) {
            $datos['tipo_broche'] = $prenda->tipoBroche->nombre;
        } elseif ($prenda->tipo_broche_id) {
            $tipoBroche = \App\Models\TipoBroche::find($prenda->tipo_broche_id);
            if ($tipoBroche) {
                $datos['tipo_broche'] = $tipoBroche->nombre;
            }
        }

        // Extraer descripción principal (logo, especificaciones)
        if ($prenda->descripcion) {
            $desc = $prenda->descripcion;
            
            // Extraer Color (si existe en la descripción)
            if (empty($datos['color'])) {
                if (preg_match('/\b(NARANJA|ROJO|AZUL|GRIS|NEGRO|BLANCO|VERDE|AMARILLO|BORDO|PÚRPURA|ROSA|BLANCO Y NEGRO|MULTI[A-Z]*)\b/i', $desc, $matches)) {
                    $datos['color'] = $matches[1];
                }
            }
            
            // Extraer Tela (si existe en la descripción)
            if (empty($datos['tela'])) {
                if (preg_match('/\b(DRILL|POLIESTER|ALGODÓN|OXFORD|LINO|SARGA|TWILL|POPELINA|GABARDINA)(?:\s+([A-Z]+))?\b/i', $desc, $matches)) {
                    $datos['tela'] = $matches[1];
                    // Agregar el modificador si existe (como BORNEO)
                    if (!empty($matches[2]) && strtoupper($matches[2]) !== 'NARANJA' && strtoupper($matches[2]) !== 'GRIS' && strtoupper($matches[2]) !== 'MANGA') {
                        $datos['tela'] .= ' ' . $matches[2];
                    }
                }
            }
            
            // Buscar Logo
            if (preg_match('/Logo:\s*(.+?)(?:Bolsillos?:|Reflectivo?s?:|Broche:|Manga:|$)/is', $desc, $matches)) {
                $logoText = trim($matches[1]);
                // Limpiar "SI -" si existe
                $logoText = preg_replace('/^(SI|NO)\s*-\s*/i', '', $logoText);
                if ($logoText && strlen($logoText) > 5) {
                    $datos['logo'] = trim($logoText);
                }
            }
        }

        // Extraer Bolsillos, Reflectivos, Broche y Manga de descripcion (si no están en variaciones)
        // Primero intenta descripcion_variaciones, luego descripcion
        $fuente = ($prenda->descripcion_variaciones) ? $prenda->descripcion_variaciones : $prenda->descripcion;
        
        if ($fuente) {
            $varDesc = $fuente;
            
            // Buscar Bolsillos - detener en Broche, Reflectivo, Otros, Manga, o salto de línea
            if (empty($datos['bolsillos'])) {
                if (preg_match('/Bolsillos?:\s*(.+?)(?:Broche:|Reflectivo?s?:|Otros:|Manga:|[\n\r]|$)/is', $varDesc, $matches)) {
                    $bolsillosText = trim($matches[1]);
                    $bolsilloParsed = self::parsearListaItems($bolsillosText);
                    if (!empty($bolsilloParsed)) {
                        $datos['bolsillos'] = $bolsilloParsed;
                    }
                }
            }

            // Buscar Broche - solo capturar la observación (el tipo ya se obtuvo de la relación)
            if (empty($datos['broche'])) {
                if (preg_match('/Broche:\s*([^\n\r]+?)(?:Manga:|Reflectivo?s?:|Bolsillos?:|Otros:|[\n\r]|$)/is', $varDesc, $matches)) {
                    $brocheText = trim($matches[1]);
                    // Limpiar y usar como observación
                    $datos['broche'] = trim(str_replace(['|', '•'], '', $brocheText));
                }
            }

            // Buscar Manga - detener en Broche, Reflectivo, Bolsillos, Otros
            if (empty($datos['manga'])) {
                if (preg_match('/Manga:\s*(.+?)(?:Broche:|Bolsillos?:|Reflectivo?s?:|Otros:|[\n\r]|$)/is', $varDesc, $matches)) {
                    $mangaText = trim($matches[1]);
                    // Extraer solo el tipo de manga (CORTA, LARGA, 3/4, etc.)
                    if (preg_match('/\b(CORTA|LARGA|MEDIA|3\/4|TRES CUARTOS|SIN MANGA)\b/i', $mangaText, $mangaMatch)) {
                        $datos['manga'] = $mangaMatch[1];
                    }
                    // Extraer observación de manga (después del guion)
                    if (preg_match('/-\s*(.+)$/i', $mangaText, $obsMatch)) {
                        $datos['obs_manga'] = trim($obsMatch[1]);
                    }
                }
            }

            // Buscar Reflectivos - detener en Broche, Otros, Bolsillos, Manga, o salto de línea
            if (empty($datos['reflectivos'])) {
                if (preg_match('/Reflectivo?s?:\s*(.+?)(?:Broche:|Otros:|Bolsillos?:|Manga:|[\n\r]|$)/is', $varDesc, $matches)) {
                    $reflectivosText = trim($matches[1]);
                    $reflectivoParsed = self::parsearListaItems($reflectivosText);
                    if (!empty($reflectivoParsed)) {
                        $datos['reflectivos'] = $reflectivoParsed;
                    }
                }
            }

            // Buscar Otros detalles
            if (empty($datos['otros'])) {
                if (preg_match('/Otros\s+detalles?:\s*(.+?)(?:Bolsillos?:|Reflectivo?s?:|Broche:|Manga:|[\n\r]|$)/is', $varDesc, $matches)) {
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
     * Limpia formatos redundantes como "SI -", "NO -", "LLEVA...", etc.
     * Ej: "• SI - Pecho" → "Pecho" o "• LLEVA BOLSILLOS CON..." → "BOLSILLOS CON..."
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
            
            // Limpiar prefijos que comienzan con "LLEVA " (como "LLEVA BOLSILLOS CON...")
            // Reemplazar con la parte descriptiva sin el "LLEVA"
            $linea = preg_replace('/^LLEVA\s+/i', '', $linea);
            
            // Limpiar si empieza con "CON " después del anterior
            $linea = preg_replace('/^CON\s+/i', '', $linea);
            
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

    /**
     * Limpia un array de items removiendo "SI" o "NO" como primer elemento
     * Si el primer item es solo "SI" o "NO", lo remueve
     * 
     * @param array $items
     * @return array
     */
    private static function limpiarListaItem(array $items): array
    {
        if (empty($items)) {
            return $items;
        }

        // Si el primer item es exactamente "SI" o "NO" (sin más), removerlo
        if (count($items) > 0 && in_array(strtoupper(trim($items[0])), ['SI', 'NO'])) {
            array_shift($items);
        }

        return $items;
    }
}
