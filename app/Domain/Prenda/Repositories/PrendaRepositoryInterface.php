<?php

namespace App\Domain\Prenda\Repositories;

use App\Domain\Prenda\Entities\Prenda;

interface PrendaRepositoryInterface
{
    /**
     * Obtiene una prenda por su ID
     */
    public function porId(int $id): ?Prenda;

    /**
     * Obtiene todas las prendas
     */
    public function todas(): array;

    /**
     * Obtiene prendas por origen
     */
    public function porOrigen(string $origen): array;

    /**
     * Obtiene prendas por tipo de cotización
     */
    public function porTipoCotizacion(string $tipo): array;

    /**
     * Guarda (crea o actualiza) una prenda
     */
    public function guardar(Prenda $prenda): void;

    /**
     * Elimina una prenda
     */
    public function eliminar(int $id): void;

    /**
     * Cuenta el total de prendas
     */
    public function contar(): int;

    /**
     * Busca prendas por nombre (búsqueda parcial)
     */
    public function buscarPorNombre(string $nombre): array;
}
