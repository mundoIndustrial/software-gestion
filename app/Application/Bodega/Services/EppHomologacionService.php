<?php

namespace App\Application\Bodega\Services;

use App\Models\PedidoEpp;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EppHomologacionService
{
    public function obtenerHistorialHomologaciones(int $pedidoEppId, ?int $pedidoProductionId): array
    {
        if (!$pedidoProductionId) {
            return [];
        }

        // Cargar todos los EPPs del pedido una sola vez
        $todosLosEpps = $this->cargarEppsDePedido($pedidoProductionId);

        if (!$todosLosEpps->has($pedidoEppId)) {
            return [];
        }

        // Encontrar el EPP original retrocediendo en la cadena
        $eppIdOriginal = $this->obtenerEppOriginal($pedidoEppId, $todosLosEpps);

        // Construir la cadena desde el original hacia adelante
        return $this->construirCadena($eppIdOriginal, $todosLosEpps);
    }

    private function cargarEppsDePedido(int $pedidoProductionId): Collection
    {
        return PedidoEpp::query()
            ->leftJoin('epps', 'pedido_epp.epp_id', '=', 'epps.id')
            ->where('pedido_epp.pedido_produccion_id', $pedidoProductionId)
            ->select([
                'pedido_epp.id',
                'pedido_epp.homologado_de',
                'pedido_epp.epp_id',
                'epps.nombre_completo',
                'pedido_epp.cantidad',
                'pedido_epp.created_at',
                'pedido_epp.deleted_at',
                'pedido_epp.observaciones',
            ])
            ->get()
            ->keyBy('id');
    }

    private function obtenerEppOriginal(int $eppIdActual, Collection $todosLosEpps): int
    {
        $intentos = 0;
        $maxIntentos = 30;

        while ($intentos < $maxIntentos) {
            $intentos++;
            $epp = $todosLosEpps->get($eppIdActual);

            if (!$epp || !$epp->homologado_de) {
                break;
            }

            $eppIdActual = $epp->homologado_de;
        }

        return $eppIdActual;
    }

    private function construirCadena(int $eppIdOriginal, Collection $todosLosEpps): array
    {
        $historial = [];
        $eppIdActual = $eppIdOriginal;
        $visitados = collect();
        $intentos = 0;
        $maxIntentos = 30;

        while ($intentos < $maxIntentos) {
            $intentos++;

            if ($visitados->contains($eppIdActual)) {
                break; // Ciclo detectado
            }

            $visitados->push($eppIdActual);
            $epp = $todosLosEpps->get($eppIdActual);

            if (!$epp) {
                break;
            }

            $historial[] = [
                'pedido_epp_id' => $epp->id,
                'epp_id' => $epp->epp_id,
                'epp_nombre' => $epp->nombre_completo ?? 'EPP sin nombre',
                'cantidad' => $epp->cantidad,
                'fecha_creacion' => $epp->created_at ? Carbon::parse($epp->created_at)->format('Y-m-d H:i') : null,
                'deleted_at' => $epp->deleted_at,
                'observaciones' => $epp->observaciones ?? '-',
                'es_original' => $epp->homologado_de === null,
            ];

            // Buscar siguiente en la cadena (en memoria, sin query)
            $siguiente = $todosLosEpps->first(fn ($e) => $e->homologado_de === $epp->id);
            if (!$siguiente) {
                break;
            }

            $eppIdActual = $siguiente->id;
        }

        return $historial;
    }
}
