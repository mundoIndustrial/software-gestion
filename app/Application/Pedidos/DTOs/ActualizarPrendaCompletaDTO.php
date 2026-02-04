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
        public readonly ?string $novedad = null,                       // Descripción de cambios realizados
    ) {}

    public static function fromRequest(int|string $prendaId, array $data, ?array $imagenes = null, ?array $imagenesExistentes = null): self
    {
        // Parsear tallas (nuevo formato: array de objetos con genero, talla, cantidad)
        $tallasArray = null;
        if (!empty($data['tallas'])) {
            if (is_string($data['tallas'])) {
                $tallasArray = json_decode($data['tallas'], true);
            } else {
                $tallasArray = $data['tallas'];
            }
            // Convertir a formato antiguo cantidad_talla: { GENERO: { TALLA: CANTIDAD } }
            $cantidadTalla = [];
            if (is_array($tallasArray)) {
                foreach ($tallasArray as $talla) {
                    if (isset($talla['genero'], $talla['talla'], $talla['cantidad'])) {
                        $genero = strtoupper($talla['genero']);
                        if (!isset($cantidadTalla[$genero])) {
                            $cantidadTalla[$genero] = [];
                        }
                        $cantidadTalla[$genero][$talla['talla']] = $talla['cantidad'];
                    }
                }
            }
        } else {
            // Parsear cantidad_talla (formato antiguo) si viene como JSON string
            $cantidadTalla = null;
            if (!empty($data['cantidad_talla'])) {
                if (is_string($data['cantidad_talla'])) {
                    $cantidadTalla = json_decode($data['cantidad_talla'], true);
                } else {
                    $cantidadTalla = $data['cantidad_talla'];
                }
            }
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
        $fotosTelas = null;
        if (!empty($data['fotos_telas'])) {
            if (is_string($data['fotos_telas'])) {
                $fotosTelas = json_decode($data['fotos_telas'], true);
            } else {
                $fotosTelas = $data['fotos_telas'];
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
            //  FIX: IMPORTANTE - Usar $imagenesExistentes como base para eliminar imágenes correctamente
            // Patrón MERGE:
            // 1. Si hay imágenes nuevas + existentes -> MERGE ambas
            // 2. Si solo hay existentes (usuario no agregó nuevas pero puede haber eliminado) -> usar existentes
            // 3. Si está vacío (usuario eliminó todas) -> array vacío (que causa DELETE en UseCase)
            // 4. Si no se envió nada -> null (no tocar imágenes)
            fotos: isset($data['imagenes_existentes']) 
                ? array_merge($imagenesExistentes ?? [], $imagenes ?? [])
                : ((!empty($imagenes)) ? $imagenes : null),
            procesos: $procesos,
            fotosProcesosPorProceso: $fotosProcesosPorProceso,
            novedad: $data['novedad'] ?? null,
        );
    }
}

