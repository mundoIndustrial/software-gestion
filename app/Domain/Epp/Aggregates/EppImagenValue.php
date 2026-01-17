<?php

namespace App\Domain\Epp\Aggregates;

use App\Domain\Epp\ValueObjects\UrlImagen;

/**
 * Value Object: EppImagenValue
 * 
 * Representa una imagen de un EPP
 * No tiene identidad propia, solo se identifica por su EPP padre
 */
class EppImagenValue
{
    private int $id;
    private string $archivo;
    private bool $principal;
    private int $orden;

    public function __construct(
        int $id,
        string $archivo,
        bool $principal = false,
        int $orden = 1
    ) {
        if (empty($archivo) || strlen($archivo) > 255) {
            throw new \InvalidArgumentException('Archivo de imagen inválido');
        }

        if ($orden < 0) {
            throw new \InvalidArgumentException('Orden no puede ser negativa');
        }

        $this->id = $id;
        $this->archivo = $archivo;
        $this->principal = $principal;
        $this->orden = $orden;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function archivo(): string
    {
        return $this->archivo;
    }

    public function esPrincipal(): bool
    {
        return $this->principal;
    }

    public function orden(): int
    {
        return $this->orden;
    }

    /**
     * Construir URL de la imagen
     * Requiere el código del EPP padre
     */
    public function construirUrl(string $codigoEpp): UrlImagen
    {
        return new UrlImagen($codigoEpp, $this->archivo);
    }

    public function equals(EppImagenValue $otra): bool
    {
        return $this->id === $otra->id();
    }
}
