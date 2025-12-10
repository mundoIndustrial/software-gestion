<?php

namespace App\Application\DTOs;

use Illuminate\Http\UploadedFile;

class ImagenDTO
{
    public function __construct(
        public UploadedFile $archivo,
        public string $tipo,
        public int $orden,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            archivo: $data['archivo'],
            tipo: $data['tipo'] ?? 'foto_prenda',
            orden: $data['orden'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'orden' => $this->orden,
        ];
    }

    public function validar(): bool
    {
        $tiposValidos = ['foto_prenda', 'foto_tela'];
        $extensionesValidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($this->tipo, $tiposValidos)) {
            return false;
        }

        if (!in_array($this->archivo->getClientOriginalExtension(), $extensionesValidas)) {
            return false;
        }

        if ($this->archivo->getSize() > 5 * 1024 * 1024) { // 5MB
            return false;
        }

        return true;
    }
}
