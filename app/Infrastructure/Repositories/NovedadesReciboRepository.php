<?php

namespace App\Infrastructure\Repositories;

use App\Models\PrendaPedidoNovedadRecibo;
use App\Models\PedidoProduccion;
use App\Models\ConsecutivoReciboPedido;
use Illuminate\Support\Collection;

class NovedadesReciboRepository
{
    /**
     * Obtener novedades por recibo y pedido
     */
    public function obtenerPorRecibo(int|string $pedidoId, int $numeroRecibo): Collection
    {
        \Log::info('[NovedadesReciboRepository] obtenerPorRecibo inicio', [
            'pedido_id_input' => $pedidoId,
            'numero_recibo' => $numeroRecibo,
        ]);

        $pedido = PedidoProduccion::query()
            ->where('numero_pedido', is_numeric((string) $pedidoId) ? (int) $pedidoId : trim((string) $pedidoId))
            ->first();

        if (!$pedido && is_numeric((string) $pedidoId)) {
            $pedido = PedidoProduccion::find((int) $pedidoId);
        }

        if (!$pedido) {
            \Log::warning('[NovedadesReciboRepository] pedido no encontrado', [
                'pedido_id_input' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
            ]);

            return collect();
        }
        
        // Buscar la prenda específica del recibo
        $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
            ->where('consecutivo_actual', $numeroRecibo)
            ->where('activo', 1)
            ->first();
        
        // Obtener novedades filtradas
        $query = PrendaPedidoNovedadRecibo::where('numero_recibo', $numeroRecibo);
        
        if ($recibo && $recibo->prenda_id) {
            $query->where('prenda_pedido_id', $recibo->prenda_id);
            \Log::info('[NovedadesReciboRepository] prenda del recibo encontrada', [
                'pedido_id_db' => $pedido->id,
                'pedido_numero' => $pedido->numero_pedido,
                'recibo_id' => $recibo->id,
                'prenda_id' => $recibo->prenda_id,
            ]);
        } else {
            \Log::warning('[NovedadesReciboRepository] no se pudo resolver prenda del recibo', [
                'pedido_id_db' => $pedido->id,
                'pedido_numero' => $pedido->numero_pedido,
                'numero_recibo' => $numeroRecibo,
            ]);

            return collect();
        }

        $resultado = $query->orderBy('creado_en', 'desc')
            ->with(['creadoPor', 'editadoPor', 'resueltoPor', 'prendaPedido'])
            ->get();

        \Log::info('[NovedadesReciboRepository] novedades encontradas', [
            'pedido_id_db' => $pedido->id,
            'numero_recibo' => $numeroRecibo,
            'total' => $resultado->count(),
            'prenda_ids' => $resultado->pluck('prenda_pedido_id')->unique()->values()->all(),
        ]);

        return $resultado;
    }

    /**
     * Crear novedad
     */
    public function crear(
        int $prendaId,
        int $numeroRecibo,
        string $novedadTexto,
        string $tipoNovedad,
        int $usuarioId
    ): PrendaPedidoNovedadRecibo {
        return PrendaPedidoNovedadRecibo::create([
            'prenda_pedido_id' => $prendaId,
            'numero_recibo' => $numeroRecibo,
            'novedad_texto' => $novedadTexto,
            'tipo_novedad' => $tipoNovedad,
            'creado_por' => $usuarioId,
            'estado_novedad' => PrendaPedidoNovedadRecibo::ESTADO_ACTIVA,
        ]);
    }

    /**
     * Obtener novedad por ID
     */
    public function obtenerPorId(int $novedadId): PrendaPedidoNovedadRecibo
    {
        return PrendaPedidoNovedadRecibo::findOrFail($novedadId);
    }

    /**
     * Actualizar novedad
     */
    public function actualizar(int $novedadId, array $datos): PrendaPedidoNovedadRecibo
    {
        $novedad = $this->obtenerPorId($novedadId);
        $novedad->update($datos);
        
        return $novedad->refresh();
    }

    /**
     * Eliminar novedad
     */
    public function eliminar(int $novedadId): bool
    {
        return $this->obtenerPorId($novedadId)->delete();
    }

    /**
     * Obtener consolidado de novedades por recibo
     */
    public function obtenerConsolidadoPorRecibo(int $pedidoId, int $numeroRecibo): array
    {
        $pedido = PedidoProduccion::findOrFail($pedidoId);
        
        $novedadesArray = [];
        
        if ($pedido->prendas && $pedido->prendas->count() > 0) {
            foreach ($pedido->prendas as $prenda) {
                $novedadesPrenda = $prenda->novedadesRecibo()
                    ->where('numero_recibo', $numeroRecibo)
                    ->where('estado_novedad', PrendaPedidoNovedadRecibo::ESTADO_ACTIVA)
                    ->orderBy('creado_en', 'desc')
                    ->pluck('novedad_texto')
                    ->toArray();
                
                $novedadesArray = array_merge($novedadesArray, $novedadesPrenda);
            }
        }
        
        return [
            'novedades_texto' => !empty($novedadesArray) ? implode("\n", $novedadesArray) : '',
            'total_novedades' => count($novedadesArray)
        ];
    }
}
