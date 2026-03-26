<?php

namespace App\Domain\Pedidos\DTOs;

class VariacionPrendaDTO
{
    public function __construct(
        public readonly array $variaciones_procesadas,
        public readonly array $configuracion_ui,
        public readonly array $genero,
        public readonly array $tipos_detectados,
        public readonly bool $es_valida,
        public readonly array $errores,
        public readonly bool $tiene_variaciones,
        public readonly array $resumen
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            variaciones_procesadas: $data['variaciones_procesadas'] ?? [],
            configuracion_ui: $data['configuracion_ui'] ?? [],
            genero: $data['genero'] ?? ['id' => null, 'nombre' => null],
            tipos_detectados: $data['tipos_detectados'] ?? [],
            es_valida: $data['es_valida'] ?? true,
            errores: $data['errores'] ?? [],
            tiene_variaciones: $data['tiene_variaciones'] ?? false,
            resumen: $data['resumen'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'variaciones_procesadas' => $this->variaciones_procesadas,
            'configuracion_ui' => $this->configuracion_ui,
            'genero' => $this->genero,
            'tipos_detectados' => $this->tipos_detectados,
            'es_valida' => $this->es_valida,
            'errores' => $this->errores,
            'tiene_variaciones' => $this->tiene_variaciones,
            'resumen' => $this->resumen,
        ];
    }
}
