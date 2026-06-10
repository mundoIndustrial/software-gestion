<?php

namespace App\Infrastructure\Http\Controllers\Bodega;

use App\Models\PrendaBodega;
use App\Models\PrendaBodegaFoto;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ReciboCorteBodegaController extends Controller
{
    private function normalizarUrlStorageLocal(string $ruta): string
    {
        $valor = trim($ruta);
        if ($valor === '') {
            return '';
        }

        // Si viene absoluta, extraer solo path para evitar host/IP incorrecto.
        if (preg_match('#^https?://#i', $valor) === 1) {
            $path = parse_url($valor, PHP_URL_PATH);
            $valor = is_string($path) ? $path : $valor;
        }

        $valor = str_replace('\\', '/', $valor);
        $valor = preg_replace('#^/storage/#', '', $valor);
        $valor = ltrim($valor, '/');

        return '/storage/' . $valor;
    }

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

    public function index(Request $request)
    {
        $esAdmin = (bool) (auth()->user()?->hasRole('admin'));
        $areaFilter = $request->query('area', '');

        $prendasQuery = PrendaBodega::with(['tallas', 'fotos'])
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

        // Filtro por área si se proporciona - APLICAR ANTES DE PAGINAR
        if (!empty($areaFilter)) {
            $prendasQuery->whereExists(function ($query) use ($areaFilter) {
                $query->select(DB::raw(1))
                    ->from('consecutivos_recibos_pedidos as crp')
                    ->whereColumn('crp.prenda_bodega_id', 'prenda_bodega.id')
                    ->where('crp.tipo_recibo', 'CORTE-PARA-BODEGA')
                    ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = ?", [strtolower(trim($areaFilter))]);
            });
        }

        // PAGINAR AQUÍ - DESPUÉS DE APLICAR TODOS LOS FILTROS
        $prendas = $prendasQuery->paginate(25);

        $prendaIds = $prendas->pluck('id')->all();
        $recibosMap = [];
        
        if (!empty($prendaIds)) {
            $recibosQuery = DB::table('consecutivos_recibos_pedidos as crp')
                ->whereIn('crp.prenda_bodega_id', $prendaIds)
                ->where('crp.tipo_recibo', 'CORTE-PARA-BODEGA')
                ->orderByDesc('crp.id');
            
            // Aplicar filtro de área también aquí para mantener consistencia
            if (!empty($areaFilter)) {
                $recibosQuery->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = ?", [strtolower(trim($areaFilter))]);
            }
            
            $recibosMap = $recibosQuery
                ->select('crp.*')
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
        $encargadosPorAreaMap = [];
        if (!empty($numerosRecibo)) {
            $hasPrendaBodegaColumnForEncargados = Schema::hasColumn('procesos_prenda', 'prenda_bodega_id');
            $encargados = DB::table('procesos_prenda')
                ->whereIn('numero_recibo', $numerosRecibo)
                ->whereNotNull('encargado')
                ->orderByRaw("COALESCE(fecha_de_asignacion_encargado, updated_at, created_at, fecha_inicio) DESC")
                ->orderByDesc('id')
                ->select(array_filter([
                    'numero_recibo',
                    'encargado',
                    'proceso',
                    $hasPrendaBodegaColumnForEncargados ? 'prenda_bodega_id' : null,
                ]))
                ->get();

            $normalizarTexto = static function (?string $valor): string {
                $texto = strtolower(trim((string) $valor));
                $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
                if ($ascii !== false) {
                    $texto = $ascii;
                }
                $texto = preg_replace('/[^a-z0-9]+/', '', $texto);
                return (string) $texto;
            };

            foreach ($encargados as $row) {
                $numero = (int) ($row->numero_recibo ?? 0);
                if ($numero <= 0) {
                    continue;
                }

                $procesoKey = $normalizarTexto((string) ($row->proceso ?? ''));
                $prendaBodegaId = $hasPrendaBodegaColumnForEncargados ? (int) ($row->prenda_bodega_id ?? 0) : 0;

                if (!isset($encargadosMap[$numero])) {
                    $encargadosMap[$numero] = $row->encargado;
                }

                if ($procesoKey !== '') {
                    $keyGeneral = $numero . '|*|' . $procesoKey;
                    if (!isset($encargadosPorAreaMap[$keyGeneral])) {
                        $encargadosPorAreaMap[$keyGeneral] = $row->encargado;
                    }

                    if ($prendaBodegaId > 0) {
                        $keyEspecifica = $numero . '|' . $prendaBodegaId . '|' . $procesoKey;
                        if (!isset($encargadosPorAreaMap[$keyEspecifica])) {
                            $encargadosPorAreaMap[$keyEspecifica] = $row->encargado;
                        }
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $prendas->map(function ($prenda) use ($recibosMap, $fallbackProcesoMap, $encargadosMap, $encargadosPorAreaMap) {
                $numeroRecibo = $recibosMap[$prenda->id]['numero_recibo'] ?? null;
                $pedidoProduccionId = $recibosMap[$prenda->id]['pedido_produccion_id'] ?? null;
                $prendaId = $recibosMap[$prenda->id]['prenda_id'] ?? null;
                $areaActual = $recibosMap[$prenda->id]['area'] ?? null;

                if ((!$pedidoProduccionId || !$prendaId) && $numeroRecibo) {
                    $keyEspecifica = ((int) $numeroRecibo) . ':' . ((int) $prenda->id);
                    $keyGeneral = (string) ((int) $numeroRecibo);
                    $fallback = $fallbackProcesoMap[$keyEspecifica] ?? $fallbackProcesoMap[$keyGeneral] ?? null;

                    if ($fallback) {
                        $pedidoProduccionId = $pedidoProduccionId ?: ($fallback['pedido_produccion_id'] ?? null);
                        $prendaId = $prendaId ?: ($fallback['prenda_id'] ?? null);
                    }
                }

                $encargado = null;
                if ($numeroRecibo) {
                    $normalizarTexto = static function (?string $valor): string {
                        $texto = strtolower(trim((string) $valor));
                        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
                        if ($ascii !== false) {
                            $texto = $ascii;
                        }
                        $texto = preg_replace('/[^a-z0-9]+/', '', $texto);
                        return (string) $texto;
                    };

                    $areaKey = $normalizarTexto((string) $areaActual);
                    $keyEspecificaArea = ((int) $numeroRecibo) . '|' . ((int) $prenda->id) . '|' . $areaKey;
                    $keyGeneralArea = ((int) $numeroRecibo) . '|*|' . $areaKey;

                    if ($areaKey !== '') {
                        // Si el recibo tiene área definida, solo usar encargado de esa misma área.
                        // Evita mezclar, por ejemplo, área "Insumos" con encargado de "Control".
                        $encargado = $encargadosPorAreaMap[$keyEspecificaArea]
                            ?? $encargadosPorAreaMap[$keyGeneralArea]
                            ?? null;
                    } else {
                        $encargado = $encargadosMap[$numeroRecibo] ?? null;
                    }
                }

                return [
                    'id' => $prenda->id,
                    'numero_recibo' => $numeroRecibo,
                    'area' => $recibosMap[$prenda->id]['area'] ?? null,
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'prenda_id' => $prendaId,
                    'nombre' => $prenda->nombre,
                    'descripcion' => $prenda->descripcion,
                    'total_cantidad' => $prenda->tallas->sum('cantidad'),
                    'cantidad_tallas' => $prenda->tallas->first() && strtolower(trim($prenda->tallas->first()->genero ?? '')) === 'unisex' ? 'N/A' : ($prenda->tallas->count() === 0 ? 'N/A' : $prenda->tallas->count()),
                    'fotos' => $prenda->fotos->map(fn($f) => [
                        'id' => (int) $f->id,
                        'ruta' => $f->ruta,
                        'url' => $this->normalizarUrlStorageLocal((string) $f->ruta),
                        'orden' => (int) ($f->orden ?? 0),
                    ])->toArray(),
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
        $prendasInput = $request->input('prendas');
        if (!$prendasInput && $request->filled('prendas_json')) {
            $prendasInput = json_decode((string) $request->input('prendas_json'), true);
        }
        if (!is_array($prendasInput)) {
            $prendasInput = [];
        }

        $validated = validator([
            'prendas' => $prendasInput,
            'prenda_imagenes' => $request->file('prenda_imagenes', []),
        ], [
            'prendas' => 'required|array|min:1',
            'prendas.*.nombre' => 'nullable|string|max:255',
            'prendas.*.descripcion' => 'required|string',
            'prendas.*.tallas' => 'required|array|min:1',
            'prendas.*.tallas.*.talla' => 'nullable|string|max:50',
            'prendas.*.tallas.*.genero' => 'nullable|string|in:dama,caballero,unisex,DAMA,CABALLERO,UNISEX',
            'prendas.*.tallas.*.color' => 'nullable|string|max:100',
            'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
            'prenda_imagenes' => 'nullable|array',
            'prenda_imagenes.*' => 'nullable|array',
            'prenda_imagenes.*.*' => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120',
        ])->validate();

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

            $memoriaOriginal = ini_get('memory_limit');
            ini_set('memory_limit', '512M');

            $prendas = DB::transaction(function () use ($validated, $request) {
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
                foreach ($validated['prendas'] as $prendaIndex => $prendaData) {
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

                    $imagenesPrenda = $request->file("prenda_imagenes.$prendaIndex", []);
                    foreach ((array) $imagenesPrenda as $orden => $archivo) {
                        if (!$archivo || !$archivo->isValid()) {
                            continue;
                        }

                        $numeroReciboCarpeta = (int) $siguienteConsecutivo;
                        $directorio = "bodega/{$numeroReciboCarpeta}";
                        if (!Storage::disk('public')->exists($directorio)) {
                            Storage::disk('public')->makeDirectory($directorio);
                        }

                        $nombreArchivo = bin2hex(random_bytes(20)) . '.webp';
                        $ruta = "{$directorio}/{$nombreArchivo}";

                        $imageManager = ImageManager::gd();
                        $imagen = $imageManager->read($archivo->getRealPath());
                        if (method_exists($imagen, 'orient')) {
                            $imagen = $imagen->orient();
                        }

                        if (method_exists($imagen, 'width') && method_exists($imagen, 'height')) {
                            if ($imagen->width() > 2000 || $imagen->height() > 2000) {
                                $imagen->scaleDown(width: 2000, height: 2000);
                            }
                        }

                        $contenidoWebp = $imagen->toWebp(quality: 80)->toString();
                        Storage::disk('public')->put($ruta, $contenidoWebp);

                        PrendaBodegaFoto::create([
                            'prenda_bodega_id' => $prenda->id,
                            'ruta' => $ruta,
                            'orden' => (int) $orden,
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

            if (isset($memoriaOriginal) && $memoriaOriginal !== false) {
                ini_set('memory_limit', $memoriaOriginal);
            }

            return response()->json([
                'success' => true,
                'message' => 'Recibo registrado correctamente',
                'prendas' => $prendas,
            ], 201);
        } catch (\Throwable $e) {
            if (isset($memoriaOriginal) && $memoriaOriginal !== false) {
                ini_set('memory_limit', $memoriaOriginal);
            }

            \Log::error('[ReciboCorteBodegaController@store] Error al registrar recibo', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar recibo de corte para bodega: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function resolverBase(Request $request)
    {
        $validated = validator($request->all(), [
            'pedido_produccion_id' => 'required|integer|min:1',
            'prenda_id' => 'required|integer|min:1',
            'tipo_recibo' => 'nullable|string|max:50',
        ])->validate();

        try {
            $resultado = DB::transaction(function () use ($validated) {
                $pedidoProduccionId = (int) $validated['pedido_produccion_id'];
                $prendaId = (int) $validated['prenda_id'];
                $tipoRecibo = strtoupper(trim((string) ($validated['tipo_recibo'] ?? 'COSTURA-BODEGA')));
                $tipoRecibo = $tipoRecibo !== '' ? $tipoRecibo : 'COSTURA-BODEGA';
                if ($tipoRecibo === 'CORTE-PARA-BODEGA') {
                    $tipoRecibo = 'COSTURA-BODEGA';
                }

                $reciboExistente = DB::table('consecutivos_recibos_pedidos')
                    ->where('pedido_produccion_id', $pedidoProduccionId)
                    ->where('prenda_id', $prendaId)
                    ->whereIn(DB::raw('UPPER(TRIM(tipo_recibo))'), ['COSTURA-BODEGA', 'CORTE-PARA-BODEGA'])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($reciboExistente) {
                    $camposActualizar = [];
                    if (empty($reciboExistente->pedido_produccion_id)) {
                        $camposActualizar['pedido_produccion_id'] = $pedidoProduccionId;
                    }
                    if (empty($reciboExistente->prenda_id)) {
                        $camposActualizar['prenda_id'] = $prendaId;
                    }
                    if (strtoupper(trim((string) ($reciboExistente->tipo_recibo ?? ''))) !== 'COSTURA-BODEGA') {
                        $camposActualizar['tipo_recibo'] = 'COSTURA-BODEGA';
                    }
                    if (strtoupper(trim((string) ($reciboExistente->area ?? ''))) !== 'INSUMOS') {
                        $camposActualizar['area'] = 'Insumos';
                    }
                    if (!empty($camposActualizar)) {
                        $camposActualizar['updated_at'] = now();
                        DB::table('consecutivos_recibos_pedidos')
                            ->where('id', $reciboExistente->id)
                            ->update($camposActualizar);
                        $reciboExistente = DB::table('consecutivos_recibos_pedidos')->find($reciboExistente->id);
                    }

                    return [
                        'recibo_id' => (int) $reciboExistente->id,
                        'numero_recibo' => isset($reciboExistente->consecutivo_actual) ? (int) $reciboExistente->consecutivo_actual : null,
                        'pedido_produccion_id' => $pedidoProduccionId,
                        'prenda_id' => $prendaId,
                        'tipo_recibo' => 'COSTURA-BODEGA',
                        'estado' => (string) ($reciboExistente->estado ?? ''),
                    ];
                }

                $registroMaestro = DB::table('consecutivos_recibos')
                    ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])
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
                        ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])
                        ->lockForUpdate()
                        ->first();
                }

                $consecutivoActual = (int) ($registroMaestro->consecutivo_actual ?? 0);
                $consecutivoInicial = (int) ($registroMaestro->consecutivo_inicial ?? 1);
                $siguienteConsecutivo = max($consecutivoActual + 1, $consecutivoInicial);
                $ahora = now();

                DB::table('consecutivos_recibos_pedidos')->insert([
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'prenda_id' => $prendaId,
                    'prenda_bodega_id' => null,
                    'tipo_recibo' => 'COSTURA-BODEGA',
                    'consecutivo_actual' => $siguienteConsecutivo,
                    'consecutivo_inicial' => $siguienteConsecutivo,
                    'activo' => 1,
                    'marcar_plooter' => 0,
                    'estado' => 'PENDIENTE_INSUMOS',
                    'area' => 'Insumos',
                    'notas' => null,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                    'ultima_actividad' => $ahora,
                ]);

                DB::table('consecutivos_recibos')
                    ->where('id', $registroMaestro->id)
                    ->update([
                        'consecutivo_actual' => $siguienteConsecutivo,
                        'updated_at' => $ahora,
                    ]);

                $reciboCreado = DB::table('consecutivos_recibos_pedidos')
                    ->where('prenda_id', $prendaId)
                    ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['COSTURA-BODEGA'])
                    ->orderByDesc('id')
                    ->first();

                return [
                    'recibo_id' => (int) $reciboCreado->id,
                    'numero_recibo' => isset($reciboCreado->consecutivo_actual) ? (int) $reciboCreado->consecutivo_actual : null,
                    'prenda_bodega_id' => null,
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'prenda_id' => $prendaId,
                    'tipo_recibo' => 'COSTURA-BODEGA',
                    'estado' => 'PENDIENTE_INSUMOS',
                ];
            });

            if (isset($resultado['error']) && $resultado['error']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['message'] ?? 'No se pudo resolver el recibo base de bodega.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Recibo base de bodega resuelto correctamente',
                'data' => $resultado,
            ]);
        } catch (\Throwable $e) {
            \Log::error('[ReciboCorteBodegaController@resolverBase] Error al resolver recibo base de bodega', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al resolver el recibo base de bodega: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $prenda = PrendaBodega::with(['tallas', 'fotos'])->find($id);

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
            'fotos' => $prenda->fotos->map(fn($f) => [
                'id' => (int) $f->id,
                'ruta' => $f->ruta,
                'url' => $this->normalizarUrlStorageLocal((string) $f->ruta),
                'orden' => (int) ($f->orden ?? 0),
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
