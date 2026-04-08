<?php

namespace App\Application\Services\Asesores;

use App\Application\Pedidos\Exceptions\ObtenerPedidoDetalleException;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ObtenerPedidoDetalleService
{
    public function __construct(
        private ObtenerPedidoDetalleTransformadorService $transformadorService,
    ) {}

    public function obtener($pedidoIdentifier): PedidoProduccion
    {
        $pedido = $this->obtenerPedido($pedidoIdentifier);
        if ($pedido->asesor_id !== Auth::id()) {
            throw ObtenerPedidoDetalleException::sinPermiso();
        }
        return $pedido;
    }

    public function obtenerConPrendas($pedidoIdentifier): PedidoProduccion
    {
        $pedido = PedidoProduccion::findOrFail($this->obtenerPedido($pedidoIdentifier)->id);
        $pedido->load(['prendas' => function ($q) {
            $q->with(['procesos' => function ($q2) {
                $q2->with(['tipoProceso', 'imagenes'])->orderBy('created_at', 'desc');
            }]);
        }]);
        return $pedido;
    }

    public function obtenerCompleto($pedidoIdentifier): PedidoProduccion
    {
        $pedido = $this->obtenerPedido($pedidoIdentifier);
        if ($pedido->asesor_id !== Auth::id()) {
            throw ObtenerPedidoDetalleException::sinPermiso();
        }

        $pedido->load([
            'prendas' => function ($q) {
                $q->with([
                    'procesos' => function ($q2) {
                        $q2->with(['tipoProceso', 'imagenes'])->orderBy('created_at', 'desc');
                    },
                    'fotos',
                    'fotosTelas',
                    'variantes' => function ($q3) {
                        $q3->with(['tela', 'color', 'tipoManga', 'tipoBrocheBoton']);
                    },
                ]);
            },
            'asesora',
            'logoPedidos',
            'epps' => function ($q) {
                $q->with(['epp', 'imagenes']);
            },
        ]);

        return $pedido;
    }

    public function obtenerParaEdicion($pedidoIdentifier): array
    {
        $pedido = $this->obtenerCompleto($pedidoIdentifier);
        $prendasTransformadas = $pedido->prendas
            ->map(fn($prenda) => $this->transformadorService->transformarPrendaParaEdicion($prenda))
            ->toArray();

        $pedidoData = $pedido->toArray();
        $pedidoData['prendas'] = $prendasTransformadas;

        $epps = [];
        if ($pedido->epps) {
            foreach ($pedido->epps as $pedidoEpp) {
                $epps[] = [
                    'id' => $pedidoEpp->id,
                    'epp_id' => $pedidoEpp->epp_id,
                    'nombre' => $pedidoEpp->epp?->nombre_completo ?? ($pedidoEpp->epp_id ? "EPP #{$pedidoEpp->epp_id}" : 'EPP Desconocido'),
                    'descripcion' => $pedidoEpp->epp?->descripcion ?? '',
                    'cantidad' => $pedidoEpp->cantidad,
                    'imagenes' => $pedidoEpp->imagenes ?? [],
                    'observaciones' => $pedidoEpp->observaciones ?? '',
                ];
            }
        }

        return [
            'pedido' => (object) $pedidoData,
            'epps' => $epps,
            'estados' => ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada'],
            'areas' => [
                'Creación de Orden', 'Corte', 'Costura', 'Bordado', 'Estampado',
                'Control-Calidad', 'Entrega', 'Polos', 'Taller', 'Insumos',
                'Lavanderia', 'Arreglos', 'Despachos',
            ],
        ];
    }

    public function obtenerBasico($pedidoIdentifier): array
    {
        $pedido = $this->obtenerPedido($pedidoIdentifier);
        if ($pedido->asesor_id !== Auth::id()) {
            throw ObtenerPedidoDetalleException::sinPermiso();
        }

        return [
            'id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'estado' => $pedido->estado,
            'forma_de_pago' => $pedido->forma_de_pago,
            'fecha_creacion' => $pedido->created_at->format('d/m/Y H:i'),
        ];
    }

    private function obtenerPedido($pedidoIdentifier): PedidoProduccion
    {
        if (is_numeric($pedidoIdentifier) && $pedidoIdentifier > 100) {
            $pedido = PedidoProduccion::where('numero_pedido', $pedidoIdentifier)->first();
            if ($pedido) {
                return $pedido;
            }
        }

        $pedido = PedidoProduccion::find($pedidoIdentifier);
        if ($pedido) {
            return $pedido;
        }

        throw ObtenerPedidoDetalleException::pedidoNoEncontrado();
    }

    public function esDelUsuario($pedidoIdentifier): bool
    {
        try {
            return $this->obtenerPedido($pedidoIdentifier)->asesor_id === Auth::id();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function obtenerCantidadPrendas($pedidoIdentifier): int
    {
        return $this->obtenerPedido($pedidoIdentifier)->prendas()->count();
    }

    public function obtenerCantidadProcesos($pedidoIdentifier): int
    {
        $pedido = $this->obtenerPedido($pedidoIdentifier);
        return $pedido->prendas()->with('procesos')->get()->flatMap->procesos->count();
    }

    public function obtenerPrendaConProcesos($pedidoId, $prendaId): array
    {
        Log::info(' [PRENDA-DETALLE] Obteniendo prenda con procesos', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
        ]);

        $prenda = \App\Models\PrendaPedido::where('id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->with([
                'procesos' => function ($q) {
                    $q->with(['tipoProceso', 'imagenes'])->orderBy('created_at', 'desc');
                },
                'fotos' => function ($q) {
                    $q->whereNull('deleted_at');
                },
                'fotosTelas' => function ($q) {
                    $q->whereNull('deleted_at');
                },
                'variantes' => function ($q) {
                    $q->with(['tipoManga', 'tipoBrocheBoton']);
                },
            ])
            ->firstOrFail();

        return $this->transformadorService->transformarPrendaParaEdicion($prenda);
    }
}
