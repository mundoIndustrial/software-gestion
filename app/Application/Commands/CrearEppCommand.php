<?php

namespace App\Application\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * CrearEppCommand
 *
 * Application Command para crear un nuevo EPP.
 * Encapsula la intención de crear un EPP con todos sus datos.
 *
 * Responsabilidad: Transportar los datos necesarios para crear un EPP
 */
class CrearEppCommand implements Command
{
    /**
     * Nombre del EPP
     */
    public string $nombre;

    /**
     * Categoría del EPP
     */
    public string $categoria;

    /**
     * Código único del EPP
     */
    public ?string $codigo;

    /**
     * Descripción del EPP
     */
    public ?string $descripcion;

    /**
     * Constructor
     */
    public function __construct(string $nombre, string $categoria, ?string $codigo = null, ?string $descripcion = null)
    {
        $this->nombre = $nombre;
        $this->categoria = $categoria;
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
    }
}
