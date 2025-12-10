<?php

namespace App\Application\DTOs;

use Illuminate\Http\UploadedFile;

class TelaDTO
{
    public function __construct(
        public string $nombre,
        public string $referencia,
        public string $color,
        public ?UploadedFile $foto = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nombre: $data['nombre'] ?? '',
            referencia: $data['referencia'] ?? '',
            color: $data['color'] ?? '',
            foto: $data['foto'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'referencia' => $this->referencia,
            'color' => $this->color,
        ];
    }

    public function validar(): bool
    {
        if (empty($this->nombre) || empty($this->referencia) || empty($this->color)) {
            return false;
        }

        if ($this->foto && !$this->foto->isValid()) {
            return false;
        }

        return true;
    }
}
