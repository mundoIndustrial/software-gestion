<?php

namespace App\Domain\Pedidos\Enums;

enum GeneroPrenda: string
{
    case DAMA = 'DAMA';
    case CABALLERO = 'CABALLERO';
    case UNISEX = 'UNISEX';
    
    public function getId(): int
    {
        return match($this) {
            self::DAMA => 1,
            self::CABALLERO => 2,
            self::UNISEX => 3,
        };
    }
    
    public static function fromId(int $id): ?self
    {
        return match($id) {
            1 => self::DAMA,
            2 => self::CABALLERO,
            3 => self::UNISEX,
            default => null,
        };
    }
    
    public function getNombre(): string
    {
        return match($this) {
            self::DAMA => 'Dama',
            self::CABALLERO => 'Caballero',
            self::UNISEX => 'Unisex',
        };
    }
}
