<?php

namespace App\Domain\Cotizacion\ValueObjects;

use InvalidArgumentException;

/**
 * RutaImagen - Value Object que representa la ruta de una imagen
 *
 * Reglas:
 * - No puede estar vacía
 * - Debe ser una URL válida o ruta local
 * - Inmutable (readonly)
 */
final readonly class RutaImagen
{
    private string $valor;

    /**
     * Constructor privado - usar factory method
     */
    private function __construct(string $valor)
    {
        $this->validar($valor);
        $this->valor = trim($valor);
    }

    /**
     * Factory method para crear una instancia
     */
    public static function crear(string $valor): self
    {
        return new self($valor);
    }

    /**
     * Validar la ruta de la imagen
     */
    private function validar(string $valor): void
    {
        $valor = trim($valor);

        if (empty($valor)) {
            throw new InvalidArgumentException('La ruta de la imagen no puede estar vacía');
        }

        if (strlen($valor) > 2048) {
            throw new InvalidArgumentException('La ruta de la imagen no puede exceder 2048 caracteres');
        }

        // Validar que sea una URL o ruta válida
        if (!$this->esRutaValida($valor)) {
            throw new InvalidArgumentException("La ruta de la imagen no es válida: {$valor}");
        }
    }

    /**
     * Verificar si es una ruta válida (URL o ruta local)
     */
    private function esRutaValida(string $valor): bool
    {
        // Aceptar URLs HTTP/HTTPS
        if (filter_var($valor, FILTER_VALIDATE_URL)) {
            return true;
        }

        // Aceptar rutas locales (comienzan con / o contienen storage/)
        if (str_starts_with($valor, '/') || str_contains($valor, 'storage/')) {
            return true;
        }

        // Aceptar data URLs (Base64)
        if (str_starts_with($valor, 'data:image/')) {
            return true;
        }

        return false;
    }

    /**
     * Obtener el valor de la ruta
     */
    public function valor(): string
    {
        return $this->valor;
    }

    /**
     * Verificar si es una URL remota
     */
    public function esUrlRemota(): bool
    {
        return filter_var($this->valor, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Verificar si es una ruta local
     */
    public function esRutaLocal(): bool
    {
        return str_starts_with($this->valor, '/') || str_contains($this->valor, 'storage/');
    }

    /**
     * Verificar si es una data URL (Base64)
     */
    public function esDataUrl(): bool
    {
        return str_starts_with($this->valor, 'data:image/');
    }

    /**
     * Comparar con otra RutaImagen
     */
    public function equals(self $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    /**
     * Representación en string
     */
    public function __toString(): string
    {
        return $this->valor;
    }
}
