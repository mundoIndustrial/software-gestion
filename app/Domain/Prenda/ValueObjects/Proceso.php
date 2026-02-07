<?php

namespace App\Domain\Prenda\ValueObjects;

class Proceso
{
    private const PROCESOS_VALIDOS = [
        'ESTAMPADO' => 1,
        'BORDADO' => 2,
        'TEJIDA' => 3,
        'TINTE' => 4,
        'LAVADO' => 5,
    ];

    private function __construct(private int $id, private string $nombre)
    {
        if (!in_array($id, array_values(self::PROCESOS_VALIDOS))) {
            throw new \InvalidArgumentException("ID de proceso inválido: {$id}");
        }

        if (empty(trim($nombre))) {
            throw new \InvalidArgumentException("Nombre de proceso no puede estar vacío");
        }
    }

    public static function desde(int $id, string $nombre): self
    {
        return new self($id, trim($nombre));
    }

    public static function estampado(): self
    {
        return new self(self::PROCESOS_VALIDOS['ESTAMPADO'], 'ESTAMPADO');
    }

    public static function bordado(): self
    {
        return new self(self::PROCESOS_VALIDOS['BORDADO'], 'BORDADO');
    }

    public static function tejida(): self
    {
        return new self(self::PROCESOS_VALIDOS['TEJIDA'], 'TEJIDA');
    }

    public static function tinte(): self
    {
        return new self(self::PROCESOS_VALIDOS['TINTE'], 'TINTE');
    }

    public static function lavado(): self
    {
        return new self(self::PROCESOS_VALIDOS['LAVADO'], 'LAVADO');
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function esIgual(self $otro): bool
    {
        return $this->id === $otro->id;
    }

    public function esRequerido(Origen $origen): bool
    {
        // Si origen es bodega, procesos como bordado y estampado son opcionales
        // Si origen es confección, certain procesos son requeridos
        if ($origen->esBodega()) {
            return in_array($this->id, []);
        }
        return in_array($this->id, []);
    }

    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
