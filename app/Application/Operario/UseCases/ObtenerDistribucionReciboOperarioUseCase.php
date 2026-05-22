<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ReciboDistribucionReadRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ReciboPorPartes;

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

        $pedidoProduccionId = (int) ($recibo->pedido_produccion_id ?? 0);
        if ($pedidoProduccionId <= 0 && !empty($recibo->prenda_bodega_id)) {
            $pedidoProduccionId = (int) (\Illuminate\Support\Facades\DB::table('recibo_por_partes')
                ->where('consecutivo_original', $recibo->consecutivo_actual)
                ->where('prenda_pedido_id', (int) $recibo->prenda_bodega_id)
                ->where('tipo_recibo', (string) $recibo->tipo_recibo)
                ->orderByDesc('id')
                ->value('pedido_produccion_id') ?? 0);
        }

        $generosPorTallaBodega = collect();
        if (!empty($recibo->prenda_bodega_id)) {
            $generosPorTallaBodega = \Illuminate\Support\Facades\DB::table('prenda_tallas_bodega')
                ->where('prenda_bodega_id', (int) $recibo->prenda_bodega_id)
                ->pluck('genero', 'talla')
                ->mapWithKeys(fn ($genero, $talla) => [strtoupper(trim((string) $talla)) => strtoupper(trim((string) $genero))]);
        }

        $parciales = $this->readRepository->findParcialesConTallasParaRecibo(
            pedidoProduccionId: $pedidoProduccionId,
            prendaId: (int) $recibo->prenda_id,
            tipoRecibo: (string) $recibo->tipo_recibo,
            consecutivoOriginal: $recibo->consecutivo_actual,
            prendaBodegaId: (int) ($recibo->prenda_bodega_id ?? 0)
        );

        $numeroPedido = $pedidoProduccionId > 0
            ? $this->readRepository->findNumeroPedidoByPedidoProduccionId($pedidoProduccionId)
            : null;

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

        $prendaBodegaId = (int) ($recibo->prenda_bodega_id ?? 0);

        $parcialesInfo = $parciales->map(function ($parcial) use ($numeroPedido, $generosPorTallaBodega, $prendaBodegaId, $recibo) {
            $proceso = null;
            if ($prendaBodegaId > 0) {
                $proceso = \App\Models\ProcesoPrenda::query()
                    ->where('prenda_bodega_id', $prendaBodegaId)
                    ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                    ->whereNull('deleted_at')
                    ->latest('created_at')
                    ->first();
            }

            if (!$proceso && $numeroPedido) {
                $proceso = $this->readRepository->findProcesoParcial(
                    numeroPedido: (int) $numeroPedido,
                    prendaId: (int) $parcial->prenda_pedido_id,
                    consecutivoParcial: $parcial->consecutivo_parcial
                );
            }

            if ((!$proceso || empty(trim((string) ($proceso->encargado ?? '')))) && $prendaBodegaId > 0) {
                $proceso = \App\Models\ProcesoPrenda::query()
                    ->where('prenda_bodega_id', $prendaBodegaId)
                    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                    ->whereNotNull('encargado')
                    ->whereRaw('TRIM(encargado) != ?', [''])
                    ->whereNull('deleted_at')
                    ->latest('created_at')
                    ->first();
            }

            $encargado = ($proceso ? $proceso->encargado : null) ?? $parcial->encargado ?? 'SIN ASIGNAR';

            $estaEnControlCalidad = DB::table('prenda_recibo_completado')
                ->where('id_parcial', (int) $parcial->id)
                ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->exists();

            $procesoNombre = strtolower(trim((string) ($proceso->proceso ?? '')));
            $procesoEsControlCalidad = in_array($procesoNombre, ['control calidad', 'control de calidad'], true);

            $area = $estaEnControlCalidad || $procesoEsControlCalidad
                ? 'Control Calidad'
                : (($proceso ? $proceso->proceso : null) ?? $parcial->area ?? 'SIN ASIGNAR');

            $estaCompletado = $this->readRepository->estaCompletadoParcialEnCostura((int) $parcial->id);
            $estadoParcial = strtoupper(trim((string) ($parcial->estado ?? '')));
            $estaAnulado = $estadoParcial === 'ANULADO';

            return [
                'id' => $parcial->id,
                'area' => $area,
                'encargado' => $encargado,
                'recibo_id' => (int) $recibo->id,
                'tipo_recibo' => $parcial->tipo_recibo,
                'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                'consecutivo_original' => (float) $parcial->consecutivo_original,
                'proceso_estado' => $estaAnulado
                    ? 'Anulado'
                    : ($estaCompletado
                    ? 'COMPLETADO'
                    : (($proceso && $proceso->estado_proceso ? $proceso->estado_proceso : 'En Progreso') ?: 'En Progreso')),
                'estado_parcial' => $parcial->estado,
                'fecha_asignacion' => ($proceso ? $proceso->fecha_de_asignacion_encargado : null) ?? null,
                'observaciones' => ($proceso ? $proceso->observaciones : null) ?? '',
                'pedido_produccion_id' => $parcial->pedido_produccion_id,
                'prenda_pedido_id' => $parcial->prenda_pedido_id,
                'numero_pedido' => $numeroPedido,
                'tallas' => ($parcial->tallas ?? collect())->map(function ($talla) use ($generosPorTallaBodega) {
                    $genero = $talla->genero ?? null;
                    if (($genero === null || $genero === '') && !empty($talla->talla)) {
                        $genero = $generosPorTallaBodega[strtoupper(trim((string) $talla->talla))] ?? null;
                    }

                    return [
                        'id' => $talla->id,
                        'talla' => $talla->talla,
                        'cantidad' => $talla->cantidad,
                        'color_nombre' => $talla->color_nombre,
                        'genero' => $genero,
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
