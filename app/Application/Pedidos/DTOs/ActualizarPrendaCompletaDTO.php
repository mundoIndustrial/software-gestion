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
        public readonly ?bool $deBodega = null,
        public readonly ?array $imagenes = null,
        public readonly ?array $imagenesExistentes = null,  // URLs de imágenes existentes a preservar
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

        return new self(
            prendaId: $prendaId,
            nombrePrenda: $data['nombre_prenda'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            deBodega: isset($data['de_bodega']) ? (bool) $data['de_bodega'] : null,
            imagenes: $imagenes,
            imagenesExistentes: $imagenesExistentes,
            cantidadTalla: $cantidadTalla,
            variantes: $variantes,
            coloresTelas: $coloresTelas,
            fotosTelas: $fotosTelas,
            // 🔧 FIX: Si NO hay imágenes nuevas, NO pasar 'fotos' para evitar soft delete
            // Solo pasar $imagenes si hay nuevas imágenes subidas
            // Si está vacío, dejar null para que no toque las imágenes existentes
            fotos: (!empty($imagenes) ? array_merge($imagenesExistentes ?? [], $imagenes) : null),
            procesos: $procesos,
            fotosProcesosPorProceso: $fotosProcesosPorProceso,
            novedad: $data['novedad'] ?? null,
        );
    }
}

