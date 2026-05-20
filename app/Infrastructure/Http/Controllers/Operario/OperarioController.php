<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Http\Controllers\Controller;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;
use App\Application\Operario\Services\ObtenerPrendasRecibosService;
use App\Application\Operario\UseCases\GetOperarioDashboardUseCase;
use App\Application\Operario\UseCases\CompletarReciboOperarioUseCase;
use App\Application\Operario\UseCases\CompletarReciboCorteSobremedidaUseCase;
use App\Application\Operario\UseCases\DeshacerReciboOperarioUseCase;
use App\Application\Operario\UseCases\ListarNotificacionesRecibosUseCase;
use App\Application\Operario\UseCases\MarcarNotificacionReciboLeidaUseCase;
use App\Application\Operario\UseCases\MarcarTodasNotificacionesRecibosLeidasUseCase;
use App\Application\Operario\UseCases\CrearNovedadReciboUseCase;
use App\Application\Operario\UseCases\ObtenerNovedadesPrendaUseCase;
use App\Application\Operario\UseCases\EliminarNovedadReciboUseCase;
use App\Application\Operario\UseCases\ActualizarNovedadReciboUseCase;
use App\Application\Operario\UseCases\VerPedidoOperarioUseCase;
use App\Application\Operario\UseCases\ObtenerDatosRecibosOperarioUseCase;
use App\Application\Operario\UseCases\GetPedidoDataOperarioUseCase;
use App\Application\Operario\UseCases\ReportarPendienteOperarioUseCase;
use App\Application\Operario\UseCases\ObtenerDistribucionReciboOperarioUseCase;
use App\Application\Operario\UseCases\ObtenerRecibosControlCalidadUseCase;
use App\Application\Operario\UseCases\ObtenerDistribucionControlCalidadUseCase;
use App\Domain\Operario\Repositories\OperarioRepository;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PrendaBodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

/**
 * Controller: OperarioController
 * 
 * Gestiona vistas y acciones para operarios (cortador/costurero)
 */
