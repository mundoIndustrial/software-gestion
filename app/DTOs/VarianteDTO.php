<?php

namespace App\DTOs;

/**
 * Data Transfer Object para datos de variantes de prendas
 * 
 * Estructura simple para transferir datos de variantes
 * entre capas sin exponer modelos de base de datos
 */
class VarianteDTO
{
    public function __construct(
        public ?int $colorId = null,
        public ?string $colorNombre = null,
        public ?int $telaId = null,
        public ?string $telaNombre = null,
        public ?string $tipoManga = null,
        public ?string $tipoBotador = null,
        public bool $bolsillos = false,
        public bool $reflectivo = false,
        public ?string $descripcionAdicional = null,
    ) {}

    /**
     * Crear DTO desde array de datos
     * 
     * @param array $datos
     * @return self
     */
    public static function fromArray(array $datos): self
    {
        return new self(
            colorId: $datos['color_id'] ?? null,
            colorNombre: $datos['color_nombre'] ?? null,
            telaId: $datos['tela_id'] ?? null,
            telaNombre: $datos['tela_nombre'] ?? null,
            tipoManga: $datos['tipo_manga'] ?? null,
            tipoBotador: $datos['tipo_botador'] ?? null,
            bolsillos: $datos['bolsillos'] ?? false,
            reflectivo: $datos['reflectivo'] ?? false,
            descripcionAdicional: $datos['descripcion_adicional'] ?? null,
        );
    }

    /**
     * Convertir a array para base de datos
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'color_id' => $this->colorId,
            'color_nombre' => $this->colorNombre,
            'tela_id' => $this->telaId,
            'tela_nombre' => $this->telaNombre,
            'tipo_manga' => $this->tipoManga,
            'tipo_botador' => $this->tipoBotador,
            'bolsillos' => $this->bolsillos,
            'reflectivo' => $this->reflectivo,
            'descripcion_adicional' => $this->descripcionAdicional,
        ];
    }

    /**
     * Obtener solo datos de color
     * 
     * @return array
     */
    public function getDatosColor(): array
    {
        return [
            'id' => $this->colorId,
            'nombre' => $this->colorNombre,
        ];
    }

    /**
     * Obtener solo datos de tela
     * 
     * @return array
     */
    public function getDatosTela(): array
    {
        return [
            'id' => $this->telaId,
            'nombre' => $this->telaNombre,
        ];
    }

    /**
     * Validar que la variante tiene datos mínimos
     * 
     * @return bool
     */
    public function tieneContenido(): bool
    {
        return !empty($this->colorId) 
            || !empty($this->colorNombre)
            || !empty($this->telaId)
            || !empty($this->telaNombre)
            || !empty($this->tipoManga)
            || !empty($this->tipoBotador)
            || $this->bolsillos
            || $this->reflectivo
            || !empty($this->descripcionAdicional);
    }
}
