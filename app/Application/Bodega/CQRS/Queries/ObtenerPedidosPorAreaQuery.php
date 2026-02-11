<?php

namespace App\Application\Bodega\CQRS\Queries;

use App\Domain\Bodega\ValueObjects\AreaBodega;

/**
 * Query para obtener pedidos por área específica
 * Optimizada para operaciones de lectura
 */
class ObtenerPedidosPorAreaQuery implements QueryInterface
{
    private string $queryId;
    private AreaBodega $area;
    private array $filtros;
    private int $pagina;
    private int $porPagina;

    public function __construct(
        AreaBodega $area,
        array $filtros = [],
        int $pagina = 1,
        int $porPagina = 20
    ) {
        $this->queryId = $this->generarQueryId($area, $filtros, $pagina, $porPagina);
        $this->area = $area;
        $this->filtros = $filtros;
        $this->pagina = $pagina;
        $this->porPagina = $porPagina;
    }

    public function getQueryId(): string
    {
        return $this->queryId;
    }

    public function getArea(): AreaBodega
    {
        return $this->area;
    }

    public function getFiltros(): array
    {
        return $this->filtros;
    }

    public function getPagina(): int
    {
        return $this->pagina;
    }

    public function getPorPagina(): int
    {
        return $this->porPagina;
    }

    public function getParameters(): array
    {
        return [
            'area' => $this->area->getValor(),
            'filtros' => $this->filtros,
            'pagina' => $this->pagina,
            'por_pagina' => $this->porPagina
        ];
    }

    public function validate(): void
    {
        if ($this->pagina < 1) {
            throw new \InvalidArgumentException('La página debe ser mayor a 0');
        }

        if ($this->porPagina < 1 || $this->porPagina > 100) {
            throw new \InvalidArgumentException('El por página debe estar entre 1 y 100');
        }

        // Validar filtros conocidos
        $filtrosValidos = ['cliente', 'asesor', 'numero_pedido', 'solo_retrasados', 'estados'];
        foreach ($this->filtros as $key => $value) {
            if (!in_array($key, $filtrosValidos)) {
                throw new \InvalidArgumentException("Filtro no válido: {$key}");
            }
        }
    }

    /**
     * Generar un ID único para la query basado en sus parámetros
     */
    private function generarQueryId(AreaBodega $area, array $filtros, int $pagina, int $porPagina): string
    {
        $params = [
            'area' => $area->getValor(),
            'filtros' => $filtros,
            'pagina' => $pagina,
            'por_pagina' => $porPagina
        ];
        
        return 'query_pedidos_area_' . md5(serialize($params));
    }

    /**
     * Crear una nueva instancia con diferente paginación
     */
    public function conPaginacion(int $pagina, int $porPagina): self
    {
        return new self($this->area, $this->filtros, $pagina, $porPagina);
    }

    /**
     * Crear una nueva instancia con filtros adicionales
     */
    public function conFiltros(array $filtrosAdicionales): self
    {
        $nuevosFiltros = array_merge($this->filtros, $filtrosAdicionales);
        return new self($this->area, $nuevosFiltros, $this->pagina, $this->porPagina);
    }
}
