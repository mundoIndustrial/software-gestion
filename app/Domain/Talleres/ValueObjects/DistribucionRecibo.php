<?php

namespace App\Domain\Talleres\ValueObjects;

class DistribucionRecibo
{
    private array $partes;
    private bool $esDividido;

    public function __construct(array $partes)
    {
        if (empty($partes)) {
            throw new \InvalidArgumentException('La distribución debe tener al menos una parte');
        }

        $this->partes = $partes;
        $this->esDividido = count($partes) > 1;
    }

    public function getPartes(): array
    {
        return $this->partes;
    }

    public function esDividido(): bool
    {
        return $this->esDividido;
    }

    public function getCantidadPartes(): int
    {
        return count($this->partes);
    }

    public function getTallasPorParte(string $numeroParte): array
    {
        return $this->partes[$numeroParte] ?? [];
    }

    public function getCantidadTotalDistribuida(): int
    {
        $total = 0;
        foreach ($this->partes as $parte) {
            foreach ($parte as $talla) {
                $total += $talla['cantidad'] ?? 0;
            }
        }
        return $total;
    }

    public function getCantidadEntregadaDistribuida(): int
    {
        $total = 0;
        foreach ($this->partes as $parte) {
            foreach ($parte as $talla) {
                $total += $talla['cantidad_entregada'] ?? 0;
            }
        }
        return $total;
    }

    public function toArray(): array
    {
        return [
            'es_dividido' => $this->esDividido,
            'cantidad_partes' => $this->getCantidadPartes(),
            'partes' => $this->partes,
            'cantidad_total_distribuida' => $this->getCantidadTotalDistribuida(),
            'cantidad_entregada_distribuida' => $this->getCantidadEntregadaDistribuida()
        ];
    }
}
