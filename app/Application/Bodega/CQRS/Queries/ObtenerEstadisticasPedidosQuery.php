<?php

namespace App\Application\Bodega\CQRS\Queries;

/**
 * Query para obtener estadísticas de pedidos
 * Optimizada para dashboards y reportes
 */
class ObtenerEstadisticasPedidosQuery implements QueryInterface
{
    private string $queryId;
    private ?array $areas;
    private ?array $estados;
    private ?\DateTime $fechaDesde;
    private ?\DateTime $fechaHasta;

    public function __construct(
        ?array $areas = null,
        ?array $estados = null,
        ?\DateTime $fechaDesde = null,
        ?\DateTime $fechaHasta = null
    ) {
        $this->queryId = $this->generarQueryId($areas, $estados, $fechaDesde, $fechaHasta);
        $this->areas = $areas;
        $this->estados = $estados;
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
    }

    public function getQueryId(): string
    {
        return $this->queryId;
    }

    public function getAreas(): ?array
    {
        return $this->areas;
    }

    public function getEstados(): ?array
    {
        return $this->estados;
    }

    public function getFechaDesde(): ?\DateTime
    {
        return $this->fechaDesde;
    }

    public function getFechaHasta(): ?\DateTime
    {
        return $this->fechaHasta;
    }

    public function getParameters(): array
    {
        return [
            'areas' => $this->areas,
            'estados' => $this->estados,
            'fecha_desde' => $this->fechaDesde?->format('Y-m-d'),
            'fecha_hasta' => $this->fechaHasta?->format('Y-m-d')
        ];
    }

    public function validate(): void
    {
        if ($this->fechaDesde && $this->fechaHasta) {
            if ($this->fechaDesde > $this->fechaHasta) {
                throw new \InvalidArgumentException('La fecha desde no puede ser mayor que la fecha hasta');
            }
        }

        if ($this->areas) {
            $areasValidas = ['Costura', 'EPP', 'Corte', 'Otro'];
            foreach ($this->areas as $area) {
                if (!in_array($area, $areasValidas)) {
                    throw new \InvalidArgumentException("Área no válida: {$area}");
                }
            }
        }

        if ($this->estados) {
            $estadosValidos = [
                'ENTREGADO', 'EN EJECUCIÓN', 'NO INICIADO', 'ANULADA',
                'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA'
            ];
            foreach ($this->estados as $estado) {
                if (!in_array($estado, $estadosValidos)) {
                    throw new \InvalidArgumentException("Estado no válido: {$estado}");
                }
            }
        }
    }

    /**
     * Generar un ID único para la query basado en sus parámetros
     */
    private function generarQueryId(?array $areas, ?array $estados, ?\DateTime $fechaDesde, ?\DateTime $fechaHasta): string
    {
        $params = [
            'areas' => $areas,
            'estados' => $estados,
            'fecha_desde' => $fechaDesde?->format('Y-m-d'),
            'fecha_hasta' => $fechaHasta?->format('Y-m-d')
        ];
        
        return 'query_estadisticas_' . md5(serialize($params));
    }

    /**
     * Verificar si es para todas las áreas
     */
    public function esParaTodasLasAreas(): bool
    {
        return empty($this->areas);
    }

    /**
     * Verificar si es para todos los estados
     */
    public function esParaTodosLosEstados(): bool
    {
        return empty($this->estados);
    }

    /**
     * Verificar si tiene filtro de fechas
     */
    public function tieneFiltroDeFechas(): bool
    {
        return $this->fechaDesde || $this->fechaHasta;
    }
}
