<?php

namespace App\Application\Operario\UseCases;

use App\Application\Operario\DTOs\ReciboCommandResultDTO;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Events\OperarioRecibosActualizados;
use App\Events\ReciboCompletado;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PrendaPedido;
use App\Models\ReciboPorPartes;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompletarReciboOperarioUseCase
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
                return new ReciboCommandResultDTO(false, 'Solo los roles de Costura pueden completar parciales', 403);
            }

            if ($esParcial) {
                $parcial = ReciboPorPartes::query()->with(['pedido', 'prenda'])->find((int) $idRecibo);

                if (!$parcial || !$parcial->pedido || !$parcial->prenda) {
                    return new ReciboCommandResultDTO(false, 'Parcial no encontrado', 404);
                }

                $nombreOperario = (string) $usuario->name;
                if ($esCosturaReflectivo || $esLiderReflectivo || $esAdminCostura) {
                    $procesoParcial = $this->buscarProcesoCosturaParcial($parcial);
                    $encargadoActual = is_string($procesoParcial?->encargado) ? trim($procesoParcial->encargado) : $procesoParcial?->encargado;

                    if (empty($encargadoActual)) {
                        return new ReciboCommandResultDTO(false, 'El parcial no tiene encargado de Costura asignado', 422);
                    }

                    $nombreOperario = (string) $encargadoActual;
                }

                DB::table('prenda_recibo_completado')->updateOrInsert(
                    ['id_parcial' => (int) $parcial->id, 'area' => $areaOperario],
                    [
                        'id_recibo' => null,
                        'numero_recibo' => $parcial->consecutivo_parcial,
                        'nombre_operario' => $nombreOperario,
                        'fecha_completado' => now(),
                    ]
                );

                $estadoOriginal = $this->sincronizarCompletadoOriginalDesdeParciales($parcial, $areaOperario, $nombreOperario);
                $this->notificarVistaCosturaCompletadoParcial($parcial, $areaOperario, $nombreOperario, $estadoOriginal);

                return new ReciboCommandResultDTO(true, 'Parcial marcado como completado', 200);
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
                    'id_parcial' => null,
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

                $this->notificarVistaCosturaCompletadoRecibo(
                    reciboId: (int) $recibo->id,
                    consecutivo: (string) ($recibo->consecutivo_actual ?? '0'),
                    pedidoId: (int) ($recibo->pedido_produccion_id ?? 0),
                    prendaId: $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                    tipoRecibo: (string) ($recibo->tipo_recibo ?? ''),
                    nombreOperario: (string) $nombreOperario,
                    mensaje: "El recibo #{$recibo->consecutivo_actual} fue completado por {$nombreOperario}",
                    esParcial: false,
                    pedidoParcialId: null,
                    consecutivoParcial: null,
                    originalCompletado: true
                );
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

    private function buscarProcesoCosturaParcial(ReciboPorPartes $parcial): ?\App\Models\ProcesoPrenda
    {
        $numeroPedido = (int) ($parcial->pedido?->numero_pedido ?? 0);
        if ($numeroPedido <= 0) {
            return null;
        }

        return \App\Models\ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->first();
    }

    private function sincronizarCompletadoOriginalDesdeParciales(ReciboPorPartes $parcial, string $areaOperario, string $nombreOperario): array
    {
        $parcialIds = ReciboPorPartes::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->where('tipo_recibo', $parcial->tipo_recibo)
            ->where('consecutivo_original', $parcial->consecutivo_original)
            ->pluck('id');

        if ($parcialIds->isEmpty()) {
            return ['original_completado' => false, 'recibo_original_id' => null];
        }

        $totalCompletados = DB::table('prenda_recibo_completado')
            ->where('area', $areaOperario)
            ->whereIn('id_parcial', $parcialIds->all())
            ->count();

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

        if ($totalCompletados >= $parcialIds->count()) {
            DB::table('prenda_recibo_completado')->updateOrInsert(
                ['id_recibo' => (int) $reciboOriginal->id, 'area' => $areaOperario],
                [
                    'id_parcial' => null,
                    'numero_recibo' => $reciboOriginal->consecutivo_actual,
                    'nombre_operario' => $nombreOperario,
                    'fecha_completado' => now(),
                ]
            );
            return ['original_completado' => true, 'recibo_original_id' => (int) $reciboOriginal->id];
        }

        DB::table('prenda_recibo_completado')
            ->where('id_recibo', (int) $reciboOriginal->id)
            ->where('area', $areaOperario)
            ->delete();

        return ['original_completado' => false, 'recibo_original_id' => (int) $reciboOriginal->id];
    }

    private function notificarVistaCosturaCompletadoParcial(
        ReciboPorPartes $parcial,
        string $areaOperario,
        string $nombreOperario,
        array $estadoOriginal
    ): void {
        $usuariosVistaCostura = User::query()
            ->get()
            ->filter(fn ($user) => $user->hasRole('vista-costura'));

        foreach ($usuariosVistaCostura as $usuarioVistaCostura) {
            broadcast(new OperarioRecibosActualizados(
                userId: (int) $usuarioVistaCostura->id,
                payload: [
                    'area' => $areaOperario,
                    'accion' => 'recibo_completado',
                    'es_parcial' => true,
                    'pedido_parcial_id' => (int) $parcial->id,
                    'id_parcial' => (int) $parcial->id,
                    'recibo_id' => (int) $parcial->id,
                    'consecutivo' => (string) $this->formatearConsecutivoParcial($parcial->consecutivo_parcial),
                    'consecutivo_parcial' => (string) $this->formatearConsecutivoParcial($parcial->consecutivo_parcial),
                    'consecutivo_original' => (string) $this->formatearConsecutivoParcial($parcial->consecutivo_original),
                    'pedido_produccion_id' => (int) $parcial->pedido_produccion_id,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'tipo_recibo' => (string) ($parcial->tipo_recibo ?: 'PARCIAL'),
                    'nombre_operario' => $nombreOperario,
                    'original_completado' => (bool) ($estadoOriginal['original_completado'] ?? false),
                    'recibo_original_id' => $estadoOriginal['recibo_original_id'] ?? null,
                    'mensaje' => "El parcial #{$this->formatearConsecutivoParcial($parcial->consecutivo_parcial)} fue completado por {$nombreOperario}",
                ]
            ));
        }
    }

    private function notificarVistaCosturaCompletadoRecibo(
        int $reciboId,
        string $consecutivo,
        int $pedidoId,
        ?int $prendaId,
        string $tipoRecibo,
        string $nombreOperario,
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
                    'accion' => 'recibo_completado',
                    'es_parcial' => $esParcial,
                    'recibo_id' => $reciboId,
                    'pedido_parcial_id' => $pedidoParcialId,
                    'consecutivo' => $consecutivo,
                    'consecutivo_parcial' => $consecutivoParcial,
                    'pedido_produccion_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'tipo_recibo' => $tipoRecibo,
                    'nombre_operario' => $nombreOperario,
                    'original_completado' => $originalCompletado,
                    'mensaje' => $mensaje,
                ]
            ));
        }
    }

    private function formatearConsecutivoParcial($valor): string
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return '0';
        }

        if (is_numeric($texto)) {
            $numero = (float) $texto;
            if (abs($numero - (int) $numero) < 0.00001) {
                return (string) (int) $numero;
            }

            return rtrim(rtrim(number_format($numero, 2, '.', ''), '0'), '.');
        }

        return $texto;
    }
}
