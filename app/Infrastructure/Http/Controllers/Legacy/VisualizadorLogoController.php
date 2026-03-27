<?php

namespace App\Infrastructure\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;

use App\Application\PedidosLogo\UseCases\GuardarAreaNovedadPedidoLogoUseCase;
use App\Application\PedidosLogo\UseCases\ListPedidosLogoUseCase;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function disenosLogo()
    {
        return view('visualizador-logo.disenos-logo');
    }

    public function disenosLogoData(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        if ($perPage < 1) $perPage = 20;
        if ($perPage > 100) $perPage = 100;

        $query = DB::table('disenos_logo_pedido as dlp')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'dlp.proceso_prenda_detalle_id')
            ->join('prendas_pedido as pr', 'pr.id', '=', 'ppd.prenda_pedido_id')
            ->join('pedidos_produccion as ped', 'ped.id', '=', 'pr.pedido_produccion_id')
            ->select([
                'dlp.id',
                'dlp.url',
                'dlp.observacio_diseño',
                'dlp.created_at',
                'ppd.id as proceso_prenda_detalle_id',
                'pr.id as prenda_pedido_id',
                'ped.id as pedido_id',
                'ped.cliente as cliente',
            ])
            ->orderByDesc('dlp.created_at');

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('ped.cliente', 'like', "%{$search}%")
                  ->orWhere('dlp.observacio_diseño', 'like', "%{$search}%")
                  ->orWhere('dlp.url', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    /**
     * Obtener pedidos tipo Logo
     */
    public function getPedidosLogo(Request $request)
    {
        $search = $request->filled('search') ? (string) $request->get('search') : null;
        $filtro = (string) $request->get('filtro', 'bordado');

        /** @var ListPedidosLogoUseCase $useCase */
        $useCase = app(ListPedidosLogoUseCase::class);
        $recibos = $useCase->execute($search, $filtro, 20);

        return response()->json([
            'success' => true,
            'recibos' => $recibos,
        ]);
    }

    public function guardarAreaNovedadPedidoLogo(Request $request)
    {
        /** @var GuardarAreaNovedadPedidoLogoUseCase $useCase */
        $useCase = app(GuardarAreaNovedadPedidoLogoUseCase::class);
        $result = $useCase->execute($request->all());

        if (!($result['ok'] ?? false)) {
            $status = (int) ($result['status'] ?? 422);
            if (isset($result['errors'])) {
                return response()->json([
                    'success' => false,
                    'errors' => $result['errors'],
                ], $status);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Error guardando Área/Novedad.',
            ], $status);
        }

        return response()->json($result['data'], 200);
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
