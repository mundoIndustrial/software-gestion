<?php

namespace App\Domain\Bodega\Services;

use App\Models\ReciboPrenda;
use App\Models\BodegaDetallesTalla;
use Illuminate\Support\Collection;

/**
 * Domain Service para filtrado de pedidos según reglas de negocio
 * Responsabilidad: Encapsular lógica de filtrado compleja
 */
class PedidoFilterService
{
    /**
     * Estados permitidos para pedidos activos
     */
    private const ESTADOS_PERMITIDOS = [
        'ENTREGADO', 'EN EJECUCIÓN', 'NO INICIADO', 'ANULADA', 
        'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA'
    ];

    /**
     * Filtrar pedidos por área específica
     */
    public function filtrarPorArea(Collection $pedidos, array $areasPermitidas): Collection
    {
        return $pedidos->filter(function($item) use ($areasPermitidas) {
            return $this->tieneDetallesEnArea($item->numero_pedido, $areasPermitidas);
        })->values();
    }

    /**
     * Obtener pedidos base con estados permitidos
     */
    public function obtenerPedidosBase(): Collection
    {
        return ReciboPrenda::with(['asesor'])
            ->where(function($q) {
                foreach(self::ESTADOS_PERMITIDOS as $estado) {
                    $q->orWhereRaw('UPPER(TRIM(estado)) = ?', [strtoupper($estado)]);
                }
            })
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Verificar si un pedido tiene detalles en áreas específicas
     */
    private function tieneDetallesEnArea(string $numeroPedido, array $areasPermitidas): bool
    {
        $bdDetalles = BodegaDetallesTalla::where('numero_pedido', $numeroPedido)->get();
        
        if ($bdDetalles->isEmpty()) {
            return false;
        }
        
        foreach ($bdDetalles as $detalle) {
            if (in_array($detalle->area, $areasPermitidas)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtener números de pedido únicos de una colección
     */
    public function obtenerNumerosUnicos(Collection $pedidos): Collection
    {
        return $pedidos->pluck('numero_pedido')->unique()->values();
    }

    /**
     * Preparar datos para paginación
     */
    public function prepararPaginacion(Collection $numerosPedidos, int $paginaActual = 1, int $porPagina = 15): array
    {
        $totalPedidos = $numerosPedidos->count();
        $offset = ($paginaActual - 1) * $porPagina;
        $pedidosPaginados = $numerosPedidos->slice($offset, $porPagina);

        return [
            'pedidos_paginados' => $pedidosPaginados,
            'total_pedidos' => $totalPedidos,
            'pagina_actual' => $paginaActual,
            'por_pagina' => $porPagina,
        ];
    }
}
