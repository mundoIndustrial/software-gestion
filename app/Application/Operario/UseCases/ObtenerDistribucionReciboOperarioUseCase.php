<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ReciboDistribucionReadRepository;
use Illuminate\Support\Facades\Auth;

class ObtenerDistribucionReciboOperarioUseCase
{
    public function __construct(
        private readonly ReciboDistribucionReadRepository $readRepository,
    ) {}

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function execute(int $idRecibo): array
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->hasRole('vista-costura')) {
            return [
                'status' => 403,
                'payload' => [
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta información',
                ],
            ];
        }

        \Log::info('[ObtenerDistribucionReciboOperarioUseCase] Iniciando búsqueda', [
            'recibo_id' => $idRecibo,
            'usuario' => $usuario->id,
        ]);

        $recibo = $this->readRepository->findReciboById((int) $idRecibo);
        if (!$recibo) {
            \Log::warning('[ObtenerDistribucionReciboOperarioUseCase] Recibo no encontrado', [
                'recibo_id' => $idRecibo,
            ]);

            return [
                'status' => 404,
                'payload' => [
                    'success' => false,
                    'message' => 'Recibo no encontrado',
                ],
            ];
        }

        $parciales = $this->readRepository->findParcialesConTallasParaRecibo(
            pedidoProduccionId: (int) $recibo->pedido_produccion_id,
            prendaId: (int) $recibo->prenda_id,
            tipoRecibo: (string) $recibo->tipo_recibo,
            consecutivoOriginal: $recibo->consecutivo_actual
        );

        $numeroPedido = $this->readRepository->findNumeroPedidoByPedidoProduccionId((int) $recibo->pedido_produccion_id);

        if ($parciales->isEmpty()) {
            return [
                'status' => 200,
                'payload' => [
                    'success' => true,
                    'parciales' => [],
                    'mensaje' => 'No hay parciales creados para este recibo',
                    'total_parciales' => 0,
                ],
            ];
        }

        $parcialesInfo = $parciales->map(function ($parcial) use ($numeroPedido) {
            $proceso = null;
            if ($numeroPedido) {
                $proceso = $this->readRepository->findProcesoParcial(
                    numeroPedido: (int) $numeroPedido,
                    prendaId: (int) $parcial->prenda_pedido_id,
                    consecutivoParcial: $parcial->consecutivo_parcial
                );
            }

            $encargado = ($proceso ? $proceso->encargado : null) ?? $parcial->encargado ?? 'SIN ASIGNAR';
            $area = ($proceso ? $proceso->proceso : null) ?? $parcial->area ?? 'SIN ASIGNAR';

            $estaCompletado = $this->readRepository->estaCompletadoParcialEnCostura((int) $parcial->id);

            return [
                'id' => $parcial->id,
                'area' => $area,
                'encargado' => $encargado,
                'tipo_recibo' => $parcial->tipo_recibo,
                'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                'consecutivo_original' => (float) $parcial->consecutivo_original,
                'proceso_estado' => $estaCompletado
                    ? 'COMPLETADO'
                    : (($proceso && $proceso->estado_proceso ? $proceso->estado_proceso : 'En Progreso') ?: 'En Progreso'),
                'fecha_asignacion' => ($proceso ? $proceso->fecha_de_asignacion_encargado : null) ?? null,
                'observaciones' => ($proceso ? $proceso->observaciones : null) ?? '',
                'pedido_produccion_id' => $parcial->pedido_produccion_id,
                'prenda_pedido_id' => $parcial->prenda_pedido_id,
                'numero_pedido' => $numeroPedido,
                'tallas' => ($parcial->tallas ?? collect())->map(function ($talla) {
                    return [
                        'id' => $talla->id,
                        'talla' => $talla->talla,
                        'cantidad' => $talla->cantidad,
                        'color_nombre' => $talla->color_nombre,
                        'genero' => $talla->genero,
                    ];
                })->toArray(),
            ];
        })->sortBy('area')->values();

        // Detectar el tipo de asignación original
        $tipoAsignacionOriginal = $this->detectarTipoAsignacion($parcialesInfo->toArray());

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'recibo' => [
                    'id' => $recibo->id,
                    'consecutivo' => $recibo->consecutivo_actual,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'area_actual' => $recibo->area,
                    'numero_pedido' => $numeroPedido,
                ],
                'parciales' => $parcialesInfo,
                'total_parciales' => $parcialesInfo->count(),
                'tipo_asignacion_original' => $tipoAsignacionOriginal,
            ],
        ];
    }

    /**
     * Detecta si la asignación original fue a múltiples talleres o a módulos
     * Retorna 'taller' si los encargados tienen rol 'taller', 'modulos' en caso contrario
     */
    private function detectarTipoAsignacion(array $parcialesInfo): string
    {
        if (empty($parcialesInfo)) {
            return 'modulos'; // Por defecto
        }

        // Obtener todos los encargados únicos
        $encargados = array_unique(array_map(function ($parcial) {
            return trim((string) ($parcial['encargado'] ?? ''));
        }, $parcialesInfo));

        $encargados = array_filter($encargados, fn($e) => $e !== '' && $e !== 'SIN ASIGNAR');

        if (empty($encargados)) {
            return 'modulos';
        }

        // Verificar si alguno de los encargados tiene rol 'taller'
        $usuariosConRolTaller = \App\Models\User::whereIn('name', $encargados)
            ->get()
            ->filter(function ($user) {
                return $user->hasRole('taller');
            })
            ->count();

        // Si al menos uno tiene rol 'taller', fue asignado a múltiples talleres
        if ($usuariosConRolTaller > 0) {
            \Log::info('[ObtenerDistribucionReciboOperarioUseCase] Tipo de asignación detectado: taller', [
                'encargados_con_rol_taller' => $usuariosConRolTaller,
                'total_encargados' => count($encargados),
            ]);
            return 'taller';
        }

        \Log::info('[ObtenerDistribucionReciboOperarioUseCase] Tipo de asignación detectado: modulos', [
            'total_encargados' => count($encargados),
        ]);
        return 'modulos';
    }
}

