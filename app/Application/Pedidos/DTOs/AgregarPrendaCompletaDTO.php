<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar prenda completa con fotos, tallas y colores
 * 
 * Maneja campos de prendas_pedido + fotos + tallas + colores
 * - nombre_prenda: nombre de la prenda
 * - descripcion: descripción de la prenda
 * - de_bodega: si viene de bodega
 * - imagenes: array de rutas de fotos
 * - cantidad_talla: estructura relacional { DAMA: {S: 100, M: 200}, ... }
 * - asignaciones_colores: colores asignados por talla { "dama-Letra-S": { genero, tela, talla, colores }, ... }
 * - telas: array de telas [{ tela, color, referencia }, ...]
 */
final class AgregarPrendaCompletaDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
        public readonly string $nombre_prenda,
        public readonly ?string $descripcion = null,
        public readonly bool $de_bodega = false,
        public readonly ?array $imagenes = null,
        public readonly ?array $imagenesExistentes = null,  // URLs de imágenes existentes a preservar
        public readonly ?array $cantidad_talla = null,      // Estructura relacional de tallas
        public readonly ?array $asignaciones_colores = null, // Colores por talla-género
        public readonly ?array $telas = null,                // Array de telas con detalles
        public readonly ?array $procesos = null,             // Procesos (bordado, estampado, etc)
        public readonly ?string $origen = null,              // Origen de la prenda
        public readonly ?string $novedad = null,              // Novedad/justificación del cambio
    ) {}

    public static function fromRequest(int|string $pedidoId, array $data, ?array $imagenes = null, ?array $imagenesExistentes = null): self
    {
        // Decodificar cantidad_talla si viene como JSON string
        $cantidadTalla = $data['cantidad_talla'] ?? null;
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true);
        }

        // Decodificar asignaciones_colores si viene como JSON string
        $asignacionesColores = $data['asignaciones_colores'] ?? null;
        if (is_string($asignacionesColores)) {
            $asignacionesColores = json_decode($asignacionesColores, true);
        }

        // Decodificar procesos si viene como JSON string
        $procesos = $data['procesos'] ?? null;
        if (is_string($procesos)) {
            $procesos = json_decode($procesos, true);
        }

        return new self(
            pedidoId: $pedidoId,
            nombre_prenda: $data['nombre_prenda'] ?? throw new \InvalidArgumentException('nombre_prenda requerido'),
            descripcion: $data['descripcion'] ?? null,
            de_bodega: $data['de_bodega'] ?? false,
            imagenes: $imagenes,
            imagenesExistentes: $imagenesExistentes,
            cantidad_talla: $cantidadTalla,
            asignaciones_colores: $asignacionesColores,
            telas: $data['telas'] ?? null,
            procesos: $procesos,
            origen: $data['origen'] ?? null,
            novedad: $data['novedad'] ?? null,
        );
    }
}

