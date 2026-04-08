<?php

namespace App\Domain\Pedidos\ValueObjects;

use InvalidArgumentException;

/**
 * ImagenPrenda - Value Object
 * 
 * Representa una imagen de prenda en el formato que llega del frontend
 * Encapsula la lógica de qué es una imagen válida
 * 
 * Formatos soportados:
 * 1. Array con previewUrl: {previewUrl: "blob:...", nombre: "...", tamano: ...}
 * 2. Array con file (UploadedFile): {file: UploadedFile, nombre: "...", tamano: ...}
 * 3. String con ruta: "/storage/..."
 * 
 * DDD: Este VO garantiza que solo imágenes válidas existen en el sistema
 */
final class ImagenPrenda
{
    private function __construct(
        public readonly ?string $previewUrl,    // Para blobs del frontend
        public readonly ?object $file,          // Para UploadedFile
        public readonly ?string $ruta,          // Para rutas almacenadas
        public readonly string $nombre,
        public readonly int $tamano,
        public readonly int $orden,
    ) {}

    /**
     * Factory: Crear desde array del frontend (previewUrl)
     * 
     * Formato esperado:
     * {
     *   previewUrl: "blob:http://localhost:3000/...",
     *   nombre: "prenda-roja.png",
     *   tamano: 2048,
     *   orden: 1
     * }
     */
    public static function fromPreviewUrl(array $data, int $orden): self
    {
        if (empty($data['previewUrl'])) {
            throw new InvalidArgumentException('previewUrl es requerido para ImagenPrenda');
        }

        return new self(
            previewUrl: $data['previewUrl'],
            file: null,
            ruta: null,
            nombre: $data['nombre'] ?? 'imagen-prenda.png',
            tamano: (int) ($data['tamano'] ?? 0),
            orden: $orden,
        );
    }

    /**
     * Factory: Crear desde UploadedFile
     * 
     * Formato esperado:
     * {
     *   file: UploadedFile,
     *   nombre: "prenda-azul.png",
     *   tamano: 4096
     * }
     */
    public static function fromUploadedFile(object $file, array $data, int $orden): self
    {
        if (!is_object($file)) {
            throw new InvalidArgumentException('file debe ser un UploadedFile');
        }

        return new self(
            previewUrl: null,
            file: $file,
            ruta: null,
            nombre: $data['nombre'] ?? $file->getClientOriginalName(),
            tamano: (int) ($data['tamano'] ?? $file->getSize()),
            orden: $orden,
        );
    }

    /**
     * Factory: Crear desde ruta almacenada
     * 
     * Formato esperado:
     * {
     *   ruta: "/storage/pedidos/123/prenda.webp"
     * }
     */
    public static function fromRutaAlmacenada(string $ruta, int $orden): self
    {
        if (empty(trim($ruta))) {
            throw new InvalidArgumentException('ruta no puede estar vacía');
        }

        return new self(
            previewUrl: null,
            file: null,
            ruta: $ruta,
            nombre: basename($ruta),
            tamano: 0,
            orden: $orden,
        );
    }

    /**
     * Factory: Crear desde input genérico
     * Determina automáticamente el formato
     */
    public static function from($imagen, int $orden): self
    {
        // String: es una ruta almacenada
        if (is_string($imagen)) {
            return self::fromRutaAlmacenada($imagen, $orden);
        }

        // Array: verificar qué tipo de array es
        if (is_array($imagen)) {
            if (isset($imagen['previewUrl'])) {
                return self::fromPreviewUrl($imagen, $orden);
            }
            if (isset($imagen['file']) && is_object($imagen['file'])) {
                return self::fromUploadedFile($imagen['file'], $imagen, $orden);
            }
            if (isset($imagen['ruta'])) {
                return self::fromRutaAlmacenada($imagen['ruta'], $orden);
            }
        }

        // Object: es probablemente un UploadedFile
        if (is_object($imagen)) {
            return self::fromUploadedFile($imagen, [], $orden);
        }

        throw new InvalidArgumentException(
            'Formato de imagen no válido. Esperaba array, string o UploadedFile. Recibí: ' . gettype($imagen)
        );
    }

    /**
     * ¿Es un preview del frontend (blob)?
     */
    public function esPreview(): bool
    {
        return $this->previewUrl !== null;
    }

    /**
     * ¿Es un UploadedFile?
     */
    public function esArchivo(): bool
    {
        return $this->file !== null;
    }

    /**
     * ¿Es una ruta almacenada?
     */
    public function esRutaAlmacenada(): bool
    {
        return $this->ruta !== null;
    }

    /**
     * Convertir a array para guardar en BD
     */
    public function toArray(): array
    {
        return [
            'ruta_original' => $this->nombre,
            'ruta_webp' => $this->previewUrl ?? $this->ruta,
            'orden' => $this->orden,
            'tamano' => $this->tamano,
        ];
    }
}
