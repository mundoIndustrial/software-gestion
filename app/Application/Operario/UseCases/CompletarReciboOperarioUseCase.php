<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Events\OperarioRecibosActualizados;
use App\Events\ReciboCompletado;
use App\Models\PrendaPedido;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompletarReciboOperarioUseCase
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

            $recibo = $this->recibos->findActiveById((int) $idRecibo);

            if (!$recibo) {
                return new ReciboCommandResultDTO(false, 'Recibo no encontrado', 404);
            }

            $areaRecibo = trim((string) ($recibo->area ?? ''));
            if (strcasecmp($areaRecibo, $areaOperario) !== 0) {
                return new ReciboCommandResultDTO(false, 'Este recibo no está en tu área actual', 403);
            }

            $nombreOperario = (string) $usuario->name;

            // Para costura-reflectivo, lider-reflectivo y administrador-costura: usar el nombre del encargado asignado
            if ($esCosturaReflectivo || $esLiderReflectivo || $esAdminCostura) {
                $encargadoActual = null;
                if (!empty($recibo->prenda_id)) {
                    $procesoCostura = $this->procesos->findLatestByPrendaAndProceso((int) $recibo->prenda_id, 'Costura');
                    $encargadoActual = $procesoCostura?->encargado;
                }

                $encargadoActual = is_string($encargadoActual) ? trim($encargadoActual) : $encargadoActual;
                if (empty($encargadoActual)) {
                    return new ReciboCommandResultDTO(false, 'El recibo no tiene encargado de Costura asignado', 422);
                }
                $nombreOperario = (string) $encargadoActual;
            }

            if ($esCortador) {
                DB::transaction(function () use ($recibo) {
                    $recibo->area = 'Costura';
                    $recibo->save();

                    if (!empty($recibo->prenda_id)) {
                        $prenda = PrendaPedido::where('id', $recibo->prenda_id)
                            ->with(['pedidoProduccion'])
                            ->first();

                        $numeroPedido = $prenda && $prenda->pedidoProduccion
                            ? (int) $prenda->pedidoProduccion->numero_pedido
                            : null;

                        if (!empty($numeroPedido)) {
                            $procesoCostura = $this->procesos->findLatestByProceso(
                                numeroPedido: (int) $numeroPedido,
                                prendaId: (int) $recibo->prenda_id,
                                proceso: 'Costura',
                            );

                            if (!$procesoCostura) {
                                $procesoCostura = $this->procesos->create([
                                    'numero_pedido' => $numeroPedido,
                                    'prenda_pedido_id' => $recibo->prenda_id,
                                    'numero_recibo' => $recibo->consecutivo_actual,
                                    'proceso' => 'Costura',
                                    'fecha_inicio' => now(),
                                    'encargado' => null,
                                    'estado_proceso' => 'Pendiente',
                                    'codigo_referencia' => 'COS-' . ($recibo->consecutivo_actual ?? 0) . '-' . date('YmdHis'),
                                ]);
                            } else {
                                $this->procesos->update($procesoCostura, [
                                    'encargado' => null,
                                ]);
                            }
                        }
                    }
                });
            }

            DB::table('prenda_recibo_completado')->updateOrInsert(
                ['id_recibo' => (int) $recibo->id, 'area' => $areaOperario],
                [
                    'numero_recibo' => (int) ($recibo->consecutivo_actual ?? 0),
                    'nombre_operario' => $nombreOperario,
                    'fecha_completado' => now(),
                ]
            );

            try {
                event(new ReciboCompletado([
                    'recibo_id' => (int) $recibo->id,
                    'consecutivo' => (int) ($recibo->consecutivo_actual ?? 0),
                    'pedido_produccion_id' => (int) ($recibo->pedido_produccion_id ?? 0),
                    'prenda_id' => $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                    'tipo_recibo' => (string) ($recibo->tipo_recibo ?? ''),
                    'area' => (string) $areaOperario,
                    'nombre_operario' => (string) $nombreOperario,
                ]));

                // Notificar a todos los usuarios con rol vista-costura
                $usuariosVistaCostura = User::all()->filter(function ($user) {
                    return $user->hasRole('vista-costura');
                });

                foreach ($usuariosVistaCostura as $usuarioVistaCostura) {
                    broadcast(new OperarioRecibosActualizados(
                        userId: (int) $usuarioVistaCostura->id,
                        payload: [
                            'area' => (string) $areaOperario,
                            'accion' => 'recibo_completado',
                            'recibo_id' => (int) $recibo->id,
                            'consecutivo' => (int) ($recibo->consecutivo_actual ?? 0),
                            'prenda_id' => $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                            'tipo_recibo' => (string) ($recibo->tipo_recibo ?? ''),
                            'nombre_operario' => (string) $nombreOperario,
                            'mensaje' => "El recibo #{$recibo->consecutivo_actual} fue completado por {$nombreOperario}",
                        ]
                    ));
                }

                \Log::info('[OperarioController] Broadcast a vista-costura enviado', [
                    'recibo_id' => (int) $idRecibo,
                    'total_vista_costura' => $usuariosVistaCostura->count(),
                ]);
            } catch (\Exception $e) {
                \Log::warning('[OperarioController] Error al broadcast ReciboCompletado', [
                    'recibo_id' => (int) $idRecibo,
                    'error' => $e->getMessage(),
                ]);
            }

            return new ReciboCommandResultDTO(true, 'Recibo marcado como completado', 200);
        } catch (\Exception $e) {
            \Log::error('Error al completar recibo: ' . $e->getMessage(), [
                'id_recibo' => $idRecibo,
                'exception' => $e,
            ]);

            return new ReciboCommandResultDTO(false, 'Error al completar el recibo', 500);
        }
    }
}
