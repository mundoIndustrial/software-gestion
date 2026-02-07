<?php

namespace App\Application\Prenda\DTOs;

class ObtenerPrendaResponse
{
    public function __construct(
        public bool $exito,
        public ?array $datos = null,
        public array $errores = []
    ) {}

    public static function exitosa(array $datos): self
    {
        return new self(exito: true, datos: $datos, errores: []);
    }

    public static function conErrores(array $errores): self
    {
        return new self(exito: false, datos: null, errores: $errores);
    }

    public function toArray(): array
    {
        return [
            'exito' => $this->exito,
            'datos' => $this->datos,
            'errores' => $this->errores,
        ];
    }
}
