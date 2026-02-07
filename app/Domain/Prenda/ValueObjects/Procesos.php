<?php

namespace App\Domain\Prenda\ValueObjects;

class Procesos
{
    /** @var Proceso[] */
    private array $procesos = [];

    private function __construct(Proceso ...$procesos)
    {
        foreach ($procesos as $proceso) {
            $this->agregarProceso($proceso);
        }
    }

    public static function desde(Proceso ...$procesos): self
    {
        return new self(...$procesos);
    }

    public static function desdeArray(array $procesosData): self
    {
        $procesos = array_map(
            fn(array $data) => Proceso::desde($data['id'], $data['nombre']),
            $procesosData
        );
        return new self(...$procesos);
    }

    public static function vacia(): self
    {
        return new self();
    }

    private function agregarProceso(Proceso $proceso): void
    {
        // Evitar duplicados por ID
        foreach ($this->procesos as $existente) {
            if ($existente->esIgual($proceso)) {
                return;
            }
        }
        $this->procesos[] = $proceso;
    }

    public function todos(): array
    {
        return $this->procesos;
    }

    public function contar(): int
    {
        return count($this->procesos);
    }

    public function tieneAlgun(): bool
    {
        return $this->contar() > 0;
    }

    public function porId(int $id): ?Proceso
    {
        foreach ($this->procesos as $proceso) {
            if ($proceso->id() === $id) {
                return $proceso;
            }
        }
        return null;
    }

    public function contiene(int $procesoId): bool
    {
        return $this->porId($procesoId) !== null;
    }

    public function paraArray(): array
    {
        return array_map(fn(Proceso $p) => $p->paraArray(), $this->procesos);
    }
}
