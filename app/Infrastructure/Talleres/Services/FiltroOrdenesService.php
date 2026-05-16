<?php

namespace App\Infrastructure\Talleres\Services;

use App\Domain\Talleres\Services\FiltroOrdenesServiceContract;

class FiltroOrdenesService implements FiltroOrdenesServiceContract
{
    public function filtrar(array $ordenes, string $search): array
    {
        if (empty($search)) {
            return $ordenes;
        }

        $searchLower = strtolower($search);

        return array_filter($ordenes, function($orden) use ($searchLower) {
            return strpos(strtolower($orden['numero_recibo'] ?? ''), $searchLower) !== false
                || strpos(strtolower($orden['cliente'] ?? ''), $searchLower) !== false
                || strpos(strtolower($orden['descripcion'] ?? ''), $searchLower) !== false;
        });
    }

    public function paginar(array $ordenes, int $page = 1, int $perPage = 10): array
    {
        $total = count($ordenes);
        $lastPage = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        $ordenesPaginadas = array_slice($ordenes, $offset, $perPage);

        return [
            'ordenes' => array_values($ordenesPaginadas),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage
            ]
        ];
    }

    public function ordenar(array $ordenes, string $campo = 'numero_recibo', string $direccion = 'asc'): array
    {
        usort($ordenes, function($a, $b) use ($campo, $direccion) {
            $valorA = $a[$campo] ?? '';
            $valorB = $b[$campo] ?? '';

            if (is_numeric($valorA) && is_numeric($valorB)) {
                $comparacion = $valorA <=> $valorB;
            } else {
                $comparacion = strcmp($valorA, $valorB);
            }

            return $direccion === 'desc' ? -$comparacion : $comparacion;
        });

        return $ordenes;
    }
}
