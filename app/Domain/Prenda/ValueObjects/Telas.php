<?php

namespace App\Domain\Prenda\ValueObjects;

class Telas
{
    /** @var Tela[] */
    private array $telas = [];

    private function __construct(Tela ...$telas)
    {
        if (count($telas) === 0) {
            throw new \InvalidArgumentException("Una prenda debe tener al menos una tela");
        }

        foreach ($telas as $tela) {
            $this->agregarTela($tela);
        }
    }

    public static function desde(Tela ...$telas): self
    {
        return new self(...$telas);
    }

    public static function desdeArray(array $telasData): self
    {
        $telas = array_map(
            fn(array $data) => Tela::desde($data['id'], $data['nombre'], $data['codigo']),
            $telasData
        );
        return new self(...$telas);
    }

    private function agregarTela(Tela $tela): void
    {
        // Evitar duplicados por ID
        foreach ($this->telas as $existente) {
            if ($existente->id() === $tela->id()) {
                return;
            }
        }
        $this->telas[] = $tela;
    }

    public function todas(): array
    {
        return $this->telas;
    }

    public function contar(): int
    {
        return count($this->telas);
    }

    public function primera(): Tela
    {
        if (empty($this->telas)) {
            throw new \RuntimeException("No hay telas disponibles");
        }
        return $this->telas[0];
    }

    public function porId(int $id): ?Tela
    {
        foreach ($this->telas as $tela) {
            if ($tela->id() === $id) {
                return $tela;
            }
        }
        return null;
    }

    public function contiene(int $telaId): bool
    {
        return $this->porId($telaId) !== null;
    }

    public function paraArray(): array
    {
        return array_map(fn(Tela $tela) => $tela->paraArray(), $this->telas);
    }
}
