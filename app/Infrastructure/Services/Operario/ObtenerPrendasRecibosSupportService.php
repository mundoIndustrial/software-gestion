<?php

namespace App\Infrastructure\Services\Operario;

use App\Infrastructure\Repositories\Operario\OperarioRecibosRepository;
use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use App\Models\User;
use Illuminate\Support\Collection;

class ObtenerPrendasRecibosSupportService
{
    public function __construct(
        private readonly OperarioRecibosRepository $operarioRecibosRepository
    ) {}

    public function logTipoOperario(User $usuario, string $tipoOperario): void
    {
        if (config('app.debug') && request()->boolean('debug_operario_dashboard')) {
            \Log::info(' [obtenerPrendasConRecibos] TIPO OPERARIO DETECTADO', [
                'usuario' => $usuario->name,
                'usuario_id' => $usuario->id,
                'tipo_operario' => $tipoOperario,
                'es_costura_reflectivo' => $tipoOperario === 'costura-reflectivo' ? 'SI' : 'NO',
                'es_vista_costura' => $tipoOperario === 'vista-costura' ? 'SI' : 'NO',
            ]);
        }
    }

    public function resolverTiposRecibo(string $tipoOperario, ?string $filtroRecibo): array
    {
        $tiposRecibo = ['COSTURA', 'COSTURA-BODEGA'];

        if (in_array($tipoOperario, ['vista-costura', 'costura-reflectivo'], true)) {
            $tiposRecibo = ['COSTURA', 'REFLECTIVO'];
        }

        if ($filtroRecibo === 'bodega') {
            return ['CORTE-PARA-BODEGA'];
        }

        if ($filtroRecibo === 'reflectivo') {
            return array_values(array_intersect($tiposRecibo, ['REFLECTIVO']));
        }

        if ($filtroRecibo === 'costura') {
            return array_values(array_intersect($tiposRecibo, ['COSTURA', 'COSTURA-BODEGA']));
        }

        return $tiposRecibo;
    }

    public function cargarRecibosBaseParaOperario(User $usuario, string $tipoOperario, array $tiposRecibo): Collection
    {
        $esBodegaVistaCostura = in_array('CORTE-PARA-BODEGA', $tiposRecibo, true);
        $areasPermitidas = $esBodegaVistaCostura
            ? ['Costura', 'Control de Calidad', 'Control Calidad']
            : ['Corte', 'Costura', 'Control de Calidad', 'Control Calidad'];

        $query = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo)
            ->whereIn('area', $areasPermitidas)
            ->select([
                'id',
                'prenda_id',
                'pedido_produccion_id',
                'tipo_recibo',
                'consecutivo_actual',
                'consecutivo_inicial',
                'notas',
                'area',
                'estado',
                'created_at',
                'activo',
            ])
            ->with([
                'prenda:id,pedido_produccion_id,nombre_prenda,descripcion,de_bodega,created_at',
                'prenda.pedidoProduccion:id,numero_pedido,cliente,created_at',
                'prenda.procesosPrenda',
                'prenda.tallas:id,prenda_pedido_id,genero,talla,cantidad,tipo_talla,es_sobremedida,tela,colores',
                'pedido:id,numero_pedido,cliente,created_at',
            ]);

