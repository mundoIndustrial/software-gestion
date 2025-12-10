<?php

namespace App\Enums;

enum TipoPrendaEnum: string
{
    case CAMISA = 'CAMISA';
    case PANTALON = 'PANTALON';
    case JEAN = 'JEAN';
    case FALDA = 'FALDA';
    case BLUSA = 'BLUSA';
    case CHAQUETA = 'CHAQUETA';
    case SUDADERA = 'SUDADERA';
    case POLO = 'POLO';
    case CAMISETA = 'CAMISETA';
    case VESTIDO = 'VESTIDO';
    case OTRO = 'OTRO';

    public function label(): string
    {
        return match($this) {
            self::CAMISA => 'Camisa',
            self::PANTALON => 'Pantalón',
            self::JEAN => 'Jean',
            self::FALDA => 'Falda',
            self::BLUSA => 'Blusa',
            self::CHAQUETA => 'Chaqueta',
            self::SUDADERA => 'Sudadera',
            self::POLO => 'Polo',
            self::CAMISETA => 'Camiseta',
            self::VESTIDO => 'Vestido',
            self::OTRO => 'Otro',
        };
    }

    public static function palabrasClave(): array
    {
        return [
            'CAMISA' => ['camisa', 'shirt', 'blouse'],
            'PANTALON' => ['pantalón', 'pants', 'trousers'],
            'JEAN' => ['jean', 'jeans', 'denim'],
            'FALDA' => ['falda', 'skirt'],
            'BLUSA' => ['blusa', 'blouse'],
            'CHAQUETA' => ['chaqueta', 'jacket'],
            'SUDADERA' => ['sudadera', 'hoodie', 'sweatshirt'],
            'POLO' => ['polo', 'polo shirt'],
            'CAMISETA' => ['camiseta', 't-shirt', 'tee'],
            'VESTIDO' => ['vestido', 'dress'],
        ];
    }
}
