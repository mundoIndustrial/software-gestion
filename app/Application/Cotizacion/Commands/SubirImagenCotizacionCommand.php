<?php

namespace App\Application\Cotizacion\Commands;

use Illuminate\Http\UploadedFile;

/**
 * SubirImagenCotizacionCommand - Comando para subir imagen a cotización
 *
 * Caso de uso: Subir imagen de prenda o tela a una cotización
 *
 * Ventajas sobre Base64:
 * - Transmisión directa sin encoding
 * - 33% menos datos
 * - Más rápido
 * - Escalable
 */
final readonly class SubirImagenCotizacionCommand
{
    public function __construct(
        public int $cotizacionId,
        public int $prendaId,
        public string $tipo,  // 'prenda', 'tela', 'logo', 'bordado', 'estampado'
        public UploadedFile $archivo,
        public int $usuarioId
    ) {
    }

    /**
     * Factory method
     */
    public static function crear(
        int $cotizacionId,
        int $prendaId,
        string $tipo,
        UploadedFile $archivo,
        int $usuarioId
    ): self {
        return new self($cotizacionId, $prendaId, $tipo, $archivo, $usuarioId);
    }
}
