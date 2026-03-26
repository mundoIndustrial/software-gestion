<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PrendaPedido;
use App\Models\TipoProceso;
use Illuminate\Support\Facades\Log;

class PedidoProcesoBuilder
{
    public function resolverTipoProcesoId(string $tipoProceso): ?int
    {
        $tipoProceso = trim(strtolower($tipoProceso));

        $tipoProcesoModel = TipoProceso::where('slug', $tipoProceso)
            ->orWhere('nombre', $tipoProceso)
            ->orWhere('nombre', strtoupper($tipoProceso))
            ->first();

        return $tipoProcesoModel?->id;
    }

    public function eliminarDuplicado(PrendaPedido $prenda, int $tipoProcesoId): void
    {
        $procesoExistente = PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $prenda->id)
            ->where('tipo_proceso_id', $tipoProcesoId)
            ->first();

        if ($procesoExistente) {
            Log::warning('[PedidoProcesoBuilder] Proceso duplicado eliminado', [
                'prenda_pedido_id' => $prenda->id,
                'tipo_proceso_id' => $tipoProcesoId,
                'proceso_id' => $procesoExistente->id,
            ]);

            $procesoExistente->delete();
        }
    }

    public function crearBase(
        PrendaPedido $prenda,
        int $tipoProcesoId,
        array $ubicaciones = [],
        ?string $observaciones = null,
        string $modoTallas = 'generico',
        ?array $datosAdicionales = null,
        string $estado = 'PENDIENTE'
    ): PedidosProcesosPrendaDetalle {
        $payload = [
            'prenda_pedido_id' => $prenda->id,
            'tipo_proceso_id' => $tipoProcesoId,
            'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : json_encode([]),
            'observaciones' => $observaciones,
            'modo_tallas' => $modoTallas,
            'estado' => $estado,
        ];

        if ($datosAdicionales !== null) {
            $payload['datos_adicionales'] = json_encode($datosAdicionales);
        }

        $procesoPrenda = PedidosProcesosPrendaDetalle::create($payload)->fresh();

        Log::info('[PedidoProcesoBuilder] Proceso base creado', [
            'proceso_id' => $procesoPrenda->id,
            'prenda_id' => $prenda->id,
            'tipo_proceso_id' => $tipoProcesoId,
            'modo_tallas' => $modoTallas,
        ]);

        return $procesoPrenda;
    }
}
