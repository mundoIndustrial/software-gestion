<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Events\OperarioRecibosActualizados;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PrendaPedido;
use App\Models\ReciboPorPartes;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeshacerReciboOperarioUseCase
{
    public function __construct(
        private readonly ConsecutivoReciboPedidoRepository $recibos,
        private readonly ProcesoPrendaRepository $procesos,
    ) {}

    public function execute(int $idRecibo, bool $esParcial = false): ReciboCommandResultDTO
    {
        try {
            $usuario = Auth::user();

            $esCortador = $usuario->hasRole('cortador');
            $esCosturero = $usuario->hasRole('costurero');
            $esConfeccionSobremedida = $usuario->hasRole('confeccion-sobremedida');
            $esCosturaReflectivo = $usuario->hasRole('costura-reflectivo');
            $esLiderReflectivo = $usuario->hasRole('lider-reflectivo');
            $esAdminCostura = $usuario->hasRole('administrador-costura');
            $areaOperario = $esCortador
                ? 'Corte'
                : (($esCosturero || $esConfeccionSobremedida || $esCosturaReflectivo || $esLiderReflectivo || $esAdminCostura) ? 'Costura' : null);
            if (!$areaOperario) {
                return new ReciboCommandResultDTO(false, 'Rol no autorizado', 403);
            }

            if ($esParcial && $areaOperario !== 'Costura') {
                return new ReciboCommandResultDTO(false, 'Solo los roles de Costura pueden deshacer parciales', 403);
            }

            if ($esParcial) {
                $parcial = ReciboPorPartes::query()->find((int) $idRecibo);

                if (!$parcial) {
                    return new ReciboCommandResultDTO(false, 'Parcial no encontrado', 404);
                }

                DB::table('prenda_recibo_completado')
                    ->where('id_parcial', (int) $parcial->id)
                    ->where('area', $areaOperario)
                    ->delete();

                $estadoOriginal = $this->sincronizarCompletadoOriginalDesdeParciales($parcial, $areaOperario);
                $this->notificarVistaCosturaDeshacerParcial($parcial, $areaOperario, $estadoOriginal);

                return new ReciboCommandResultDTO(true, 'Marca de completado del parcial eliminada', 200);
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

            try {
                $recibo = $this->recibos->findActiveById((int) $idRecibo);
                if ($recibo) {
                    $this->notificarVistaCosturaDeshacerRecibo(
                        reciboId: (int) $recibo->id,
                        consecutivo: (string) ($recibo->consecutivo_actual ?? '0'),
                        pedidoId: (int) ($recibo->pedido_produccion_id ?? 0),
                        prendaId: $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                        tipoRecibo: (string) ($recibo->tipo_recibo ?? ''),
                        mensaje: "El recibo #{$recibo->consecutivo_actual} fue deshecho",
                        esParcial: false,
                        pedidoParcialId: null,
                        consecutivoParcial: null,
                        originalCompletado: false
                    );
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

    private function sincronizarCompletadoOriginalDesdeParciales(ReciboPorPartes $parcial, string $areaOperario): array
    {
        $parcialIds = ReciboPorPartes::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->where('tipo_recibo', $parcial->tipo_recibo)
            ->where('consecutivo_original', $parcial->consecutivo_original)
            ->pluck('id');

        $reciboOriginal = ConsecutivoReciboPedido::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_id', $parcial->prenda_pedido_id)
            ->where('tipo_recibo', $parcial->tipo_recibo)
            ->where('consecutivo_actual', $parcial->consecutivo_original)
            ->where('activo', true)
            ->first();

        if (!$reciboOriginal) {
            return ['original_completado' => false, 'recibo_original_id' => null];
        }

        $totalCompletados = $parcialIds->isEmpty()
            ? 0
            : DB::table('prenda_recibo_completado')
                ->where('area', $areaOperario)
                ->whereIn('id_parcial', $parcialIds->all())
                ->count();

        if ($totalCompletados < $parcialIds->count()) {
            DB::table('prenda_recibo_completado')
                ->where('id_recibo', (int) $reciboOriginal->id)
                ->where('area', $areaOperario)
                ->delete();
        }

        return [
            'original_completado' => $totalCompletados >= $parcialIds->count(),
            'recibo_original_id' => (int) $reciboOriginal->id,
        ];
    }

    private function notificarVistaCosturaDeshacerParcial(ReciboPorPartes $parcial, string $areaOperario, array $estadoOriginal): void
    {
        $usuariosVistaCostura = User::query()
            ->get()
            ->filter(fn ($user) => $user->hasRole('vista-costura'));

        foreach ($usuariosVistaCostura as $usuarioVistaCostura) {
            broadcast(new OperarioRecibosActualizados(
                userId: (int) $usuarioVistaCostura->id,
                payload: [
                    'area' => $areaOperario,
                    'accion' => 'recibo_deshecho',
                    'es_parcial' => true,
                    'pedido_parcial_id' => (int) $parcial->id,
                    'id_parcial' => (int) $parcial->id,
                    'recibo_id' => (int) $parcial->id,
                    'consecutivo' => (string) $parcial->consecutivo_parcial,
                    'consecutivo_parcial' => (string) $parcial->consecutivo_parcial,
                    'consecutivo_original' => (string) $parcial->consecutivo_original,
                    'pedido_produccion_id' => (int) $parcial->pedido_produccion_id,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'tipo_recibo' => (string) ($parcial->tipo_recibo ?: 'PARCIAL'),
                    'original_completado' => (bool) ($estadoOriginal['original_completado'] ?? false),
                    'recibo_original_id' => $estadoOriginal['recibo_original_id'] ?? null,
                    'mensaje' => "El parcial #{$parcial->consecutivo_parcial} fue deshecho",
                ]
            ));
        }
    }

    private function notificarVistaCosturaDeshacerRecibo(
        int $reciboId,
        string $consecutivo,
        int $pedidoId,
        ?int $prendaId,
        string $tipoRecibo,
        string $mensaje,
        bool $esParcial,
        ?int $pedidoParcialId,
        ?string $consecutivoParcial,
        bool $originalCompletado
    ): void {
        $usuariosVistaCostura = User::query()
            ->get()
            ->filter(fn ($user) => $user->hasRole('vista-costura'));

        foreach ($usuariosVistaCostura as $usuarioVistaCostura) {
            broadcast(new OperarioRecibosActualizados(
                userId: (int) $usuarioVistaCostura->id,
                payload: [
                    'area' => 'Costura',
                    'accion' => 'recibo_deshecho',
                    'es_parcial' => $esParcial,
                    'recibo_id' => $reciboId,
                    'pedido_parcial_id' => $pedidoParcialId,
                    'consecutivo' => $consecutivo,
                    'consecutivo_parcial' => $consecutivoParcial,
                    'pedido_produccion_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'tipo_recibo' => $tipoRecibo,
                    'original_completado' => $originalCompletado,
                    'mensaje' => $mensaje,
                ]
            ));
        }
    }
}
