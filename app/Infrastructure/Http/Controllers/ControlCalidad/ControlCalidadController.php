<?php

namespace App\Infrastructure\Http\Controllers\ControlCalidad;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use App\Models\PrendaReciboCompletado;
use App\Models\ReciboPorPartes;
use App\Models\PedidoProduccion;
use App\Application\Operario\UseCases\GetPedidoDataOperarioUseCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class ControlCalidadController extends Controller
{
    public function __construct()
    {
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

    public function dashboard(Request $request)
    {
        $usuario = auth()->user();
        $esLiderControlCalidad = $usuario && $usuario->hasRole('lider-control-calidad');

        // Filtrar recibos que estén en el área de Control de Calidad
        $recibosQuery = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', ['COSTURA', 'REFLECTIVO']);

        // Siempre listar únicamente recibos cuyo área actual sea Control de Calidad
        $recibosQuery->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad']);

        $recibos = $recibosQuery
            ->with(['pedido', 'prenda', 'pedido.prendas'])
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
            $prenda = $recibo->prenda ?: $pedido?->prendas?->first();
            $numeroPedido = $pedido?->numero_pedido;
            $procesoActual = $numeroPedido ? ($ultimoProcesoPorPedido[$numeroPedido] ?? null) : null;

            return [
                'prenda_id' => $prenda->id ?? 0,
                'pedido_id' => $pedido->id ?? 0,
                'numero_pedido' => $pedido->numero_pedido ?? '',
                'cliente' => $pedido->cliente ?? '',
                'nombre_prenda' => $prenda->nombre_prenda ?? 'Pedido',
                'descripcion' => $prenda->descripcion ?? ($pedido->descripcion ?? ''),
                'proceso_actual' => $procesoActual,
                'de_bodega' => $prenda->de_bodega ?? null,
                'recibos' => [[
                    'id' => $recibo->id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'consecutivo_inicial' => $recibo->consecutivo_inicial,
                    'notas' => $recibo->notas,
                    'creado_en' => $recibo->created_at,
                    'area' => $recibo->area,
                ]],
                'total_recibos' => 1,
                'fecha_creacion' => $recibo->created_at,
                'estado_pedido' => $pedido->estado ?? 'Pendiente',
            ];
        });

        $idsRecibos = $prendasConRecibos
            ->flatMap(fn($p) => collect($p['recibos'] ?? [])->pluck('id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $parcialesEnControlCalidad = ReciboPorPartes::query()
            ->with(['pedido', 'prenda', 'tallas'])
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

        $parcialesConRecibos = $parcialesEnControlCalidad->map(function (ReciboPorPartes $parcial) use ($completadosPorParcialId) {
            $pedido = $parcial->pedido;
            $prenda = $parcial->prenda;
            $numeroPedido = $pedido?->numero_pedido;
            $consecutivoOriginal = (string) ($parcial->getRawOriginal('consecutivo_original') ?? $parcial->consecutivo_original ?? '');
            $consecutivoParcial = (string) ($parcial->getRawOriginal('consecutivo_parcial') ?? $parcial->consecutivo_parcial ?? '');

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
                ]],
                'total_recibos' => 1,
                'fecha_creacion' => $proceso?->fecha_inicio ?? $parcial->created_at,
                'estado_pedido' => $pedido->estado ?? 'Pendiente',
                'es_parcial' => true,
                'parcial_id' => $parcial->id,
                'tipo_recibo' => $parcial->tipo_recibo,
                'consecutivo_actual' => $consecutivoParcial,
                'completado_area' => $completadosPorParcialId->has($parcial->id),
            ];
        });

        $prendasConRecibos = $prendasConRecibos
            ->concat($parcialesConRecibos)
            ->sortByDesc(fn ($item) => $item['fecha_creacion'] ?? now())
            ->values();

        return view('control-calidad.dashboard', [
            'usuario' => $usuario,
            'prendasConRecibos' => $prendasConRecibos,
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

                // Actualizar el area del parcial a Entrega
                $parcial->update(['area' => 'Entrega']);

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

                $this->sincronizarEntregaOriginalDesdeParciales($parcial);

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
                        'area' => 'Entrega',
                        'proceso_actual' => 'Entrega',
                    ], 'updated', 'parcial'));
                } catch (\Throwable $e) {
                    \Log::warning('[ControlCalidadController] Error al broadcast ControlCalidadUpdated parcial completado', [
                        'parcial_id' => (int) $parcial->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Parcial marcado como completado y movido a Entrega'
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
                'message' => 'Recibo marcado como completado y movido a Entrega'
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
                // Restaurar el area del parcial a Control de Calidad
                $parcial = ReciboPorPartes::with('pedido')->find((int) $idRecibo);
                if ($parcial) {
                    $parcial->update(['area' => 'Control de Calidad']);

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
                    'message' => 'Marca de completado del parcial eliminada y area restaurada'
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
                'message' => 'Marca de completado eliminada y area restaurada'
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

        $pedidoDB = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with('prendas')
            ->first();

        if (!$pedidoDB) {
            return redirect()->route('control-calidad.dashboard')
                ->with('error', 'Pedido no encontrado');
        }

        $tipoRecibo = strtoupper(trim((string) $request->query('tipo_recibo', '')));
        $tipoRecibo = $tipoRecibo === '' ? null : $tipoRecibo;
        $prendaIdParam = $request->query('prenda_id', null);
        $parcialIdParam = (int) $request->query('parcial_id', 0);
        $consecutivoParcialParam = trim((string) $request->query('consecutivo_parcial', ''));

        $parcialSeleccionado = null;
        if ($tipoRecibo === 'PARCIAL') {
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
            $tieneReciboEnControlCalidad = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoDB->id)
                ->whereIn('tipo_recibo', ['COSTURA', 'REFLECTIVO'])
                ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
                ->where('activo', 1)
                ->exists();

            $tieneParcialEnControlCalidad = false;
            if ($tipoRecibo === 'PARCIAL' && $parcialSeleccionado) {
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
        if ($tipoRecibo === 'PARCIAL' && $parcialSeleccionado) {
            $numeroReciboSeleccionado = $parcialSeleccionado->getRawOriginal('consecutivo_parcial')
                ?? $parcialSeleccionado->consecutivo_parcial;
        } elseif ($tipoRecibo) {
            $queryRecibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoDB->id)
                ->where('tipo_recibo', $tipoRecibo)
                ->where('activo', 1);

            // Si no es líder, filtrar por área
            if (!$esLiderControlCalidad) {
                $queryRecibo->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad']);
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
                $queryReciboCostura->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad']);
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
        if ($prendaIdParam !== null && isset($result['payload']['data']['prendas'])) {
            $prendaIdParam = (int) $prendaIdParam;
            
            // Filtrar solo la prenda especificada
            $prendasFiltradas = array_filter(
                $result['payload']['data']['prendas'],
                fn($prenda) => (int) ($prenda['id'] ?? 0) === $prendaIdParam
            );
            
            // Si encontramos la prenda, dejarla como única
            if (!empty($prendasFiltradas)) {
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

    private function sincronizarEntregaOriginalDesdeParciales(ReciboPorPartes $parcial): void
    {
        $numeroPedido = (int) ($parcial->pedido?->numero_pedido ?? 0);
        if ($numeroPedido <= 0) {
            return;
        }

        $parcialesRelacionados = ReciboPorPartes::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim((string) $parcial->tipo_recibo))])
            ->where('consecutivo_original', $parcial->consecutivo_original)
            ->get(['id', 'consecutivo_parcial']);

        $totalParciales = $parcialesRelacionados->count();
        if ($totalParciales <= 0) {
            return;
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

        $todosParcialesEnEntrega = $parcialesEnEntrega >= $totalParciales;

        $consecutivoOriginalNum = (int) $parcial->consecutivo_original;
        $queryReciboPadre = ConsecutivoReciboPedido::query()
            ->where('pedido_produccion_id', $parcial->pedido_produccion_id)
            ->where('consecutivo_actual', $consecutivoOriginalNum)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim((string) $parcial->tipo_recibo))]);

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

            return;
        }

        $queryReciboPadre->update(['area' => 'Control Calidad']);
        $queryProcesoEntregaPadre->delete();
    }
}
