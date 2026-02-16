<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para actualizar prenda COMPLETA con todas sus relaciones
 * 
 * Maneja:
 * - prendas_pedido (campos bÃ¡sicos)
 * - prenda_pedido_tallas
 * - prenda_pedido_variantes
 * - prenda_pedido_colores_telas
 * - prenda_fotos_tela_pedido
 * - prenda_fotos_pedido
 * - pedidos_procesos_prenda_detalles
 * - pedidos_procesos_imagenes
 */
final class ActualizarPrendaCompletaDTO
{
    public function __construct(
        public readonly int|string $prendaId,
        public readonly ?string $nombrePrenda = null,
        public readonly ?string $descripcion = null,
        public readonly ?int $deBodega = null,  // Cambiar a INT: 1=bodega, 0=confección
        public readonly ?array $imagenes = null,
        public readonly ?array $imagenesExistentes = null,  // URLs de imágenes existentes a preservar
        public readonly ?array $imagenesAEliminar = null,   // Array de IDs o rutas de imágenes a eliminar
        public readonly ?array $cantidadTalla = null,                  // { GENERO: { TALLA: CANTIDAD } }
        public readonly ?array $variantes = null,                      // [ { manga_id, broche_id, bolsillos, obs } ]
        public readonly ?array $coloresTelas = null,                   // [ { color_id, tela_id } ]
        public readonly ?array $fotosTelas = null,                     // [ { color_tela_id, ruta } ]
        public readonly ?array $fotos = null,                          // [ { ruta } ]
        public readonly ?array $procesos = null,                       // [ { tipo_proceso_id, ubicaciones, obs } ]
        public readonly ?array $fotosProcesosPorProceso = null,         // [ { proceso_id, imagenes: [ ruta ] } ]
        public readonly ?array $fotosTelasProcesadas = null,            // [ indice => { ruta_original, ruta_webp } ]
        public readonly ?array $fotosProcesoNuevo = null,              // [ indice => { ruta_original, ruta_webp } ] para procesos nuevos
        public readonly ?string $novedad = null,                       // Descripción de cambios realizados
        public readonly ?array $asignacionesColores = null,              // [ { genero, talla, tela, color, cantidad } ] → prenda_pedido_talla_colores
    ) {}

