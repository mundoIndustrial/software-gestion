<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\PedidoProduccionNovedadesRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Repositories\TablaOriginalBodegaNovedadesRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportarPendienteOperarioUseCase
{
    public function __construct(
        private readonly ProcesoPrendaRepository $procesos,
        private readonly PedidoProduccionNovedadesRepository $pedidosNovedades,
        private readonly TablaOriginalBodegaNovedadesRepository $bodegaNovedades,
    ) {}

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(Request $request): array
    {
        $usuario = Auth::user();
        $numeroPedido = (int) $request->input('numero_pedido');
        $novedad = (string) $request->input('novedad');

        $area = $usuario->hasRole('cortador') ? 'Corte' : 'Costura';

        $proceso = $this->procesos->findByNumeroPedidoProcesoEncargado(
            numeroPedido: $numeroPedido,
            proceso: $area,
            encargado: (string) $usuario->name
        );

        if (!$proceso) {
            return [
                'status' => 404,
                'payload' => [
                    'success' => false,
                    'message' => 'Proceso no encontrado para este pedido',
                ],
            ];
        }

        $novedadFormato = "[{$usuario->name} - " . now()->format('d-m-Y H:i:s') . "] {$area}: {$novedad}";

        $this->procesos->update($proceso, [
            'estado_proceso' => 'Pendiente',
            'observaciones' => $novedad,
            'novedades' => $novedadFormato,
        ]);

        $this->pedidosNovedades->appendNovedadesPorNumeroPedido($numeroPedido, $novedadFormato);
        $this->bodegaNovedades->appendNovedadesPorNumeroPedido($numeroPedido, $novedadFormato);

        \Cache::forget("pedido_data_{$numeroPedido}");
        \Cache::forget("fotos_pedido_{$numeroPedido}");
        \Cache::forget("registros_index");
        \Cache::forget("registros_search_{$numeroPedido}");
        \Cache::forget("registro_pedido_{$numeroPedido}");

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'message' => 'Novedad reportada correctamente. El estado ha sido cambiado a Pendiente.',
                'estado_nuevo' => 'Pendiente',
            ],
        ];
    }
}

