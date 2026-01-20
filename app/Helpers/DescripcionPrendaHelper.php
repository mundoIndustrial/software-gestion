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
     * 1. PRENDA X: NOMBRE (solo si hay múltiples prendas)
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
     * @param int|null $totalPrendas Total de prendas en el pedido (para decidir si mostrar "PRENDA X:")
     * @return string
     */
    public static function generarDescripcion(array $prenda, $totalPrendas = null): string
    {
        // Validar que tengamos los datos mínimos
        $numero = $prenda['numero'] ?? 1;
        $tipo = mb_strtoupper($prenda['tipo'] ?? '', 'UTF-8');
        $color = mb_strtoupper($prenda['color'] ?? '', 'UTF-8');
        $tela = mb_strtoupper($prenda['tela'] ?? '', 'UTF-8');
        $ref = mb_strtoupper($prenda['ref'] ?? '', 'UTF-8');
        // Limpiar REF: si ya está en el valor
        if (stripos($ref, 'REF:') === 0) {
            $ref = substr($ref, 4);
        }
        $manga = mb_strtoupper($prenda['manga'] ?? '', 'UTF-8');
        $obsManga = mb_strtoupper($prenda['obs_manga'] ?? '', 'UTF-8');
        $tipoBroche = mb_strtoupper($prenda['tipo_broche'] ?? '', 'UTF-8');
        $broche = mb_strtoupper($prenda['broche'] ?? '', 'UTF-8');
        $descripcionUserInput = mb_strtoupper($prenda['descripcion_usuario'] ?? '', 'UTF-8'); //  Campo para descripción del usuario
        
        // Procesar listas
        $bolsillos = $prenda['bolsillos'] ?? [];
        $reflectivos = $prenda['reflectivos'] ?? [];
        $tallas = $prenda['tallas'] ?? [];

        // Limpiar bolsillos de "SI" si existe como primer item
        $bolsillos = self::limpiarListaItem($bolsillos);
        
        // Limpiar reflectivos de "SI" si existe como primer item
        $reflectivos = self::limpiarListaItem($reflectivos);

        // 1. Nombre de la prenda -  Si solo hay una prenda, no mostrar "PRENDA 1:"
        if ($totalPrendas === 1) {
            $descripcion = "<span style='font-size: 15px !important; font-weight: bold;'>{$tipo}</span>";
        } else {
            $descripcion = "<span style='font-size: 15px !important; font-weight: bold;'>PRENDA {$numero}: {$tipo}</span>";
        }
        
        // 2. Línea de atributos: Color | Tela | Manga (con observación de manga si existe)
        $atributos = [];
        
        if ($color) {
            $atributos[] = "<b>COLOR:</b> " . $color;
        }
        
        if ($tela) {
            $telaTexto = $tela;
            if ($ref) {
                $telaTexto .= " <b>REF:</b>" . $ref;
            }
            $atributos[] = "<b>TELA:</b> {$telaTexto}";
        }
        
        if ($manga) {
            $mangaTexto = $manga;
            // Agregar observación de manga si existe y es diferente al tipo
            if ($obsManga && $obsManga !== $manga) {
                $mangaTexto .= " (" . $obsManga . ")";
            }
            $atributos[] = "<b>MANGA:</b> {$mangaTexto}";
        }
        
        if (!empty($atributos)) {
            $descripcion .= "\n<span style='font-size: 15px !important;'>" . implode(' | ', $atributos) . "</span>";
        }
        
        // 3. DESCRIPCION DEL USUARIO (si existe en el campo descripcion)
        if (!empty($descripcionUserInput)) {
            $descripcion .= "\n<span style='font-size: 15px !important;'>DESCRIPCION:\n" . $descripcionUserInput . "</span>";
        }
        
        // 3B. VIÑETAS: REFLECTIVO, BOLSILLOS, BROCHE (solo si existen)
        $partes = [];
        
        // Reflectivo
        if (!empty($reflectivos)) {
            $reflectivoTexto = implode(', ', array_map(function($item) { return mb_strtoupper($item, 'UTF-8'); }, $reflectivos));
            $partes[] = self::BULLET . " <b>REFLECTIVO:</b> {$reflectivoTexto}";
        }
        
        // Bolsillos
        if (!empty($bolsillos)) {
            $bolsillosTexto = implode(', ', array_map(function($item) { return mb_strtoupper($item, 'UTF-8'); }, $bolsillos));
            $partes[] = self::BULLET . " <b>BOLSILLOS:</b> {$bolsillosTexto}";
        }
        
        // Broche/Botón - SOLO si existe tipo_broche (label dinámico según el tipo)
        if ($tipoBroche && $broche) {
            $tipoLabel = $tipoBroche;
            $observacion = $broche;
            $partes[] = self::BULLET . " <b>{$tipoLabel}:</b> {$observacion}";
        }
        
        if (!empty($partes)) {
            // Agregar un salto de línea si ya hay descripción del usuario
            if (!empty($descripcionUserInput)) {
                $descripcion .= "\n<span style='font-size: 15px !important;'>" . implode("\n", $partes) . "</span>";
            } else {
                $descripcion .= "\n<span style='font-size: 15px !important;'>" . implode("\n", $partes) . "</span>";
            }
        }
        
        // 4. Tallas
        //  MANEJAR AMBAS ESTRUCTURAS:
        // 1. Nueva: {genero: {talla: cantidad}} 
        // 2. Antigua: {talla: cantidad}
        if (!empty($tallas) && is_array($tallas)) {
            // Detectar estructura: ¿tiene géneros o es plana?
            $esPorGenero = false;
            foreach ($tallas as $clave => $valor) {
                if (is_array($valor) && !is_numeric($clave)) {
                    // Es estructura con géneros {genero: {talla: cantidad}}
                    $esPorGenero = true;
                }
                break;
            }
            
            if ($esPorGenero) {
                // Estructura con géneros: formato en una sola línea
                $tallasHTML = "<span style='font-size: 15px !important;'><b>TALLAS:</b> ";
                $generosPartes = [];
                
                foreach ($tallas as $genero => $tallasCant) {
                    if (is_array($tallasCant) && !empty($tallasCant)) {
                        // Convertir género a mayúsculas
                        $generoFormato = mb_strtoupper($genero, 'UTF-8');
                        $tallasParte = [];
                        
                        foreach ($tallasCant as $talla => $cant) {
                            $cant = (int)$cant;
                            if ($cant > 0) {
                                $tallasParte[] = "<b><span style=\"color: #d32f2f;\">{$talla}: {$cant}</span></b>";
                            }
                        }
                        
                        if (!empty($tallasParte)) {
                            $generosPartes[] = "<b>{$generoFormato}:</b> " . implode(", ", $tallasParte);
                        }
                    }
                }
                
                $tallasHTML .= implode(" | ", $generosPartes) . "</span>";
                $descripcion .= "\n" . $tallasHTML;
            } else {
                // Estructura plana: {talla: cantidad}
                $tallasList = [];
                foreach ($tallas as $talla => $cant) {
                    $cant = (int)$cant;
                    if ($cant > 0) {
                        $tallasList[] = "<b><span style=\"color: #d32f2f;\">{$talla}: {$cant}</span></b>";
                    }
                }
                if (!empty($tallasList)) {
                    $descripcion .= "\n<span style='font-size: 15px !important;'><b>TALLAS:</b> " . implode(', ', $tallasList) . "</span>";
                }
            }
        }

        $descripcionFinal = trim($descripcion);
        
        \Log::info(' [HELPER] Descripción generada:', [
            'numero' => $numero,
            'tipo' => $tipo,
            'tiene_descripcion_usuario' => !empty($descripcionUserInput),
            'tiene_viñetas' => !empty($partes),
            'tiene_tallas' => !empty($tallas),
            'descripcion_HTML' => $descripcionFinal,
        ]);
        
        return $descripcionFinal;
    }

    /**
     * Extrae los datos de una prenda para el template
     * Analiza descripcion_variaciones para extraer bolsillos, reflectivos, etc.
     * 
     * @param \App\Models\PrendaPedido $prenda
     * @param int $index Número de prenda para la descripción
     * @return array
     */
    public static function extraerDatosPrenda($prenda, int $index = 1, $totalPrendas = null): array
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
        
        //  Leer manga_obs directamente del campo si existe (tiene prioridad)
        if ($prenda->manga_obs) {
            $datos['obs_manga'] = $prenda->manga_obs;
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
                    if (!empty($matches[2]) && mb_strtoupper($matches[2], 'UTF-8') !== 'NARANJA' && mb_strtoupper($matches[2], 'UTF-8') !== 'GRIS' && mb_strtoupper($matches[2], 'UTF-8') !== 'MANGA') {
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

        //  Extraer descripción personalizada del usuario
        // Si el campo descripcion contiene texto simple (sin estructura), usarlo como descripcion_usuario
        if (!empty($prenda->descripcion)) {
            $desc = $prenda->descripcion;
            
            // Detectar si tiene estructura (palabras clave como Logo, Reflectivo, Bolsillos, etc.)
            $tieneEstructura = preg_match('/\b(Logo|Reflectivo|Bolsillos|Broche|BOTÓN|DESCRIPCION|Manga|Talla|Cantidad):/i', $desc);
            
            if (!$tieneEstructura) {
                // Es texto simple del usuario - usarlo como descripcion_usuario
                $datos['descripcion_usuario'] = trim($desc);
            }
        }

        // Extraer Tallas (sin duplicados)
        $cantidadTalla = $prenda->cantidad_talla;
        
        // Si es string JSON, decodificar
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true);
        }
        
        if ($cantidadTalla && is_array($cantidadTalla)) {
            foreach ($cantidadTalla as $talla => $cantidad) {
                // Convertir la clave a string para evitar problemas con tallas numéricas
                $tallaStr = (string)$talla;
                
                // Solo incluir si tiene cantidad > 0
                // No filtrar nada - todas las tallas son válidas (XS, S, M, L, 32, 34, etc.)
                if ($cantidad > 0) {
                    $datos['tallas'][$tallaStr] = $cantidad;
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
        if (count($items) > 0 && in_array(mb_strtoupper(trim($items[0]), 'UTF-8'), ['SI', 'NO'])) {
            array_shift($items);
        }

        return $items;
    }
}
