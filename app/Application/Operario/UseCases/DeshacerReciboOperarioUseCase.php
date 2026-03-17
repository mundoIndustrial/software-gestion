<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Events\OperarioRecibosActualizados;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\PrendaPedido;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeshacerReciboOperarioUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
    ) {}

    public function execute(int $idRecibo): ReciboCommandResultDTO
    {
        try {
            $usuario = Auth::user();

            $esCortador = $usuario->hasRole('cortador');
            $esCosturero = $usuario->hasRole('costurero');
            $esCosturaReflectivo = $usuario->hasRole('costura-reflectivo');
            $esLiderReflectivo = $usuario->hasRole('lider-reflectivo');
            $esAdminCostura = $usuario->hasRole('administrador-costura');
            $areaOperario = $esCortador
                ? 'Corte'
                : (($esCosturero || $esCosturaReflectivo || $esLiderReflectivo || $esAdminCostura) ? 'Costura' : null);
            if (!$areaOperario) {
                return new ReciboCommandResultDTO(false, 'Rol no autorizado', 403);
            }

            if ($esCortador) {
                $recibo = $this->recibos->findActiveById((int) $idRecibo);

                if ($recibo) {
                    $areaRecibo = strtolower(trim((string) ($recibo->area ?? '')));
                    if ($areaRecibo === 'costura') {
                        $sinEncargadoCostura = true;
                        $procesoCostura = null;

                        if (!empty($recibo->prenda_id)) {
                            $numeroPedido = null;
                            $prenda = PrendaPedido::where('id', $recibo->prenda_id)
                                ->with(['pedidoProduccion'])
                                ->first();
                            if ($prenda && $prenda->pedidoProduccion) {
                                $numeroPedido = (int) $prenda->pedidoProduccion->numero_pedido;
                            }

                            $procesoCostura = !empty($numeroPedido)
                                ? $this->procesos->findLatestByProcesoAndNumeroRecibo(
                                    numeroPedido: (int) $numeroPedido,
                                    prendaId: (int) $recibo->prenda_id,
                                    proceso: 'Costura',
                                    numeroRecibo: (int) $recibo->consecutivo_actual,
                                )
                                : $this->procesos->findLatestByPrendaAndProceso((int) $recibo->prenda_id, 'Costura');
                            if ($procesoCostura && !empty($procesoCostura->encargado)) {
                                $sinEncargadoCostura = false;
                            }

                            // Fallback
                            if ($sinEncargadoCostura && empty($procesoCostura)) {
                                $procesoCosturaFallback = !empty($numeroPedido)
                                    ? $this->procesos->findLatestByProceso(
                                        numeroPedido: (int) $numeroPedido,
                                        prendaId: (int) $recibo->prenda_id,
                                        proceso: 'Costura',
                                    )
                                    : $this->procesos->findLatestByPrendaAndProceso((int) $recibo->prenda_id, 'Costura');

                                if ($procesoCosturaFallback && !empty($procesoCosturaFallback->encargado)) {
                                    $sinEncargadoCostura = false;
                                }

                                if (empty($procesoCostura) && !empty($procesoCosturaFallback)) {
                                    $procesoCostura = $procesoCosturaFallback;
                                }
                            }
                        }

                        if ($sinEncargadoCostura) {
                            $recibo->area = 'Corte';
                            $recibo->save();

                            if (!empty($procesoCostura)) {
                                $procesoCostura->forceDelete();
                            }
                        }
                    }
                }
            }

            DB::table('prenda_recibo_completado')
                ->where('id_recibo', (int) $idRecibo)
                ->where('area', $areaOperario)
                ->delete();

            // Notificar a todos los usuarios con rol vista-costura
            try {
                $recibo = $this->recibos->findActiveById((int) $idRecibo);
                if ($recibo) {
                    $usuariosVistaCostura = User::all()->filter(function ($user) {
                        return $user->hasRole('vista-costura');
                    });

                    foreach ($usuariosVistaCostura as $usuarioVistaCostura) {
                        broadcast(new OperarioRecibosActualizados(
                            userId: (int) $usuarioVistaCostura->id,
                            payload: [
                                'area' => (string) $areaOperario,
                                'accion' => 'recibo_deshecho',
                                'recibo_id' => (int) $recibo->id,
                                'consecutivo' => (int) ($recibo->consecutivo_actual ?? 0),
                                'prenda_id' => $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                                'tipo_recibo' => (string) ($recibo->tipo_recibo ?? ''),
                                'mensaje' => "El recibo #{$recibo->consecutivo_actual} fue deshecho",
                            ]
                        ));
                    }

                    \Log::info('[OperarioController] Broadcast deshacer a vista-costura enviado', [
                        'recibo_id' => (int) $idRecibo,
                        'total_vista_costura' => $usuariosVistaCostura->count(),
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('[OperarioController] Error al broadcast deshacer a vista-costura', [
                    'recibo_id' => (int) $idRecibo,
                    'error' => $e->getMessage(),
                ]);
            }

            return new ReciboCommandResultDTO(true, 'Marca de completado eliminada', 200);
        } catch (\Exception $e) {
            \Log::error('Error al deshacer recibo: ' . $e->getMessage(), [
                'id_recibo' => $idRecibo,
                'exception' => $e,
            ]);

            return new ReciboCommandResultDTO(false, 'Error al deshacer el recibo', 500);
        }
    }
}