class OperarioController extends Controller
{
    public function __construct(
        private ObtenerPedidosOperarioService $obtenerPedidosService,
        private ObtenerPrendasRecibosService $obtenerPrendasRecibosService,
        private OperarioRepository $operarioRepository,
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private GetOperarioDashboardUseCase $getOperarioDashboardUseCase,
        private CompletarReciboOperarioUseCase $completarReciboOperarioUseCase,
        private CompletarReciboCorteSobremedidaUseCase $completarReciboCorteSobremedidaUseCase,
        private DeshacerReciboOperarioUseCase $deshacerReciboOperarioUseCase,
        private ListarNotificacionesRecibosUseCase $listarNotificacionesRecibosUseCase,
        private MarcarNotificacionReciboLeidaUseCase $marcarNotificacionReciboLeidaUseCase,
        private MarcarTodasNotificacionesRecibosLeidasUseCase $marcarTodasNotificacionesRecibosLeidasUseCase,
        private CrearNovedadReciboUseCase $crearNovedadReciboUseCase,
        private ObtenerNovedadesPrendaUseCase $obtenerNovedadesPrendaUseCase,
        private EliminarNovedadReciboUseCase $eliminarNovedadReciboUseCase,
        private ActualizarNovedadReciboUseCase $actualizarNovedadReciboUseCase,
        private VerPedidoOperarioUseCase $verPedidoOperarioUseCase,
        private ObtenerDatosRecibosOperarioUseCase $obtenerDatosRecibosOperarioUseCase,
        private GetPedidoDataOperarioUseCase $getPedidoDataOperarioUseCase,
        private ReportarPendienteOperarioUseCase $reportarPendienteOperarioUseCase,
        private ObtenerDistribucionReciboOperarioUseCase $obtenerDistribucionReciboOperarioUseCase,
        private ObtenerRecibosControlCalidadUseCase $obtenerRecibosControlCalidadUseCase,
        private ObtenerDistribucionControlCalidadUseCase $obtenerDistribucionControlCalidadUseCase,
    ) {
        $this->middleware('auth')->except(['getPedidoData']);
        $this->middleware('operario-access')->except(['getPedidoData']);
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
     * Muestra las prendas con sus recibos de costura
     */
    public function dashboard(Request $request)
    {
        $dashboardData = $this->getOperarioDashboardUseCase->execute($request);
        $prendasConRecibosControlCalidad = collect();
        $resultadosBusquedaFueraDeArea = collect();

        $esVistaCostura = $request->user()?->hasRole('vista-costura') ?? false;
        $filtroEncargado = strtolower(trim((string) $request->query('encargado', '')));
        $filtroRecibo = strtolower(trim((string) $request->query('filtro', 'costura')));
        $busquedaDashboard = strtolower(trim((string) $request->query('q', '')));
        $mensajeBusquedaDashboard = null;

        if ($esVistaCostura && $filtroEncargado === 'control-calidad') {
            $tipoReciboControlCalidad = $filtroRecibo === 'reflectivo' ? 'REFLECTIVO' : 'COSTURA';
            $resultadoCC = $this->obtenerRecibosControlCalidadUseCase->execute($tipoReciboControlCalidad);
            $prendasConRecibosControlCalidad = $this->formatearRecibosControlCalidadParaDashboard(
                (array) ($resultadoCC['payload']['data'] ?? []),
                $tipoReciboControlCalidad
            );

            if ($busquedaDashboard !== '') {
                $prendasConRecibosControlCalidad = $this->filtrarPrendasControlCalidadPorBusqueda(
                    $prendasConRecibosControlCalidad,
                    $busquedaDashboard
                );
            }
        }

        if ($busquedaDashboard !== '' && $dashboardData->prendasConRecibos->isEmpty()) {
            $resultadosBusquedaFueraDeArea = $this->buscarResultadosBusquedaVistaCosturaFueraDeArea(
                $busquedaDashboard,
                $filtroRecibo
            );

            if ($resultadosBusquedaFueraDeArea->isEmpty()) {
                $mensajeBusquedaDashboard = $this->resolverMensajeBusquedaVistaCostura($busquedaDashboard, $filtroRecibo);
            }
        }

        $conteoControlCalidadCostura = 0;
        $conteoControlCalidadReflectivo = 0;
        $conteoControlCalidadBodega = 0;

        // ==================== PARTE 1: CONTAR RECIBOS NORMALES ====================
        $conteoControlCalidadCostura = ConsecutivoReciboPedido::query()
            ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->where('activo', 1)
            ->where('tipo_recibo', 'COSTURA')
            ->count();

        $conteoControlCalidadReflectivo = ConsecutivoReciboPedido::query()
            ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->where('activo', 1)
            ->where('tipo_recibo', 'REFLECTIVO')
            ->count();

        $conteoControlCalidadBodega = ConsecutivoReciboPedido::query()
            ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->where('activo', 1)
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->count();

        // ==================== PARTE 2: CONTAR PARCIALES ====================
        $parcialesCCCounts = DB::table('procesos_prenda')
            ->leftJoin('pedidos_produccion as pp', 'pp.numero_pedido', '=', 'procesos_prenda.numero_pedido')
            ->leftJoin('recibo_por_partes', function ($join) {
                $join->on('recibo_por_partes.pedido_produccion_id', '=', 'pp.id')
                    ->on('recibo_por_partes.prenda_pedido_id', '=', 'procesos_prenda.prenda_pedido_id')
                    ->on('recibo_por_partes.consecutivo_parcial', '=', 'procesos_prenda.numero_recibo_parcial');
            })
            ->whereRaw('LOWER(TRIM(procesos_prenda.proceso)) IN (?, ?)', ['control de calidad', 'control calidad'])
            ->whereNull('procesos_prenda.deleted_at')
            ->select('recibo_por_partes.tipo_recibo', DB::raw('count(*) as total'))
            ->groupBy('recibo_por_partes.tipo_recibo')
            ->get();

        foreach ($parcialesCCCounts as $fila) {
            if ($fila->tipo_recibo) {
                $tipoRecibo = strtoupper(trim((string) $fila->tipo_recibo));
                if ($tipoRecibo === 'COSTURA') {
                    $conteoControlCalidadCostura += $fila->total;
                } elseif ($tipoRecibo === 'REFLECTIVO') {
                    $conteoControlCalidadReflectivo += $fila->total;
                }
            }
        }

        return view('operario.dashboard', [
            'operario' => $dashboardData->operario,
            'prendasConRecibos' => $dashboardData->prendasConRecibos,
            'usuario' => $dashboardData->usuario,
            'tab' => $dashboardData->tab,
            'recibosCompletados' => $dashboardData->recibosCompletados,
            'recibosCompletadosCount' => $dashboardData->recibosCompletadosCount,
            'recibosBodegaCompletados' => $dashboardData->recibosBodegaCompletados,
            'recibosBodegaCompletadosCount' => $dashboardData->recibosBodegaCompletadosCount,
            'pendientesPedidosCount' => $dashboardData->pendientesPedidosCount,
            'recibosBodegaPendientesCount' => $dashboardData->recibosBodegaPendientesCount,
            'vistaCosturaSinEncargadoCount' => $dashboardData->vistaCosturaSinEncargadoCount,
            'vistaCosturaBodegaSinEncargadoCount' => $dashboardData->vistaCosturaBodegaSinEncargadoCount,
            'vistaCosturaBodegaControlCalidadCount' => $dashboardData->vistaCosturaBodegaControlCalidadCount,
            'prendasConRecibosControlCalidad' => $prendasConRecibosControlCalidad,
            'resultadosBusquedaFueraDeArea' => $resultadosBusquedaFueraDeArea,
            'mensajeBusquedaDashboard' => $mensajeBusquedaDashboard,
            'conteoControlCalidadCostura' => $conteoControlCalidadCostura,
            'conteoControlCalidadReflectivo' => $conteoControlCalidadReflectivo,
            'conteoControlCalidadBodega' => $conteoControlCalidadBodega,
        ]);
    }

    private function formatearRecibosControlCalidadParaDashboard(array $recibos, string $tipoRecibo): \Illuminate\Support\Collection
    {
        return collect($recibos)
            ->map(function (array $item) use ($tipoRecibo) {
                $reciboPrincipal = collect($item['recibos'] ?? [])->first() ?? [];
                $fechaCreacion = $item['fecha_creacion'] ?? ($reciboPrincipal['creado_en'] ?? null);
                $consecutivoActual = $reciboPrincipal['consecutivo_actual']
                    ?? $item['consecutivo_actual']
                    ?? '';
                $consecutivoInicial = $reciboPrincipal['consecutivo_inicial']
                    ?? $item['consecutivo_inicial']
                    ?? $consecutivoActual;
                $esParcial = (bool) ($item['es_parcial'] ?? ($reciboPrincipal['es_parcial'] ?? false));
                $parcialId = $item['parcial_id'] ?? ($reciboPrincipal['parcial_id'] ?? null);

                return [
                    'prenda_id' => (int) ($item['prenda_id'] ?? 0),
                    'pedido_id' => (int) ($item['pedido_id'] ?? 0),
                    'numero_pedido' => (string) ($item['numero_pedido'] ?? ''),
                    'cliente' => (string) ($item['cliente'] ?? ''),
                    'nombre_prenda' => (string) ($item['nombre_prenda'] ?? ''),
                    'descripcion' => (string) ($item['descripcion'] ?? ''),
                    'proceso_actual' => (string) ($item['proceso_actual'] ?? 'Control Calidad'),
                    'de_bodega' => $item['de_bodega'] ?? null,
                    'tiene_parciales' => (bool) ($item['tiene_parciales'] ?? false),
                    'es_parcial' => $esParcial,
                    'parcial_id' => $parcialId,
                    'estado_pedido' => (string) ($item['estado_pedido'] ?? 'Pendiente'),
                    'fecha_creacion' => $fechaCreacion,
                    'tipo_recibo' => $tipoRecibo,
                    'recibos' => [[
                        'id' => (int) ($reciboPrincipal['id'] ?? ($item['id'] ?? 0)),
                        'tipo_recibo' => $tipoRecibo,
                        'consecutivo_actual' => $consecutivoActual,
                        'consecutivo_inicial' => $consecutivoInicial,
                        'notas' => (string) ($reciboPrincipal['notas'] ?? ''),
                        'creado_en' => $fechaCreacion,
                        'created_at' => $fechaCreacion,
                        'area' => (string) ($reciboPrincipal['area'] ?? 'Control Calidad'),
                        'es_parcial' => $esParcial,
                        'parcial_id' => $parcialId,
                        'pedido_parcial_id' => $parcialId,
                        'tiene_parciales' => (bool) ($item['tiene_parciales'] ?? false),
                        'encargado_costura' => $reciboPrincipal['encargado_costura'] ?? null,
                        'encargado_corte' => $reciboPrincipal['encargado_corte'] ?? null,
                        'encargado_control_calidad' => $reciboPrincipal['encargado_control_calidad'] ?? null,
                        'completado_area' => (bool) ($reciboPrincipal['completado_area'] ?? false),
                        'completado_corte' => (bool) ($reciboPrincipal['completado_corte'] ?? false),
                        'completado_costura' => (bool) ($reciboPrincipal['completado_costura'] ?? false),
                        'completado_control_calidad' => (bool) ($reciboPrincipal['completado_area'] ?? false),
                    ]],
                    'total_recibos' => 1,
                ];
            })
            ->sortBy(fn (array $item) => $item['fecha_creacion'] instanceof \DateTimeInterface
                ? $item['fecha_creacion']->getTimestamp()
                : ((is_numeric($item['fecha_creacion'])
                    ? (int) $item['fecha_creacion']
                    : (strtotime((string) $item['fecha_creacion']) ?: 0))))
            ->values();
    }

    private function filtrarPrendasControlCalidadPorBusqueda(\Illuminate\Support\Collection $prendas, string $busqueda): \Illuminate\Support\Collection
    {
        $busqueda = strtolower(trim($busqueda));

        if ($busqueda === '') {
            return $prendas->values();
        }

        return $prendas->filter(function (array $prenda) use ($busqueda) {
            $camposPrenda = [
                strtolower(trim((string) ($prenda['numero_pedido'] ?? ''))),
                strtolower(trim((string) ($prenda['cliente'] ?? ''))),
                strtolower(trim((string) ($prenda['nombre_prenda'] ?? ''))),
                strtolower(trim((string) ($prenda['descripcion'] ?? ''))),
            ];

            foreach ($camposPrenda as $campo) {
                if ($campo !== '' && str_contains($campo, $busqueda)) {
                    return true;
                }
            }

            foreach (($prenda['recibos'] ?? []) as $recibo) {
                $camposRecibo = [
                    strtolower(trim((string) ($recibo['consecutivo_actual'] ?? ''))),
                    strtolower(trim((string) ($recibo['consecutivo_inicial'] ?? ''))),
                    strtolower(trim((string) ($recibo['tipo_recibo'] ?? ''))),
                    strtolower(trim((string) ($recibo['area'] ?? ''))),
                    strtolower(trim((string) ($recibo['notas'] ?? ''))),
                ];

                foreach ($camposRecibo as $campo) {
                    if ($campo !== '' && str_contains($campo, $busqueda)) {
                        return true;
                    }
                }
            }

            return false;
        })->values();
    }

    private function buscarResultadosBusquedaVistaCosturaFueraDeArea(string $busqueda, ?string $filtroRecibo): \Illuminate\Support\Collection
    {
        $busqueda = strtolower(trim($busqueda));

        if ($busqueda === '') {
            return collect();
        }

        $esNumerica = ctype_digit($busqueda);
        $comodin = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busqueda) . '%';
        $areasVisibles = in_array($filtroRecibo, ['reflectivo', 'bodega'], true)
            ? ['costura', 'control calidad', 'control de calidad']
            : ['corte', 'costura', 'control calidad', 'control de calidad'];
        $tiposPermitidos = $this->tiposReciboBusquedaVistaCostura($filtroRecibo);

        return DB::table('consecutivos_recibos_pedidos as crp')
            ->leftJoin('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->leftJoin('prendas_pedido as pr', 'pr.id', '=', 'crp.prenda_id')
            ->select([
                'crp.id',
                'crp.pedido_produccion_id',
                'crp.prenda_id',
                'crp.consecutivo_actual',
                'crp.consecutivo_inicial',
                'crp.tipo_recibo',
                'crp.area',
                'crp.estado',
                'crp.created_at',
                'crp.notas',
                'pp.numero_pedido',
                'pp.cliente',
                'pr.nombre_prenda',
                'pr.descripcion',
            ])
            ->where(function ($query) use ($comodin, $busqueda, $esNumerica) {
                if ($esNumerica) {
                    $query
                        ->orWhereRaw('CAST(crp.consecutivo_actual AS CHAR) = ?', [$busqueda])
                        ->orWhereRaw('CAST(crp.consecutivo_inicial AS CHAR) = ?', [$busqueda])
                        ->orWhereRaw('CAST(pp.numero_pedido AS CHAR) = ?', [$busqueda]);
                    return;
                }

                $query
                    ->orWhereRaw('LOWER(CAST(crp.consecutivo_actual AS CHAR)) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(CAST(crp.consecutivo_inicial AS CHAR)) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.tipo_recibo, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.area, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.estado, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.notas, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pp.numero_pedido, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pp.cliente, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pr.nombre_prenda, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pr.descripcion, "")) LIKE ?', [$comodin]);
            })
            ->where(function ($query) use ($tiposPermitidos) {
                foreach ($tiposPermitidos as $tipoPermitido) {
                    $query->orWhereRaw('UPPER(COALESCE(crp.tipo_recibo, "")) = ?', [$tipoPermitido]);
                }
            })
            ->orderBy('crp.created_at')
            ->get()
            ->filter(function ($row) use ($areasVisibles) {
                $area = strtolower(trim((string) ($row->area ?? '')));
                return !in_array($area, $areasVisibles, true);
            })
            ->map(function ($row) {
                $area = trim((string) ($row->area ?? ''));
                $estado = trim((string) ($row->estado ?? ''));
                $tipoRecibo = strtoupper(trim((string) ($row->tipo_recibo ?? '')));
                $consecutivoActual = (string) ($row->consecutivo_actual ?? '');
                $consecutivoInicial = (string) ($row->consecutivo_inicial ?? $consecutivoActual);
                $fechaCreacion = $row->created_at ?? null;

                return [
                    'recibo_id' => (int) ($row->id ?? 0),
                    'pedido_id' => (int) ($row->pedido_produccion_id ?? 0),
                    'prenda_id' => (int) ($row->prenda_id ?? 0),
                    'numero_pedido' => (string) ($row->numero_pedido ?? ''),
                    'cliente' => (string) ($row->cliente ?? ''),
                    'nombre_prenda' => (string) ($row->nombre_prenda ?? ''),
                    'descripcion' => (string) ($row->descripcion ?? ''),
                    'tipo_recibo' => $tipoRecibo,
                    'area' => $area,
                    'estado' => $estado,
                    'consecutivo_actual' => $consecutivoActual,
                    'consecutivo_inicial' => $consecutivoInicial,
                    'notas' => (string) ($row->notas ?? ''),
                    'fecha_creacion' => $fechaCreacion,
                    'area_label' => strtoupper($area !== '' ? $area : 'OTRA ÁREA'),
                    'estado_label' => strtoupper($estado !== '' ? $estado : 'SIN ESTADO'),
                ];
            })
            ->values();
    }

    private function resolverMensajeBusquedaVistaCostura(string $busqueda, ?string $filtroRecibo = null): ?string
    {
        $busqueda = strtolower(trim($busqueda));

        if ($busqueda === '') {
            return null;
        }

        $esNumerica = ctype_digit($busqueda);
        $comodin = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busqueda) . '%';
        $tiposPermitidos = $this->tiposReciboBusquedaVistaCostura($filtroRecibo);

        $coincidencias = DB::table('consecutivos_recibos_pedidos as crp')
            ->leftJoin('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->leftJoin('prendas_pedido as pr', 'pr.id', '=', 'crp.prenda_id')
            ->select([
                'crp.id',
                'crp.consecutivo_actual',
                'crp.consecutivo_inicial',
                'crp.tipo_recibo',
                'crp.area',
                'crp.estado',
                'pp.numero_pedido',
                'pp.cliente',
                'pr.nombre_prenda',
                'pr.descripcion',
            ])
            ->where(function ($query) use ($comodin, $busqueda, $esNumerica) {
                if ($esNumerica) {
                    $query
                        ->orWhereRaw('CAST(crp.consecutivo_actual AS CHAR) = ?', [$busqueda])
                        ->orWhereRaw('CAST(crp.consecutivo_inicial AS CHAR) = ?', [$busqueda])
                        ->orWhereRaw('CAST(pp.numero_pedido AS CHAR) = ?', [$busqueda]);
                    return;
                }

                $query
                    ->orWhereRaw('LOWER(CAST(crp.consecutivo_actual AS CHAR)) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(CAST(crp.consecutivo_inicial AS CHAR)) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.tipo_recibo, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.area, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.estado, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(crp.notas, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pp.numero_pedido, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pp.cliente, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pr.nombre_prenda, "")) LIKE ?', [$comodin])
                    ->orWhereRaw('LOWER(COALESCE(pr.descripcion, "")) LIKE ?', [$comodin]);
            })
            ->where(function ($query) use ($tiposPermitidos) {
                foreach ($tiposPermitidos as $tipoPermitido) {
                    $query->orWhereRaw('UPPER(COALESCE(crp.tipo_recibo, "")) = ?', [$tipoPermitido]);
                }
            })
            ->limit(20)
            ->get();

        if ($coincidencias->isEmpty()) {
            return null;
        }

        $anuladas = $coincidencias->filter(function ($row) {
            $estado = strtolower(trim((string) ($row->estado ?? '')));
            $area = strtolower(trim((string) ($row->area ?? '')));

            return in_array($estado, ['anulada', 'anulado'], true)
                || in_array($area, ['anulada', 'anulado'], true);
        });

        if ($anuladas->isNotEmpty()) {
            $numero = $anuladas->first()?->consecutivo_actual ?? $anuladas->first()?->numero_pedido ?? $busqueda;
            return "Encontré coincidencias, pero el recibo #{$numero} está en área Anulado con estado Anulado.";
        }

        $entregaDespacho = $coincidencias->filter(function ($row) {
            $area = strtolower(trim((string) ($row->area ?? '')));
            return in_array($area, ['entrega', 'despacho'], true);
        });

        if ($entregaDespacho->isNotEmpty()) {
            $numero = $entregaDespacho->first()?->consecutivo_actual ?? $entregaDespacho->first()?->numero_pedido ?? $busqueda;
            $area = strtoupper(trim((string) ($entregaDespacho->first()?->area ?? '')));
            return "Encontré coincidencias, pero el recibo #{$numero} ya está en {$area}.";
        }

        if ($filtroRecibo === 'bodega') {
            return 'Encontré coincidencias, pero no están en Bodega.';
        }

        return 'Encontré coincidencias, pero no están en Costura.';
    }

    private function tiposReciboBusquedaVistaCostura(?string $filtroRecibo): array
    {
        if ($filtroRecibo === 'bodega') {
            return ['CORTE-PARA-BODEGA'];
        }

        return $filtroRecibo === 'reflectivo'
            ? ['REFLECTIVO']
            : ['COSTURA'];
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
    /**
     * Ver detalle de un pedido
     */
    public function verPedido($numeroPedido)
    {
        $result = $this->verPedidoOperarioUseCase->execute((int) $numeroPedido, request());

        if ((int) ($result['status'] ?? 200) === 302) {
            return redirect()->route((string) $result['redirect_route'])
                ->with('error', (string) $result['redirect_error']);
        }

        return view((string) $result['view'], (array) $result['data']);
    }
    public function obtenerPedidosJson(Request $request)
    {
        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        return response()->json($datosOperario->toArray());
    }
    public function listarNotificacionesRecibos(Request $request): JsonResponse
    {
        try {
            $result = $this->listarNotificacionesRecibosUseCase->execute($request);
            $items = collect($result['items'] ?? []);

            return response()->json([
                'success' => true,
                'total' => $items->count(),
                'notificaciones' => $items,
            ]);
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error listarNotificacionesRecibos', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al listar notificaciones',
                'total' => 0,
                'notificaciones' => [],
            ], 500);
        }
    }
    public function marcarNotificacionReciboLeida(Request $request, $id): JsonResponse
    {
        try {
            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo', 'COSTURA')));

            $result = $this->marcarNotificacionReciboLeidaUseCase->execute((int) $id, $tipoRecibo);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
                'recibo_id' => $result['recibo_id'] ?? null,
            ], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error marcarNotificacionReciboLeida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como leidas',
            ], 500);
        }
    }
    public function marcarTodasNotificacionesRecibosLeidas(Request $request): JsonResponse
    {
        try {
            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo', 'COSTURA')));

            $result = $this->marcarTodasNotificacionesRecibosLeidasUseCase->execute($tipoRecibo);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
                'total' => (int) ($result['total'] ?? 0),
            ], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error marcarTodasNotificacionesRecibosLeidas', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar todas como leidas',
            ], 500);
        }
    }
    /**
     * API: Obtener datos del pedido para el modal móvil de operarios
     * Endpoint: /api/operario/pedido/{numeroPedido}
     */
    public function obtenerDatosRecibosOperario($numeroPedido)
    {
        try {
            $result = $this->obtenerDatosRecibosOperarioUseCase->execute((int) $numeroPedido, request());
            return response()->json($result['payload'] ?? [], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioController] ERROR en obtenerDatosRecibosOperario', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'not found',
                'message' => 'Pedido no encontrado'
            ], 404);
        }
    }

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
     * Obtener novedades existentes de un pedido
     */
    public function obtenerNovedades($numeroPedido)
    {
        try {
            // Obtener novedades de procesos_prenda
            $proceso = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)->first();
            $novedades = $proceso?->novedades ?? '';

            return response()->json([
                'success' => true,
                'novedades' => $novedades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener novedades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reportar pendiente - Cambiar estado del proceso a Pendiente y guardar novedad
     * OPTIMIZADO: Guarda novedad en procesos_prenda, pedidos_produccion y tabla_original_bodega
     */
    /**
     * Reportar pendiente - Cambiar estado del proceso a Pendiente y guardar novedad
     * OPTIMIZADO: Guarda novedad en procesos_prenda, pedidos_produccion y tabla_original_bodega
     */
    public function reportarPendiente(Request $request)
    {
        $request->validate([
            'numero_pedido' => 'required|numeric',
            'novedad' => 'required|string',
        ]);

        try {
            $result = $this->reportarPendienteOperarioUseCase->execute($request);
            return response()->json($result['payload'] ?? [], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reportar la novedad: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * API: Obtener datos completos del pedido (igual que /pedidos-public/{id}/recibos-datos)
     * Usa el mismo endpoint y lógica que el módulo de recibos públicos
     */
    public function getPedidoData($numeroPedido)
    {
        $result = $this->getPedidoDataOperarioUseCase->execute((int) $numeroPedido, request());

        // FILTRAR POR PRENDA_ID si se proporciona
        $prendaIdParam = request()->query('prenda_id');
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

    /**
     * API: Obtener datos de una prenda de bodega para el modal de asignación.
     * Endpoint: /operario/api/prenda-bodega/{prendaBodegaId}
     */
    public function obtenerDatosPrendaBodega($prendaBodegaId): JsonResponse
    {
        try {
            $prenda = PrendaBodega::with([
                'tallas' => function ($query) {
                    $query->orderBy('genero')->orderBy('talla')->orderBy('color');
                },
            ])->findOrFail((int) $prendaBodegaId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => (int) $prenda->id,
                    'numero_recibo' => (int) $prenda->numero_recibo,
                    'nombre' => (string) $prenda->nombre,
                    'descripcion' => (string) $prenda->descripcion,
                    'tallas' => $prenda->tallas->map(function ($talla) {
                        return [
                            'talla' => (string) $talla->talla,
                            'genero' => $talla->genero ? (string) $talla->genero : null,
                            'color' => $talla->color ? (string) $talla->color : null,
                            'cantidad' => (int) $talla->cantidad,
                        ];
                    })->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('[OperarioController] Error obteniendo datos de prenda de bodega', [
                'prenda_bodega_id' => $prendaBodegaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la prenda de bodega',
            ], 404);
        }
    }

    public function debugPrendasRecibos()
    {
        try {
            $usuario = Auth::user();

            // Obtener prendas con recibos usando el servicio
            $prendasConRecibos = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibos($usuario);

            // Obtener información de la BD sin filtros
            $todosPedidos = \App\Models\PedidoProduccion::where('area', 'costura')
                ->select('id', 'numero_pedido', 'estado', 'area')
                ->get();

            $receptivos = \App\Models\ConsecutivoReciboPedido::where('activo', 1)
                ->whereIn('tipo_recibo', ['REFLECTIVO', 'COSTURA'])
                ->with(['pedido:id,numero_pedido,estado', 'prenda:id,nombre_prenda'])
                ->get();

            $detallesProcesos = \App\Models\PedidosProcesosPrendaDetalle::select('id', 'prenda_pedido_id', 'estado', 'tipo_recibo')
                ->whereIn('estado', ['APROBADO', 'PENDIENTE'])
                ->get();

            return response()->json([
                'success' => true,
                'usuario' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'roles' => $usuario->roles()->pluck('name')->toArray()
                ],
                'prendas_con_recibos_filtradas' => [
                    'total' => $prendasConRecibos->count(),
                    'datos' => $prendasConRecibos->map(function ($p) {
                        return [
                            'numero_pedido' => $p['numero_pedido'],
                            'nombre_prenda' => $p['nombre_prenda'],
                            'total_recibos' => $p['total_recibos'],
                            'tipos_recibos' => array_map(fn($r) => $r['tipo_recibo'], $p['recibos'])
                        ];
                    })->toArray()
                ],
                'todos_pedidos_costura' => [
                    'total' => $todosPedidos->count(),
                    'datos' => $todosPedidos->map(function ($p) {
                        return [
                            'numero_pedido' => $p->numero_pedido,
                            'estado' => $p->estado,
                        ];
                    })->toArray()
                ],
                'recibos_si_filtros' => [
                    'total' => $receptivos->count(),
                    'datos' => $receptivos->map(function ($r) {
                        return [
                            'tipo_recibo' => $r->tipo_recibo,
                            'pedido_numero' => $r->pedido?->numero_pedido,
                            'pedido_estado' => $r->pedido?->estado,
                            'prenda_nombre' => $r->prenda?->nombre_prenda,
                        ];
                    })->toArray()
                ],
                'detalles_procesos' => [
                    'total' => $detallesProcesos->count(),
                    'aprobados' => $detallesProcesos->where('estado', 'APROBADO')->count(),
                    'pendientes' => $detallesProcesos->where('estado', 'PENDIENTE')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Crear novedad de prenda/recibo
     * POST /operario/api/novedades/crear
     */
    /**
     * API: Crear novedad de prenda/recibo
     * POST /operario/api/novedades/crear
     */
    public function crearNovedad(Request $request)
    {
        try {
            $request->validate([
                'numero_pedido' => 'required|numeric',
                'prenda_id' => 'required|numeric',
                'numero_recibo' => 'required|string',
                'novedad_texto' => 'required|string|min:5',
                'tipo_novedad' => 'required|in:observacion,problema,cambio,correccion,aprobacion,rechazo'
            ]);

            $result = $this->crearNovedadReciboUseCase->execute($request);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
            ], (int) ($result['status'] ?? 200));

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error creando novedad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear novedad: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * API: Obtener novedades de una prenda
     * GET /operario/api/novedades/{numeroPedido}/{prendaId}
     */
    public function obtenerNovedadesPrenda($numeroPedido, $prendaId)
    {
        try {
            $result = $this->obtenerNovedadesPrendaUseCase->execute((int) $prendaId);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'novedades' => $result['novedades'] ?? [],
            ], (int) ($result['status'] ?? 200));

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error obteniendo novedades: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener novedades'
            ], 500);
        }
    }
    /**
     * API: Eliminar novedad
     * DELETE /operario/api/novedades/{id}
     */
    public function eliminarNovedad($id)
    {
        try {
            $result = $this->eliminarNovedadReciboUseCase->execute((int) $id);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
            ], (int) ($result['status'] ?? 200));

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error eliminando novedad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar novedad'
            ], 500);
        }
    }
    /**
     * API: Actualizar novedad
     * PUT /operario/api/novedades/{id}
     */
    public function actualizarNovedad(Request $request, $id)
    {
        try {
            $request->validate([
                'novedad_texto' => 'required|string|min:5',
                'tipo_novedad' => 'required|in:observacion,problema,cambio,correccion,aprobacion,rechazo'
            ]);

            $result = $this->actualizarNovedadReciboUseCase->execute($request, (int) $id);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
            ], (int) ($result['status'] ?? 200));

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error actualizando novedad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar novedad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /operario/api/recibos/control-calidad/{tipoRecibo}
     * Obtiene recibos en el área Control de Calidad filtrados por tipo
     */
    public function obtenerRecibosControlCalidad(Request $request, $tipoRecibo): JsonResponse
    {
        $resultado = $this->obtenerRecibosControlCalidadUseCase->execute($tipoRecibo);
        return response()->json($resultado['payload'], $resultado['status']);
    }

    /**
     * GET /operario/api/recibos/{idRecibo}/distribucion-control-calidad
     * Obtiene solo los parciales en Control de Calidad
     */
    public function obtenerDistribucionControlCalidad(Request $request, $idRecibo): JsonResponse
    {
        $resultado = $this->obtenerDistribucionControlCalidadUseCase->execute((int) $idRecibo);
        return response()->json($resultado['payload'], $resultado['status']);
    }

    /**
     * GET /operario/api/recibos/{idRecibo}/distribucion
     */
    public function obtenerDistribucionRecibo(Request $request, $idRecibo)
    {
        try {
            $result = $this->obtenerDistribucionReciboOperarioUseCase->execute((int) $idRecibo);
            return response()->json($result['payload'] ?? [], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error obteniendo distribución: ' . $e->getMessage(), [
                'recibo_id' => $idRecibo,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener distribución: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /operario/api/recibos-procesos/observacion
     * Obtiene observacion de proceso por pedido + prenda + tipo.
     */
    public function obtenerObservacionReciboProceso(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_id' => 'required|integer|exists:pedidos_produccion,id',
            'prenda_id' => 'nullable|integer',
            'parcial_id' => 'nullable|integer',
            'tipo_proceso' => 'required|string|max:100',
        ]);

        $pedidoId = (int) $validated['pedido_id'];
        $prendaId = (int) ($validated['prenda_id'] ?? 0);
        $parcialId = (int) ($validated['parcial_id'] ?? 0);
        $tipoProceso = $this->normalizarTipoProceso((string) $validated['tipo_proceso']);
        $prendaIdsCandidatas = [];
        if ($prendaId > 0) {
            $prendaIdsCandidatas[] = $prendaId;
        }
        if ($parcialId > 0) {
            $prendaParcialId = (int) DB::table('pedidos_parciales')
                ->where('id', $parcialId)
                ->where('pedido_produccion_id', $pedidoId)
                ->value('prenda_pedido_id');
            if ($prendaParcialId > 0) {
                $prendaIdsCandidatas[] = $prendaParcialId;
            }
        }
        $prendaIdsCandidatas = array_values(array_unique(array_filter($prendaIdsCandidatas, fn($id) => (int) $id > 0)));

        $row = null;
        foreach ($this->tiposProcesoCandidatos($tipoProceso) as $tipoCandidato) {
            if (!empty($prendaIdsCandidatas)) {
                foreach ($prendaIdsCandidatas as $prendaCandidataId) {
                    $row = DB::table('observaciones_recibos_procesos')
                        ->where('pedido_produccion_id', $pedidoId)
                        ->where('prenda_pedido_id', (int) $prendaCandidataId)
                        ->where('tipo_proceso', $tipoCandidato)
                        ->orderByDesc('updated_at')
                        ->first();

                    if ($row) {
                        break 2;
                    }
                }
            } else {
                $row = DB::table('observaciones_recibos_procesos')
                    ->where('pedido_produccion_id', $pedidoId)
                    ->where('tipo_proceso', $tipoCandidato)
                    ->orderByDesc('updated_at')
                    ->first();
                if ($row) {
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pedido_id' => $pedidoId,
                'prenda_id' => (int) ($row->prenda_pedido_id ?? $prendaId),
                'tipo_proceso' => $tipoProceso,
                'observacion' => $row?->observacion,
                'updated_at' => $row?->updated_at,
            ],
        ]);
    }

    /**
     * API: Completar recibo (normal o parcial)
     * POST /operario/api/recibos/{idRecibo}/completar
     */
    public function completarRecibo(Request $request, $idRecibo): JsonResponse
    {
        try {
            $esParcial = (bool) ($request->boolean('es_parcial')
                || $request->boolean('esParcial'));

            $result = $this->completarReciboOperarioUseCase->execute((int) $idRecibo, $esParcial);

            return response()->json([
                'success' => (bool) $result->success,
                'message' => (string) $result->message,
                'data' => $result->data,
            ], (int) $result->statusCode);
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error al completar recibo', [
                'id_recibo' => (int) $idRecibo,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al completar el recibo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Deshacer completado de recibo (normal o parcial)
     * DELETE /operario/api/recibos/{idRecibo}/deshacer
     */
    public function deshacerRecibo(Request $request, $idRecibo): JsonResponse
    {
        try {
            $esParcial = (bool) ($request->boolean('es_parcial')
                || $request->boolean('esParcial'));

            $result = $this->deshacerReciboOperarioUseCase->execute((int) $idRecibo, $esParcial);

            return response()->json([
                'success' => (bool) $result->success,
                'message' => (string) $result->message,
                'data' => $result->data,
            ], (int) $result->statusCode);
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error al deshacer recibo', [
                'id_recibo' => (int) $idRecibo,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al deshacer el recibo: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deshacerParcial(Request $request, $id)
    {
        try {
            // Validar autenticación
            $usuario = Auth::user();
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Encontrar el parcial
            $parcial = \App\Models\ReciboPorPartes::with(['pedido', 'prenda'])->findOrFail($id);

            // Iniciar transacción
            DB::beginTransaction();

            try {
                // Obtener identificadores para borrar procesos_prenda
                $numeroPedido = $parcial->pedido?->numero_pedido;
                $prendaPedidoId = $parcial->prenda_pedido_id;
                $numeroReciboParcial = $parcial->consecutivo_parcial;
                $esBodega = strtoupper(trim((string) ($parcial->tipo_recibo ?? ''))) === 'CORTE-PARA-BODEGA';

                \Log::info('[DeshacerParcial] Iniciando eliminación', [
                    'parcial_id' => $id,
                    'numero_pedido' => $numeroPedido,
                    'prenda_pedido_id' => $prendaPedidoId,
                    'numero_recibo_parcial' => $numeroReciboParcial
                ]);

                // 1. Eliminar tallas asociadas
                $tallasEliminadas = \App\Models\ReciboPorPartesTalla::where('recibo_por_partes_id', $id)->delete();
                \Log::info('[DeshacerParcial] Tallas eliminadas', ['count' => $tallasEliminadas]);

                // 2. Eliminar COMPLETAMENTE procesos_prenda asociados al parcial
                $procesosParcialQuery = \App\Models\ProcesoPrenda::withTrashed()
                    ->where('numero_pedido', $numeroPedido)
                    ->where('numero_recibo_parcial', $numeroReciboParcial);

                if ($esBodega) {
                    $procesosParcialQuery->where('prenda_bodega_id', $prendaPedidoId);
                } else {
                    $procesosParcialQuery->where('prenda_pedido_id', $prendaPedidoId);
                }

                $procesosParcial = $procesosParcialQuery->get();

                $procesosEliminados = 0;
                foreach ($procesosParcial as $procesoParcial) {
                    $procesoParcial->forceDelete();
                    $procesosEliminados++;
                }
                \Log::info('[DeshacerParcial] Procesos eliminados', ['count' => $procesosEliminados]);

                // 3. Eliminar el parcial
                $parcialEliminado = $parcial->delete();
                \Log::info('[DeshacerParcial] Parcial eliminado', ['deleted' => $parcialEliminado]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Parcial eliminado correctamente',
                    'deleted' => [
                        'tallas' => $tallasEliminadas,
                        'procesos' => $procesosEliminados,
                        'parcial' => $parcialEliminado
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('[DeshacerParcial] Error durante eliminación', [
                    'parcial_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parcial no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar parcial: ' . $e->getMessage()
            ], 500);
        }
    }

    private function normalizarTipoProceso(string $tipoProceso): string
    {
        return mb_strtoupper(trim($tipoProceso), 'UTF-8');
    }

    /**
     * Los parciales se tratan como COSTURA dentro de operario.
     *
     * @return string[]
     */
    private function tiposProcesoCandidatos(string $tipoProceso): array
    {
        $tipo = $this->normalizarTipoProceso($tipoProceso);

        if ($tipo === 'PARCIAL' || $tipo === 'COSTURA' || $tipo === 'COSTURA-BODEGA') {
            return ['COSTURA'];
        }

        return [$tipo];
    }

    /**
     * API: Completar recibo en área Corte desde pestaña sobremedida
     * POST /operario/api/recibos/{idRecibo}/completar-corte-sobremedida
     * 
     * Solo para administrador-costura
     * Mueve el recibo de Corte a Costura y crea el proceso de costura
     */
    public function completarReciboCorteSobremedida(Request $request, $idRecibo): JsonResponse
    {
        try {
            $result = $this->completarReciboCorteSobremedidaUseCase->execute((int) $idRecibo);

            return response()->json([
                'success' => (bool) $result->success,
                'message' => (string) $result->message,
                'data' => $result->data,
            ], (int) $result->statusCode);
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error al completar recibo en Corte (sobremedida)', [
                'id_recibo' => (int) $idRecibo,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al completar el recibo: ' . $e->getMessage(),
            ], 500);
        }
    }

}
