<?php

namespace App\Domain\Shared;

/**
 * Entity - Clase base para todas las entidades del dominio
 * 
 * Define la estructura básica de una entidad con ID único
 * y métodos para gestionar su identidad
 */
abstract class Entity
{
    protected ?int $id;

    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /**
     * Obtener el ID de la entidad
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Establecer el ID de la entidad (usado después de persistir)
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Verificar si la entidad es nueva (sin ID asignado)
     */
    public function esNueva(): bool
    {
        return is_null($this->id);
    }

    /**
     * Verificar si la entidad ya existe en la base de datos
     */
    public function existe(): bool
    {
        return !is_null($this->id);
    }

    /**
     * Comparar dos entidades por su ID
     */
    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    /**
     * Método abstracto para convertir la entidad a array
     */
    abstract public function toArray(): array;
}
