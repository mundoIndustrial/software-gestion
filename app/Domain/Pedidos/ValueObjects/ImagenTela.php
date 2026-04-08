<?php

namespace App\Domain\Pedidos\ValueObjects;

use InvalidArgumentException;

/**
 * ImagenTela - Value Object
 * 
 * Similar a ImagenPrenda pero específicamente para imágenes de telas
 * Puede incluir metadatos adicionales específicos de telas
 */
final class ImagenTela
{
    private function __construct(
        public readonly ?string $previewUrl,
        public readonly ?object $file,
        public readonly ?string $ruta,
        public readonly string $nombre,
        public readonly int $tamano,
        public readonly int $orden,
    ) {}

    /**
     * Factory: Crear desde array del frontend (previewUrl)
     */
    public static function fromPreviewUrl(array $data, int $orden): self
    {
        if (empty($data['previewUrl'])) {
            throw new InvalidArgumentException('previewUrl es requerido para ImagenTela');
        }

        return new self(
            previewUrl: $data['previewUrl'],
            file: null,
            ruta: null,
            nombre: $data['nombre'] ?? 'imagen-tela.png',
            tamano: (int) ($data['tamano'] ?? 0),
            orden: $orden,
        );
    }

    /**
     * Factory: Crear desde UploadedFile
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
     */
    public static function from($imagen, int $orden): self
    {
        if (is_string($imagen)) {
            return self::fromRutaAlmacenada($imagen, $orden);
        }

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

        if (is_object($imagen)) {
            return self::fromUploadedFile($imagen, [], $orden);
        }

        throw new InvalidArgumentException(
            'Formato de imagen tela no válido'
        );
    }

    public function esPreview(): bool
    {
        return $this->previewUrl !== null;
    }

    public function esArchivo(): bool
    {
        return $this->file !== null;
    }

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
