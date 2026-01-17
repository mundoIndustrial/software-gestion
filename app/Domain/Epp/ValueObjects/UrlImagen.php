<?php

namespace App\Domain\Epp\ValueObjects;

/**
 * ValueObject para URL de Imagen
 * Construye y valida URLs de imágenes de EPP
 * 
 * Formato: /storage/epp/{codigo_epp}/{nombre_archivo}
 */
class UrlImagen
{
    private string $url;

    public function __construct(string $codigoEpp, string $archivo)
    {
        if (empty($codigoEpp) || empty($archivo)) {
            throw new \InvalidArgumentException('Código EPP y archivo son requeridos');
        }

        // Construir URL: /storage/epp/{codigo}/{archivo}
        $this->url = '/storage/epp/' . $codigoEpp . '/' . $archivo;
    }

    public function valor(): string
    {
        return $this->url;
    }

    public function equals(UrlImagen $otra): bool
    {
        return $this->url === $otra->valor();
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
