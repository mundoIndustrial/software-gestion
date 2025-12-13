<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Http\Controllers\Controller;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;
use App\Domain\Operario\Repositories\OperarioRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller: OperarioController
 * 
 * Gestiona vistas y acciones para operarios (cortador/costurero)
 */
class OperarioController extends Controller
{
    public function __construct(
        private ObtenerPedidosOperarioService $obtenerPedidosService,
        private OperarioRepository $operarioRepository
    ) {
        $this->middleware('auth');
        $this->middleware('operario-access');
    }

    /**
     * Debug: Ver datos del usuario y procesos
     */
    public function debug()
    {
        $usuario = auth()->user();
        $area = $usuario->roles()->first()?->name === 'cortador' ? 'Corte' : 'Costura';

        // Obtener TODOS los procesos sin filtros
        $todosProcesos = \App\Models\ProcesoPrenda::all();

        // Procesos filtrados por área (sin filtrar por estado)
        $procesesPorArea = \App\Models\ProcesoPrenda::where('proceso', $area)
            ->get();

        return response()->json([
            'usuario_actual' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->roles()->first()?->name,
                'area_buscada' => $area
            ],
            'total_procesos_en_bd' => $todosProcesos->count(),
            'todos_procesos' => $todosProcesos->map(function ($p) {
                return [
                    'numero_pedido' => $p->numero_pedido,
                    'proceso' => $p->proceso,
                    'encargado' => $p->encargado,
                    'estado_proceso' => $p->estado_proceso
                ];
            }),
            'procesos_filtrados_por_area' => $procesesPorArea->map(function ($p) {
                return [
                    'numero_pedido' => $p->numero_pedido,
                    'proceso' => $p->proceso,
                    'encargado' => $p->encargado,
                    'encargado_trim' => trim($p->encargado),
                    'encargado_lower' => strtolower(trim($p->encargado)),
                    'estado_proceso' => $p->estado_proceso
                ];
            }),
            'comparaciones' => $procesesPorArea->map(function ($p) use ($usuario) {
                $encargado_normalizado = strtolower(trim($p->encargado));
                $usuario_normalizado = strtolower(trim($usuario->name));
                return [
                    'numero_pedido' => $p->numero_pedido,
                    'encargado_bd' => $p->encargado,
                    'usuario_name' => $usuario->name,
                    'encargado_normalizado' => $encargado_normalizado,
                    'usuario_normalizado' => $usuario_normalizado,
                    'coinciden' => $encargado_normalizado === $usuario_normalizado
                ];
            })
        ]);
    }

    /**
     * Dashboard del operario
     * Muestra los pedidos asignados a su área
     */
    public function dashboard(Request $request)
    {
        $usuario = Auth::user();

        // Obtener pedidos del operario
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        return view('operario.dashboard', [
            'operario' => $datosOperario,
            'usuario' => $usuario,
        ]);
    }

    /**
     * Listar pedidos del operario
     */
    public function misPedidos(Request $request)
    {
        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        return view('operario.mis-pedidos', [
            'operario' => $datosOperario,
            'usuario' => $usuario,
        ]);
    }

    /**
     * Ver detalle de un pedido
     */
    public function verPedido($numeroPedido)
    {
        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        // Buscar el pedido específico
        $pedido = collect($datosOperario->pedidos)
            ->firstWhere('numero_pedido', $numeroPedido);

        if (!$pedido) {
            return redirect()->route('operario.dashboard')
                ->with('error', 'Pedido no encontrado');
        }

        // Obtener fotos relacionadas del pedido
        $fotos = $this->obtenerFotosPedido($numeroPedido);

        return view('operario.ver-pedido', [
            'operario' => $datosOperario,
            'pedido' => $pedido,
            'usuario' => $usuario,
            'fotos' => $fotos,
        ]);
    }

    /**
     * Obtener fotos relacionadas al pedido
     */
    private function obtenerFotosPedido($numeroPedido)
    {
        // Usar caché por 10 minutos para las fotos
        $cacheKey = "fotos_pedido_{$numeroPedido}";
        
        return \Cache::remember($cacheKey, 600, function() use ($numeroPedido) {
            $fotos = [];

            try {
                // Obtener solo las columnas necesarias de la cotización
                $pedido = \App\Models\PedidoProduccion::select('id', 'cotizacion_id')
                    ->where('numero_pedido', $numeroPedido)
                    ->first();
                
                if (!$pedido || !$pedido->cotizacion_id) {
                    return [];
                }

                // Obtener IDs de prendas de una sola query
                $prendasCotIds = \App\Models\PrendaCot::where('cotizacion_id', $pedido->cotizacion_id)
                    ->pluck('id')
                    ->toArray();

                if (empty($prendasCotIds)) {
                    return [];
                }

                // ===== 1. FOTOS DE PRENDAS =====
                $fotosPrendas = \App\Models\PrendaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();
                
                foreach($fotosPrendas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if($ruta) $fotos[] = $ruta;
                }

                // ===== 2. FOTOS DE TELAS =====
                $fotosTelas = \App\Models\PrendaTelaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();
                
                foreach($fotosTelas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if($ruta) $fotos[] = $ruta;
                }

                // ===== 3. FOTOS DE LOGOS =====
                $logoCotIds = \App\Models\LogoCotizacion::select('id')
                    ->where('cotizacion_id', $pedido->cotizacion_id)
                    ->pluck('id')
                    ->toArray();
                
                if (!empty($logoCotIds)) {
                    $fotosLogos = \App\Models\LogoFotoCot::select('ruta_webp', 'ruta_original')
                        ->whereIn('logo_cotizacion_id', $logoCotIds)
                        ->orderBy('orden')
                        ->get();
                    
                    foreach($fotosLogos as $foto) {
                        $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                        if($ruta) $fotos[] = $ruta;
                    }
                }
                
            } catch (\Exception $e) {
                \Log::error('Error en obtenerFotosPedido: ' . $e->getMessage());
                return [];
            }

            return $fotos;
        });
    }

    /**
     * API: Obtener pedidos en JSON
     */
    public function obtenerPedidosJson(Request $request)
    {
        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        return response()->json($datosOperario->toArray());
    }

    /**
     * Buscar pedido
     */
    public function buscarPedido(Request $request)
    {
        $request->validate([
            'busqueda' => 'required|string|min:2',
        ]);

        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        $busqueda = strtolower($request->input('busqueda'));

        $resultados = collect($datosOperario->pedidos)
            ->filter(function ($pedido) use ($busqueda) {
                return str_contains(strtolower($pedido['numero_pedido']), $busqueda) ||
                       str_contains(strtolower($pedido['cliente']), $busqueda) ||
                       str_contains(strtolower($pedido['descripcion']), $busqueda);
            })
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'resultados' => $resultados,
            'total' => count($resultados),
        ]);
    }

    /**
     * Reportar pendiente - Cambiar estado del proceso a Pendiente y guardar novedad
     * OPTIMIZADO: Guarda novedad en procesos_prenda, pedidos_produccion y tabla_original_bodega
     */
    public function reportarPendiente(Request $request)
    {
        $request->validate([
            'numero_pedido' => 'required|numeric',
            'novedad' => 'required|string|min:5',
        ]);

        $usuario = Auth::user();
        $numeroPedido = $request->input('numero_pedido');
        $novedad = $request->input('novedad');

        try {
            // Obtener el área del usuario
            $area = $usuario->hasRole('cortador') ? 'Corte' : 'Costura';

            // Buscar el proceso del usuario en este pedido
            $proceso = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
                ->where('proceso', $area)
                ->where('encargado', $usuario->name)
                ->first();

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado para este pedido'
                ], 404);
            }

            // Formato de novedad con timestamp y usuario
            $novedadFormato = "[{$usuario->name} - " . now()->format('Y-m-d H:i:s') . "] {$area}: {$novedad}";

            // Cambiar estado a Pendiente y guardar novedad en proceso
            $proceso->update([
                'estado_proceso' => 'Pendiente',
                'observaciones' => $novedad,
                'novedades' => $novedadFormato
            ]);

            // ===== GUARDAR EN pedidos_produccion =====
            $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            if ($pedido) {
                // Concatenar con las novedades existentes
                $novedadesActuales = $pedido->novedades ?? '';
                $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : "") . $novedadFormato;
                $pedido->update(['novedades' => $novedadesActualizadas]);
            }

            // ===== GUARDAR EN tabla_original_bodega =====
            $bodega = \DB::table('tabla_original_bodega')
                ->where('pedido', $numeroPedido)
                ->first();
            if ($bodega) {
                // Concatenar con las novedades existentes
                $novedadesActuales = $bodega->novedades ?? '';
                $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : "") . $novedadFormato;
                \DB::table('tabla_original_bodega')
                    ->where('pedido', $numeroPedido)
                    ->update(['novedades' => $novedadesActualizadas]);
            }

            // ===== LIMPIAR CACHÉ =====
            \Cache::forget("pedido_data_{$numeroPedido}");
            \Cache::forget("fotos_pedido_{$numeroPedido}");

            return response()->json([
                'success' => true,
                'message' => 'Novedad reportada correctamente. El estado ha sido cambiado a Pendiente.',
                'estado_nuevo' => 'Pendiente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reportar la novedad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener datos del pedido para vista móvil
     * OPTIMIZADO: Con caché y eager loading
     */
    public function getPedidoData($numeroPedido)
    {
        // Usar caché por 10 minutos para evitar consultas repetidas
        $cacheKey = "pedido_data_{$numeroPedido}";
        
        return \Cache::remember($cacheKey, 600, function() use ($numeroPedido) {
            $pedido = \App\Models\PedidoProduccion::with(['asesora', 'prendas'])->where('numero_pedido', $numeroPedido)->first();
            
            if (!$pedido) {
                return response()->json(['error' => 'not found'], 404);
            }

            // Devolver también las prendas para que el JS construya la descripción
            return [
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente ?? 'N/A',
                'asesora' => $pedido->asesora?->name ?? 'N/A',
                'forma_pago' => $pedido->forma_de_pago ?? 'N/A',
                'descripcion_prendas' => $pedido->descripcion_prendas ?? 'N/A',
                'fecha_creacion' => $pedido->fecha_creacion ?? '',
                'cantidad' => $pedido->cantidad ?? 0,
                'encargado' => \Illuminate\Support\Facades\Auth::user()?->name ?? 'Operario',
                'fotos' => $this->obtenerFotosPedido($pedido->numero_pedido),
                'prendas' => $pedido->prendas->map(function($prenda) {
                    return [
                        'nombre' => $prenda->nombre_prenda ?? '',
                        'talla' => $prenda->talla ?? '',
                        'cantidad' => $prenda->cantidad ?? 0,
                        'descripcion' => $prenda->descripcion ?? ''
                    ];
                })
            ];
        });
    }

    /**
     * Marcar proceso como completado
     */
    public function completarProceso(Request $request, $numeroPedido)
    {
        try {
            $usuario = Auth::user();
            $area = $usuario->roles()->first()?->name === 'cortador' ? 'Corte' : 'Costura';

            // Buscar el proceso actual del pedido
            $proceso = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
                ->where('proceso', $area)
                ->where('estado_proceso', '!=', 'Completado')
                ->first();

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado'
                ], 404);
            }

            // Actualizar el proceso a completado
            $proceso->update([
                'estado_proceso' => 'Completado',
                'fecha_fin' => now(),
            ]);

            // Limpiar cache
            \Illuminate\Support\Facades\Cache::forget("pedido_data_{$numeroPedido}");
            \Illuminate\Support\Facades\Cache::forget("fotos_pedido_{$numeroPedido}");

            \Log::info("Proceso marcado como completado", [
                'numero_pedido' => $numeroPedido,
                'area' => $area,
                'usuario' => $usuario->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proceso marcado como completado'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error al completar proceso: " . $e->getMessage(), [
                'numero_pedido' => $numeroPedido,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al completar el proceso'
            ], 500);
        }
    }

}
