<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Operario\DTOs\CambiarAreaControlCalidadCommandDTO;
use App\Application\Operario\DTOs\DeshacerControlCalidadCommandDTO;
use App\Application\Operario\DTOs\DeshacerCosturaCommandDTO;
use App\Application\Operario\DTOs\LimpiarEncargadoCosturaCommandDTO;
use App\Application\Operario\DTOs\PasarACosturaCommandDTO;
use App\Application\Operario\UseCases\CambiarAreaControlCalidadUseCase;
use App\Application\Operario\UseCases\DeshacerControlCalidadUseCase;
use App\Application\Operario\UseCases\DeshacerCosturaUseCase;
use App\Application\Operario\UseCases\LimpiarEncargadoCosturaUseCase;
use App\Application\Operario\UseCases\PasarACosturaUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Events\EncargadoCosturaAsignado;
use App\Events\ControlCalidadUpdated;
use App\Events\OperarioRecibosActualizados;
use App\Events\ReciboAsignadoCosturero;
use App\Models\PedidoProduccion;
use App\Models\PedidoParcial;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PrendaBodega;
use App\Models\ProcesoPrenda;
use App\Models\ReciboPorPartes;
use App\Models\User;

class ReciboCosturaController extends Controller
{
    public function __construct(
        private readonly CambiarAreaControlCalidadUseCase $cambiarAreaControlCalidadUseCase,
        private readonly DeshacerControlCalidadUseCase $deshacerControlCalidadUseCase,
        private readonly PasarACosturaUseCase $pasarACosturaUseCase,
        private readonly DeshacerCosturaUseCase $deshacerCosturaUseCase,
        private readonly LimpiarEncargadoCosturaUseCase $limpiarEncargadoCosturaUseCase,
    ) {
        $this->middleware('auth');
    }

    private function insertReciboParteTalla(int $reciboParteId, array $tallaData, int $prendaId, bool $esBodega = false): void
    {
        $tallaNombre = strtoupper(trim((string) ($tallaData['talla'] ?? '')));
        $cantidad = (int) ($tallaData['cantidad'] ?? 0);
        $colorNombre = trim((string) ($tallaData['color_nombre'] ?? ''));
        $genero = $this->normalizarGeneroReciboParte($tallaData['genero'] ?? null);

        if ($genero === null) {
            $genero = $this->resolverGeneroParaReciboParte($prendaId, $tallaNombre, $colorNombre, $esBodega);
        }

        $payload = [
            'recibo_por_partes_id' => $reciboParteId,
            'talla' => $tallaNombre,
            'genero' => $genero !== '' ? $genero : null,
            'cantidad' => $cantidad,
            'color_nombre' => $colorNombre !== '' ? $colorNombre : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('recibos_por_partes_tallas')->insert($payload);
    }

    private function normalizarGeneroReciboParte(mixed $genero): ?string
    {
        $generoLimpio = strtoupper(trim((string) $genero));

        if ($generoLimpio === '') {
            return null;
        }

        $generoAscii = strtoupper((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $generoLimpio));
        if (in_array($generoAscii, ['SIN GENERO', 'SIN_GENERO'], true)) {
            return null;
        }

        if (in_array($generoLimpio, ['SIN GENERO', 'SIN GÉNERO', 'SIN_GENERO'], true)) {
            return null;
        }

        return $generoLimpio;
    }

    private function resolverGeneroParaReciboParte(int $prendaId, string $tallaNombre, string $colorNombre = '', bool $esBodega = false): ?string
    {
        if ($tallaNombre === '') {
            return null;
        }

        if ($esBodega) {
            $query = DB::table('prenda_tallas_bodega')
                ->where('prenda_bodega_id', $prendaId)
                ->whereRaw('UPPER(TRIM(talla)) = ?', [$tallaNombre]);

            if (trim($colorNombre) !== '') {
                $query->whereRaw('UPPER(TRIM(color)) = ?', [strtoupper(trim($colorNombre))]);
            }

            $genero = $query->value(DB::raw('UPPER(TRIM(genero))'));
            if (is_string($genero) && trim($genero) !== '') {
                return strtoupper(trim($genero));
            }

            $generoFallback = DB::table('prenda_tallas_bodega')
                ->where('prenda_bodega_id', $prendaId)
                ->whereRaw('UPPER(TRIM(talla)) = ?', [$tallaNombre])
                ->value(DB::raw('UPPER(TRIM(genero))'));

            return is_string($generoFallback) && trim($generoFallback) !== ''
                ? strtoupper(trim($generoFallback))
                : null;
        }

        $genero = DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prendaId)
            ->whereRaw('UPPER(TRIM(talla)) = ?', [$tallaNombre])
            ->value(DB::raw('UPPER(TRIM(genero))'));

        return is_string($genero) && trim($genero) !== ''
            ? strtoupper(trim($genero))
            : null;
    }

    private function normalizarTallasControlCalidad(mixed $tallasInput): array
    {
        if (is_string($tallasInput)) {
            $decoded = json_decode($tallasInput, true);
            $tallasInput = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($tallasInput)) {
            return [];
        }

        return collect($tallasInput)
            ->map(function ($talla) {
                $nombreTalla = trim((string) ($talla['talla'] ?? $talla['nombre'] ?? ''));
                $cantidad = (int) ($talla['cantidad'] ?? 0);

                return [
                    'talla' => $nombreTalla,
                    'cantidad' => $cantidad,
                    'genero' => isset($talla['genero']) ? (string) $talla['genero'] : null,
                    'color_nombre' => isset($talla['color_nombre']) ? (string) $talla['color_nombre'] : null,
                ];
            })
            ->filter(fn (array $talla) => $talla['talla'] !== '' && $talla['cantidad'] > 0)
            ->values()
            ->all();
    }

    private function normalizarTallasParaComparacion(array $tallas): array
    {
        $normalizadas = [];

        foreach ($tallas as $talla) {
            if (!is_array($talla)) {
                continue;
            }

            $clave = implode('|', [
                strtoupper(trim((string) ($talla['talla'] ?? ''))),
                strtoupper(trim((string) ($talla['genero'] ?? ''))),
                strtoupper(trim((string) ($talla['color_nombre'] ?? ''))),
            ]);

            if ($clave === '||') {
                continue;
            }

            $normalizadas[$clave] = ($normalizadas[$clave] ?? 0) + (int) ($talla['cantidad'] ?? 0);
        }

        ksort($normalizadas);

        return $normalizadas;
    }

    private function tallasCoincidenExactamente(array $tallasOriginales, array $tallasEnviadas): bool
    {
        $originales = $this->normalizarTallasParaComparacion($tallasOriginales);
        $enviadas = $this->normalizarTallasParaComparacion($tallasEnviadas);

        if (empty($originales) || empty($enviadas)) {
            return false;
        }

        return $originales === $enviadas;
    }

    private function obtenerTallasPedidoParcialParaComparacion(PedidoParcial|ReciboPorPartes $parcial): array
    {
        if ($parcial instanceof PedidoParcial) {
            return DB::table('pedidos_parciales_tallas')
                ->where('pedido_parcial_id', (int) $parcial->id)
                ->orderBy('pedidos_parciales_tallas.id')
                ->get()
                ->map(function ($talla) {
                    return [
                        'talla' => (string) ($talla->talla ?? ''),
                        'cantidad' => (int) ($talla->cantidad ?? 0),
                        'genero' => (string) ($talla->genero ?? ''),
                        'color_nombre' => (string) ($talla->color_nombre ?? ''),
                    ];
                })
                ->filter(fn (array $talla) => trim($talla['talla']) !== '' && $talla['cantidad'] > 0)
                ->values()
                ->all();
        }

        $tipoRecibo = strtoupper(trim((string) $parcial->tipo_recibo));

        if (in_array($tipoRecibo, ['CORTE-PARA-BODEGA', 'COSTURA-BODEGA'], true)) {
            return $this->obtenerTallasBodegaParaComparacion($parcial);
        }

        $tallasReciboPorPartes = DB::table('recibos_por_partes_tallas')
            ->where('recibo_por_partes_id', (int) $parcial->id)
            ->orderBy('recibos_por_partes_tallas.id')
            ->get()
            ->map(function ($talla) {
                return [
                    'talla' => (string) ($talla->talla ?? ''),
                    'cantidad' => (int) ($talla->cantidad ?? 0),
                    'genero' => (string) ($talla->genero ?? ''),
                    'color_nombre' => (string) ($talla->color_nombre ?? ''),
                ];
            })
            ->filter(fn (array $talla) => trim($talla['talla']) !== '' && $talla['cantidad'] > 0)
            ->values()
            ->all();

        if (!empty($tallasReciboPorPartes)) {
            return $tallasReciboPorPartes;
        }

        $parcialRelacionado = $this->resolverPedidoParcialRelacionadoParaReciboPorPartes($parcial);
        if ($parcialRelacionado) {
            $tallasParcial = DB::table('pedidos_parciales_tallas')
                ->where('pedido_parcial_id', (int) $parcialRelacionado->id)
                ->orderBy('pedidos_parciales_tallas.id')
                ->get()
                ->map(function ($talla) {
                    return [
                        'talla' => (string) ($talla->talla ?? ''),
                        'cantidad' => (int) ($talla->cantidad ?? 0),
                        'genero' => (string) ($talla->genero ?? ''),
                        'color_nombre' => (string) ($talla->color_nombre ?? ''),
                    ];
                })
                ->filter(fn (array $talla) => trim($talla['talla']) !== '' && $talla['cantidad'] > 0)
                ->values()
                ->all();

            if (!empty($tallasParcial)) {
                return $tallasParcial;
            }
        }

        return $this->obtenerTallasPedidoBaseParaComparacion($parcial);
    }

