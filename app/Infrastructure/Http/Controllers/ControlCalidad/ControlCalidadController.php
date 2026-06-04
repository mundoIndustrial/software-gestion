<?php

namespace App\Infrastructure\Http\Controllers\ControlCalidad;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use App\Models\PrendaReciboCompletado;
use App\Models\PrendaPedidoTalla;
use App\Models\ReciboPorPartes;
use App\Models\PedidoProduccion;
use App\Application\Operario\UseCases\GetPedidoDataOperarioUseCase;
use App\Application\Operario\UseCases\ObtenerDistribucionControlCalidadUseCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class ControlCalidadController extends Controller
{
    public function __construct(
        private readonly ObtenerDistribucionControlCalidadUseCase $obtenerDistribucionCCUseCase,
    ) {
        $this->middleware('auth');
        $this->middleware('control-calidad-access');
    }

    private function esControlDeCalidadProceso(?string $proceso): bool
    {
        $norm = strtolower(trim((string) $proceso));
        $norm = str_replace(['-', '_'], ' ', $norm);
        $norm = preg_replace('/\s+/', ' ', $norm);

        return in_array($norm, ['control de calidad', 'control calidad'], true);
    }

    private function construirClaveControlCalidad(string $talla, string $genero, string $colorNombre): string
    {
        return implode('|', [
            strtoupper(trim($talla)),
            strtoupper(trim($genero)),
            strtoupper(trim($colorNombre)),
        ]);
    }

    private function normalizarTallasControlCalidad(array $tallas): array
    {
        $normalizadas = [];

        foreach ($tallas as $talla) {
            if (!is_array($talla)) {
                continue;
            }

            $clave = $this->construirClaveControlCalidad(
                (string) ($talla['talla'] ?? ''),
                (string) ($talla['genero'] ?? ''),
                (string) ($talla['color_nombre'] ?? '')
            );

            if ($clave === '||') {
                continue;
            }

            $normalizadas[$clave] = ($normalizadas[$clave] ?? 0) + (int) ($talla['cantidad'] ?? 0);
        }

        return $normalizadas;
    }

    private function resolverEstadoControlCalidadDesdeTallas(array $tallasOriginales, array $tallasEnviadas): string
    {
        if (empty($tallasEnviadas)) {
            return 'pendiente';
        }

        $originales = $this->normalizarTallasControlCalidad($tallasOriginales);
        $enviadas = $this->normalizarTallasControlCalidad($tallasEnviadas);

        if (empty($originales)) {
            return 'pendiente';
        }

        foreach ($originales as $clave => $cantidadOriginal) {
            if ((int) ($enviadas[$clave] ?? 0) < (int) $cantidadOriginal) {
                return 'parcial';
            }
        }

        return 'completo';
    }

    private function mapearTallasCompletadasControlCalidad(iterable $filas): array
    {
        return collect($filas)
            ->map(function ($fila) {
                return [
                    'talla' => (string) ($fila->talla ?? ''),
                    'genero' => (string) ($fila->genero ?? ''),
                    'color_nombre' => (string) ($fila->color_nombre ?? ''),
                    'cantidad' => (int) ($fila->cantidad ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function obtenerTallasOriginalesReciboControlCalidad(mixed $prenda, bool $esBodega = false): array
    {
        if (!$prenda) {
            return [];
        }

        if ($esBodega) {
            return collect($prenda->tallas ?? [])
                ->map(function ($talla) {
                    return [
                        'talla' => (string) ($talla->talla ?? ''),
                        'genero' => (string) ($talla->genero ?? ''),
                        'color_nombre' => (string) ($talla->color ?? ''),
                        'cantidad' => (int) ($talla->cantidad ?? 0),
                    ];
                })
                ->values()
                ->all();
        }

        return collect($prenda->tallas ?? [])
            ->flatMap(function ($talla) {
                $base = [
                    'talla' => (string) ($talla->talla ?? ''),
                    'genero' => (string) ($talla->genero ?? ''),
                    'cantidad' => (int) ($talla->obtenerCantidadTotal()),
                ];

                $coloresAsignados = collect($talla->coloresAsignados ?? []);
                if ($coloresAsignados->isEmpty()) {
                    return [[
                        'talla' => $base['talla'],
                        'genero' => $base['genero'],
                        'color_nombre' => '',
                        'cantidad' => $base['cantidad'],
                    ]];
                }

                return $coloresAsignados->map(function ($color) use ($base) {
                    return [
                        'talla' => $base['talla'],
                        'genero' => $base['genero'],
                        'color_nombre' => (string) ($color->color_nombre ?? ''),
                        'cantidad' => (int) ($color->cantidad ?? 0),
                    ];
                })->all();
            })
            ->values()
            ->all();
    }

    private function obtenerTallasOriginalesParcialControlCalidad(?ReciboPorPartes $parcial): array
    {
        if (!$parcial) {
            return [];
        }

        return collect($parcial->tallas ?? [])
            ->map(function ($talla) {
                return [
                    'talla' => (string) ($talla->talla ?? ''),
                    'genero' => (string) ($talla->genero ?? ''),
                    'color_nombre' => (string) ($talla->color_nombre ?? ''),
                    'cantidad' => (int) ($talla->cantidad ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function debeOcultarseDelDashboardControlCalidad(array $tallasOriginales, array $tallasCompletadas): bool
    {
        return $this->resolverEstadoControlCalidadDesdeTallas($tallasOriginales, $tallasCompletadas) === 'completo';
    }

    private function aplicarCondicionVisibleEnControlCalidad($query): void
    {
        $query->where(function ($subQuery) {
            $subQuery
                ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->orWhereExists(function ($completadoQuery) {
                    $completadoQuery
                        ->select(DB::raw(1))
                        ->from('prenda_recibo_completado as prc')
                        ->whereColumn('prc.id_recibo', 'consecutivos_recibos_pedidos.id')
                        ->whereRaw('LOWER(TRIM(COALESCE(prc.area, ""))) IN (?, ?)', ['control calidad', 'control de calidad']);
                });
        });
    }

    public function dashboard(Request $request)
    {
        $usuario = auth()->user();
        $esLiderControlCalidad = $usuario && $usuario->hasRole('lider-control-calidad');

        $activeTab = strtoupper($request->query('tab', 'COSTURA'));
        if (!in_array($activeTab, ['COSTURA', 'REFLECTIVO', 'BODEGA'])) {
            $activeTab = 'COSTURA';
        }

        // Filtrar recibos que estén en el área de Control de Calidad
        $recibosQuery = ConsecutivoReciboPedido::where('activo', 1);

        if ($activeTab === 'REFLECTIVO') {
            $recibosQuery->where('tipo_recibo', 'REFLECTIVO');
        } elseif ($activeTab === 'BODEGA') {
            $recibosQuery->whereIn('tipo_recibo', ['BODEGA', 'CORTE-PARA-BODEGA']);
        } else {
            $recibosQuery->where('tipo_recibo', 'COSTURA');
        }

        // Listar recibos ya movidos a C.C o con avance registrado en C.C.
        $this->aplicarCondicionVisibleEnControlCalidad($recibosQuery);

        $recibos = $recibosQuery
            ->with(['pedido', 'prenda.tallas.coloresAsignados', 'prendaBodega.tallas', 'pedido.prendas'])
            ->orderBy('created_at', 'desc')
            ->get();

        $numeroPedidos = $recibos
            ->map(fn ($r) => $r->pedido?->numero_pedido)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $ultimoProcesoPorPedido = [];
        if (!empty($numeroPedidos)) {
            $procesosActuales = DB::table('procesos_prenda')
                ->whereIn('numero_pedido', $numeroPedidos)
                ->orderBy('numero_pedido', 'asc')
                ->orderBy('fecha_inicio', 'DESC')
                ->orderBy('id', 'DESC')
                ->select('numero_pedido', 'proceso', 'fecha_inicio', 'id')
                ->get();

            foreach ($procesosActuales as $p) {
                if (!isset($ultimoProcesoPorPedido[$p->numero_pedido])) {
                    $ultimoProcesoPorPedido[$p->numero_pedido] = $p->proceso;
                }
            }
        }

        // Formatear para reutilizar el mismo layout de tarjetas
        $prendasConRecibos = $recibos->map(function ($recibo) use ($ultimoProcesoPorPedido) {
            $pedido = $recibo->pedido;
            
            $tipoReciboLower = strtolower((string)$recibo->tipo_recibo);
            $esBodega = str_contains($tipoReciboLower, 'bodega');
            
            if ($esBodega && $recibo->prendaBodega) {
                $prendaBodega = $recibo->prendaBodega;
                $prenda = (object) [
                    'id' => $prendaBodega->id,
                    'nombre_prenda' => $prendaBodega->nombre,
                    'descripcion' => $prendaBodega->descripcion,
                    'de_bodega' => true,
                    'tallas' => $prendaBodega->tallas ?? collect(),
                ];
            } else {
                $prenda = $recibo->prenda;
            }

            if (!$prenda) {
                $prenda = $pedido?->prendas?->first();
            }
            if (is_object($prenda) && method_exists($prenda, 'loadMissing')) {
                $prenda->loadMissing(['tallas.coloresAsignados']);
            }
            $numeroPedido = $pedido?->numero_pedido;
            $procesoActual = $numeroPedido ? ($ultimoProcesoPorPedido[$numeroPedido] ?? null) : null;
            $procesoControlCalidad = null;
            $tallasOriginales = $this->obtenerTallasOriginalesReciboControlCalidad($prenda, $esBodega);

            if ($numeroPedido && !empty($prenda?->id) && !empty($recibo->consecutivo_actual)) {
                $procesoControlCalidad = ProcesoPrenda::query()
                    ->where('numero_pedido', (int) $numeroPedido)
                    ->where('prenda_pedido_id', (int) $prenda->id)
                    ->where('numero_recibo', (int) $recibo->consecutivo_actual)
                    ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                    ->latest('created_at')
                    ->first();
            }

            // Detectar si este recibo tiene parciales
            $tieneParciales = ReciboPorPartes::query()
                ->where('pedido_produccion_id', $pedido->id ?? 0)
                ->where('prenda_pedido_id', $prenda->id ?? 0)
                ->where('consecutivo_original', $recibo->consecutivo_actual)
                ->where('tipo_recibo', $recibo->tipo_recibo)
                ->exists();

            // Fuente de verdad para parciales: tabla recibo_por_partes.
            // No inferir parcial desde texto en "notas" para evitar IDs cruzados.
            $parcialId = null;
            $esParcial = false;

            return [
                'prenda_id' => $prenda->id ?? 0,
                'pedido_id' => $pedido->id ?? 0,
                'numero_pedido' => $pedido->numero_pedido ?? '',
                'cliente' => $pedido->cliente ?? '',
                'nombre_prenda' => $prenda->nombre_prenda ?? 'Pedido',
                'descripcion' => $prenda->descripcion ?? ($pedido->descripcion ?? ''),
                'proceso_actual' => $procesoActual,
                'de_bodega' => $prenda->de_bodega ?? null,
                'tiene_parciales' => $tieneParciales,
                'recibos' => [[
                    'id' => $recibo->id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'consecutivo_inicial' => $recibo->consecutivo_inicial,
                    'notas' => $recibo->notas,
                    'creado_en' => $recibo->created_at,
                    'area' => $recibo->area,
                    'es_parcial' => $esParcial,
                    'parcial_id' => $parcialId,
                ]],
                'total_recibos' => 1,
                'fecha_creacion' => $procesoControlCalidad?->created_at ?? $recibo->created_at,
                'estado_pedido' => $pedido->estado ?? 'Pendiente',
            ];
        })->filter()->values();

        $idsRecibos = $prendasConRecibos
            ->flatMap(fn($p) => collect($p['recibos'] ?? [])->pluck('id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $detalleTallasCompletadasPorRecibo = !empty($idsRecibos)
            ? DB::table('prenda_recibo_completado_tallas as prct')
                ->join('prenda_recibo_completado as prc', 'prc.id', '=', 'prct.prenda_recibo_completado_id')
                ->where('prc.area', 'Control de Calidad')
                ->whereIn('prc.id_recibo', $idsRecibos)
                ->select([
                    'prc.id_recibo',
                    'prct.talla',
                    'prct.genero',
                    'prct.color_nombre',
                    'prct.cantidad',
                ])
                ->get()
                ->groupBy('id_recibo')
                ->map(fn ($filas) => $this->mapearTallasCompletadasControlCalidad($filas))
            : collect();

        $parcialesQuery = ReciboPorPartes::query()
            ->with(['pedido', 'prenda', 'tallas']);

        if ($activeTab === 'REFLECTIVO') {
            $parcialesQuery->where('tipo_recibo', 'REFLECTIVO');
        } elseif ($activeTab === 'BODEGA') {
            $parcialesQuery->whereIn('tipo_recibo', ['BODEGA', 'CORTE-PARA-BODEGA']);
        } else {
            $parcialesQuery->where('tipo_recibo', 'COSTURA');
        }

        $parcialesEnControlCalidad = $parcialesQuery
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (ReciboPorPartes $parcial) {
                $numeroPedido = (int) ($parcial->pedido?->numero_pedido ?? 0);
                if ($numeroPedido <= 0) {
                    return false;
                }

                return ProcesoPrenda::query()
                    ->where('numero_pedido', $numeroPedido)
                    ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                    ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                    ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                    ->latest('created_at')
                    ->exists();
            })
            ->values();

        $idsParciales = $parcialesEnControlCalidad
            ->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $detalleTallasCompletadasPorParcial = !empty($idsParciales)
            ? DB::table('prenda_recibo_completado_tallas as prct')
                ->join('prenda_recibo_completado as prc', 'prc.id', '=', 'prct.prenda_recibo_completado_id')
                ->where('prc.area', 'Control de Calidad')
                ->whereIn('prc.id_parcial', $idsParciales)
                ->select([
                    'prc.id_parcial',
                    'prct.talla',
                    'prct.genero',
                    'prct.color_nombre',
                    'prct.cantidad',
                ])
                ->get()
                ->groupBy('id_parcial')
                ->map(fn ($filas) => $this->mapearTallasCompletadasControlCalidad($filas))
            : collect();

        $completadosPorId = !empty($idsRecibos)
            ? PrendaReciboCompletado::query()
                ->where('area', 'Control de Calidad')
                ->whereIn('id_recibo', $idsRecibos)
                ->pluck('fecha_completado', 'id_recibo')
            : collect();

        $completadosPorParcialId = !empty($idsParciales)
            ? PrendaReciboCompletado::query()
                ->where('area', 'Control de Calidad')
                ->whereIn('id_parcial', $idsParciales)
                ->pluck('fecha_completado', 'id_parcial')
            : collect();

        if (!empty($idsRecibos)) {
            $prendasConRecibos = $prendasConRecibos->map(function ($prenda) use ($completadosPorId) {
                $prenda['recibos'] = array_map(function ($recibo) use ($completadosPorId) {
                    $idRecibo = $recibo['id'] ?? null;
                    $recibo['completado_area'] = $idRecibo ? $completadosPorId->has($idRecibo) : false;
                    return $recibo;
                }, $prenda['recibos'] ?? []);

                return $prenda;
            });
        }

        $prendasConRecibos = $prendasConRecibos
            ->filter(function ($prenda) use ($completadosPorId) {
                $idRecibo = (int) ($prenda['recibos'][0]['id'] ?? 0);
                return $idRecibo <= 0 || !$completadosPorId->has($idRecibo);
            })
            ->values();

        $parcialesConRecibos = $parcialesEnControlCalidad->map(function (ReciboPorPartes $parcial) use ($completadosPorParcialId, $detalleTallasCompletadasPorParcial) {
            $pedido = $parcial->pedido;
            $numeroPedido = $pedido?->numero_pedido;
            
            $tipoReciboLower = strtolower((string)$parcial->tipo_recibo);
            $esBodega = str_contains($tipoReciboLower, 'bodega');

            if ($esBodega) {
                // Si es bodega, prenda_pedido_id apunta a PrendaBodega
                $prendaBodega = \App\Models\PrendaBodega::find((int)$parcial->prenda_pedido_id);
                $prenda = null;
                if ($prendaBodega) {
                    $prenda = (object) [
                        'id' => $prendaBodega->id,
                        'nombre_prenda' => $prendaBodega->nombre,
                        'descripcion' => $prendaBodega->descripcion,
                        'de_bodega' => true,
                    ];
                }
            } else {
                $prenda = $parcial->prenda;
            }

            if (!$prenda) {
                $prenda = $pedido?->prendas?->first();
            }

            $consecutivoOriginal = (string) ($parcial->getRawOriginal('consecutivo_original') ?? $parcial->consecutivo_original ?? '');
            $consecutivoParcial = (string) ($parcial->getRawOriginal('consecutivo_parcial') ?? $parcial->consecutivo_parcial ?? '');
            $reciboOriginal = null;
            if ($consecutivoOriginal !== '') {
                $consecutivoOriginalTrim = trim($consecutivoOriginal);
                $consecutivoOriginalNum = is_numeric($consecutivoOriginalTrim) ? (float) $consecutivoOriginalTrim : null;
                $consecutivoOriginalInt = $consecutivoOriginalNum !== null ? (string) ((int) $consecutivoOriginalNum) : null;

                $queryOriginal = ConsecutivoReciboPedido::query()
                    ->where('pedido_produccion_id', (int) ($pedido->id ?? 0))
                    ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim((string) $parcial->tipo_recibo))])
                    ->where(function ($q) use ($consecutivoOriginalTrim, $consecutivoOriginalInt) {
                        $q->where('consecutivo_actual', $consecutivoOriginalTrim);
                        if ($consecutivoOriginalInt !== null) {
                            $q->orWhere('consecutivo_actual', $consecutivoOriginalInt);
                        }
                    });

                if ($esBodega) {
                    $queryOriginal->where('prenda_bodega_id', (int) ($prenda->id ?? 0));
                } else {
                    $queryOriginal->where('prenda_id', (int) ($prenda->id ?? 0));
                }

                $reciboOriginal = $queryOriginal->latest('id')->first();
            }

            $proceso = null;
            if ($numeroPedido) {
                $proceso = ProcesoPrenda::query()
                    ->where('numero_pedido', $numeroPedido)
                    ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                    ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                    ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                    ->latest('created_at')
                    ->first();
            }

            return [
                'prenda_id' => $prenda->id ?? 0,
                'pedido_id' => $pedido->id ?? 0,
                'numero_pedido' => $pedido->numero_pedido ?? '',
                'cliente' => $pedido->cliente ?? '',
                'nombre_prenda' => $prenda->nombre_prenda ?? 'Pedido',
                'descripcion' => $prenda->descripcion ?? ($pedido->descripcion ?? ''),
                'proceso_actual' => $proceso->proceso ?? 'Control Calidad',
                'de_bodega' => $prenda->de_bodega ?? null,
                'recibos' => [[
                    'id' => $parcial->id,
                    'tipo_recibo' => $parcial->tipo_recibo,
                    'consecutivo_actual' => $consecutivoParcial,
                    'consecutivo_inicial' => $consecutivoOriginal,
                    'notas' => 'parcial_id:' . $parcial->id,
                    'creado_en' => $parcial->created_at,
                    'area' => $proceso->proceso ?? 'Control Calidad',
                    'es_parcial' => true,
                    'parcial_id' => $parcial->id,
                    'consecutivo_parcial' => $consecutivoParcial,
                    'completado_area' => $completadosPorParcialId->has($parcial->id),
                    'recibo_id_origen' => $reciboOriginal?->id,
                ]],
                'total_recibos' => 1,
                'fecha_creacion' => $proceso?->created_at ?? $parcial->created_at,
                'estado_pedido' => $pedido->estado ?? 'Pendiente',
                'es_parcial' => true,
                'parcial_id' => $parcial->id,
                'tipo_recibo' => $parcial->tipo_recibo,
                'consecutivo_actual' => $consecutivoParcial,
                'completado_area' => $completadosPorParcialId->has($parcial->id),
            ];
        })->filter(function ($parcial) use ($completadosPorParcialId) {
            $parcialId = (int) ($parcial['parcial_id'] ?? 0);
            return $parcialId <= 0 || !$completadosPorParcialId->has($parcialId);
        })->values();

        // Regla UI: si existe al menos un parcial visible en C.C. para un recibo original,
        // ocultar la tarjeta del recibo original y mostrar solo las tarjetas parciales.
        $llavesOriginalConParcialVisible = $parcialesConRecibos
            ->map(function ($parcial) {
                $pedidoId = (int) ($parcial['pedido_id'] ?? 0);
                $prendaId = (int) ($parcial['prenda_id'] ?? 0);
                $consecutivoOriginal = trim((string) ($parcial['recibos'][0]['consecutivo_inicial'] ?? ''));

                if ($pedidoId <= 0 || $prendaId <= 0 || $consecutivoOriginal === '') {
                    return null;
                }

                return "{$pedidoId}|{$prendaId}|{$consecutivoOriginal}";
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($llavesOriginalConParcialVisible)) {
            $prendasConRecibos = $prendasConRecibos
                ->filter(function ($item) use ($llavesOriginalConParcialVisible) {
                    $pedidoId = (int) ($item['pedido_id'] ?? 0);
                    $prendaId = (int) ($item['prenda_id'] ?? 0);
                    $consecutivoActual = trim((string) ($item['recibos'][0]['consecutivo_actual'] ?? ''));
                    $llave = "{$pedidoId}|{$prendaId}|{$consecutivoActual}";

                    return !in_array($llave, $llavesOriginalConParcialVisible, true);
                })
                ->values();
        }

        $prendasConRecibos = $prendasConRecibos
            ->concat($parcialesConRecibos)
            ->values();

        if ($activeTab === 'REFLECTIVO') {
            $prendasConRecibos = $prendasConRecibos
                ->sortBy(function ($item) {
                    $consecutivo = (string) ($item['recibos'][0]['consecutivo_actual'] ?? '');
                    $consecutivo = str_replace(',', '.', trim($consecutivo));
                    return is_numeric($consecutivo) ? (float) $consecutivo : INF;
                })
                ->values();
        } else {
            $prendasConRecibos = $prendasConRecibos
                ->sortBy(fn ($item) => $item['fecha_creacion'] ?? now())
                ->values();
        }

        return view('control-calidad.dashboard', [
            'usuario' => $usuario,
            'prendasConRecibos' => $prendasConRecibos,
            'activeTab' => $activeTab,
        ]);
    }

    public function completarRecibo(Request $request, $idRecibo)
    {
        try {
            $usuario = Auth::user();
            if ($request->boolean('es_parcial')) {
                $parcial = ReciboPorPartes::query()
                    ->with(['pedido', 'prenda'])
                    ->find((int) $idRecibo);

                if (!$parcial) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parcial no encontrado'
                    ], 404);
                }

                $procesoCC = ProcesoPrenda::query()
                    ->where('numero_pedido', (int) ($parcial->pedido?->numero_pedido ?? 0))
                    ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                    ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                    ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                    ->latest('created_at')
                    ->first();

                if (!$procesoCC) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este parcial no está en Control de Calidad'
                    ], 403);
                }

                DB::table('prenda_recibo_completado')->updateOrInsert(
                    ['id_parcial' => (int) $parcial->id, 'area' => 'Control de Calidad'],
                    [
                        'id_recibo' => (int) $parcial->id,
                        'numero_recibo' => (string) ($parcial->getRawOriginal('consecutivo_parcial') ?? $parcial->consecutivo_parcial),
                        'nombre_operario' => (string) $usuario->name,
                        'fecha_completado' => now(),
                    ]
                );

                // Crear (una sola vez) proceso Entrega para el parcial
                $procesoEntregaParcial = ProcesoPrenda::query()
                    ->where('numero_pedido', (int) ($parcial->pedido?->numero_pedido ?? 0))
                    ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                    ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['entrega'])
                    ->whereNull('deleted_at')
                    ->first();

                if (!$procesoEntregaParcial) {
                    ProcesoPrenda::create([
                        'numero_pedido' => (int) ($parcial->pedido?->numero_pedido ?? 0),
                        'prenda_pedido_id' => (int) $parcial->prenda_pedido_id,
                        'numero_recibo_parcial' => $parcial->consecutivo_parcial,
                        'proceso' => 'Entrega',
                        'estado_proceso' => 'Pendiente',
                        'fecha_inicio' => now(),
                        'codigo_referencia' => (string) ($parcial->pedido?->numero_pedido ?? ''),
                    ]);
                }

                $reciboPadreCompletado = $this->sincronizarEntregaOriginalDesdeParciales($parcial);

                event(new \App\Events\ReciboCompletado([
                    'recibo_id' => (int) $parcial->id,
                    'consecutivo' => (string) ($parcial->getRawOriginal('consecutivo_parcial') ?? $parcial->consecutivo_parcial),
                    'pedido_produccion_id' => (int) ($parcial->pedido_produccion_id ?? 0),
                    'prenda_id' => $parcial->prenda_pedido_id ? (int) $parcial->prenda_pedido_id : null,
                    'tipo_recibo' => (string) ($parcial->tipo_recibo ?? ''),
                    'area' => 'Entrega',
                    'nombre_operario' => (string) ($usuario->name ?? ''),
                ]));

                try {
                    broadcast(new \App\Events\ControlCalidadUpdated([
                        'id' => (int) $parcial->id,
                        'pedido' => $parcial->pedido?->numero_pedido,
                        'cliente' => $parcial->pedido?->cliente,
                        'prenda_id' => $parcial->prenda_pedido_id,
                        'nombre_prenda' => $parcial->prenda?->nombre_prenda,
                        'tipo_recibo' => $parcial->tipo_recibo,
                        'consecutivo_actual' => (string) ($parcial->getRawOriginal('consecutivo_parcial') ?? $parcial->consecutivo_parcial),
                        'consecutivo_original' => (string) ($parcial->getRawOriginal('consecutivo_original') ?? $parcial->consecutivo_original),
                        'es_parcial' => true,
                        'parcial_id' => $parcial->id,
                        'completado_area' => true,
                        'completado_total_area' => true,
                        'ocultar_en_dashboard' => true,
                        'area' => 'Entrega',
                        'proceso_actual' => 'Entrega',
                    ], 'removed', 'parcial'));
                } catch (\Throwable $e) {
                    \Log::warning('[ControlCalidadController] Error al broadcast ControlCalidadUpdated parcial completado', [
                        'parcial_id' => (int) $parcial->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => '',
                    'recibo_padre_completado' => $reciboPadreCompletado,
                    'ocultar_en_dashboard' => true,
                ]);
            }

            $recibo = ConsecutivoReciboPedido::where('id', $idRecibo)
                ->where('activo', 1)
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }

            $areaRecibo = strtolower(trim((string) ($recibo->area ?? '')));
            if (!in_array($areaRecibo, ['control calidad', 'control de calidad'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este recibo no está en Control de Calidad'
                ], 403);
            }

            DB::table('prenda_recibo_completado')->updateOrInsert(
                ['id_recibo' => (int) $recibo->id, 'area' => 'Control de Calidad'],
                [
                    'numero_recibo' => (int) ($recibo->consecutivo_actual ?? 0),
                    'nombre_operario' => (string) $usuario->name,
                    'fecha_completado' => now(),
                ]
            );

            // Actualizar el area del recibo a Entrega
            $recibo->update(['area' => 'Entrega']);

            // Crear nuevo proceso para Entrega
            ProcesoPrenda::create([
                'numero_pedido' => (int) ($recibo->pedido?->numero_pedido ?? 0),
                'prenda_pedido_id' => $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                'numero_recibo' => (int) ($recibo->consecutivo_actual ?? 0),
                'proceso' => 'Entrega',
                'estado_proceso' => 'Pendiente',
                'fecha_inicio' => now(),
                'codigo_referencia' => (string) ($recibo->pedido?->numero_pedido ?? ''),
            ]);

            try {
                event(new \App\Events\ReciboCompletado([
                    'recibo_id' => (int) $recibo->id,
                    'consecutivo' => (int) ($recibo->consecutivo_actual ?? 0),
                    'pedido_produccion_id' => (int) ($recibo->pedido_produccion_id ?? 0),
                    'prenda_id' => $recibo->prenda_id ? (int) $recibo->prenda_id : null,
                    'tipo_recibo' => (string) ($recibo->tipo_recibo ?? ''),
                    'area' => 'Entrega',
                    'nombre_operario' => (string) ($usuario->name ?? ''),
                ]));
            } catch (\Exception $e) {
                \Log::warning('[ControlCalidadController] Error al broadcast ReciboCompletado', [
                    'recibo_id' => (int) $idRecibo,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => '',
                'ocultar_en_dashboard' => true,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al completar recibo C.C: ' . $e->getMessage(), [
                'id_recibo' => $idRecibo,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al completar el recibo'
            ], 500);
        }
    }

    public function deshacerRecibo(Request $request, $idRecibo)
    {
        try {
            if ($request->boolean('es_parcial')) {
                // Obtener el parcial para eliminar procesos relacionados
                $parcial = ReciboPorPartes::with('pedido')->find((int) $idRecibo);
                if ($parcial) {
                    // Eliminar el proceso de Entrega creado
                    ProcesoPrenda::where('numero_pedido', (int) ($parcial->pedido?->numero_pedido ?? 0))
                        ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
                        ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                        ->whereRaw('LOWER(TRIM(proceso)) = ?', ['entrega'])
                        ->delete();

                    $this->sincronizarEntregaOriginalDesdeParciales($parcial);
                }

                DB::table('prenda_recibo_completado')
                    ->where('id_parcial', (int) $idRecibo)
                    ->where('area', 'Control de Calidad')
                    ->delete();

                try {
                    broadcast(new \App\Events\ControlCalidadUpdated([
                        'id' => (int) $idRecibo,
                        'es_parcial' => true,
                        'parcial_id' => (int) $idRecibo,
                        'completado_area' => false,
                        'area' => 'Control Calidad',
                    ], 'added', 'parcial'));
                } catch (\Throwable $e) {
                    \Log::warning('[ControlCalidadController] Error al broadcast ControlCalidadUpdated parcial deshecho', [
                        'parcial_id' => (int) $idRecibo,
                        'error' => $e->getMessage(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => ''
                ]);
            }

            // Restaurar el area del recibo a Control de Calidad
            $recibo = ConsecutivoReciboPedido::with('pedido')->find((int) $idRecibo);
            if ($recibo) {
                $recibo->update(['area' => 'Control de Calidad']);

                // Eliminar el proceso de Entrega creado
                ProcesoPrenda::where('numero_pedido', (int) ($recibo->pedido?->numero_pedido ?? 0))
                    ->where('numero_recibo', (int) ($recibo->consecutivo_actual ?? 0))
                    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['entrega'])
                    ->delete();
            }

            DB::table('prenda_recibo_completado')
                ->where('id_recibo', (int) $idRecibo)
                ->where('area', 'Control de Calidad')
                ->delete();

            return response()->json([
                'success' => true,
                'message' => ''
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al deshacer recibo C.C: ' . $e->getMessage(), [
                'id_recibo' => $idRecibo,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer el recibo'
            ], 500);
        }
    }

    /**
     * Ver detalle completo del recibo/pedido (reutiliza la vista del módulo Operario)
     */
    public function verPedido(Request $request, $numeroPedido)
    {
        $usuario = Auth::user();
        $esLiderControlCalidad = $usuario && $usuario->hasRole('lider-control-calidad');
        $tipoRecibo = strtoupper(trim((string) $request->query('tipo_recibo', '')));
        $tipoRecibo = $tipoRecibo === '' ? null : $tipoRecibo;
        $prendaIdParam = $request->query('prenda_id', null);
        $parcialIdParam = (int) $request->query('parcial_id', 0);
        $consecutivoParcialParam = trim((string) $request->query('consecutivo_parcial', ''));
        $reciboIdParam = (int) $request->query('recibo_id', 0);

        // Caso especial: recibos de bodega que no tienen numero_pedido asociado.
        if ((int) $numeroPedido === 0 && in_array($tipoRecibo, ['BODEGA', 'CORTE-PARA-BODEGA'], true)) {
            $reciboBodegaQuery = ConsecutivoReciboPedido::query()
                ->where('id', $reciboIdParam)
                ->whereIn('tipo_recibo', ['BODEGA', 'CORTE-PARA-BODEGA'])
                ->where('activo', 1);
            $this->aplicarCondicionVisibleEnControlCalidad($reciboBodegaQuery);
            $reciboBodega = $reciboBodegaQuery->first();

            if (!$reciboBodega) {
                return redirect()->route('control-calidad.dashboard', ['tab' => 'BODEGA'])
                    ->with('error', 'Recibo de bodega no encontrado');
            }

            return view('operario.ver-pedido', [
                'operario' => null,
                'pedido' => [
                    'numero_pedido' => 0,
                    'numero_recibo_costura' => $reciboBodega->consecutivo_actual,
                    'cliente' => 'BODEGA',
                    'asesor' => 'N/A',
                    'asesora' => 'N/A',
                    'forma_de_pago' => 'N/A',
                    'forma_pago' => 'N/A',
                    'estado' => 'En Ejecución',
                    'area' => 'Control de Calidad',
                    'fecha_creacion' => now()->format('d/m/Y'),
                    'fecha_estimada' => null,
                    'descripcion' => 'Recibo de bodega',
                    'descripcion_prendas' => 'Recibo de bodega',
                    'cantidad' => 0,
                    'novedades' => 'Sin novedades',
                ],
                'usuario' => $usuario,
                'fotos' => [],
            ]);
        }

        $pedidoDB = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with('prendas')
            ->first();

        if (!$pedidoDB) {
            return redirect()->route('control-calidad.dashboard')
                ->with('error', 'Pedido no encontrado');
        }

        // Detectar si es un parcial por la presencia de parcial_id y consecutivo_parcial
        $esParcial = $parcialIdParam > 0 || $consecutivoParcialParam !== '';

        $parcialSeleccionado = null;
        if ($esParcial) {
            $parcialSeleccionado = ReciboPorPartes::query()
                ->with(['pedido', 'prenda'])
                ->where('pedido_produccion_id', $pedidoDB->id)
                ->when($prendaIdParam, fn ($query) => $query->where('prenda_pedido_id', $prendaIdParam))
                ->when($parcialIdParam > 0, fn ($query) => $query->where('id', $parcialIdParam))
                ->when($consecutivoParcialParam !== '', fn ($query) => $query->where('consecutivo_parcial', $consecutivoParcialParam))
                ->first();
        }

        // Seguridad adicional: solo permitir ver pedidos que tengan al menos un recibo/parcial en Control de Calidad
        // EXCEPCIÓN: el rol lider-control-calidad puede ver cualquier recibo COSTURA/REFLECTIVO
        if (!$esLiderControlCalidad) {
            $tieneReciboEnControlCalidadQuery = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoDB->id)
                ->whereIn('tipo_recibo', ['COSTURA', 'REFLECTIVO', 'CORTE-PARA-BODEGA', 'BODEGA'])
                ->where('activo', 1);
            $this->aplicarCondicionVisibleEnControlCalidad($tieneReciboEnControlCalidadQuery);
            $tieneReciboEnControlCalidad = $tieneReciboEnControlCalidadQuery->exists();

            $tieneParcialEnControlCalidad = false;
            if ($esParcial && $parcialSeleccionado) {
                $tieneParcialEnControlCalidad = ProcesoPrenda::query()
                    ->where('numero_pedido', (int) ($parcialSeleccionado->pedido?->numero_pedido ?? 0))
                    ->where('prenda_pedido_id', (int) $parcialSeleccionado->prenda_pedido_id)
                    ->where('numero_recibo_parcial', $parcialSeleccionado->consecutivo_parcial)
                    ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                    ->whereNull('deleted_at')
                    ->exists();
            }

            if (!$tieneReciboEnControlCalidad && !$tieneParcialEnControlCalidad) {
                return redirect()->route('control-calidad.dashboard')
                    ->with('error', 'Este pedido no tiene recibos en Control de Calidad');
            }
        }

        $fotos = $this->obtenerFotosPedido($numeroPedido);

        // Para reutilizar operario.ver-pedido sin cambios, inyectamos el consecutivo
        // del recibo seleccionado en el mismo campo que el blade espera.
        $numeroReciboSeleccionado = null;
        if ($esParcial && $parcialSeleccionado) {
            $numeroReciboSeleccionado = $parcialSeleccionado->getRawOriginal('consecutivo_parcial')
                ?? $parcialSeleccionado->consecutivo_parcial;
        } elseif ($tipoRecibo) {
            $queryRecibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoDB->id)
                ->where('tipo_recibo', $tipoRecibo)
                ->where('activo', 1);

            // Si no es líder, filtrar por área
            if (!$esLiderControlCalidad) {
                $this->aplicarCondicionVisibleEnControlCalidad($queryRecibo);
            }

            // Filtrar por prenda_id si se proporcionó
            if ($prendaIdParam) {
                $queryRecibo->where(function ($q) use ($prendaIdParam) {
                    $q->where('prenda_id', $prendaIdParam)
                      ->orWhereNull('prenda_id');
                });
            }

            $reciboSeleccionado = $queryRecibo->first();

            if ($reciboSeleccionado) {
                $numeroReciboSeleccionado = $reciboSeleccionado->consecutivo_actual;
            }
        }

        // Fallback a COSTURA (compatibilidad)
        if (!$numeroReciboSeleccionado) {
            $queryReciboCostura = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoDB->id)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1);

            // Si no es líder, filtrar por área
            if (!$esLiderControlCalidad) {
                $this->aplicarCondicionVisibleEnControlCalidad($queryReciboCostura);
            }

            $reciboCostura = $queryReciboCostura->first();

            if ($reciboCostura) {
                $numeroReciboSeleccionado = $reciboCostura->consecutivo_actual;
            }
        }

        return view('operario.ver-pedido', [
            'operario' => null,
            'pedido' => [
                'numero_pedido' => $pedidoDB->numero_pedido,
                'numero_recibo_costura' => $numeroReciboSeleccionado,
                'cliente' => $pedidoDB->cliente,
                'asesor' => $pedidoDB->asesor_id ? $pedidoDB->asesor_id : 'N/A',
                'asesora' => $pedidoDB->asesor_id ? $pedidoDB->asesor_id : 'N/A',
                'forma_de_pago' => $pedidoDB->forma_de_pago ?? 'N/A',
                'forma_pago' => $pedidoDB->forma_de_pago ?? 'N/A',
                'estado' => $pedidoDB->estado ?? 'Pendiente',
                'area' => 'Control de Calidad',
                'fecha_creacion' => $pedidoDB->created_at ? $pedidoDB->created_at->format('d/m/Y') : date('d/m/Y'),
                'fecha_estimada' => $pedidoDB->fecha_estimada ? $pedidoDB->fecha_estimada->format('d/m/Y') : null,
                'descripcion' => $pedidoDB->descripcion ?? 'N/A',
                'descripcion_prendas' => $pedidoDB->descripcion ?? 'N/A',
                'cantidad' => $pedidoDB->total_prendas ?? 0,
                'novedades' => $pedidoDB->novedades ?? 'Sin novedades',
            ],
            'usuario' => $usuario,
            'fotos' => $fotos,
        ]);
    }

    /**
     * API: Obtener datos completos del pedido/recibo para Control de Calidad.
     * Reutiliza la misma respuesta del endpoint Operario (incluye soporte de parciales).
     */
    public function getPedidoData(Request $request, int $numeroPedido, GetPedidoDataOperarioUseCase $useCase)
    {
        $result = $useCase->execute((int) $numeroPedido, $request);
        
        // FILTRAR POR PRENDA_ID si se proporciona
        $prendaIdParam = $request->query('prenda_id');
        $tipoReciboParam = strtoupper(trim((string) $request->query('tipo_recibo', '')));
        
        if ($prendaIdParam !== null && isset($result['payload']['data']['prendas'])) {
            $prendaIdParam = (int) $prendaIdParam;
            
            // Filtrar solo la prenda especificada
            $prendasFiltradas = array_filter(
                $result['payload']['data']['prendas'],
                fn($prenda) => (int) ($prenda['id'] ?? 0) === $prendaIdParam
            );
            
            // Si encontramos la prenda, dejarla como única
            if (!empty($prendasFiltradas)) {
                $prendasFiltradas = array_map(function($prenda) use ($tipoReciboParam) {
                    // Si se especifica un tipo_recibo, filtrar también los procesos y recibos
                    if ($tipoReciboParam && isset($prenda['procesos'])) {
                        $prenda['procesos'] = array_filter(
                            $prenda['procesos'],
                            fn($proceso) => strtoupper(trim((string) ($proceso['tipo_proceso'] ?? $proceso['proceso'] ?? ''))) === $tipoReciboParam
                        );
                        $prenda['procesos'] = array_values($prenda['procesos']);
                    }
                    
                    // También filtrar los recibos por tipo
                    if ($tipoReciboParam && isset($prenda['recibos']) && is_array($prenda['recibos'])) {
                        $prendaRecibosOriginal = $prenda['recibos'];
                        $prenda['recibos'] = [];
                        
                        foreach ($prendaRecibosOriginal as $tipoRecibo => $reciboData) {
                            $tipoRecibonormalizado = strtoupper(trim((string) $tipoRecibo));
                            // Mantener el recibo si coincide con el filtro o si es PARCIAL (que puede ser de cualquier tipo)
                            if ($tipoRecibonormalizado === $tipoReciboParam || $tipoRecibonormalizado === 'PARCIAL') {
                                $prenda['recibos'][$tipoRecibo] = $reciboData;
                            }
                        }
                    }
                    
                    return $prenda;
                }, $prendasFiltradas);
                
                $result['payload']['data']['prendas'] = array_values($prendasFiltradas);
            }
        }

        return response()->json($result['payload'] ?? [], (int) ($result['status'] ?? 200));
    }

    private function obtenerFotosPedido($numeroPedido)
    {
        $cacheKey = "fotos_pedido_{$numeroPedido}";

        return Cache::remember($cacheKey, 600, function() use ($numeroPedido) {
            $fotos = [];

            try {
                $pedido = PedidoProduccion::select('id', 'cotizacion_id')
                    ->where('numero_pedido', $numeroPedido)
                    ->first();

                if (!$pedido || !$pedido->cotizacion_id) {
                    return [];
                }

                $prendasCotIds = \App\Models\PrendaCot::where('cotizacion_id', $pedido->cotizacion_id)
                    ->pluck('id')
                    ->toArray();

                if (empty($prendasCotIds)) {
                    return [];
                }

                $fotosPrendas = \App\Models\PrendaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();

                foreach($fotosPrendas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if($ruta) $fotos[] = $ruta;
                }

                $fotosTelas = \App\Models\PrendaTelaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();

                foreach($fotosTelas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if($ruta) $fotos[] = $ruta;
                }

                // LogoFotoCot ya no se usa (tabla no utilizada)
            } catch (\Exception $e) {
                return [];
            }

            return $fotos;
        });
    }

    private function sincronizarEntregaOriginalDesdeParciales(ReciboPorPartes $parcial): bool
    {
        $numeroPedido = (int) ($parcial->pedido?->numero_pedido ?? 0);
        if ($numeroPedido <= 0) {
            return false;
        }

        $parcialesRelacionados = ReciboPorPartes::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim((string) $parcial->tipo_recibo))])
            ->where('consecutivo_original', $parcial->consecutivo_original)
            ->get(['id', 'consecutivo_parcial']);

        $totalParciales = $parcialesRelacionados->count();
        if ($totalParciales <= 0) {
            return false;
        }

        $consecutivosParciales = $parcialesRelacionados
            ->pluck('consecutivo_parcial')
            ->filter(fn ($valor) => $valor !== null && $valor !== '')
            ->values();

        $parcialesEnEntrega = $consecutivosParciales->isEmpty()
            ? 0
            : ProcesoPrenda::query()
                ->where('numero_pedido', $numeroPedido)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->whereIn('numero_recibo_parcial', $consecutivosParciales->all())
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['entrega'])
                ->whereNull('deleted_at')
                ->distinct('numero_recibo_parcial')
                ->count('numero_recibo_parcial');

        // Regla estricta: el recibo original solo puede pasar a Entrega
        // cuando TODOS los parciales asociados estén completados en C.C.
        $parcialesCompletadosEnCC = DB::table('prenda_recibo_completado')
            ->where('area', 'Control de Calidad')
            ->whereIn('id_parcial', $parcialesRelacionados->pluck('id')->all())
            ->distinct()
            ->count('id_parcial');

        // Validar cobertura real del recibo original por cantidades de tallas:
        // la suma de tallas registradas en todas las partes debe cubrir el total
        // de tallas de la prenda original.
        $cantidadTotalPrendaOriginal = PrendaPedidoTalla::query()
            ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
            ->with('coloresAsignados')
            ->get()
            ->sum(fn (PrendaPedidoTalla $talla) => (int) $talla->obtenerCantidadTotal());

        $cantidadTotalParcializada = $parcialesRelacionados->isEmpty()
            ? 0
            : (int) DB::table('recibos_por_partes_tallas')
                ->whereIn('recibo_por_partes_id', $parcialesRelacionados->pluck('id')->all())
                ->sum('cantidad');

        $coberturaCompletaPorCantidades = $cantidadTotalPrendaOriginal > 0
            && $cantidadTotalParcializada >= $cantidadTotalPrendaOriginal;

        $todosParcialesEnEntrega = $parcialesEnEntrega >= $totalParciales
            && $parcialesCompletadosEnCC >= $totalParciales
            && $coberturaCompletaPorCantidades;

        $consecutivoOriginalNum = (int) $parcial->consecutivo_original;
        $queryReciboPadre = ConsecutivoReciboPedido::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_id', (int) $parcial->prenda_pedido_id)
            ->where('consecutivo_actual', $consecutivoOriginalNum)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim((string) $parcial->tipo_recibo))]);

        $reciboPadre = (clone $queryReciboPadre)->first();

        $queryProcesoEntregaPadre = ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->where('numero_recibo', $parcial->consecutivo_original)
            ->where(function ($query) {
                $query->whereNull('numero_recibo_parcial')
                    ->orWhere('numero_recibo_parcial', 0);
            })
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['entrega'])
            ->whereNull('deleted_at');

        if ($todosParcialesEnEntrega) {
            $queryReciboPadre->update(['area' => 'Entrega']);

            $procesoEntregaPadre = $queryProcesoEntregaPadre->latest('created_at')->first();
            if (!$procesoEntregaPadre) {
                ProcesoPrenda::create([
                    'numero_pedido' => $numeroPedido,
                    'prenda_pedido_id' => $parcial->prenda_pedido_id ? (int) $parcial->prenda_pedido_id : null,
                    'numero_recibo' => $parcial->consecutivo_original,
                    'numero_recibo_parcial' => null,
                    'proceso' => 'Entrega',
                    'estado_proceso' => 'Pendiente',
                    'fecha_inicio' => now(),
                    'encargado' => null,
                    'codigo_referencia' => 'EPO-' . $parcial->consecutivo_original . '-' . date('YmdHis'),
                ]);
            }

            return true;
        }

        // Si el recibo padre fue devuelto manualmente a Costura,
        // no debemos sobrescribirlo al sincronizar parciales.
        $areaPadreActual = strtolower(trim((string) ($reciboPadre?->area ?? '')));
        if ($areaPadreActual !== 'costura') {
            $queryReciboPadre->update(['area' => 'Control Calidad']);
        }

        $queryProcesoEntregaPadre->delete();
        
        return false;
    }

    /**
     * API: Obtener distribución de parciales para un recibo (llamado desde el dashboard de Control de Calidad)
     * GET /control-calidad/api/recibos/{idRecibo}/distribucion-parciales
     */
    public function obtenerDistribucionParciales(Request $request, $idRecibo)
    {
        try {
            $result = $this->obtenerDistribucionCCUseCase->execute((int) $idRecibo);
            return response()->json($result['payload'] ?? [], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[ControlCalidadController] Error en obtenerDistribucionParciales: ' . $e->getMessage(), [
                'recibo_id' => $idRecibo,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener distribución de parciales: ' . $e->getMessage()
            ], 500);
        }
    }
}
