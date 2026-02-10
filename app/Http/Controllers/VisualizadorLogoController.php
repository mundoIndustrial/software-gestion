<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Services\CalculadorDiasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VisualizadorLogoController extends Controller
{
    /**
     * Mostrar dashboard del visualizador de cotizaciones logo
     */
    public function dashboard()
    {
        return view('visualizador-logo.dashboard');
    }

    /**
     * Obtener cotizaciones tipo Logo (L) y Combinadas (PL)
     * Solo muestra las que tienen información de logo
     */
    public function getCotizaciones(Request $request)
    {
        \Log::info(' ===== INICIO getCotizaciones =====');
        
        $query = Cotizacion::with([
            'asesor',
            'cliente',
            'logoCotizacion',
            'logoCotizacion.fotos'
        ])
        ->select('cotizaciones.*')
        ->selectRaw('(SELECT nombre FROM clientes WHERE clientes.id = cotizaciones.cliente_id) as cliente_nombre')
        ->whereNotNull('numero_cotizacion') // Solo cotizaciones enviadas (no borradores)
        ->where('es_borrador', false);

        // Filtrar por tipo de cotización
        // Mostrar:
        // 1. Cotizaciones tipo_cotizacion_id = 2 (tipo Logo)
        // 2. Cotizaciones tipo_cotizacion_id = 1 que tengan relación en logo_cotizaciones
        $query->where(function($q) {
            $q->where('tipo_cotizacion_id', 2) // Tipo Logo siempre
              ->orWhere(function($subQ) {
                  // O tipo 1 pero SOLO si están relacionadas a logo_cotizaciones
                  $subQ->where('tipo_cotizacion_id', 1)
                       ->whereHas('logoCotizacion');
              });
        });
        
        \Log::info(' Filtro de tipos de cotización aplicado:', [
            'filtro' => 'tipo_cotizacion_id = 2 O (tipo_cotizacion_id = 1 Y tiene logoCotizacion)'
        ]);

        // Filtros adicionales
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_cotizacion', 'like', "%{$search}%")
                  ->orWhereHas('cliente', function($subQ) use ($search) {
                      $subQ->where('nombre', 'like', "%{$search}%");
                  });
            });
            \Log::info('🔎 Filtro de búsqueda aplicado:', ['search' => $search]);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
            \Log::info(' Filtro de estado aplicado:', ['estado' => $request->estado]);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_envio', '>=', $request->fecha_desde);
            \Log::info('📅 Filtro fecha desde aplicado:', ['fecha_desde' => $request->fecha_desde]);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_envio', '<=', $request->fecha_hasta);
            \Log::info('📅 Filtro fecha hasta aplicado:', ['fecha_hasta' => $request->fecha_hasta]);
        }

        // Ordenar por más reciente
        $query->orderBy('created_at', 'desc');

        // Paginación
        $cotizaciones = $query->paginate(20);
        
        \Log::info(' Total de cotizaciones encontradas:', ['total' => $cotizaciones->total()]);
        
        // Log detallado de cada cotización
        foreach ($cotizaciones->items() as $index => $cot) {
            \Log::info(" Cotización #{$index}:", [
                'id' => $cot->id,
                'numero_cotizacion' => $cot->numero_cotizacion,
                'cliente_id' => $cot->cliente_id,
                'cliente_nombre' => $cot->cliente_nombre,
                'cliente_objeto' => $cot->cliente ? [
                    'id' => $cot->cliente->id ?? null,
                    'nombre' => $cot->cliente->nombre ?? null,
                    'razon_social' => $cot->cliente->razon_social ?? null,
                    'nit' => $cot->cliente->nit ?? null,
                ] : null,
                'cliente_campo_texto' => $cot->cliente ?? null,
                'asesor_id' => $cot->asesor_id,
                'asesor_name' => $cot->asesor?->name ?? null,
                'tipo_cotizacion_id' => $cot->tipo_cotizacion_id,
                'fecha_envio' => $cot->fecha_envio,
                'created_at' => $cot->created_at,
            ]);
        }
        
        \Log::info(' ===== FIN getCotizaciones =====');

        return response()->json([
            'success' => true,
            'cotizaciones' => $cotizaciones
        ]);
    }

    /**
     * Ver detalle de una cotización
     * Solo permite ver información de logo
     */
    public function verCotizacion($id)
    {
        $cotizacion = Cotizacion::with([
            'cliente',
            'asesor',
            'logoCotizacion',
            'logoCotizacion.fotos',
            'tipoCotizacion',
            'prendas.variantes.genero', // Cargar prendas, variantes y género
            'logoCotizacion.tecnicasPrendas.prenda.variantes.genero', // Cargar prendas técnicas con sus variantes y género
            'logoCotizacion.tecnicasPrendas.tipoLogo', // Cargar tipo de logo (BORDADO, ESTAMPADO, etc)
        ])->findOrFail($id);

        // Verificar que sea tipo 2 o tipo 1 con logoCotizacion
        $tipoPermitido = $cotizacion->tipo_cotizacion_id == 2 || 
                        ($cotizacion->tipo_cotizacion_id == 1 && $cotizacion->logoCotizacion);
        
        if (!$tipoPermitido) {
            abort(403, 'No tienes permiso para ver esta cotización. Solo se puede ver cotizaciones tipo Logo (2) o combinadas (1) relacionadas a logo.');
        }

        // Verificar que tenga información de logo
        if (!$cotizacion->logoCotizacion) {
            abort(404, 'Esta cotización no tiene información de logo.');
        }

        return view('visualizador-logo.detalle', compact('cotizacion'));
    }

    /**
     * Mostrar vista de pedidos logo
     */
    public function pedidosLogo()
    {
        return view('visualizador-logo.pedidos-logo');
    }

    /**
     * Obtener pedidos tipo Logo
     */
    public function getPedidosLogo(Request $request)
    {
        \Log::info(' ===== INICIO getPedidosLogo =====');

        $user = Auth::user();
        $isDisenadorLogos = $user && $user->hasRole('diseñador-logos');
        $isBordador = $user && $user->hasRole('bordador');
        $isMinimalLogoRole = $isDisenadorLogos || $isBordador;

        $filtro = $request->get('filtro', 'bordado');
        $tipoProcesoIds = $isMinimalLogoRole
            ? [2]
            : ($filtro === 'estampado' ? [3, 4, 5] : [2]);

        // Listar RECIBOS individuales (procesos aprobados) en vez de pedidos
        $query = PedidosProcesosPrendaDetalle::query()
            ->select([
                'pedidos_procesos_prenda_detalles.*',
                'palp.area as area',
                'palp.novedades as novedades',
                'palp.fechas_areas as fechas_areas',
            ])
            ->leftJoin('prenda_areas_logo_pedido as palp', 'palp.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
            ->with([
                'tipoProceso',
                'prenda.pedidoProduccion.cliente',
                'prenda.pedidoProduccion.asesora'
            ])
            ->whereIn('tipo_proceso_id', $tipoProcesoIds)
            ->where('estado', 'APROBADO')
            ->whereNotNull('numero_recibo');

        if ($isMinimalLogoRole) {
            $query->where('palp.area', $isBordador ? 'BORDANDO' : 'DISENO');
        }

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_recibo', 'like', "%{$search}%")
                  ->orWhereHas('prenda.pedidoProduccion.cliente', function($subQ) use ($search) {
                      $subQ->where('nombre', 'like', "%{$search}%");
                  });
            });
            \Log::info('🔎 Filtro de búsqueda aplicado (recibos):', ['search' => $search]);
        }

        // Ordenar por más reciente (creación del recibo/proceso)
        $query->orderBy('created_at', 'desc');

        $recibos = $query->paginate(20);

        \Log::info(' Total de recibos encontrados:', ['total' => $recibos->total()]);

        // Normalizar items para el frontend
        $recibos->getCollection()->transform(function($proceso) use ($isMinimalLogoRole) {
            $pedido = $proceso->prenda?->pedidoProduccion;
            $clienteNombre = $pedido?->cliente?->nombre
                ?? $pedido?->cliente
                ?? 'Sin cliente';

            $numeroPedido = $pedido?->numero_pedido;
            $asesoraNombre = $pedido?->asesora?->name
                ?? $pedido?->asesor?->name
                ?? '';

            $fechasAreas = null;
            $fechaEntrega = null;
            if (!empty($proceso->fechas_areas)) {
                $decoded = json_decode($proceso->fechas_areas, true);
                if (is_array($decoded)) {
                    $fechasAreas = $decoded;
                    $fechaEntrega = $decoded['ENTREGADO'] ?? null;
                }
            }

            $fechaFinDias = $fechaEntrega ? \Carbon\Carbon::parse($fechaEntrega) : now();
            $totalDias = CalculadorDiasService::calcularDiasHabiles($proceso->created_at, $fechaFinDias) ?? 0;

            if ($isMinimalLogoRole) {
                return [
                    'id' => $proceso->id,
                    'numero_recibo' => $proceso->numero_recibo,
                    'cliente' => $clienteNombre,
                    'created_at' => $proceso->created_at,
                    'area' => $proceso->area,
                    'pedido_id' => $pedido?->id,
                    'prenda_id' => $proceso->prenda_pedido_id,
                    'tipo_proceso' => $proceso->tipoProceso?->nombre,
                    'tipo_proceso_id' => $proceso->tipo_proceso_id,
                ];
            }

            return [
                'id' => $proceso->id,
                'numero_recibo' => $proceso->numero_recibo,
                'cliente' => $clienteNombre,
                'created_at' => $proceso->created_at,
                'fecha_entrega' => $fechaEntrega,
                'fechas_areas' => $fechasAreas,
                'pedido_id' => $pedido?->id,
                'numero_pedido' => $numeroPedido,
                'prenda_id' => $proceso->prenda_pedido_id,
                'tipo_proceso' => $proceso->tipoProceso?->nombre,
                'tipo_proceso_id' => $proceso->tipo_proceso_id,
                'area' => $proceso->area,
                'novedades' => $proceso->novedades,
                'total_dias' => (int) $totalDias,
                'asesora' => $asesoraNombre,
            ];
        });

        \Log::info(' ===== FIN getPedidosLogo =====');

        return response()->json([
            'success' => true,
            'recibos' => $recibos,
        ]);
    }

    public function guardarAreaNovedadPedidoLogo(Request $request)
    {
        $areasPermitidas = [
            'CREACION_DE_ORDEN',
            'PENDIENTE_DISENO',
            'DISENO',
            'PENDIENTE_CONFIRMAR',
            'CORTE_Y_APLIQUE',
            'HACIENDO_MUESTRA',
            'BORDANDO',
            'ENTREGADO',
            'ANULADO',
            'PENDIENTE',
        ];

        $data = $request->validate([
            'proceso_prenda_detalle_id' => ['required', 'integer', 'exists:pedidos_procesos_prenda_detalles,id'],
            'area' => ['required', 'string', Rule::in($areasPermitidas)],
            'novedades' => ['nullable', 'string'],
        ]);

        $proceso = PedidosProcesosPrendaDetalle::query()->select(['id', 'prenda_pedido_id'])->findOrFail($data['proceso_prenda_detalle_id']);

        $now = now();

        DB::transaction(function () use ($proceso, $data, $now) {
            $existente = DB::table('prenda_areas_logo_pedido')
                ->where('proceso_prenda_detalle_id', $proceso->id)
                ->first();

            $fechasAreas = [];
            if ($existente && !empty($existente->fechas_areas)) {
                $decoded = json_decode($existente->fechas_areas, true);
                if (is_array($decoded)) {
                    $fechasAreas = $decoded;
                }
            }

            $fechasAreas[$data['area']] = $now->toDateTimeString();

            if ($existente) {
                DB::table('prenda_areas_logo_pedido')
                    ->where('proceso_prenda_detalle_id', $proceso->id)
                    ->update([
                        'prenda_pedido_id' => $proceso->prenda_pedido_id,
                        'area' => $data['area'],
                        'novedades' => $data['novedades'] ?? null,
                        'fechas_areas' => json_encode($fechasAreas),
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('prenda_areas_logo_pedido')->insert([
                    'proceso_prenda_detalle_id' => $proceso->id,
                    'prenda_pedido_id' => $proceso->prenda_pedido_id,
                    'area' => $data['area'],
                    'novedades' => $data['novedades'] ?? null,
                    'fechas_areas' => json_encode($fechasAreas),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        $row = DB::table('prenda_areas_logo_pedido')
            ->select(['fechas_areas'])
            ->where('proceso_prenda_detalle_id', $proceso->id)
            ->first();

        $fechasAreas = null;
        $fechaEntrega = null;
        if ($row && !empty($row->fechas_areas)) {
            $decoded = json_decode($row->fechas_areas, true);
            if (is_array($decoded)) {
                $fechasAreas = $decoded;
                $fechaEntrega = $decoded['ENTREGADO'] ?? null;
            }
        }

        return response()->json([
            'success' => true,
            'fechas_areas' => $fechasAreas,
            'fecha_entrega' => $fechaEntrega,
        ]);
    }

    /**
     * Obtener estadísticas del dashboard
     */
    public function getEstadisticas()
    {
        $baseQuery = Cotizacion::whereNotNull('numero_cotizacion')
            ->where('es_borrador', false)
            ->where(function($q) {
                // Tipo 2 siempre o tipo 1 con logoCotizacion
                $q->where('tipo_cotizacion_id', 2)
                  ->orWhere(function($subQ) {
                      $subQ->where('tipo_cotizacion_id', 1)
                           ->whereHas('logoCotizacion');
                  });
            });

        $estadisticas = [
            'total' => (clone $baseQuery)->count(),
            'pendientes' => (clone $baseQuery)->where('estado', 'pendiente')->count(),
            'aprobadas' => (clone $baseQuery)->where('estado', 'aprobado')->count(),
            'rechazadas' => (clone $baseQuery)->where('estado', 'rechazado')->count(),
            'este_mes' => (clone $baseQuery)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'estadisticas' => $estadisticas
        ]);
    }
}
