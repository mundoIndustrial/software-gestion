<?php

namespace App\Domain\Shared;

/**
 * Base Class: AggregateRoot
 * 
 * Raíz de un agregado
 * Contiene múltiples entidades y es responsable de consistencia
 */
abstract class AggregateRoot extends Entity
{
    protected array $eventos = [];

    public function agregarEvento(DomainEvent $evento): void
    {
        $this->eventos[] = $evento;
    }

    public function obtenerEventos(): array
    {
        return $this->eventos;
    }

    public function limpiarEventos(): void
    {
        $this->eventos = [];
    }
}
