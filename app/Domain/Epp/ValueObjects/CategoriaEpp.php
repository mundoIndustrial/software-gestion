<?php

namespace App\Domain\Epp\ValueObjects;

/**
 * ValueObject para Categoría de EPP
 * Define categorías válidas de EPP
 */
class CategoriaEpp
{
    private string $valor;

    // Categorías válidas
    public const CABEZA = 'CABEZA';
    public const MANOS = 'MANOS';
    public const PIES = 'PIES';
    public const CUERPO = 'CUERPO';
    public const PROTECCION_AUDITIVA = 'PROTECCION_AUDITIVA';
    public const PROTECCION_VISUAL = 'PROTECCION_VISUAL';
    public const RESPIRATORIA = 'RESPIRATORIA';
    public const OTRA = 'OTRA';
    public const OJOS = 'OJOS';
    public const OIDOS = 'OIDOS';

    private static array $categoriasValidas = [
        self::CABEZA,
        self::MANOS,
        self::PIES,
        self::CUERPO,
        self::PROTECCION_AUDITIVA,
        self::PROTECCION_VISUAL,
        self::RESPIRATORIA,
        self::OTRA,
        self::OJOS,
        self::OIDOS,
    ];

    public function __construct(string $categoria)
    {
        $categoria = strtoupper(trim($categoria));

        if (empty($categoria)) {
            throw new \InvalidArgumentException("Categoría no puede estar vacía");
        }

        //  SIMPLIFICADO: Aceptar cualquier categoría que venga de la BD
        // Sin validación estricta de lista fija (permite categorías nuevas)
        $this->valor = $categoria;
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function equals(CategoriaEpp $otra): bool
    {
        return $this->valor === $otra->valor();
    }

    public static function categoriasValidas(): array
    {
        return self::$categoriasValidas;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
