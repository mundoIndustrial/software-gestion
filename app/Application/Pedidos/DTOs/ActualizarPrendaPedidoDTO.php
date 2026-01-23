<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para actualizar datos de una prenda existente
 * 
 * Permite actualizar campos en prendas_pedido + todas sus relaciones:
 * - nombre_prenda, descripcion, de_bodega
 * - tallas (como { GENERO: { TALLA: CANTIDAD } })
 * - variantes (manga, broche, bolsillos)
 * - colores_telas (color + tela)
 * - procesos
 */
final class ActualizarPrendaPedidoDTO
{
    public function __construct(
        public readonly int $prendaId,
        public readonly ?string $nombrePrenda = null,
        public readonly ?string $descripcion = null,
        public readonly ?bool $deBodega = null,
        public readonly ?array $cantidadTalla = null,                  // { GENERO: { TALLA: CANTIDAD } }
        public readonly ?array $variantes = null,                      // [ { manga_id, broche_id, bolsillos, obs } ]
        public readonly ?array $coloresTelas = null,                   // [ { color_id, tela_id } ]
        public readonly ?array $procesos = null,                       // [ { tipo_proceso_id, ubicaciones, obs } ]
    ) {}

    public static function fromRequest(int $prendaId, array $data): self
    {
        // Parsear cantidad_talla si viene como JSON string
        $cantidadTalla = null;
        if (!empty($data['tallas'])) {
            if (is_string($data['tallas'])) {
                $cantidadTalla = json_decode($data['tallas'], true);
            } else {
                $cantidadTalla = $data['tallas'];
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

        // Parsear procesos si viene como JSON string
        $procesos = null;
        if (!empty($data['procesos'])) {
            if (is_string($data['procesos'])) {
                $procesos = json_decode($data['procesos'], true);
            } else {
                $procesos = $data['procesos'];
            }
        }

        return new self(
            prendaId: $prendaId,
            nombrePrenda: $data['nombre_prenda'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            deBodega: isset($data['de_bodega']) ? (bool) $data['de_bodega'] : null,
            cantidadTalla: $cantidadTalla,
            variantes: $variantes,
            coloresTelas: $coloresTelas,
            procesos: $procesos,
        );
    }
}
