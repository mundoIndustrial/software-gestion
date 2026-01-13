<?php

namespace App\Application\DTOs;

class ItemPedidoDTO
{
    public function __construct(
        public string $tipo, // 'cotizacion' o 'nuevo'
        public array $prenda,
        public string $origen,
        public array $procesos = [],
        public bool $es_proceso = false,
        public ?int $cotizacion_id = null,
        public ?array $tallas = null,
        public ?array $variaciones = null,
        public ?array $imagenes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tipo: $data['tipo'] ?? 'nuevo',
            prenda: $data['prenda'] ?? [],
            origen: $data['origen'] ?? 'bodega',
            procesos: $data['procesos'] ?? [],
            es_proceso: $data['es_proceso'] ?? false,
            cotizacion_id: $data['cotizacion_id'] ?? null,
            tallas: $data['tallas'] ?? null,
            variaciones: $data['variaciones'] ?? null,
            imagenes: $data['imagenes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'prenda' => $this->prenda,
            'origen' => $this->origen,
            'procesos' => $this->procesos,
            'es_proceso' => $this->es_proceso,
            'cotizacion_id' => $this->cotizacion_id,
            'tallas' => $this->tallas,
            'variaciones' => $this->variaciones,
            'imagenes' => $this->imagenes,
        ];
    }
}
