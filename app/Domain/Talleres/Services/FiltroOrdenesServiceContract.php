<?php

namespace App\Domain\Talleres\Services;

interface FiltroOrdenesServiceContract
{
    /**
     * Filtra órdenes por búsqueda
     * 
     * @param array $ordenes
     * @param string $search
     * @return array
     */
    public function filtrar(array $ordenes, string $search): array;

    /**
     * Pagina un conjunto de órdenes
     * 
     * @param array $ordenes
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function paginar(array $ordenes, int $page = 1, int $perPage = 10): array;

    /**
     * Ordena las órdenes por un campo
     * 
     * @param array $ordenes
     * @param string $campo
     * @param string $direccion
     * @return array
     */
    public function ordenar(array $ordenes, string $campo = 'numero_recibo', string $direccion = 'asc'): array;
}
