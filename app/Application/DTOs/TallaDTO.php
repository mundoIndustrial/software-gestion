<?php

namespace App\Application\DTOs;

class TallaDTO
{
    public function __construct(
        public string $talla,
        public int $cantidad = 1,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            talla: $data['talla'] ?? '',
            cantidad: (int)($data['cantidad'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'talla' => $this->talla,
            'cantidad' => $this->cantidad,
        ];
    }

    public function validar(): bool
    {
        $tallasValidas = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
        
        return in_array(strtoupper($this->talla), $tallasValidas) && $this->cantidad > 0;
    }
}
