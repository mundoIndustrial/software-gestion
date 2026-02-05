<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        
        // Usar la misma lógica que RegistroOrdenQueryController pero filtrando solo logos con recibos activos
        $query = \App\Models\PedidoProduccion::with([
            'cliente',
            'asesor',
            'cotizacion',
            'cotizacion.tipoCotizacion',
            'prendas',
            'prendas.procesos' => function($q) {
                $q->whereIn('tipo_proceso_id', [2, 3, 4, 5]) // Solo Bordado, Estampado, DTF, Sublimado
                  ->where('estado', 'APROBADO'); // Solo procesos aprobados
            }
        ])
        ->whereNotNull('numero_pedido')
        ->select('pedidos_produccion.*')
        ->selectRaw('(SELECT nombre FROM clientes WHERE clientes.id = pedidos_produccion.cliente_id) as cliente_nombre');

        // Filtrar solo pedidos que tengan al menos un proceso APROBADO de los tipos específicos
        $query->whereHas('prendas.procesos', function($q) {
            $q->whereIn('tipo_proceso_id', [2, 3, 4, 5]) // Bordado, Estampado, DTF, Sublimado
              ->where('estado', 'APROBADO'); // Al menos uno en estado APROBADO
        });
        
        \Log::info(' Filtro de procesos aprobados aplicado:', [
            'filtro' => 'prendas.procesos.tipo_proceso_id IN [2, 3, 4, 5] AND estado = APROBADO'
        ]);

        // Filtros adicionales
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                  ->orWhere('cliente', 'like', "%{$search}%");
            });
            \Log::info('🔎 Filtro de búsqueda aplicado:', ['search' => $search]);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
            \Log::info(' Filtro de estado aplicado:', ['estado' => $request->estado]);
        }

        // Ordenar por más reciente
        $query->orderBy('created_at', 'desc');

        // Paginación
        $pedidos = $query->paginate(20);
        
        \Log::info(' Total de pedidos encontrados:', ['total' => $pedidos->total()]);
        
        // Log detallado de cada pedido con sus procesos aprobados
        foreach ($pedidos->items() as $index => $pedido) {
            $procesosAprobados = [];
            
            // Extraer procesos aprobados de cada prenda
            foreach ($pedido->prendas as $prenda) {
                foreach ($prenda->procesos as $proceso) {
                    if (in_array($proceso->tipo_proceso_id, [2, 3, 4, 5]) && $proceso->estado === 'APROBADO') {
                        $procesosAprobados[] = [
                            'tipo_proceso_id' => $proceso->tipo_proceso_id,
                            'nombre_proceso' => $proceso->nombre_proceso ?? $proceso->tipo_proceso,
                            'estado' => $proceso->estado,
                            'numero_recibo' => $proceso->numero_recibo,
                        ];
                    }
                }
            }
            
            \Log::info(" Pedido #{$index}:", [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->cliente_nombre ?? $pedido->cliente ?? 'Sin cliente',
                'estado' => $pedido->estado,
                'procesos_aprobados' => $procesosAprobados,
            ]);
        }
        
        \Log::info(' ===== FIN getPedidosLogo =====');

        return response()->json([
            'success' => true,
            'pedidos' => $pedidos
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
