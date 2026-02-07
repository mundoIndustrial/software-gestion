<?php

namespace App\Application\Prenda\DTOs;

class GuardarPrendaRequest
{
    public function __construct(
        public ?int $id = null,
        public string $nombre_prenda = '',
        public ?string $descripcion = null,
        public int $genero = 0,
        public string $tipo_cotizacion = '',
        public array $telas = [],
        public array $procesos = [],
        public array $variaciones = [],
    ) {}

    /**
     * Crea DTO desde array (típicamente de request JSON)
     */
    public static function desdeArray(array $datos): self
    {
        return new self(
            id: $datos['id'] ?? null,
            nombre_prenda: $datos['nombre_prenda'] ?? '',
            descripcion: $datos['descripcion'] ?? null,
            genero: (int)($datos['genero'] ?? 0),
            tipo_cotizacion: $datos['tipo_cotizacion'] ?? '',
            telas: $datos['telas'] ?? [],
            procesos: $datos['procesos'] ?? [],
            variaciones: $datos['variaciones'] ?? [],
        );
    }

    /**
     * Convierte a array para procesamiento interno
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre_prenda' => $this->nombre_prenda,
            'descripcion' => $this->descripcion,
            'genero' => $this->genero,
            'tipo_cotizacion' => $this->tipo_cotizacion,
            'telas' => $this->telas,
            'procesos' => $this->procesos,
            'variaciones' => $this->variaciones,
        ];
    }

    /**
     * Valida datos básicos antes de pasar al servicio
     */
    public function validar(): array
    {
        $errores = [];

        if (empty($this->nombre_prenda)) {
            $errores[] = "Nombre de prenda es requerido";
        }

        if (empty($this->tipo_cotizacion)) {
            $errores[] = "Tipo de cotización es requerido";
        }

        if ($this->genero <= 0) {
            $errores[] = "Género no válido";
        }

        if (empty($this->telas)) {
            $errores[] = "Al menos una tela es requerida";
        }

        return $errores;
    }

    public function esValida(): bool
    {
        return count($this->validar()) === 0;
    }
}