    private function resolverPedidoParcialRelacionadoParaReciboPorPartes(ReciboPorPartes $parcial): ?PedidoParcial
    {
        $consecutivoOriginal = (int) ($parcial->consecutivo_original ?? 0);

        $query = PedidoParcial::query()
            ->where('pedido_produccion_id', (int) $parcial->pedido_produccion_id)
            ->where('prenda_pedido_id', (int) $parcial->prenda_pedido_id)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim((string) $parcial->tipo_recibo))]);

        if ($consecutivoOriginal > 0) {
            $query->where(function ($subQuery) use ($consecutivoOriginal) {
                $subQuery->where('consecutivo_inicial', $consecutivoOriginal)
                    ->orWhere('consecutivo_actual', $consecutivoOriginal);
            });
        }

        return $query->latest('created_at')->first();
    }

    private function obtenerTallasPedidoBaseParaComparacion(ReciboPorPartes $parcial): array
    {
        return DB::table('prenda_pedido_tallas as ppt')
            ->leftJoin('prenda_pedido_talla_colores as pptc', 'pptc.prenda_pedido_talla_id', '=', 'ppt.id')
            ->where('ppt.prenda_pedido_id', (int) $parcial->prenda_pedido_id)
            ->orderBy('ppt.id')
            ->orderBy('pptc.id')
            ->get([
                'ppt.talla',
                'ppt.genero',
                DB::raw('COALESCE(pptc.color_nombre, "") as color_nombre'),
                DB::raw('COALESCE(pptc.cantidad, ppt.cantidad) as cantidad'),
            ])
            ->map(function ($talla) {
                return [
                    'talla' => (string) ($talla->talla ?? ''),
                    'cantidad' => (int) ($talla->cantidad ?? 0),
                    'genero' => (string) ($talla->genero ?? ''),
                    'color_nombre' => (string) ($talla->color_nombre ?? ''),
                ];
            })
            ->filter(fn (array $talla) => trim($talla['talla']) !== '' && $talla['cantidad'] > 0)
            ->values()
            ->all();
    }

    private function obtenerTallasBodegaParaComparacion(ReciboPorPartes $parcial): array
    {
        return DB::table('prenda_tallas_bodega')
            ->where('prenda_bodega_id', (int) $parcial->prenda_pedido_id)
            ->orderBy('prenda_tallas_bodega.id')
            ->get([
                'talla',
                'genero',
                DB::raw('COALESCE(color, "") as color_nombre'),
                'cantidad',
            ])
            ->map(function ($talla) {
                return [
                    'talla' => (string) ($talla->talla ?? ''),
                    'cantidad' => (int) ($talla->cantidad ?? 0),
                    'genero' => (string) ($talla->genero ?? ''),
                    'color_nombre' => (string) ($talla->color_nombre ?? ''),
                ];
            })
            ->filter(fn (array $talla) => trim($talla['talla']) !== '' && $talla['cantidad'] > 0)
            ->values()
            ->all();
    }

    private function guardarTallasControlCalidadDetalle(int $completadoId, array $tallas): void
    {
        if ($completadoId <= 0) {
            return;
        }

        DB::table('prenda_recibo_completado_tallas')
            ->where('prenda_recibo_completado_id', $completadoId)
            ->delete();

        if (empty($tallas)) {
            return;
        }

        $ahora = now();
        $registros = collect($tallas)
            ->map(function (array $talla) use ($completadoId, $ahora) {
                return [
                    'prenda_recibo_completado_id' => $completadoId,
                    'talla' => (string) ($talla['talla'] ?? ''),
                    'cantidad' => (int) ($talla['cantidad'] ?? 0),
                    'genero' => isset($talla['genero']) ? (string) $talla['genero'] : null,
                    'color_nombre' => isset($talla['color_nombre']) ? (string) $talla['color_nombre'] : null,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            })
            ->filter(fn (array $registro) => $registro['talla'] !== '' && $registro['cantidad'] > 0)
            ->values()
            ->all();

        if (!empty($registros)) {
            DB::table('prenda_recibo_completado_tallas')->insert($registros);
        }
    }

    public function distribuirPorModulos(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta accion'
                ], 403);
            }

            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo')));
            $esBodega = $tipoRecibo === 'CORTE-PARA-BODEGA';

            $rules = [
                'tipo_recibo' => 'required|string',
                'es_edicion' => 'nullable|boolean',
                'asignaciones' => 'required|array|min:1',
                'asignaciones.*.encargado' => 'required|string|max:100',
                'asignaciones.*.tallas' => 'required|array|min:1',
                'asignaciones.*.tallas.*.talla' => 'required|string|max:50',
                'asignaciones.*.tallas.*.cantidad' => 'required|integer|min:1',
                'asignaciones.*.tallas.*.color_nombre' => 'nullable|string|max:191',
                'asignaciones.*.tallas.*.genero' => 'nullable|string|max:50',
            ];

            if ($esBodega) {
                $rules['prenda_bodega_id'] = 'required|integer|exists:prenda_bodega,id';
            } else {
                $rules['prenda_id'] = 'required|integer|exists:prendas_pedido,id';
            }

            $request->validate($rules);

            $pedido = PedidoProduccion::findOrFail((int) $pedidoId);
            $prendaId = (int) $request->input('prenda_id', $request->input('prenda_bodega_id', 0));
            $prendaBodegaId = $esBodega ? (int) $request->input('prenda_bodega_id', $prendaId) : null;
            $consecutivoOriginal = (int) $numeroRecibo;
            $esEdicion = (bool) $request->boolean('es_edicion');

            Log::info('[COSTURA][DISTRIBUIR] Solicitud recibida', [
                'pedido_id' => (int) $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $prendaId,
                'tipo_recibo' => $tipoRecibo,
                'consecutivo_original' => $consecutivoOriginal,
                'es_edicion' => $esEdicion,
                'asignaciones_count' => count((array) $request->asignaciones),
            ]);

            $recibo = ConsecutivoReciboPedido::query()
                ->where('pedido_produccion_id', (int) $pedidoId)
                ->where('consecutivo_actual', $consecutivoOriginal)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoRecibo))])
                ->where('activo', 1)
                ->first();

            if (!$recibo && !$esBodega) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }

            if (!$recibo && $esBodega) {
                $prendaBodega = PrendaBodega::findOrFail($prendaBodegaId);
                $recibo = (object) [
                    'id' => (int) $prendaBodega->id,
                    'tipo_recibo' => $tipoRecibo,
                    'area' => 'Costura',
                ];
            }

            $tipoReciboReal = $esBodega ? $tipoRecibo : (string) $recibo->tipo_recibo;

            $resultado = DB::transaction(function () use ($pedido, $recibo, $pedidoId, $prendaId, $prendaBodegaId, $esBodega, $tipoReciboReal, $consecutivoOriginal, $request, $esEdicion) {
                $prendaColumn = $esBodega ? 'prenda_bodega_id' : 'prenda_pedido_id';
                // Buscar el proceso padre de Costura de forma mas flexible
                // El proceso padre ya debe existir, solo necesitamos localizarlo
                $procesoPadre = ProcesoPrenda::query()
                    ->where('numero_pedido', $pedido->numero_pedido)
                    ->where($prendaColumn, $prendaId)
                    ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                    ->where('numero_recibo', $consecutivoOriginal)
                    ->where(function ($query) {
                        // El proceso padre NO debe tener numero_recibo_parcial
                        $query->whereNull('numero_recibo_parcial')
                            ->orWhere('numero_recibo_parcial', 0);
                    })
                    ->whereNull('deleted_at')
                    ->orderByDesc('created_at')
                    ->first();

                Log::info('[COSTURA][DISTRIBUIR] Busqueda proceso padre', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'numero_recibo' => $consecutivoOriginal,
                    'proceso_padre_encontrado' => $procesoPadre ? $procesoPadre->id : null,
                ]);

                if (!$procesoPadre) {
                    // Si no existe, significa que el recibo nunca fue enviado a Costura
                    // Crear el proceso padre sin numero_recibo (es un placeholder)
                    $procesoPadre = ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $esBodega ? null : $prendaId,
                        'prenda_bodega_id' => $esBodega ? $prendaBodegaId : null,
                        'numero_recibo' => $consecutivoOriginal,
                        'numero_recibo_parcial' => null,
                        'proceso' => 'Costura',
                        'fecha_inicio' => now(),
                        'encargado' => null,
                        'estado_proceso' => 'Pendiente',
                        'codigo_referencia' => 'COS-' . $consecutivoOriginal . '-' . date('YmdHis'),
                    ]);

                    Log::info('[COSTURA][DISTRIBUIR] Proceso padre creado', [
                        'proceso_padre_id' => $procesoPadre->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_id' => $prendaId,
                    ]);
                } else {
                    // Si ya existe, asegurarse de que el Area del recibo esta en Costura
                    if (!$esBodega && method_exists($recibo, 'save')) {
                        $recibo->area = 'Costura';
                        $recibo->save();
                    }

                    Log::info('[COSTURA][DISTRIBUIR] Proceso padre ya existi­a, reutilizado', [
                        'proceso_padre_id' => $procesoPadre->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_id' => $prendaId,
                    ]);
                }

                $parcialesExistentes = DB::table('recibo_por_partes')
                    ->where('pedido_produccion_id', (int) $pedidoId)
                    ->where('prenda_pedido_id', $prendaId)
                    ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoReciboReal))])
                    ->where('consecutivo_original', $consecutivoOriginal)
                    ->get(['id', 'consecutivo_parcial', 'estado']);

                // Mapear parcial existente -> encargado (desde proceso hijo)
                $parcialesConEncargado = [];
                foreach ($parcialesExistentes as $parcialExistente) {
                    $estadoParcial = strtoupper(trim((string) ($parcialExistente->estado ?? '')));
                    if ($estadoParcial === 'ANULADO') {
                        continue;
                    }

                    $procesoHijoExistente = ProcesoPrenda::query()
                        ->where('numero_pedido', $pedido->numero_pedido)
                        ->where($prendaColumn, $prendaId)
                        ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                        ->where('numero_recibo_parcial', $parcialExistente->consecutivo_parcial)
                        ->whereNull('deleted_at')
                        ->orderByDesc('created_at')
                        ->first();

                    $encargadoNorm = strtolower(trim((string) ($procesoHijoExistente->encargado ?? '')));
                    if ($encargadoNorm !== '') {
                        $parcialesConEncargado[$encargadoNorm] = [
                            'id' => (int) $parcialExistente->id,
                            'consecutivo_parcial' => (string) $parcialExistente->consecutivo_parcial,
                            'estado' => (string) ($parcialExistente->estado ?? ''),
                        ];
                    }
                }

                $maxParcialExistente = DB::table('recibo_por_partes')
                    ->where('pedido_produccion_id', (int) $pedidoId)
                    ->where('prenda_pedido_id', $prendaId)
                    ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoReciboReal))])
                    ->where('consecutivo_original', $consecutivoOriginal)
                    ->max('consecutivo_parcial');

                $siguienteConsecutivoParcial = $this->obtenerSiguienteConsecutivoParcial($consecutivoOriginal, $maxParcialExistente);

                $creados = [];

                foreach ((array) $request->asignaciones as $asig) {
                    $encargado = trim((string) ($asig['encargado'] ?? ''));
                    $tallas = (array) ($asig['tallas'] ?? []);

                    if ($encargado === '' || empty($tallas)) {
                        continue;
                    }

                    // No se sobreescriben parciales existentes: siempre crear un nuevo consecutivo.
                    $consecutivoParcialDb = $this->formatearConsecutivoParcial($siguienteConsecutivoParcial);
                    $siguienteConsecutivoParcial = round($siguienteConsecutivoParcial + 0.1, 1);

                    $procesoHijo = ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $esBodega ? null : $prendaId,
                        'prenda_bodega_id' => $esBodega ? $prendaBodegaId : null,
                        'numero_recibo' => null,
                        'numero_recibo_parcial' => $consecutivoParcialDb,
                        'proceso' => 'Costura',
                        'fecha_inicio' => now(),
                        'encargado' => $encargado,
                        'fecha_de_asignacion_encargado' => now(),
                        'estado_proceso' => 'En Progreso',
                        'codigo_referencia' => 'COS-' . $consecutivoParcialDb . '-' . date('YmdHis'),
                    ]);

                    $reciboParteId = DB::table('recibo_por_partes')->insertGetId([
                        'pedido_produccion_id' => (int) $pedidoId,
                        'prenda_pedido_id' => $prendaId,
                        'tipo_recibo' => $tipoReciboReal,
                        'consecutivo_original' => $consecutivoOriginal,
                        'consecutivo_parcial' => $consecutivoParcialDb,
                        'estado' => 'En ejecución',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($tallas as $t) {
                        $talla = trim((string) ($t['talla'] ?? ''));
                        $cantidad = (int) ($t['cantidad'] ?? 0);
                        $colorNombre = isset($t['color_nombre']) ? (string) $t['color_nombre'] : null;
                        if ($talla === '' || $cantidad <= 0) {
                            continue;
                        }

                        $this->insertReciboParteTalla($reciboParteId, [
                            'talla' => $talla,
                            'cantidad' => $cantidad,
                            'genero' => isset($t['genero']) ? (string) $t['genero'] : null,
                            'color_nombre' => $colorNombre,
                        ], $prendaId, $esBodega);
                    }

                    $creados[] = [
                        'proceso_id' => (int) $procesoHijo->id,
                        'numero_recibo' => null,
                        'numero_recibo_parcial' => $consecutivoParcialDb,
                        'parcial_id' => (int) $reciboParteId,
                        'encargado' => $encargado,
                    ];
                }

                return [
                    'proceso_padre_id' => (int) $procesoPadre->id,
                    'hijos' => $creados,
                    'recibo_id' => (int) $recibo->id,
                ];
            });

            try {
                $this->notificarParcialesDistribuidos(
                    pedido: $pedido,
                    prendaId: $prendaId,
                    tipoRecibo: $tipoReciboReal,
                    parcialesCreados: (array) ($resultado['hijos'] ?? [])
                );
            } catch (\Throwable $broadcastError) {
                Log::warning('[COSTURA][DISTRIBUIR] Distribucion guardada sin notificacion en tiempo real', [
                    'pedido_id' => (int) $pedidoId,
                    'numero_recibo' => $numeroRecibo,
                    'error' => $broadcastError->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Distribucion del recibo guardada correctamente',
                'data' => $resultado,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validacion',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[COSTURA][DISTRIBUIR] Error', [
                'pedido_id' => $pedidoId,
                'numero_recibo' => $numeroRecibo,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al distribuir por modulos: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function notificarParcialesDistribuidos(PedidoProduccion $pedido, int $prendaId, string $tipoRecibo, array $parcialesCreados): void
    {
        if (empty($parcialesCreados)) {
            return;
        }

        $prenda = \App\Models\PrendaPedido::find($prendaId);
        $nombrePrenda = (string) ($prenda?->nombre_prenda ?? 'Prenda sin nombre');
        $cliente = (string) ($pedido->cliente ?? '-');
        $tipoReciboUpper = strtoupper(trim($tipoRecibo));

        $usuariosAdminCostura = User::query()->get()->filter(function ($user) {
            return $user->hasRole('administrador-costura');
        })->values();

        $usuariosVistaCostura = User::query()->get()->filter(function ($user) {
            return $user->hasRole('vista-costura');
        })->values();

        foreach ($parcialesCreados as $parcial) {
            $encargado = trim((string) ($parcial['encargado'] ?? ''));
            $numeroReciboParcial = trim((string) ($parcial['numero_recibo_parcial'] ?? ''));
            $procesoId = (int) ($parcial['proceso_id'] ?? 0);
            $parcialId = (int) ($parcial['parcial_id'] ?? 0);

            if ($encargado === '' || $numeroReciboParcial === '' || $parcialId <= 0) {
                continue;
            }

            $mensajeAsignado = "Se te asignó el recibo parcial #{$numeroReciboParcial} de {$nombrePrenda}";
            $mensajeGlobal = "El recibo parcial #{$numeroReciboParcial} ({$nombrePrenda}) fue asignado a {$encargado}";

            $encargadoUsuario = User::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($encargado)])
                ->first();

            $encargadoRol = null;
            try {
                $encargadoRol = $encargadoUsuario?->roles?->first()?->name;
            } catch (\Exception $e) {
                $encargadoRol = null;
            }

            broadcast(new EncargadoCosturaAsignado(
                $pedido->id,
                $prendaId,
                $numeroReciboParcial,
                $encargado,
                $procesoId,
                $nombrePrenda,
                now()->toIso8601String(),
                null,
                $cliente,
                $encargadoRol
            ));

            if ($encargadoUsuario) {
                broadcast(new ReciboAsignadoCosturero(
                    $pedido->id,
                    $prendaId,
                    $numeroReciboParcial,
                    $nombrePrenda,
                    $encargado,
                    $procesoId,
                    $encargado
                ));

                broadcast(new OperarioRecibosActualizados(
                    userId: (int) $encargadoUsuario->id,
                    payload: [
                        'area' => 'Costura',
                        'accion' => 'asignado',
                        'pedido_id' => (int) $pedido->id,
                        'numero_pedido' => (int) $pedido->numero_pedido,
                        'prenda_id' => $prendaId,
                        'proceso_id' => $procesoId,
                        'tipo_recibo' => 'PARCIAL',
                        'numero_recibo' => $numeroReciboParcial,
                        'pedido_parcial_id' => $parcialId,
                        'es_parcial' => true,
                        'encargado' => $encargado,
                        'mensaje' => $mensajeAsignado,
                    ]
                ));
            }

            foreach ($usuariosAdminCostura as $usuarioAdmin) {
                Log::info('[COSTURA][DISTRIBUIR] Notificando parcial a administrador-costura', [
                    'user_id' => (int) $usuarioAdmin->id,
                    'user_name' => (string) $usuarioAdmin->name,
                    'parcial_id' => $parcialId,
                    'numero_recibo_parcial' => $numeroReciboParcial,
                ]);

                broadcast(new OperarioRecibosActualizados(
                    userId: (int) $usuarioAdmin->id,
                    payload: [
                        'area' => 'Costura',
                        'accion' => 'asignado',
                        'pedido_id' => (int) $pedido->id,
                        'numero_pedido' => (int) $pedido->numero_pedido,
                        'prenda_id' => $prendaId,
                        'proceso_id' => $procesoId,
                        'tipo_recibo' => 'PARCIAL',
                        'numero_recibo' => $numeroReciboParcial,
                        'pedido_parcial_id' => $parcialId,
                        'es_parcial' => true,
                        'encargado' => $encargado,
                        'mensaje' => $mensajeGlobal,
                    ]
                ));
            }

            foreach ($usuariosVistaCostura as $usuarioVista) {
                Log::info('[COSTURA][DISTRIBUIR] Notificando parcial a vista-costura', [
                    'user_id' => (int) $usuarioVista->id,
                    'user_name' => (string) $usuarioVista->name,
                    'parcial_id' => $parcialId,
                    'numero_recibo_parcial' => $numeroReciboParcial,
                ]);

                broadcast(new OperarioRecibosActualizados(
                    userId: (int) $usuarioVista->id,
                    payload: [
                        'area' => 'Costura',
                        'accion' => 'asignado',
                        'pedido_id' => (int) $pedido->id,
                        'numero_pedido' => (int) $pedido->numero_pedido,
                        'prenda_id' => $prendaId,
                        'proceso_id' => $procesoId,
                        'tipo_recibo' => 'PARCIAL',
                        'numero_recibo' => $numeroReciboParcial,
                        'pedido_parcial_id' => $parcialId,
                        'es_parcial' => true,
                        'encargado' => $encargado,
                        'mensaje' => $mensajeGlobal,
                    ]
                ));
            }

            if ($tipoReciboUpper === 'REFLECTIVO') {
                $usuariosReflectivos = User::all()->filter(function ($user) {
                    return $user->hasRole('costura-reflectivo') || $user->hasRole('lider-reflectivo');
                });

                foreach ($usuariosReflectivos as $usuarioReflectivo) {
                    broadcast(new OperarioRecibosActualizados(
                        userId: (int) $usuarioReflectivo->id,
                        payload: [
                            'area' => 'Costura',
                            'accion' => 'recibo_asignado_reflectivo',
                            'pedido_id' => (int) $pedido->id,
                            'numero_pedido' => (int) $pedido->numero_pedido,
                            'prenda_id' => $prendaId,
                            'proceso_id' => $procesoId,
                            'tipo_recibo' => 'REFLECTIVO',
                            'numero_recibo' => $numeroReciboParcial,
                            'pedido_parcial_id' => $parcialId,
                            'es_parcial' => true,
                            'encargado' => $encargado,
                            'mensaje' => "El recibo parcial #{$numeroReciboParcial} de REFLECTIVO fue asignado a {$encargado}",
                        ]
                    ));
                }
            }

            if (in_array($tipoReciboUpper, ['COSTURA', 'COSTURA-BODEGA'], true) && $encargadoUsuario && $encargadoUsuario->hasRole('costura-reflectivo')) {
                $usuariosLiderReflectivo = User::all()->filter(function ($user) {
                    return $user->hasRole('lider-reflectivo');
                });

                foreach ($usuariosLiderReflectivo as $usuarioLider) {
                    broadcast(new OperarioRecibosActualizados(
                        userId: (int) $usuarioLider->id,
                        payload: [
                            'area' => 'Costura',
                            'accion' => 'recibo_asignado_costura',
                            'pedido_id' => (int) $pedido->id,
                            'numero_pedido' => (int) $pedido->numero_pedido,
                            'prenda_id' => $prendaId,
                            'proceso_id' => $procesoId,
                            'tipo_recibo' => $tipoReciboUpper,
                            'numero_recibo' => $numeroReciboParcial,
                            'pedido_parcial_id' => $parcialId,
                            'es_parcial' => true,
                            'encargado' => $encargado,
                            'mensaje' => "El recibo parcial #{$numeroReciboParcial} de {$tipoReciboUpper} fue asignado a {$encargado}",
                        ]
                    ));
                }
            }
        }
    }

    public function limpiarEncargadoCostura(Request $request, $pedidoId, $prendaId)
    {
        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta accion'
                ], 403);
            }

            $request->validate([
                'tipo_recibo' => 'required|string'
            ]);

            $resultado = $this->limpiarEncargadoCosturaUseCase->execute(new LimpiarEncargadoCosturaCommandDTO(
                pedidoId: (int) $pedidoId,
                prendaId: (int) $prendaId,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);
        } catch (\Exception $e) {
            Log::error('Error limpiando encargado de Costura', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar encargado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar Area de recibo a Control Calidad
     */
    public function cambiarAreaControlCalidad(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            // Solo vista-costura puede hacer esto
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta accion'
                ], 403);
            }

            if ($request->boolean('es_parcial')) {
                return $this->cambiarAreaControlCalidadParcial($request, (int) $pedidoId);
            }

            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo')));
            $esBodega = $tipoRecibo === 'CORTE-PARA-BODEGA';
            $tallasControlCalidad = $this->normalizarTallasControlCalidad($request->input('tallas_control_calidad'));

            $rules = [
                'tipo_recibo' => 'required|string',
                'tallas_control_calidad' => 'required|array|min:1',
                'tallas_control_calidad.*.talla' => 'required|string|max:50',
                'tallas_control_calidad.*.cantidad' => 'required|integer|min:1',
                'tallas_control_calidad.*.genero' => 'nullable|string|max:50',
                'tallas_control_calidad.*.color_nombre' => 'nullable|string|max:191',
            ];

            if ($esBodega) {
                $rules['prenda_bodega_id'] = 'required|integer|exists:prenda_bodega,id';
            } else {
                $rules['prenda_id'] = 'required|integer|exists:prendas_pedido,id';
            }

            $request->merge([
                'tallas_control_calidad' => $tallasControlCalidad,
            ]);
            $request->validate($rules);

            $prendaId = (int) $request->input('prenda_id', 0);
            $prendaBodegaId = $esBodega ? (int) $request->input('prenda_bodega_id') : null;

            $resultado = $this->cambiarAreaControlCalidadUseCase->execute(new CambiarAreaControlCalidadCommandDTO(
                pedidoId: (int) $pedidoId,
                numeroRecibo: (int) $numeroRecibo,
                prendaId: $prendaId,
                prendaBodegaId: $prendaBodegaId,
                tipoRecibo: (string) $request->tipo_recibo,
                tallasControlCalidad: $tallasControlCalidad,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error cambiando Area de recibo a Control Calidad', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el Area: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deshacer el cambio a Control Calidad - eliminar proceso y restaurar Area anterior
     */
    public function deshacerControlCalidad(Request $request, $pedidoId, $prendaId)
    {
        try {
            // Solo vista-costura puede hacer esto
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta accion'
                ], 403);
            }

            if ($request->boolean('es_parcial')) {
                return $this->deshacerControlCalidadParcial($request, (int) $pedidoId, (int) $prendaId);
            }

            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo')));
            $esBodega = $tipoRecibo === 'CORTE-PARA-BODEGA';

            $rules = [
                'tipo_recibo' => 'required|string'
            ];

            if ($esBodega) {
                $rules['prenda_bodega_id'] = 'required|integer|exists:prenda_bodega,id';
            }

            $request->validate($rules);

            $prendaBodegaId = $esBodega ? (int) $request->input('prenda_bodega_id', $prendaId) : null;
            $prendaIdReal = $esBodega ? 0 : (int) $prendaId;

            $resultado = $this->deshacerControlCalidadUseCase->execute(new DeshacerControlCalidadCommandDTO(
                pedidoId: (int) $pedidoId,
                prendaId: $prendaIdReal,
                prendaBodegaId: $prendaBodegaId,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error deshaciendo Control de Calidad', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer: ' . $e->getMessage()
            ], 500);
        }
    }

    private function resolverParcialParaControlCalidad(
        int $pedidoId,
        int $prendaId,
        string $tipoRecibo,
        int $parcialId
    ): PedidoParcial|ReciboPorPartes|null {
        $parcialNuevo = PedidoParcial::query()
            ->where('id', $parcialId)
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoRecibo])
            ->first();

        if ($parcialNuevo) {
            return $parcialNuevo;
        }

        return ReciboPorPartes::query()
            ->where('id', $parcialId)
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoRecibo])
            ->first();
    }

    /**
     * Pasar recibo a Costura - crea proceso con encargado y actualiza Area
     */
    private function cambiarAreaControlCalidadParcial(Request $request, int $pedidoId)
    {
        $tallasControlCalidad = $this->normalizarTallasControlCalidad($request->input('tallas_control_calidad'));
        $request->merge([
            'tallas_control_calidad' => $tallasControlCalidad,
        ]);
        $request->validate([
            'prenda_id' => 'required|integer|exists:prendas_pedido,id',
            'tipo_recibo' => 'required|string',
            'parcial_id' => 'required|integer|min:1',
            'tallas_control_calidad' => 'required|array|min:1',
            'tallas_control_calidad.*.talla' => 'required|string|max:50',
            'tallas_control_calidad.*.cantidad' => 'required|integer|min:1',
            'tallas_control_calidad.*.genero' => 'nullable|string|max:50',
            'tallas_control_calidad.*.color_nombre' => 'nullable|string|max:191',
        ]);

        $pedido = PedidoProduccion::findOrFail($pedidoId);
        $tipoRecibo = strtoupper(trim((string) $request->tipo_recibo));

        $parcial = $this->resolverParcialParaControlCalidad(
            $pedidoId,
            (int) $request->prenda_id,
            $tipoRecibo,
            (int) $request->parcial_id
        );

        if (!$parcial) {
            return response()->json([
                'success' => false,
                'message' => 'Parcial no encontrado',
            ], 404);
        }

        $tallasOriginalesParcial = $this->obtenerTallasPedidoParcialParaComparacion($parcial);
        if (!$this->tallasCoincidenExactamente($tallasOriginalesParcial, $tallasControlCalidad)) {
            return response()->json([
                'success' => false,
                'message' => 'Las tallas enviadas no coinciden exactamente con las tallas registradas en el parcial',
            ], 422);
        }

        $consecutivoParcial = (int) ($parcial->consecutivo_parcial ?? $parcial->consecutivo_actual ?? 0);

        try {
            DB::beginTransaction();

            $procesoExistente = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->where(function ($query) use ($consecutivoParcial) {
                    $query->where('numero_recibo_parcial', $consecutivoParcial)
                        ->orWhere(function ($query) use ($consecutivoParcial) {
                            $query->whereNull('numero_recibo_parcial')
                                ->where('codigo_referencia', 'like', 'CCP-%')
                                ->where('numero_recibo', $consecutivoParcial);
                        });
                })
                ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->latest('created_at')
                ->first();

            if ($procesoExistente) {
                if ((int) ($procesoExistente->numero_recibo_parcial ?? 0) !== $consecutivoParcial) {
                    $procesoExistente->update([
                        'numero_recibo' => null,
                        'numero_recibo_parcial' => $consecutivoParcial,
                    ]);
                }

                DB::table('prenda_recibo_completado')->updateOrInsert(
                    [
                        'id_parcial' => (int) $parcial->id,
                        'area' => 'Control Calidad',
                    ],
                    [
                        'id_recibo' => (int) $parcial->id,
                        'numero_recibo' => $consecutivoParcial,
                        'nombre_operario' => (string) (auth()->user()->name ?? 'control'),
                        'fecha_completado' => now(),
                        'tallas_control_calidad' => !empty($tallasControlCalidad)
                            ? json_encode(array_values($tallasControlCalidad), JSON_UNESCAPED_UNICODE)
                            : null,
                    ]
                );

                $completado = DB::table('prenda_recibo_completado')
                    ->where('id_recibo', (int) $parcial->id)
                    ->where('area', 'Control Calidad')
                    ->first('id');
                if ($completado) {
                    $this->guardarTallasControlCalidadDetalle((int) $completado->id, $tallasControlCalidad);
                }

                $estadoParcialesCc = $this->sincronizarProcesoControlCalidadOriginal($pedido, $parcial);
                DB::commit();

                $this->notificarVistaCosturaCambioControlCalidadParcial($pedido, $parcial, $estadoParcialesCc, true);

                return response()->json([
                    'success' => true,
                    'message' => 'El parcial ya estaba en Control de Calidad',
                    'data' => [
                        'proceso_id' => $procesoExistente->id,
                        'area_nueva' => 'Control Calidad',
                        'parcial_id' => $parcial->id,
                        'consecutivo_parcial' => (string) $consecutivoParcial,
                        'total_parciales' => $estadoParcialesCc['total_parciales'],
                        'parciales_en_cc' => $estadoParcialesCc['parciales_en_cc'],
                        'todos_parciales_en_cc' => $estadoParcialesCc['todos_parciales_en_cc'],
                    ],
                ]);
            }

                $nuevoProceso = ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $parcial->prenda_pedido_id,
                    'numero_recibo' => null,
                    'numero_recibo_parcial' => $consecutivoParcial,
                    'proceso' => 'Control de Calidad',
                    'fecha_inicio' => now(),
                    'encargado' => 'control',
                    'estado_proceso' => 'En Progreso',
                    'codigo_referencia' => 'CCP-' . $consecutivoParcial . '-' . date('YmdHis'),
                ]);

            $estadoParcialesCc = $this->sincronizarProcesoControlCalidadOriginal($pedido, $parcial);

            DB::table('prenda_recibo_completado')->updateOrInsert(
                [
                    'id_parcial' => (int) $parcial->id,
                    'area' => 'Control Calidad',
                ],
                [
                    'id_recibo' => (int) $parcial->id,
                    'numero_recibo' => $consecutivoParcial,
                    'nombre_operario' => (string) (auth()->user()->name ?? 'control'),
                    'fecha_completado' => now(),
                    'tallas_control_calidad' => !empty($tallasControlCalidad)
                        ? json_encode(array_values($tallasControlCalidad), JSON_UNESCAPED_UNICODE)
                        : null,
                ]
            );

            $completado = DB::table('prenda_recibo_completado')
                ->where('id_recibo', (int) $parcial->id)
                ->where('area', 'Control Calidad')
                ->first('id');
            if ($completado) {
                $this->guardarTallasControlCalidadDetalle((int) $completado->id, $tallasControlCalidad);
            }

            DB::commit();

            try {
                $prenda = \App\Models\PrendaPedido::find($parcial->prenda_pedido_id);
                broadcast(new ControlCalidadUpdated([
                    'id' => (int) $parcial->id,
                    'pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'nombre_prenda' => $prenda?->nombre_prenda,
                    'descripcion' => $prenda?->descripcion,
                    'tipo_recibo' => $parcial->tipo_recibo,
                    'consecutivo_actual' => (string) ($parcial->getRawOriginal('consecutivo_parcial') ?? $parcial->consecutivo_parcial ?? $parcial->consecutivo_actual ?? ''),
                    'consecutivo_original' => (string) ($parcial->getRawOriginal('consecutivo_original') ?? $parcial->consecutivo_original ?? $parcial->consecutivo_inicial ?? ''),
                    'es_parcial' => true,
                    'parcial_id' => (int) $parcial->id,
                    'completado_area' => false,
                    'area' => 'Control Calidad',
                    'proceso_actual' => 'Control Calidad',
                    'fecha_creacion' => now()->toISOString(),
                    'numero_pedido' => $pedido->numero_pedido,
                ], 'added', 'parcial'));
            } catch (\Throwable $e) {
                Log::warning('[COSTURA][DISTRIBUIR] Error broadcast ControlCalidadUpdated parcial', [
                    'parcial_id' => (int) $parcial->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->notificarVistaCosturaCambioControlCalidadParcial($pedido, $parcial, $estadoParcialesCc, true);

            return response()->json([
                    'success' => true,
                    'message' => 'Parcial enviado a Control de Calidad correctamente',
                    'data' => [
                        'proceso_id' => $nuevoProceso->id,
                        'area_nueva' => 'Control Calidad',
                        'parcial_id' => $parcial->id,
                        'consecutivo_parcial' => (string) $consecutivoParcial,
                        'total_parciales' => $estadoParcialesCc['total_parciales'],
                        'parciales_en_cc' => $estadoParcialesCc['parciales_en_cc'],
                        'todos_parciales_en_cc' => $estadoParcialesCc['todos_parciales_en_cc'],
                    ],
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error cambiando Area de parcial a Control Calidad', [
                'pedido_id' => $pedidoId,
                'parcial_id' => (int) $request->parcial_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el Area del parcial: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function deshacerControlCalidadParcial(Request $request, int $pedidoId, int $prendaId)
    {
        $request->validate([
            'tipo_recibo' => 'required|string',
            'parcial_id' => 'required|integer|min:1',
        ]);

        $pedido = PedidoProduccion::findOrFail($pedidoId);
        $tipoRecibo = strtoupper(trim((string) $request->tipo_recibo));

        $parcial = $this->resolverParcialParaControlCalidad($pedidoId, $prendaId, $tipoRecibo, (int) $request->parcial_id);

        if (!$parcial) {
            return response()->json([
                'success' => false,
                'message' => 'Parcial no encontrado',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $consecutivoParcial = (int) ($parcial->consecutivo_parcial ?? $parcial->consecutivo_actual ?? 0);

            $procesoCC = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->where(function ($query) use ($consecutivoParcial) {
                    $query->where('numero_recibo_parcial', $consecutivoParcial)
                        ->orWhere(function ($query) use ($consecutivoParcial) {
                            $query->whereNull('numero_recibo_parcial')
                                ->where('codigo_referencia', 'like', 'CCP-%')
                                ->where('numero_recibo', $consecutivoParcial);
                        });
                })
                ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->latest('created_at')
                ->first();

            if (!$procesoCC) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'No se encontro proceso de Control de Calidad para este parcial',
                ], 404);
            }

            $procesoAnterior = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->where('numero_recibo_parcial', $consecutivoParcial)
                ->whereRaw('LOWER(TRIM(proceso)) NOT IN (?, ?)', ['control calidad', 'control de calidad'])
                ->latest('created_at')
                ->first();

            $areaAnterior = $procesoAnterior?->proceso ?: 'Costura';

            $procesoCC->forceDelete();

            $estadoParcialesCc = $this->sincronizarProcesoControlCalidadOriginal($pedido, $parcial);

            DB::commit();

            try {
                broadcast(new ControlCalidadUpdated([
                    'id' => (int) $parcial->id,
                    'pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'nombre_prenda' => $parcial->prenda?->nombre_prenda,
                    'descripcion' => $parcial->prenda?->descripcion,
                    'tipo_recibo' => $parcial->tipo_recibo,
                    'consecutivo_actual' => (string) ($parcial->getRawOriginal('consecutivo_parcial') ?? $consecutivoParcial),
                    'consecutivo_original' => (string) ($parcial->getRawOriginal('consecutivo_original') ?? $parcial->consecutivo_original ?? $parcial->consecutivo_inicial ?? ''),
                    'es_parcial' => true,
                    'parcial_id' => (int) $parcial->id,
                    'completado_area' => false,
                    'area' => 'Costura',
                    'proceso_actual' => $areaAnterior,
                    'fecha_creacion' => now()->toISOString(),
                    'numero_pedido' => $pedido->numero_pedido,
                ], 'removed', 'parcial'));
            } catch (\Throwable $e) {
                Log::warning('[COSTURA][DISTRIBUIR] Error broadcast ControlCalidadUpdated parcial removido', [
                    'parcial_id' => (int) $parcial->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->notificarVistaCosturaCambioControlCalidadParcial($pedido, $parcial, $estadoParcialesCc, false);

            return response()->json([
                'success' => true,
                'message' => 'Control de Calidad del parcial deshecho correctamente',
                'data' => [
                    'area_nueva' => $areaAnterior,
                    'parcial_id' => $parcial->id,
                    'consecutivo_parcial' => (string) $parcial->consecutivo_parcial,
                    'total_parciales' => $estadoParcialesCc['total_parciales'],
                    'parciales_en_cc' => $estadoParcialesCc['parciales_en_cc'],
                    'todos_parciales_en_cc' => $estadoParcialesCc['todos_parciales_en_cc'],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deshaciendo Control de Calidad de parcial', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'parcial_id' => (int) $request->parcial_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer el Control de Calidad del parcial: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function sincronizarProcesoControlCalidadOriginal(PedidoProduccion $pedido, mixed $parcial): array
    {
        $esParcialNuevo = $parcial instanceof PedidoParcial;
        $tablaParciales = $esParcialNuevo ? 'pedidos_parciales' : 'recibo_por_partes';
        $campoOriginal = $esParcialNuevo ? 'consecutivo_inicial' : 'consecutivo_original';
        $campoParcial = $esParcialNuevo ? 'consecutivo_actual' : 'consecutivo_parcial';

        $parcialesRelacionados = DB::table($tablaParciales)
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim((string) $parcial->tipo_recibo))])
            ->where($campoOriginal, $esParcialNuevo ? $parcial->consecutivo_inicial : $parcial->consecutivo_original)
            ->get(['id', $campoParcial]);

        $totalParciales = $parcialesRelacionados->count();
        $consecutivosParciales = $parcialesRelacionados
            ->pluck($campoParcial)
            ->filter(fn($valor) => $valor !== null && $valor !== '')
            ->values();

        $parcialesEnCc = $parcialesRelacionados->filter(function ($parcialRelacionado) use ($pedido, $parcial, $campoParcial) {
            $consecutivoParcial = (int) ($parcialRelacionado->{$campoParcial} ?? 0);

            if ($consecutivoParcial <= 0) {
                return false;
            }

            $tieneProcesoCc = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->where(function ($query) use ($consecutivoParcial) {
                    $query->where('numero_recibo_parcial', $consecutivoParcial)
                        ->orWhere(function ($query) use ($consecutivoParcial) {
                            $query->whereNull('numero_recibo_parcial')
                                ->where('codigo_referencia', 'like', 'CCP-%')
                                ->where('numero_recibo', $consecutivoParcial);
                        });
                })
                ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->whereNull('deleted_at')
                ->exists();

            if ($tieneProcesoCc) {
                return true;
            }

            return DB::table('prenda_recibo_completado')
                ->where('id_parcial', (int) $parcialRelacionado->id)
                ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->exists();
        })->count();

        $todosParcialesEnCc = $totalParciales >= 1 && $parcialesEnCc >= $totalParciales;
        $algunParcialEnCc = $parcialesEnCc > 0;

        $procesoOriginalCc = ProcesoPrenda::query()
            ->where('numero_pedido', $pedido->numero_pedido)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->where('numero_recibo', $esParcialNuevo ? $parcial->consecutivo_inicial : $parcial->consecutivo_original)
            ->where(function ($query) {
                $query->whereNull('numero_recibo_parcial')
                    ->orWhere('numero_recibo_parcial', 0);
            })
            ->whereRaw('LOWER(TRIM(proceso)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->first();

        if ($algunParcialEnCc) {
            if ($todosParcialesEnCc) {
                // Solo cuando TODOS los parciales estan en CC, crear/actualizar proceso padre en CC
                if ($procesoOriginalCc) {
                    $procesoOriginalCc->fill([
                        'encargado' => null,
                        'estado_proceso' => 'En Progreso',
                    ])->save();
                } else {
                    $procesoOriginalCc = ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $parcial->prenda_pedido_id,
                        'numero_recibo' => $esParcialNuevo ? $parcial->consecutivo_inicial : $parcial->consecutivo_original,
                        'numero_recibo_parcial' => null,
                        'proceso' => 'Control de Calidad',
                        'fecha_inicio' => now(),
                        'encargado' => null,
                        'estado_proceso' => 'En Progreso',
                        'codigo_referencia' => 'CCO-' . $parcial->consecutivo_original . '-' . date('YmdHis'),
                    ]);
                }
            }
        } elseif ($procesoOriginalCc) {
            $procesoOriginalCc->forceDelete();
            $procesoOriginalCc = null;
        }

        // IMPORTANTE: Si TODOS los parciales estan en Control Calidad y hay mas de uno, actualizar el recibo original y el proceso padre
        if ($todosParcialesEnCc) {
            // 1. Cambiar el recibo original a Control Calidad en consecutivos_recibos_pedidos
            $consecutivoNum = (int) ($esParcialNuevo ? $parcial->consecutivo_inicial : $parcial->consecutivo_original);
            $actualizados = ConsecutivoReciboPedido::query()
                ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
                ->where('consecutivo_actual', $consecutivoNum)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim((string) $parcial->tipo_recibo))])
                ->update(['area' => 'Control Calidad']);

            Log::info('[COSTURA][PARCIAL][TODOS_EN_CC] Recibo original actualizado a Control Calidad', [
                'pedido_id' => (int) $pedido->id,
                'numero_pedido' => (int) $pedido->numero_pedido,
                'prenda_id' => (int) $parcial->prenda_pedido_id,
                'consecutivo_original' => (string) $parcial->consecutivo_original,
                'consecutivo_num' => $consecutivoNum,
                'tipo_recibo' => (string) $parcial->tipo_recibo,
                'filas_actualizadas' => $actualizados,
            ]);

            // 2. Cambiar el proceso padre de Costura a Control Calidad
            $procesoPadreCostura = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->where(function ($query) {
                    $query->whereNull('numero_recibo_parcial')
                        ->orWhere('numero_recibo_parcial', 0);
                })
                ->whereNull('deleted_at')
                ->first();

            if ($procesoPadreCostura) {
                $procesoPadreCostura->update([
                    'proceso' => 'Control de Calidad',
                    'estado_proceso' => 'Pendiente',
                    'encargado' => 'control',
                ]);

                Log::info('[COSTURA][PARCIAL][TODOS_EN_CC] Proceso padre Costura actualizado a Control Calidad', [
                    'proceso_padre_id' => (int) $procesoPadreCostura->id,
                    'numero_pedido' => (int) $pedido->numero_pedido,
                    'prenda_id' => (int) $parcial->prenda_pedido_id,
                    'encargado' => 'control',
                ]);
            }
        }

        Log::info('[COSTURA][PARCIAL][CONTROL_CALIDAD] Sincronizacion proceso original', [
            'pedido_id' => (int) $pedido->id,
            'numero_pedido' => (int) $pedido->numero_pedido,
            'prenda_id' => (int) $parcial->prenda_pedido_id,
            'tipo_recibo' => (string) $parcial->tipo_recibo,
                'consecutivo_original' => (string) $parcial->consecutivo_original,
            'total_parciales' => $totalParciales,
            'parciales_en_cc' => $parcialesEnCc,
            'todos_parciales_en_cc' => $todosParcialesEnCc,
            'proceso_original_cc_id' => $procesoOriginalCc?->id,
        ]);

        return [
            'total_parciales' => $totalParciales,
            'parciales_en_cc' => $parcialesEnCc,
            'todos_parciales_en_cc' => $todosParcialesEnCc,
            'algun_parcial_en_cc' => $algunParcialEnCc,
            'proceso_original_cc_id' => $procesoOriginalCc?->id,
        ];
    }

    private function notificarVistaCosturaCambioControlCalidadParcial(
        PedidoProduccion $pedido,
        PedidoParcial|ReciboPorPartes $parcial,
        array $estadoParcialesCc,
        bool $parcialEnviadoAcc
    ): void {
        $usuariosVistaCostura = User::all()->filter(function ($user) {
            return $user->hasRole('vista-costura');
        });

        $accion = $parcialEnviadoAcc ? 'control_calidad_parcial_actualizado' : 'control_calidad_parcial_deshecho';
        $mensaje = $parcialEnviadoAcc
            ? "El parcial #{$parcial->consecutivo_parcial} fue enviado a Control de Calidad"
            : "Se deshizo Control de Calidad del parcial #{$parcial->consecutivo_parcial}";

        foreach ($usuariosVistaCostura as $usuarioVista) {
            try {
                broadcast(new OperarioRecibosActualizados(
                    userId: (int) $usuarioVista->id,
                    payload: [
                        'accion' => $accion,
                        'mensaje' => $mensaje,
                        'area' => $parcialEnviadoAcc ? 'Control Calidad' : 'Costura',
                        'pedido_id' => (int) $pedido->id,
                        'numero_pedido' => (int) $pedido->numero_pedido,
                        'prenda_id' => (int) $parcial->prenda_pedido_id,
                        'tipo_recibo' => (string) $parcial->tipo_recibo,
                        'numero_recibo' => (string) ($parcial->getRawOriginal('consecutivo_parcial') ?? $parcial->consecutivo_parcial),
                        'consecutivo_original' => (string) ($parcial->getRawOriginal('consecutivo_original') ?? $parcial->consecutivo_original),
                        'pedido_parcial_id' => (int) $parcial->id,
                        'es_parcial' => true,
                        'total_parciales' => (int) ($estadoParcialesCc['total_parciales'] ?? 0),
                        'parciales_en_cc' => (int) ($estadoParcialesCc['parciales_en_cc'] ?? 0),
                        'todos_parciales_en_cc' => (bool) ($estadoParcialesCc['todos_parciales_en_cc'] ?? false),
                        'proceso_original_cc_id' => $estadoParcialesCc['proceso_original_cc_id'] ?? null,
                    ]
                ));
            } catch (\Throwable $e) {
                Log::warning('[COSTURA][PARCIAL][CONTROL_CALIDAD] Error broadcast vista-costura', [
                    'pedido_id' => (int) $pedido->id,
                    'parcial_id' => (int) $parcial->id,
                    'usuario_id' => (int) $usuarioVista->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function pasarACostura(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            // Logging para debugging
            Log::info('[COSTURA] Datos recibidos:', [
                'request_all' => $request->all(),
                'pedidoId' => $pedidoId,
                'numeroRecibo' => $numeroRecibo,
                'prenda_id' => $request->input('prenda_id'),
                'encargado' => $request->input('encargado'),
                'tipo_recibo' => $request->input('tipo_recibo')
            ]);

            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta accion'
                ], 403);
            }

            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo')));
            $esBodega = $tipoRecibo === 'CORTE-PARA-BODEGA';

            $rules = [
                'tipo_recibo' => 'required|string',
                'encargado' => 'required|string|max:100',
            ];

            if ($esBodega) {
                $rules['prenda_bodega_id'] = 'required|integer|exists:prenda_bodega,id';
            } else {
                $rules['prenda_id'] = 'required|integer|exists:prendas_pedido,id';
            }

            $request->validate($rules);

            $prendaId = (int) $request->input('prenda_id', 0);
            $prendaBodegaId = $esBodega ? (int) $request->input('prenda_bodega_id') : null;

            $resultado = $this->pasarACosturaUseCase->execute(new PasarACosturaCommandDTO(
                pedidoId: (int) $pedidoId,
                numeroRecibo: (int) $numeroRecibo,
                prendaId: $prendaId,
                prendaBodegaId: $prendaBodegaId,
                tipoRecibo: (string) $request->tipo_recibo,
                encargado: (string) $request->encargado,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error al pasar recibo a Costura', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al pasar a Costura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deshacer el proceso de Costura - eliminar proceso y restaurar Area anterior
     */
    public function deshacerCostura(Request $request, $pedidoId, $prendaId)
    {
        // Logging para debugging - mostrar todos los parametros
        Log::info('[DESHACER-COSTURA] Parametros recibidos', [
            'route_params' => func_get_args(),
            'request_all' => $request->all(),
            'pedidoId_param' => $pedidoId,
            'prendaId_param' => $prendaId,
            'request_prenda_id' => $request->prenda_id,
            'request_tipo_recibo' => $request->tipo_recibo
        ]);

        // Logging para debugging
        Log::info('[DESHACER-COSTURA] Iniciando proceso', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'tipo_recibo' => $request->tipo_recibo
        ]);

        try {
            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta accion'
                ], 403);
            }

            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo')));
            $esBodega = $tipoRecibo === 'CORTE-PARA-BODEGA';

            $rules = [
                'tipo_recibo' => 'required|string'
            ];

            if ($esBodega) {
                $rules['prenda_bodega_id'] = 'required|integer|exists:prenda_bodega,id';
            }

            $request->validate($rules);

            $prendaBodegaId = $esBodega ? (int) $request->input('prenda_bodega_id', $prendaId) : null;
            $prendaIdReal = $esBodega ? 0 : (int) $prendaId;
            $pedidoIdReal = $esBodega ? 0 : (int) $pedidoId;

            $resultado = $this->deshacerCosturaUseCase->execute(new DeshacerCosturaCommandDTO(
                pedidoId: $pedidoIdReal,
                prendaId: $prendaIdReal,
                prendaBodegaId: $prendaBodegaId,
                tipoRecibo: (string) $request->tipo_recibo,
            ));

            $payload = [
                'success' => $resultado->success,
                'message' => $resultado->message,
            ];
            if (!empty($resultado->data)) {
                $payload['data'] = $resultado->data;
            }

            return response()->json($payload, $resultado->statusCode);

        } catch (\Exception $e) {
            Log::error('Error al deshacer Costura', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer Costura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pasar recibo a Taller (distribucion a talleres externos)
     */
    public function pasarATaller(Request $request, $pedidoId, $numeroRecibo)
    {
        try {
            Log::info('[TALLER] Datos recibidos:', [
                'request_all' => $request->all(),
                'pedidoId' => $pedidoId,
                'numeroRecibo' => $numeroRecibo,
            ]);

            if (!auth()->user()->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta accion'
                ], 403);
            }

            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo')));
            $esBodega = $tipoRecibo === 'CORTE-PARA-BODEGA';

            $rules = [
                'tipo_recibo' => 'required|string',
                'tipo_distribucion' => 'required|string|in:taller',
                'subtipo_taller' => 'required|string|in:unico,multiple',
                'encargado' => 'required_if:subtipo_taller,unico|string|max:100',
                'asignaciones' => 'required_if:subtipo_taller,multiple|array',
                'asignaciones.*.encargado' => 'required|string|max:100',
                'asignaciones.*.tallas' => 'required|array|min:1',
                'asignaciones.*.tallas.*.talla' => 'required|string|max:50',
                'asignaciones.*.tallas.*.cantidad' => 'required|integer|min:1',
                'asignaciones.*.tallas.*.genero' => 'nullable|string|max:50',
                'asignaciones.*.tallas.*.color_nombre' => 'nullable|string|max:191',
                'es_edicion' => 'nullable|boolean',
            ];

            if ($esBodega) {
                $rules['prenda_bodega_id'] = 'required|integer|exists:prenda_bodega,id';
            } else {
                $rules['prenda_id'] = 'required|integer|exists:prendas_pedido,id';
            }

            $request->validate($rules);

            $pedido = PedidoProduccion::findOrFail((int) $pedidoId);
            $prendaId = (int) $request->input('prenda_id', $request->input('prenda_bodega_id', 0));
            $prendaBodegaId = $esBodega ? (int) $request->input('prenda_bodega_id', $prendaId) : null;
            $consecutivoOriginal = (int) $numeroRecibo;
            $subtipoTaller = (string) $request->subtipo_taller;
            $esEdicion = (bool) $request->es_edicion;

            Log::info('[TALLER] Procesando distribucion', [
                'pedido_id' => (int) $pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_id' => $prendaId,
                'tipo_recibo' => $tipoRecibo,
                'subtipo_taller' => $subtipoTaller,
                'es_edicion' => $esEdicion,
            ]);

            $recibo = ConsecutivoReciboPedido::query()
                ->where('pedido_produccion_id', (int) $pedidoId)
                ->where('consecutivo_actual', $consecutivoOriginal)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoRecibo))])
                ->where('activo', 1)
                ->first();

            if (!$recibo && !$esBodega) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }

            if (!$recibo && $esBodega) {
                $prendaBodega = PrendaBodega::findOrFail($prendaBodegaId);
                $recibo = (object) [
                    'id' => (int) $prendaBodega->id,
                    'tipo_recibo' => $tipoRecibo,
                    'area' => 'Costura',
                ];
            }

            $tipoReciboReal = $esBodega ? $tipoRecibo : (string) $recibo->tipo_recibo;

            // Procesar segun el subtipo de taller
            if ($subtipoTaller === 'unico') {
                // Un solo taller - actualizar el encargado del proceso de costura existente
                $encargado = (string) $request->encargado;

                // Verificar que el usuario existe y tiene rol 'taller'
                $taller = User::where('name', $encargado)
                    ->get()
                    ->first(function ($user) {
                        return $user->hasRole('taller');
                    });

                // Si no existe, crear el taller
                if (!$taller) {
                    $taller = DB::transaction(function () use ($encargado) {
                        // Crear el usuario con rol 'taller'
                        $nuevoTaller = User::create([
                            'name' => $encargado,
                            'email' => strtolower(str_replace(' ', '.', $encargado)) . '@taller.local',
                            'password' => bcrypt('password123'),
                            'email_verified_at' => now(),
                        ]);

                        // Asignar rol 'taller'
                        $tallerRole = \App\Models\Role::where('name', 'taller')->first();
                        if ($tallerRole) {
                            $nuevoTaller->addRole($tallerRole->id);
                        }

                        \Log::info('[TALLER] Nuevo taller creado', [
                            'taller_id' => $nuevoTaller->id,
                            'taller_nombre' => $nuevoTaller->name,
                        ]);

                        return $nuevoTaller;
                    });
                }

                // Actualizar el proceso de costura existente
                $resultado = DB::transaction(function () use ($pedido, $prendaId, $prendaBodegaId, $esBodega, $taller, $consecutivoOriginal) {
                    $prendaColumn = $esBodega ? 'prenda_bodega_id' : 'prenda_pedido_id';
                    $prendaValor = $esBodega ? $prendaBodegaId : $prendaId;

                    // Buscar todos los procesos de costura del recibo (base y parciales).
                    // Esto permite asignar taller aun cuando el recibo viene de flujo por modulos.
                    $procesosCostura = ProcesoPrenda::query()
                        ->where('numero_pedido', $pedido->numero_pedido)
                        ->where($prendaColumn, $prendaValor)
                        ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                        ->where('numero_recibo', $consecutivoOriginal)
                        ->whereNull('deleted_at')
                        ->orderByDesc('created_at')
                        ->get();

                    if ($procesosCostura->isEmpty() && $esBodega && $prendaId > 0) {
                        $procesosCostura = ProcesoPrenda::query()
                            ->where('numero_pedido', $pedido->numero_pedido)
                            ->where('prenda_pedido_id', $prendaId)
                            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                            ->where('numero_recibo', $consecutivoOriginal)
                            ->whereNull('deleted_at')
                            ->orderByDesc('created_at')
                            ->get();
                    }

                    if ($procesosCostura->isEmpty()) {
                        $procesoCosturaBase = ProcesoPrenda::create([
                            'numero_pedido' => $pedido->numero_pedido,
                            'prenda_pedido_id' => $esBodega ? null : $prendaId,
                            'prenda_bodega_id' => $esBodega ? $prendaBodegaId : null,
                            'numero_recibo' => $consecutivoOriginal,
                            'numero_recibo_parcial' => null,
                            'proceso' => 'Costura',
                            'fecha_inicio' => now(),
                            'encargado' => null,
                            'estado_proceso' => 'Pendiente',
                            'codigo_referencia' => 'COS-' . $consecutivoOriginal . '-' . date('YmdHis'),
                        ]);

                        $procesosCostura = collect([$procesoCosturaBase]);
                    }

                    foreach ($procesosCostura as $procesoCostura) {
                        // Actualizar el encargado y estado
                        $procesoCostura->update([
                            'encargado' => $taller->name,
                            'usuario_id' => $taller->id,
                            'fecha_de_asignacion_encargado' => now(),
                            'estado_proceso' => 'En Progreso',
                        ]);
                    }

                    return [
                        'success' => true,
                        'message' => 'Recibo asignado a taller correctamente',
                        'data' => [
                            'procesos_actualizados' => $procesosCostura->count(),
                            'proceso_ids' => $procesosCostura->pluck('id')->values()->all(),
                            'taller' => $taller->name,
                        ]
                    ];
                });

                return response()->json($resultado, 200);

            } else {
                // Multiples talleres
                $asignaciones = (array) $request->asignaciones;

                if (empty($asignaciones)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay asignaciones de talleres'
                    ], 400);
                }

                // Si es edicion, eliminar parciales existentes y recrearlos
                if ($esEdicion) {
                    $resultado = $this->procesarEdicionDistribucionTaller(
                        $pedido, $recibo, $pedidoId, $prendaId, $tipoReciboReal, 
                        $consecutivoOriginal, $asignaciones, $esBodega, $prendaBodegaId
                    );
                } else {
                    // Crear nuevos parciales (flujo original)
                    $resultado = $this->procesarNuevaDistribucionTaller(
                        $pedido, $recibo, $pedidoId, $prendaId, $tipoReciboReal, 
                        $consecutivoOriginal, $asignaciones, $esBodega, $prendaBodegaId
                    );
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Recibo distribuido a talleres correctamente',
                    'data' => $resultado,
                ], 200);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validacion',
                'errors' => $e->errors(),
            ], 422);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al pasar recibo a Taller', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al pasar a Taller: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar edicion de distribucion a talleres
     */
    private function procesarEdicionDistribucionTaller($pedido, $recibo, $pedidoId, $prendaId, $tipoReciboReal, $consecutivoOriginal, $asignaciones, bool $esBodega = false, ?int $prendaBodegaId = null)
    {
        return DB::transaction(function () use ($pedido, $recibo, $pedidoId, $prendaId, $tipoReciboReal, $consecutivoOriginal, $asignaciones, $esBodega, $prendaBodegaId) {
            $prendaColumn = $esBodega ? 'prenda_bodega_id' : 'prenda_pedido_id';
            // En edición no se eliminan parciales históricos; solo se agregan nuevos.

            // Buscar el proceso padre de Costura
            $procesoPadre = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where($prendaColumn, $prendaId)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->where('numero_recibo', $consecutivoOriginal)
                ->where(function ($query) {
                    $query->whereNull('numero_recibo_parcial')
                        ->orWhere('numero_recibo_parcial', 0);
                })
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->first();

            if (!$procesoPadre) {
                // Si no existe, crear el proceso padre
                $procesoPadre = ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $esBodega ? null : $prendaId,
                    'prenda_bodega_id' => $esBodega ? $prendaBodegaId : null,
                    'numero_recibo' => $consecutivoOriginal,
                    'numero_recibo_parcial' => null,
                    'proceso' => 'Costura',
                    'fecha_inicio' => now(),
                    'encargado' => null,
                    'estado_proceso' => 'Pendiente',
                    'codigo_referencia' => 'COS-' . $consecutivoOriginal . '-' . date('YmdHis'),
                ]);
            }

            // Crear nuevos parciales continuando la numeracion existente.
            $maxParcialExistente = DB::table('recibo_por_partes')
                ->where('pedido_produccion_id', (int) $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoReciboReal))])
                ->where('consecutivo_original', $consecutivoOriginal)
                ->max('consecutivo_parcial');
            $siguienteConsecutivoParcial = $this->obtenerSiguienteConsecutivoParcial($consecutivoOriginal, $maxParcialExistente);
            $creados = [];

            foreach ($asignaciones as $asig) {
                $encargado = trim((string) ($asig['encargado'] ?? ''));
                $tallas = (array) ($asig['tallas'] ?? []);

                if ($encargado === '' || empty($tallas)) {
                    continue;
                }

                // Verificar que el usuario existe y tiene rol 'taller'
                $taller = User::where('name', $encargado)
                    ->get()
                    ->first(function ($user) {
                        return $user->hasRole('taller');
                    });

                // Si no existe, crear el taller
                if (!$taller) {
                    $taller = User::create([
                        'name' => $encargado,
                        'email' => strtolower(str_replace(' ', '.', $encargado)) . '@taller.local',
                        'password' => bcrypt('password123'),
                        'email_verified_at' => now(),
                    ]);

                    $tallerRole = \App\Models\Role::where('name', 'taller')->first();
                    if ($tallerRole) {
                        $taller->addRole((int) $tallerRole->id);
                    }

                    \Log::info('[TALLER] Nuevo taller creado en edicion', [
                        'taller_id' => $taller->id,
                        'taller_nombre' => $taller->name,
                    ]);
                }

                // Calcular consecutivo parcial en decimas validas para DECIMAL(10,1)
                $consecutivoParcialDb = $this->formatearConsecutivoParcial($siguienteConsecutivoParcial);
                $siguienteConsecutivoParcial = round($siguienteConsecutivoParcial + 0.1, 1);

                // Crear proceso hijo para el taller
                $procesoHijo = ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $esBodega ? null : $prendaId,
                    'prenda_bodega_id' => $esBodega ? $prendaBodegaId : null,
                    'numero_recibo' => null,
                    'numero_recibo_parcial' => $consecutivoParcialDb,
                    'proceso' => 'Costura',
                    'fecha_inicio' => now(),
                    'encargado' => $encargado,
                    'fecha_de_asignacion_encargado' => now(),
                    'estado_proceso' => 'En Progreso',
                    'codigo_referencia' => 'COS-' . $consecutivoParcialDb . '-' . date('YmdHis'),
                ]);

                // Crear registro en recibo_por_partes
                $reciboParteId = DB::table('recibo_por_partes')->insertGetId([
                    'pedido_produccion_id' => (int) $pedidoId,
                    'prenda_pedido_id' => $prendaId,
                    'tipo_recibo' => $tipoReciboReal,
                    'consecutivo_original' => $consecutivoOriginal,
                    'consecutivo_parcial' => $consecutivoParcialDb,
                    'estado' => 'En ejecucion',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Crear registros de tallas para el parcial
                foreach ($tallas as $t) {
                    $talla = trim((string) ($t['talla'] ?? ''));
                    $cantidad = (int) ($t['cantidad'] ?? 0);
                    $genero = isset($t['genero']) ? (string) $t['genero'] : null;
                    $colorNombre = isset($t['color_nombre']) ? (string) $t['color_nombre'] : null;

                    if ($talla === '' || $cantidad <= 0) {
                        continue;
                    }

                    $this->insertReciboParteTalla($reciboParteId, [
                        'talla' => $talla,
                        'cantidad' => $cantidad,
                        'genero' => $genero,
                        'color_nombre' => $colorNombre,
                    ], $prendaId, $esBodega);
                }

                $creados[] = [
                    'proceso_id' => (int) $procesoHijo->id,
                    'numero_recibo' => null,
                    'numero_recibo_parcial' => $consecutivoParcialDb,
                    'parcial_id' => (int) $reciboParteId,
                    'encargado' => $encargado,
                ];
            }

            if (empty($creados)) {
                throw new \Exception('No se pudieron crear procesos para los talleres especificados');
            }

            return [
                'proceso_padre_id' => (int) $procesoPadre->id,
                'hijos' => $creados,
                'recibo_id' => (int) $recibo->id,
            ];
        });
    }

    /**
     * Procesar nueva distribucion a talleres
     */
    private function procesarNuevaDistribucionTaller($pedido, $recibo, $pedidoId, $prendaId, $tipoReciboReal, $consecutivoOriginal, $asignaciones, bool $esBodega = false, ?int $prendaBodegaId = null)
    {
        return DB::transaction(function () use ($pedido, $recibo, $pedidoId, $prendaId, $tipoReciboReal, $consecutivoOriginal, $asignaciones, $esBodega, $prendaBodegaId) {
            $prendaColumn = $esBodega ? 'prenda_bodega_id' : 'prenda_pedido_id';
            // Buscar el proceso padre de Costura
            $procesoPadre = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where($prendaColumn, $prendaId)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->where('numero_recibo', $consecutivoOriginal)
                ->where(function ($query) {
                    $query->whereNull('numero_recibo_parcial')
                        ->orWhere('numero_recibo_parcial', 0);
                })
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->first();

            if (!$procesoPadre) {
                // Si no existe, crear el proceso padre
                $procesoPadre = ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $esBodega ? null : $prendaId,
                    'prenda_bodega_id' => $esBodega ? $prendaBodegaId : null,
                    'numero_recibo' => $consecutivoOriginal,
                    'numero_recibo_parcial' => null,
                    'proceso' => 'Costura',
                    'fecha_inicio' => now(),
                    'encargado' => null,
                    'estado_proceso' => 'Pendiente',
                    'codigo_referencia' => 'COS-' . $consecutivoOriginal . '-' . date('YmdHis'),
                ]);
            }

            // Calcular el siguiente iÂ­ndice de parcial
            $maxParcialExistente = DB::table('recibo_por_partes')
                ->where('pedido_produccion_id', (int) $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoReciboReal))])
                ->where('consecutivo_original', $consecutivoOriginal)
                ->max('consecutivo_parcial');

            $siguienteConsecutivoParcial = $this->obtenerSiguienteConsecutivoParcial($consecutivoOriginal, $maxParcialExistente);

            $creados = [];

            foreach ($asignaciones as $asig) {
                $encargado = trim((string) ($asig['encargado'] ?? ''));
                $tallas = (array) ($asig['tallas'] ?? []);

                if ($encargado === '' || empty($tallas)) {
                    continue;
                }

                // Verificar que el usuario existe y tiene rol 'taller'
                $taller = User::where('name', $encargado)
                    ->get()
                    ->first(function ($user) {
                        return $user->hasRole('taller');
                    });

                // Si no existe, crear el taller
                if (!$taller) {
                    $taller = User::create([
                        'name' => $encargado,
                        'email' => strtolower(str_replace(' ', '.', $encargado)) . '@taller.local',
                        'password' => bcrypt('password123'),
                        'email_verified_at' => now(),
                    ]);

                    $tallerRole = \App\Models\Role::where('name', 'taller')->first();
                    if ($tallerRole) {
                        $taller->addRole((int) $tallerRole->id);
                    }

                    \Log::info('[TALLER] Nuevo taller creado en multiples talleres', [
                        'taller_id' => $taller->id,
                        'taller_nombre' => $taller->name,
                    ]);
                }

                // Calcular numero de parcial
                $consecutivoParcialDb = $this->formatearConsecutivoParcial($siguienteConsecutivoParcial);
                $siguienteConsecutivoParcial = round($siguienteConsecutivoParcial + 0.1, 1);

                // Crear proceso hijo para el taller
                $procesoHijo = ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $esBodega ? null : $prendaId,
                    'prenda_bodega_id' => $esBodega ? $prendaBodegaId : null,
                    'numero_recibo' => null,
                    'numero_recibo_parcial' => $consecutivoParcialDb,
                    'proceso' => 'Costura',
                    'fecha_inicio' => now(),
                    'encargado' => $encargado,
                    'fecha_de_asignacion_encargado' => now(),
                    'estado_proceso' => 'En Progreso',
                    'codigo_referencia' => 'COS-' . $consecutivoParcialDb . '-' . date('YmdHis'),
                ]);

                // Crear registro en recibo_por_partes
                $reciboParteId = DB::table('recibo_por_partes')->insertGetId([
                    'pedido_produccion_id' => (int) $pedidoId,
                    'prenda_pedido_id' => $prendaId,
                    'tipo_recibo' => $tipoReciboReal,
                    'consecutivo_original' => $consecutivoOriginal,
                    'consecutivo_parcial' => $consecutivoParcialDb,
                    'estado' => 'En ejecucion',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Crear registros de tallas para el parcial
                foreach ($tallas as $t) {
                    $talla = trim((string) ($t['talla'] ?? ''));
                    $cantidad = (int) ($t['cantidad'] ?? 0);
                    $genero = isset($t['genero']) ? (string) $t['genero'] : null;
                    $colorNombre = isset($t['color_nombre']) ? (string) $t['color_nombre'] : null;

                    if ($talla === '' || $cantidad <= 0) {
                        continue;
                    }

                    $this->insertReciboParteTalla($reciboParteId, [
                        'talla' => $talla,
                        'cantidad' => $cantidad,
                        'genero' => $genero,
                        'color_nombre' => $colorNombre,
                    ], $prendaId, $esBodega);
                }

                $creados[] = [
                    'proceso_id' => (int) $procesoHijo->id,
                    'numero_recibo' => null,
                    'numero_recibo_parcial' => $consecutivoParcialDb,
                    'parcial_id' => (int) $reciboParteId,
                    'encargado' => $encargado,
                ];
            }

            if (empty($creados)) {
                throw new \Exception('No se pudieron crear procesos para los talleres especificados');
            }

            return [
                'proceso_padre_id' => (int) $procesoPadre->id,
                'hijos' => $creados,
                'recibo_id' => (int) $recibo->id,
            ];
        });
    }

    /**
     * Obtiene el siguiente consecutivo parcial valido para una columna DECIMAL(10,1).
     * Ejemplo: 1.3 -> 1.4, 1.9 -> 2.0
     */
    private function obtenerSiguienteConsecutivoParcial(int $consecutivoOriginal, $maxParcialExistente): float
    {
        if ($maxParcialExistente === null) {
            return round($consecutivoOriginal + 0.1, 1);
        }

        return round(((float) $maxParcialExistente) + 0.1, 1);
    }

    /**
     * Formatea el consecutivo parcial para almacenar/mostrar con una sola decimal.
     */
    private function formatearConsecutivoParcial(float $valor): string
    {
        return number_format($valor, 1, '.', '');
    }
}
