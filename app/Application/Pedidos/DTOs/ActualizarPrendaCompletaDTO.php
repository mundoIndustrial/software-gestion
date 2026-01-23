<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para actualizar prenda COMPLETA con todas sus relaciones
 * 
 * Maneja:
 * - prendas_pedido (campos básicos)
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
        public readonly ?array $cantidadTalla = null,                  // { GENERO: { TALLA: CANTIDAD } }
        public readonly ?array $variantes = null,                      // [ { manga_id, broche_id, bolsillos, obs } ]
        public readonly ?array $coloresTelas = null,                   // [ { color_id, tela_id } ]
        public readonly ?array $fotosTelas = null,                     // [ { color_tela_id, ruta } ]
        public readonly ?array $fotos = null,                          // [ { ruta } ]
        public readonly ?array $procesos = null,                       // [ { tipo_proceso_id, ubicaciones, obs } ]
        public readonly ?array $fotosProcesosPorProceso = null,         // [ { proceso_id, imagenes: [ ruta ] } ]
    ) {}

    public static function fromRequest(int|string $prendaId, array $data, ?array $imagenes = null): self
    {
        // Parsear cantidad_talla si viene como JSON string
        $cantidadTalla = null;
        if (!empty($data['cantidad_talla'])) {
            if (is_string($data['cantidad_talla'])) {
                $cantidadTalla = json_decode($data['cantidad_talla'], true);
            } else {
                $cantidadTalla = $data['cantidad_talla'];
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
            cantidadTalla: $cantidadTalla,
            variantes: $variantes,
            coloresTelas: $coloresTelas,
            fotosTelas: $fotosTelas,
            fotos: $fotos,
            procesos: $procesos,
            fotosProcesosPorProceso: $fotosProcesosPorProceso,
        );
    }
}
