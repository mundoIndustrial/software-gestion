<?php

namespace App\Infrastructure\Http\Controllers\Bodega;

use App\Models\PrendaBodega;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReciboCorteBodegaController extends Controller
{
    public function index()
    {
        $prendas = PrendaBodega::with('tallas')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $prendaIds = $prendas->pluck('id')->all();
        $recibosMap = [];
        if (!empty($prendaIds)) {
            $recibosMap = DB::table('consecutivos_recibos_pedidos')
                ->whereIn('prenda_bodega_id', $prendaIds)
                ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
                ->orderByDesc('id')
                ->get()
                ->groupBy('prenda_bodega_id')
                ->map(fn($rows) => (int) ($rows->first()->consecutivo_actual ?? 0))
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => $prendas->map(fn($prenda) => [
                'id' => $prenda->id,
                'numero_recibo' => $recibosMap[$prenda->id] ?? null,
                'nombre' => $prenda->nombre,
                'descripcion' => $prenda->descripcion,
                'total_cantidad' => $prenda->tallas->sum('cantidad'),
                'cantidad_tallas' => $prenda->tallas->count(),
                'fecha' => $prenda->created_at->format('Y-m-d H:i'),
                'fecha_corta' => $prenda->created_at->format('d/m/Y'),
            ])->toArray(),
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
            'prendas.*.nombre' => 'required|string|max:255',
            'prendas.*.descripcion' => 'nullable|string',
            'prendas.*.tallas' => 'required|array|min:1',
            'prendas.*.tallas.*.talla' => 'required|string|max:50',
            'prendas.*.tallas.*.genero' => 'nullable|string|in:dama,caballero,DAMA,CABALLERO',
            'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
        ]);

        if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'prenda_bodega_id')) {
            return response()->json([
                'success' => false,
                'message' => "Falta columna 'consecutivos_recibos_pedidos.prenda_bodega_id'. Ejecuta el ALTER TABLE pendiente.",
            ], 500);
        }

        try {
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
                    $prenda = PrendaBodega::create([
                        'nombre' => $prendaData['nombre'],
                        'descripcion' => $prendaData['descripcion'] ?? null,
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
                        'estado' => 'En Ejecución',
                        'area' => 'Corte',
                        'notas' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'ultima_actividad' => now(),
                    ]);

                    foreach ($prendaData['tallas'] as $tallaData) {
                        $genero = isset($tallaData['genero']) ? strtoupper((string) $tallaData['genero']) : null;
                        $prenda->tallas()->create([
                            'talla' => $tallaData['talla'],
                            'genero' => in_array($genero, ['DAMA', 'CABALLERO'], true) ? $genero : null,
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
                'genero' => $t->genero,
                'cantidad' => $t->cantidad,
            ])->toArray(),
            'total' => $totalCantidad,
        ]);
    }
}