    public static function fromRequest(int|string $prendaId, array $data, ?array $imagenes = null, ?array $imagenesExistentes = null, ?array $fotosTelasProcesadas = null, ?array $fotosProcesoNuevo = null): self
    {
        // Parsear tallas - soporta 2 formatos:
        // Formato A (edición): { "DAMA": {"M": 30}, "CABALLERO": {} }  → ya es cantidadTalla
        // Formato B (creación): [ {"genero":"DAMA","talla":"M","cantidad":30} ] → convertir
        $cantidadTalla = null;
        if (!empty($data['tallas'])) {
            $tallasArray = is_string($data['tallas']) ? json_decode($data['tallas'], true) : $data['tallas'];
            
            // Si el JSON decodificado es vacío ({} → []), no tocar tallas
            if (is_array($tallasArray) && !empty($tallasArray)) {
                // Detectar formato: si tiene keys numéricas con sub-arrays que tienen 'genero' → Formato B
                // Si tiene keys como DAMA/CABALLERO/UNISEX → Formato A (ya es cantidadTalla)
                $primeraKey = array_key_first($tallasArray);
                $primerValor = reset($tallasArray);
                
                $esFormatoB = is_int($primeraKey) && is_array($primerValor) && isset($primerValor['genero']);
                $esFormatoA = is_string($primeraKey) && in_array(strtoupper($primeraKey), ['DAMA', 'CABALLERO', 'UNISEX', 'SOBREMEDIDA']);
                
                if ($esFormatoA) {
                    // Formato A: ya es {GENERO: {TALLA: CANTIDAD}} → usar directamente
                    $cantidadTalla = $tallasArray;
                } elseif ($esFormatoB) {
                    // Formato B: [{genero, talla, cantidad}] → convertir
                    $cantidadTalla = [];
                    foreach ($tallasArray as $talla) {
                        if (isset($talla['genero'], $talla['cantidad'])) {
                            $genero = strtoupper($talla['genero']);
                            if (!isset($cantidadTalla[$genero])) {
                                $cantidadTalla[$genero] = [];
                            }
                            $tallaKey = $talla['talla'] ?? null;
                            $cantidadTalla[$genero][$tallaKey] = $talla['cantidad'];
                            if (!empty($talla['es_sobremedida'])) {
                                $cantidadTalla[$genero]['_es_sobremedida'] = true;
                            }
                        }
                    }
                } else {
                    // Fallback: asumir formato A
                    $cantidadTalla = $tallasArray;
                }
            }
            // Si tallasArray es vacío → cantidadTalla queda null → no tocar tallas existentes
        } elseif (!empty($data['cantidad_talla'])) {
            // Parsear cantidad_talla (formato directo) si viene como JSON string
            $cantidadTalla = is_string($data['cantidad_talla']) ? json_decode($data['cantidad_talla'], true) : $data['cantidad_talla'];
        }

        // Parsear variantes si viene como JSON string
        $variantes = null;
        if (!empty($data['variantes'])) {
            if (is_string($data['variantes'])) {
                $variantes = json_decode($data['variantes'], true);
            } else {
                $variantes = $data['variantes'];
            }
        }

        // Parsear colores_telas si viene como JSON string
        $coloresTelas = null;
        if (!empty($data['colores_telas'])) {
            if (is_string($data['colores_telas'])) {
                $coloresTelas = json_decode($data['colores_telas'], true);
            } else {
                $coloresTelas = $data['colores_telas'];
            }
        }

        // Parsear fotosTelas si viene como JSON string
        // Aceptar tanto 'fotos_telas' como 'fotosTelas' (camelCase del frontend)
        $fotosTelas = null;
        $fotostelasKey = !empty($data['fotos_telas']) ? 'fotos_telas' : (!empty($data['fotosTelas']) ? 'fotosTelas' : null);
        if ($fotostelasKey && !empty($data[$fotostelasKey])) {
            if (is_string($data[$fotostelasKey])) {
                $fotosTelas = json_decode($data[$fotostelasKey], true);
            } else {
                $fotosTelas = $data[$fotostelasKey];
            }
        }

        // Parsear fotos si viene como JSON string
        $fotos = null;
        if (!empty($data['fotos'])) {
            if (is_string($data['fotos'])) {
                $fotos = json_decode($data['fotos'], true);
            } else {
                $fotos = $data['fotos'];
            }
        }

        // Parsear procesos si viene como JSON string
        $procesos = null;
        if (!empty($data['procesos'])) {
            if (is_string($data['procesos'])) {
                $procesos = json_decode($data['procesos'], true);
            } else {
                $procesos = $data['procesos'];
            }
        }

        // Parsear fotos de procesos si viene como JSON string
        $fotosProcesosPorProceso = null;
        if (!empty($data['fotos_procesos'])) {
            if (is_string($data['fotos_procesos'])) {
                $fotosProcesosPorProceso = json_decode($data['fotos_procesos'], true);
            } else {
                $fotosProcesosPorProceso = $data['fotos_procesos'];
            }
        }

        // Parsear imagenes_a_eliminar si viene como JSON string
        $imagenesAEliminar = null;
        if (!empty($data['imagenes_a_eliminar'])) {
            if (is_string($data['imagenes_a_eliminar'])) {
                $imagenesAEliminar = json_decode($data['imagenes_a_eliminar'], true);
            } else {
                $imagenesAEliminar = $data['imagenes_a_eliminar'];
            }
        }

        // Parsear asignaciones_colores si viene como JSON string
        // 🔴 NUEVO: Usar array_key_exists para distinguir "no enviado" (null) de "enviado vacío" ([])
        $asignacionesColores = null;
        if (array_key_exists('asignaciones_colores', $data)) {
            $raw = $data['asignaciones_colores'];
            if (is_string($raw)) {
                $asignacionesColores = json_decode($raw, true) ?? [];
            } else {
                $asignacionesColores = is_array($raw) ? $raw : [];
            }
        }

        return new self(
            prendaId: $prendaId,
            nombrePrenda: $data['nombre_prenda'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            deBodega: isset($data['de_bodega']) 
                ? (int) $data['de_bodega']  // Convertir a INT, no a BOOL
                : (isset($data['origen']) ? ($data['origen'] === 'bodega' ? 1 : 0) : null),
            imagenes: $imagenes,
            imagenesExistentes: $imagenesExistentes,
            imagenesAEliminar: $imagenesAEliminar,
            cantidadTalla: $cantidadTalla,
            variantes: $variantes,
            coloresTelas: $coloresTelas,
            fotosTelas: $fotosTelas,
            //  FIX: Lógica de fotos para edición selectiva
            // - Si hay imágenes nuevas (File uploads) → merge con existentes
            // - Si hay imágenes a eliminar → pasar existentes (puede ser [] si eliminó todas)
            // - Si imagenes_existentes tiene datos → preservar esas
            // - Si no hay cambios (existentes=[], nuevas=[], sin eliminar) → null (NO TOCAR)
            fotos: (function() use ($imagenes, $imagenesExistentes, $imagenesAEliminar, $data) {
                $tieneNuevas = !empty($imagenes);
                $tieneExistentes = !empty($imagenesExistentes);
                $tieneEliminaciones = !empty($imagenesAEliminar);
                
                if ($tieneNuevas || $tieneExistentes) {
                    // Hay contenido real → merge
                    return array_merge($imagenesExistentes ?? [], $imagenes ?? []);
                }
                if ($tieneEliminaciones) {
                    // Usuario eliminó imágenes pero no quedó ninguna → array vacío = eliminar todas
                    return [];
                }
                // No hay cambios en imágenes → null = no tocar
                return null;
            })(),
            procesos: $procesos,
            fotosProcesosPorProceso: $fotosProcesosPorProceso,
            fotosTelasProcesadas: $fotosTelasProcesadas,
            fotosProcesoNuevo: $fotosProcesoNuevo,
            novedad: $data['novedad'] ?? null,
            asignacionesColores: $asignacionesColores,
        );
    }
}

