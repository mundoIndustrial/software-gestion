<?php

namespace App\Domain\Prenda\ValueObjects;

class Variaciones
{
    /** @var Variacion[] */
    private array $variaciones = [];

    private function __construct(Variacion ...$variaciones)
    {
        foreach ($variaciones as $variacion) {
            $this->agregarVariacion($variacion);
        }
    }

    public static function desde(Variacion ...$variaciones): self
    {
        return new self(...$variaciones);
    }

    public static function desdeArray(array $variacionesData): self
    {
        $variaciones = array_map(
            fn(array $data) => Variacion::desde($data['id'], $data['talla'], $data['color']),
            $variacionesData
        );
        return new self(...$variaciones);
    }

    public static function vacia(): self
    {
        return new self();
    }

    private function agregarVariacion(Variacion $variacion): void
    {
        // Evitar duplicados por descriptor único (talla-color)
        foreach ($this->variaciones as $existente) {
            if ($existente->descriptorUnico() === $variacion->descriptorUnico()) {
                return;
            }
        }
        $this->variaciones[] = $variacion;
    }

    public function todas(): array
    {
        return $this->variaciones;
    }

    public function contar(): int
    {
        return count($this->variaciones);
    }

    public function tieneAlguna(): bool
    {
        return $this->contar() > 0;
    }

    public function porId(int $id): ?Variacion
    {
        foreach ($this->variaciones as $variacion) {
            if ($variacion->id() === $id) {
                return $variacion;
            }
        }
        return null;
    }

    public function contiene(int $variacionId): bool
    {
        return $this->porId($variacionId) !== null;
    }

    public function tallas(): array
    {
        return array_unique(array_map(fn(Variacion $v) => $v->talla(), $this->variaciones));
    }

    public function colores(): array
    {
        return array_unique(array_map(fn(Variacion $v) => $v->color(), $this->variaciones));
    }

    public function paraArray(): array
    {
        return array_map(fn(Variacion $v) => $v->paraArray(), $this->variaciones);
    }
}