        if ($tipoOperario === 'cortador') {
            $usuarioNombre = strtolower(trim($usuario->name));
            $prendasDelCortador = \App\Models\ProcesoPrenda::whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNombre])
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
                ->pluck('prenda_pedido_id')
                ->unique()
                ->values()
                ->all();

            if (empty($prendasDelCortador)) {
                return collect();
            }

            $query->whereIn('prenda_id', $prendasDelCortador);
            $query->where('estado', '!=', 'PENDIENTE_INSUMOS');
            $query->whereIn('area', ['Corte']);
        }

        if (in_array($tipoOperario, ['costurero', 'confeccion-sobremedida', 'costura-reflectivo'], true)) {
            $query->whereIn('area', ['Costura']);
        }

        $recibos = $query->orderBy('created_at', 'asc')->get();

        if ($tipoOperario === 'cortador' && config('app.debug') && request()->boolean('debug_operario_dashboard')) {
            \Log::info(' [Filtro CORTADOR SQL] Recibos filtrados por prendas asignadas en Corte', [
                'usuario' => $usuario->name,
                'total' => $recibos->count(),
            ]);
        }

        return $recibos;
    }

    public function filtrarRecibosVisualizadorPlooter(Collection $recibos, User $usuario, string $tipoOperario): Collection
    {
        if ($tipoOperario !== 'visualizador_plooter') {
            return $recibos;
        }

        $usuarioNombre = strtolower(trim($usuario->name));
        $prendasDelUsuario = \App\Models\ProcesoPrenda::whereRaw('LOWER(TRIM(encargado)) = ?', [$usuarioNombre])
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['corte'])
            ->pluck('prenda_pedido_id')
            ->unique()
            ->values();

        return $recibos->filter(function ($recibo) use ($prendasDelUsuario) {
            $area = strtolower(trim((string) ($recibo->area ?? '')));
            if ($area !== 'corte') {
                return false;
            }
            if (empty($recibo->prenda_id)) {
                return false;
            }
            return $prendasDelUsuario->contains($recibo->prenda_id);
        })->values();
    }

    public function ordenarResultadoFinalPorTipoOperario(Collection $resultadoFinal, string $tipoOperario): Collection
    {
        if ($tipoOperario === 'vista-costura') {
            return $resultadoFinal->sortBy(fn ($item) => $item['fecha_creacion'] ?? null)->values();
        }

        if ($tipoOperario === 'cortador') {
            return $resultadoFinal
                ->map(function ($item) {
                    $recibosCorte = collect($item['recibos'] ?? [])
                        ->filter(fn ($recibo) => strtolower(trim((string) ($recibo['area'] ?? ''))) === 'corte')
                        ->values()
                        ->all();

                    $item['recibos'] = $recibosCorte;
                    $item['total_recibos'] = count($recibosCorte);
                    if (!empty($recibosCorte)) {
                        $item['fecha_creacion'] = $recibosCorte[0]['created_at']
                            ?? ($item['fecha_creacion'] ?? null);
                    }

                    return $item;
                })
                ->filter(fn ($item) => !empty($item['recibos']))
                ->sortBy(fn ($item) => $item['fecha_creacion'] ?? null)
                ->values();
        }

        return $resultadoFinal->sortBy(fn ($item) => $item['fecha_creacion'] ?? null)->values();
    }

    public function resolverContextoProcesosRecibo(mixed $recibo): array
    {
        $procesos = $recibo->prenda && $recibo->prenda->relationLoaded('procesosPrenda')
            ? $recibo->prenda->procesosPrenda
            : collect();

        $numeroRecibo = $recibo->consecutivo_actual;
        $procesoCostura = $this->buscarProcesoCosturaOriginal($procesos, $numeroRecibo);
        $procesoCorte = $procesos
            ->filter(fn ($p) => is_string($p->proceso ?? null) && strtolower(trim((string) $p->proceso)) === 'corte')
            ->sortByDesc(fn ($p) => $p->created_at)
            ->first();
        $procesoControlCalidad = $procesos
            ->filter(function ($p) {
                $proc = strtolower(trim((string) ($p->proceso ?? '')));
                return in_array($proc, ['control de calidad', 'control calidad'], true);
            })
            ->sortByDesc(fn ($p) => $p->created_at)
            ->first();

        $parcialId = $this->detectarParcialId($recibo);
        $esParcial = $parcialId !== null;

        return [
            'procesos' => $procesos,
            'numero_recibo' => $numeroRecibo,
            'proceso_costura' => $procesoCostura,
            'proceso_corte' => $procesoCorte,
            'proceso_control_calidad' => $procesoControlCalidad,
            'parcial_id' => $parcialId,
            'es_parcial' => $esParcial,
        ];
    }

    public function detectarParcialId(mixed $recibo): ?int
    {
        $notas = (string) ($recibo->notas ?? '');
        if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function resolverCreadoEnRecibo(mixed $recibo, ?int $parcialId = null): mixed
    {
        $parcialId ??= $this->detectarParcialId($recibo);
        if ($parcialId === null) {
            return $recibo->created_at ?? null;
        }

        try {
            $parcialCreatedAt = $this->operarioRecibosRepository->obtenerPedidoParcialCreatedAt($parcialId);
            if (!empty($parcialCreatedAt)) {
                return $parcialCreatedAt;
            }
        } catch (\Exception $e) {
            // Keep created_at if lookup fails.
        }

        return $recibo->created_at ?? null;
    }

    public function buscarProcesoCosturaOriginal(Collection $procesos, $numeroRecibo): ?ProcesoPrenda
    {
        return $procesos
            ->filter(function ($proceso) use ($numeroRecibo) {
                if (!is_string($proceso->proceso ?? null) || strtolower(trim((string) $proceso->proceso)) !== 'costura') {
                    return false;
                }

                if ((string) ($proceso->numero_recibo ?? '') !== (string) $numeroRecibo) {
                    return false;
                }

                $numeroReciboParcial = $proceso->numero_recibo_parcial ?? null;

                return $numeroReciboParcial === null || trim((string) $numeroReciboParcial) === '' || (float) $numeroReciboParcial === 0.0;
            })
            ->sortByDesc(fn ($proceso) => $proceso->created_at)
            ->first();
    }

    public function obtenerFechaCreacionProcesoCostura($recibo): mixed
    {
        if (!$recibo) {
            return null;
        }

        $contexto = $this->resolverContextoProcesosRecibo($recibo);
        $procesoCostura = $contexto['proceso_costura'];
        if ($procesoCostura) {
            return $procesoCostura->created_at;
        }

        $proc = $this->operarioRecibosRepository->buscarUltimoProcesoPorPedidoProduccionYPrenda(
            (int) $recibo->pedido_produccion_id,
            (int) $recibo->prenda_id,
            'COSTURA',
            (string) $contexto['numero_recibo'],
            (bool) $contexto['es_parcial']
        );

        return $proc ? $proc->created_at : null;
    }

    public function obtenerFechaLlegadaACorte($recibo): mixed
    {
        $contexto = $this->resolverContextoProcesosRecibo($recibo);
        $procesoCorte = $contexto['proceso_corte'];
        if ($procesoCorte) {
            return $procesoCorte->fecha_de_asignacion_encargado ?? $procesoCorte->created_at;
        }

        $proc = $this->operarioRecibosRepository->buscarUltimoProcesoPorPedidoProduccionYPrenda(
            (int) $recibo->pedido_produccion_id,
            (int) $recibo->prenda_id,
            'CORTE',
            (string) $contexto['numero_recibo'],
            (bool) $contexto['es_parcial']
        );

        return $proc ? ($proc->fecha_de_asignacion_encargado ?? $proc->created_at) : null;
    }

    public function normalizarFechaAOrdenable($fecha): int
    {
        if ($fecha instanceof \DateTimeInterface) {
            return $fecha->getTimestamp();
        }

        if (is_numeric($fecha)) {
            return (int) $fecha;
        }

        if (is_string($fecha) && trim($fecha) !== '') {
            $timestamp = strtotime($fecha);
            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        return 0;
    }

    public function obtenerTipoOperario(User $usuario): string
    {
        if ($usuario->hasRole('cortador')) {
            return 'cortador';
        }

        if ($usuario->hasRole('vista-costura')) {
            return 'vista-costura';
        }

        if ($usuario->hasRole('costurero')) {
            return 'costurero';
        }

        if ($usuario->hasRole('confeccion-sobremedida')) {
            return 'confeccion-sobremedida';
        }

        if ($usuario->hasRole('bodeguero')) {
            return 'bodeguero';
        }

        if ($usuario->hasRole('costura-reflectivo')) {
            return 'costura-reflectivo';
        }

        if ($usuario->hasRole('lider-reflectivo')) {
            return 'costura-reflectivo';
        }

        if ($usuario->hasRole('visualizador_plooter')) {
            return 'visualizador_plooter';
        }

        return 'desconocido';
    }
}
