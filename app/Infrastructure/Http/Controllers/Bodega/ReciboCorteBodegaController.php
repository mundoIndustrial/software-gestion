<?php

namespace App\Infrastructure\Http\Controllers\Bodega;

use App\Models\PrendaBodega;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReciboCorteBodegaController extends Controller
{
    private function inferirGeneroDesdeTalla(?string $talla): ?string
    {
        $valor = strtoupper(trim((string) $talla));
        if ($valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            $num = (int) $valor;
            if ($num >= 4 && $num <= 26) {
                return 'DAMA';
            }
            if ($num >= 28) {
                return 'CABALLERO';
            }
        }

        return null;
    }

    public function index()
    {
        $esAdmin = (bool) (auth()->user()?->hasRole('admin'));

        $prendasQuery = PrendaBodega::with('tallas')
            ->orderBy('created_at', 'desc');

        if ($esAdmin) {
            $prendasQuery->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('consecutivos_recibos_pedidos as crp')
                    ->whereColumn('crp.prenda_bodega_id', 'prenda_bodega.id')
                    ->where('crp.tipo_recibo', 'CORTE-PARA-BODEGA')
                    ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = 'insumos'");
            });
        }

        $prendas = $prendasQuery->paginate(25);

        $prendaIds = $prendas->pluck('id')->all();
        $recibosMap = [];
        if (!empty($prendaIds)) {
            $recibosMap = DB::table('consecutivos_recibos_pedidos')
                ->whereIn('prenda_bodega_id', $prendaIds)
                ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
                ->orderByDesc('id')
                ->get()
                ->groupBy('prenda_bodega_id')
                ->map(function ($rows) {
                    $first = $rows->first();
                    return [
                        'numero_recibo' => isset($first->consecutivo_actual) ? (int) $first->consecutivo_actual : null,
                        'area' => $first->area ?? null,
                        'pedido_produccion_id' => isset($first->pedido_produccion_id) ? (int) $first->pedido_produccion_id : null,
                        'prenda_id' => isset($first->prenda_id) ? (int) $first->prenda_id : null,
                    ];
                })
                ->toArray();
        }

        // Fallback: cuando consecutivos no tenga pedido/prenda asociados,
        // intentar resolverlos desde procesos_prenda por numero_recibo.
        $fallbackProcesoMap = [];
        $numerosRecibo = collect($recibosMap)
            ->pluck('numero_recibo')
            ->filter(fn($n) => !empty($n))
            ->map(fn($n) => (int) $n)
            ->unique()
            ->values()
            ->all();

        if (!empty($numerosRecibo)) {
            $hasPrendaBodegaColumn = Schema::hasColumn('procesos_prenda', 'prenda_bodega_id');

            $queryProcesos = DB::table('procesos_prenda')
                ->whereIn('numero_recibo', $numerosRecibo)
                ->whereNotNull('numero_pedido')
                ->whereNotNull('prenda_pedido_id')
                ->orderByDesc('fecha_inicio')
                ->orderByDesc('id');

            if ($hasPrendaBodegaColumn) {
                $queryProcesos->select([
                    'numero_recibo',
                    'numero_pedido',
                    'prenda_pedido_id',
                    'prenda_bodega_id',
                ]);
            } else {
                $queryProcesos->select([
                    'numero_recibo',
                    'numero_pedido',
                    'prenda_pedido_id',
                ]);
            }

            $procesos = $queryProcesos->get();

            foreach ($procesos as $proceso) {
                $nr = (int) ($proceso->numero_recibo ?? 0);
                if ($nr <= 0) {
                    continue;
                }

                $prendaBodegaId = $hasPrendaBodegaColumn ? (int) ($proceso->prenda_bodega_id ?? 0) : 0;
                $keyEspecifica = $prendaBodegaId > 0 ? ($nr . ':' . $prendaBodegaId) : null;
                $keyGeneral = (string) $nr;

                $payload = [
                    'pedido_produccion_id' => (int) $proceso->numero_pedido,
                    'prenda_id' => (int) $proceso->prenda_pedido_id,
                ];

                if ($keyEspecifica && !isset($fallbackProcesoMap[$keyEspecifica])) {
                    $fallbackProcesoMap[$keyEspecifica] = $payload;
                }

                if (!isset($fallbackProcesoMap[$keyGeneral])) {
                    $fallbackProcesoMap[$keyGeneral] = $payload;
                }
            }
        }

        // Obtener encargados más recientes por número de recibo
        $encargadosMap = [];
        if (!empty($numerosRecibo)) {
            $encargados = DB::table('procesos_prenda')
                ->whereIn('numero_recibo', $numerosRecibo)
                ->whereNotNull('encargado')
                ->orderByDesc('fecha_de_asignacion_encargado')
                ->orderByDesc('id')
                ->select('numero_recibo', 'encargado')
                ->get()
                ->groupBy('numero_recibo')
                ->map(function ($rows) {
                    return $rows->first()->encargado;
                })
                ->toArray();
            
            $encargadosMap = $encargados;
        }

        return response()->json([
            'success' => true,
            'data' => $prendas->map(function ($prenda) use ($recibosMap, $fallbackProcesoMap, $encargadosMap) {
                $numeroRecibo = $recibosMap[$prenda->id]['numero_recibo'] ?? null;
                $pedidoProduccionId = $recibosMap[$prenda->id]['pedido_produccion_id'] ?? null;
                $prendaId = $recibosMap[$prenda->id]['prenda_id'] ?? null;

                if ((!$pedidoProduccionId || !$prendaId) && $numeroRecibo) {
                    $keyEspecifica = ((int) $numeroRecibo) . ':' . ((int) $prenda->id);
                    $keyGeneral = (string) ((int) $numeroRecibo);
                    $fallback = $fallbackProcesoMap[$keyEspecifica] ?? $fallbackProcesoMap[$keyGeneral] ?? null;

                    if ($fallback) {
                        $pedidoProduccionId = $pedidoProduccionId ?: ($fallback['pedido_produccion_id'] ?? null);
                        $prendaId = $prendaId ?: ($fallback['prenda_id'] ?? null);
                    }
                }

                $encargado = $numeroRecibo ? ($encargadosMap[$numeroRecibo] ?? null) : null;

                return [
                    'id' => $prenda->id,
                    'numero_recibo' => $numeroRecibo,
                    'area' => $recibosMap[$prenda->id]['area'] ?? null,
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'prenda_id' => $prendaId,
                    'nombre' => $prenda->nombre,
                    'descripcion' => $prenda->descripcion,
                    'total_cantidad' => $prenda->tallas->sum('cantidad'),
                    'cantidad_tallas' => $prenda->tallas->count(),
                    'fecha' => $prenda->created_at->format('Y-m-d H:i'),
                    'fecha_corta' => $prenda->created_at->format('d/m/Y'),
                    'encargado' => $encargado,
                ];
            })->toArray(),
            'pagination' => [
                'current_page' => $prendas->currentPage(),
                'per_page' => $prendas->perPage(),
                'total' => $prendas->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prendas' => 'required|array|min:1',
            'prendas.*.nombre' => 'nullable|string|max:255',
            'prendas.*.descripcion' => 'required|string',
            'prendas.*.tallas' => 'required|array|min:1',
            'prendas.*.tallas.*.talla' => 'nullable|string|max:50',
            'prendas.*.tallas.*.genero' => 'nullable|string|in:dama,caballero,unisex,DAMA,CABALLERO,UNISEX',
            'prendas.*.tallas.*.color' => 'nullable|string|max:100',
            'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
        ]);

        if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'prenda_bodega_id')) {
            return response()->json([
                'success' => false,
                'message' => "Falta columna 'consecutivos_recibos_pedidos.prenda_bodega_id'. Ejecuta el ALTER TABLE pendiente.",
            ], 500);
        }

        try {
            $userId = (int) (auth()->id() ?? 0);
            $fingerprint = hash('sha256', json_encode($validated, JSON_UNESCAPED_UNICODE));
            $dedupeKey = sprintf('recibo_corte_bodega:store:%d:%s', $userId, $fingerprint);

            if (!Cache::add($dedupeKey, now()->timestamp, now()->addSeconds(12))) {
                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud duplicada detectada. Ya fue procesada.',
                    'prendas' => [],
                    'duplicate' => true,
                ], 200);
            }

            $prendas = DB::transaction(function () use ($validated) {
                $registroMaestro = DB::table('consecutivos_recibos')
                    ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
                    ->lockForUpdate()
                    ->first();

                if (!$registroMaestro) {
                    DB::table('consecutivos_recibos')->insert([
                        'tipo_recibo' => 'CORTE-PARA-BODEGA',
                        'consecutivo_actual' => 0,
                        'consecutivo_inicial' => 1,
                        'año' => (int) date('Y'),
                        'activo' => 1,
                        'notas' => 'Consecutivo para RECIBO DE CORTE PARA BODEGA',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $registroMaestro = DB::table('consecutivos_recibos')
                        ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
                        ->lockForUpdate()
                        ->first();
                }

                $consecutivoActual = (int) ($registroMaestro->consecutivo_actual ?? 0);
                $consecutivoInicial = (int) ($registroMaestro->consecutivo_inicial ?? 1);
                $siguienteConsecutivo = max($consecutivoActual + 1, $consecutivoInicial);

                $resultado = [];
                foreach ($validated['prendas'] as $prendaData) {
                    $descripcion = trim((string) ($prendaData['descripcion'] ?? ''));
                    $nombre = trim((string) ($prendaData['nombre'] ?? ''));
                    $nombrePersistir = $nombre !== '' ? $nombre : $descripcion;

                    $prenda = PrendaBodega::create([
                        'nombre' => $nombrePersistir,
                        'descripcion' => $descripcion,
                    ]);

                    DB::table('consecutivos_recibos_pedidos')->insert([
                        'pedido_produccion_id' => null,
                        'prenda_id' => null,
                        'prenda_bodega_id' => $prenda->id,
                        'tipo_recibo' => 'CORTE-PARA-BODEGA',
                        'consecutivo_actual' => $siguienteConsecutivo,
                        'consecutivo_inicial' => $siguienteConsecutivo,
                        'activo' => 1,
                        'marcar_plooter' => 0,
                        'estado' => 'PENDIENTE_INSUMOS',
                        'area' => 'Insumos',
                        'notas' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'ultima_actividad' => now(),
                    ]);

                    foreach ($prendaData['tallas'] as $tallaData) {
                        $genero = isset($tallaData['genero']) ? strtoupper((string) $tallaData['genero']) : null;
                        $tallaValor = strtoupper(trim((string) ($tallaData['talla'] ?? '')));
                        if ($tallaValor === '' || $tallaValor === 'UNICA') {
                            $tallaValor = null;
                        }
                        $prenda->tallas()->create([
                            'talla' => $tallaValor,
                            'genero' => in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true) ? $genero : null,
                            'color' => isset($tallaData['color']) ? strtoupper(trim((string) $tallaData['color'])) : null,
                            'cantidad' => $tallaData['cantidad'],
                        ]);
                    }

                    $resultado[] = [
                        'id' => $prenda->id,
                        'numero_recibo' => $siguienteConsecutivo,
                        'nombre' => $prenda->nombre,
                        'descripcion' => $prenda->descripcion,
                    ];
                }

                DB::table('consecutivos_recibos')
                    ->where('id', $registroMaestro->id)
                    ->update([
                        'consecutivo_actual' => $siguienteConsecutivo,
                        'updated_at' => now(),
                    ]);

                return $resultado;
            });

            return response()->json([
                'success' => true,
                'message' => 'Recibo registrado correctamente',
                'prendas' => $prendas,
            ], 201);
        } catch (\Throwable $e) {
            \Log::error('[ReciboCorteBodegaController@store] Error al registrar recibo', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar recibo de corte para bodega: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $prenda = PrendaBodega::with('tallas')->find($id);

        if (!$prenda) {
            return response()->json([
                'success' => false,
                'message' => 'Recibo no encontrado',
            ], 404);
        }

        $totalCantidad = $prenda->tallas->sum('cantidad');

        $numeroRecibo = DB::table('consecutivos_recibos_pedidos')
            ->where('prenda_bodega_id', $prenda->id)
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->orderByDesc('id')
            ->value('consecutivo_actual');

        return response()->json([
            'success' => true,
            'id' => $prenda->id,
            'numero_recibo' => $numeroRecibo ? (int) $numeroRecibo : null,
            'nombre' => $prenda->nombre,
            'descripcion' => $prenda->descripcion,
            'fecha' => $prenda->created_at->format('Y-m-d'),
            'dia' => $prenda->created_at->format('d'),
            'mes' => $prenda->created_at->format('m'),
            'ano' => $prenda->created_at->format('Y'),
            'tallas' => $prenda->tallas->map(fn($t) => [
                'talla' => $t->talla,
                'genero' => $t->genero ?: $this->inferirGeneroDesdeTalla($t->talla),
                'color' => $t->color,
                'cantidad' => $t->cantidad,
            ])->toArray(),
            'total' => $totalCantidad,
        ]);
    }

    public function showParcial($id)
    {
        $parcial = DB::table('recibo_por_partes as rpp')
            ->leftJoin('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->where('rpp.id', $id)
            ->select(
                'rpp.id',
                'rpp.consecutivo_parcial',
                'rpp.tipo_recibo',
                'rpp.created_at',
                'pp.nombre_prenda',
                'pp.descripcion as descripcion_prenda'
            )
            ->first();

        if (!$parcial) {
            return response()->json([
                'success' => false,
                'message' => 'Recibo parcial no encontrado',
            ], 404);
        }

        $tallas = DB::table('recibos_por_partes_tallas')
            ->where('recibo_por_partes_id', $id)
            ->select('talla', 'genero', 'color_nombre as color', 'cantidad')
            ->get();

        $totalCantidad = (int) $tallas->sum('cantidad');
        $fecha = $parcial->created_at ? \Carbon\Carbon::parse($parcial->created_at) : now();
        $numeroRecibo = $parcial->consecutivo_parcial;

        return response()->json([
            'success' => true,
            'id' => (int) $parcial->id,
            'numero_recibo' => $numeroRecibo !== null ? (float) $numeroRecibo : null,
            'tipo_recibo' => strtoupper(trim((string) ($parcial->tipo_recibo ?? 'COSTURA'))),
            'nombre' => $parcial->nombre_prenda ?: 'PRENDA',
            'descripcion' => $parcial->descripcion_prenda ?: '',
            'fecha' => $fecha->format('Y-m-d'),
            'dia' => $fecha->format('d'),
            'mes' => $fecha->format('m'),
            'ano' => $fecha->format('Y'),
            'tallas' => $tallas->map(function ($t) {
                return [
                    'talla' => $t->talla,
                    'genero' => $t->genero ?: $this->inferirGeneroDesdeTalla($t->talla),
                    'color' => $t->color,
                    'cantidad' => (int) $t->cantidad,
                ];
            })->toArray(),
            'total' => $totalCantidad,
        ]);
    }
}
