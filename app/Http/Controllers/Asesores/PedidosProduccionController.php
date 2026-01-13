<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaReflectivo;
use App\Models\ProcesoPrenda;
use App\Models\Cotizacion;
use App\Models\VariantePrenda;
use App\Models\PrendaCotizacionFriendly;
use App\Enums\EstadoPedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Domain Services
use App\Domain\PedidoProduccion\Services\NumeracionService;
use App\Domain\PedidoProduccion\Services\DescripcionService;
use App\Domain\PedidoProduccion\Services\ImagenService;
use App\Domain\PedidoProduccion\Services\PedidoProduccionService;
use App\Domain\PedidoProduccion\Services\CreacionPedidoService;
use App\Domain\PedidoProduccion\Services\LogoPedidoService;
use App\Domain\PedidoProduccion\Services\ProcesosPedidoService;
use App\Domain\PedidoProduccion\Repositories\CotizacionRepository;

/**
 * Controlador de Pedidos de Producción
 * 
 * Responsabilidad: Manejar requests HTTP y delegar lógica de negocio a servicios de dominio
 * Siguiendo principios DDD y SOLID
 */
class PedidosProduccionController extends Controller
{
    public function __construct(
        private PedidoProduccionService $pedidoService,
        private CreacionPedidoService $creacionPedidoService,
        private LogoPedidoService $logoPedidoService,
        private ProcesosPedidoService $procesosService,
        private NumeracionService $numeracionService,
        private DescripcionService $descripcionService,
        private ImagenService $imagenService,
        private CotizacionRepository $cotizacionRepository
    ) {}
    /**
     * Mostrar formulario para crear pedido desde cotización
     */
    public function crearForm()
    {
        // Solo permitir crear pedidos de cotizaciones APROBADAS
        $cotizaciones = Cotizacion::where('asesor_id', Auth::id())
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->with([
                'asesor',
                'cliente',
                'prendasCotizaciones.variantes.color',
                'prendasCotizaciones.variantes.tela',
                'prendasCotizaciones.variantes.tipoManga',
                'prendasCotizaciones.variantes.tipoBroche',
                'logoCotizacion.fotos'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('asesores.pedidos.crear-desde-cotizacion', compact('cotizaciones'));
    }

    /**
     * Mostrar formulario EDITABLE para crear pedido DESDE COTIZACIÓN
     * 
     * @return \Illuminate\View\View
     */
    public function crearFormEditable()
    {
        // Obtener cotizaciones aprobadas
        $cotizaciones = Cotizacion::with(['cliente', 'asesor', 'prendasCotizaciones'])
            ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
            ->get();

        // Transformar datos para el frontend
        $transformador = new \App\Domain\PedidoProduccion\Services\TransformadorCotizacionService();
        $cotizacionesData = $transformador->transformarCotizacionesParaFrontend($cotizaciones);

        // Tipo inicial: cotizacion (para mostrar buscador de cotizaciones)
        $tipoInicial = 'cotizacion';

        return view('asesores.pedidos.crear-desde-cotizacion-editable', compact('cotizaciones', 'cotizacionesData', 'tipoInicial'));
    }

    /**
     * Mostrar formulario EDITABLE para crear PEDIDO NUEVO (sin cotización)
     * 
     * @return \Illuminate\View\View
     */
    public function crearFormEditableNuevo()
    {
        // Cargar cotizaciones vacías (no se necesitan para pedido nuevo)
        $cotizaciones = collect([]);

        // Tipo inicial: nuevo (para mostrar selector de tipo de pedido)
        $tipoInicial = 'nuevo';

        return view('asesores.pedidos.crear-desde-cotizacion-editable', compact('cotizaciones', 'tipoInicial'));
    }

    /**
     * Listar pedidos de producción del asesor
     */
    public function index(Request $request)
    {
        // ✅ FIX: Si filtran por tipo=logo, consultar logo_pedidos en lugar de pedidos_produccion
        if ($request->has('tipo') && $request->tipo === 'logo') {
            return $this->indexLogoPedidos($request);
        }

        // Delegar obtención de pedidos al servicio de dominio
        $filtros = [
            'estado' => $request->estado,
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
        ];

        $pedidos = $this->pedidoService->obtenerPedidosAsesor($filtros);

        return view('asesores.pedidos.index', compact('pedidos'));
    }

    /**
     * MÉTODO LEGACY - Mantener temporalmente para compatibilidad
     * TODO: Migrar completamente al servicio
     */
    private function indexLegacy(Request $request)
    {
        $query = PedidoProduccion::query()
            ->with([
                'cotizacion' => function($q) {
                    $q->select('id', 'tipo', 'codigo', 'cliente_id', 'estado');
                },
                'prendas' => function ($q) {
                    $q->with(['color', 'tela', 'tipoManga', 'procesos']);
                },
                'logoPedidos'
            ]);

        // Filtrar por asesor
        $query->where('asesor_id', Auth::id());

        // Filtrar por estado si se proporciona
        if ($request->has('estado')) {
            $estado = $request->input('estado');
            
            // Debug: Log el estado recibido
            \Log::info('Filtro estado recibido: "' . $estado . '"');
            
            // Para "En Producción", filtrar por múltiples estados
            if ($estado === 'En Producción') {
                $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
                \Log::info('Filtrando por En Producción (No iniciado + En Ejecución)');
            } else {
                $query->where('estado', $estado);
                \Log::info('Filtrando por estado: ' . $estado);
            }
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate(15);
        
        \Log::info('Total de pedidos encontrados: ' . $pedidos->total());

        return view('asesores.pedidos.index', compact('pedidos'));
    }

    /**
     * ✅ NUEVO: Listar pedidos de LOGO específicamente (consulta logo_pedidos)
     */
    private function indexLogoPedidos(Request $request)
    {
        $query = \App\Models\LogoPedido::query()
            ->with(['cotizacion', 'procesos']);

        // Filtrar por asesora (campo 'asesora' es el nombre del usuario)
        // ⚠️ Nota: Algunos pedidos tienen asesora NULL, mostramos todos los del usuario actual
        $nombreUsuario = Auth::user()->name;
        $query->where(function($q) use ($nombreUsuario) {
            $q->where('asesora', $nombreUsuario)
              ->orWhereNull('asesora');  // Incluir también los que no tienen asesora asignada
        });

        // Filtrar por estado si se proporciona
        if ($request->has('estado')) {
            $estado = $request->input('estado');
            \Log::info('[LOGO] Filtro estado recibido: "' . $estado . '"');
            $query->where('estado', $estado);
        }

        $pedidos = $query->orderBy('created_at', 'desc')->paginate(15);
        
        \Log::info('[LOGO] Total de pedidos de logo encontrados: ' . $pedidos->total() . ' para usuario: ' . $nombreUsuario);

        // Retornar la misma vista index (ya maneja LogoPedido)
        return view('asesores.pedidos.index', compact('pedidos'));
    }

    /**
     * Ver detalle de pedido de producción
     */
    public function show($id)
    {
        $pedido = PedidoProduccion::findOrFail($id);
        
        // Verificar que el pedido pertenece al asesor autenticado
        if ($pedido->asesor_id !== Auth::id()) {
            abort(403);
        }

        $prendas = $pedido->prendas()->with('procesos')->get();
        $cotizacion = $pedido->cotizacion;
        $prendasCotizacion = $cotizacion ? $cotizacion->prendasCotizaciones : [];

        return view('asesores.pedidos.show', compact('pedido', 'prendas', 'cotizacion', 'prendasCotizacion'));
    }

    /**
     * Ver plantilla ERP/Factura del pedido
     */
    public function plantilla($id)
    {
        $pedido = PedidoProduccion::findOrFail($id);
        
        // Verificar que el pedido pertenece al asesor autenticado
        if ($pedido->asesor_id !== Auth::id()) {
            abort(403);
        }

        $prendas = $pedido->prendas()->with('procesos')->get();
        $cotizacion = $pedido->cotizacion;
        $prendasCotizacion = $cotizacion ? $cotizacion->prendasCotizaciones : [];

        return view('asesores.pedidos.plantilla-erp', compact('pedido', 'prendas', 'cotizacion', 'prendasCotizacion'));
    }

    /**
     * Crear pedido de producción desde cotización
     * Responsabilidad: Validar request HTTP y delegar a servicio de dominio
     */
    public function crearDesdeCotizacion($cotizacionId)
    {
        try {
            // Delegar creación completa al servicio de dominio
            $resultado = $this->creacionPedidoService->crearDesdeCotizacion($cotizacionId);
            
            return response()->json($resultado);
            
        } catch (\Exception $e) {
            \Log::error('Error creando pedido desde cotización', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    // MÉTODOS LEGACY ELIMINADOS - La lógica ahora está en servicios de dominio
    // - crearDesdeCotizacion_LEGACY() -> CreacionPedidoService::crearDesdeCotizacion() (~520 líneas eliminadas)
    // - Código corrupto duplicado eliminado (~620 líneas)

    /**
     * Crear un pedido LOGO desde cotización
     * ✅ NUEVO: Crea SOLO en logo_pedidos, NO en pedidos_produccion
     * ✅ CORREGIDO: Guarda logo_cotizacion_id desde la cotización
     */

    /**
     * Crear un pedido LOGO desde cotización
     * ✅ NUEVO: Crea SOLO en logo_pedidos, NO en pedidos_produccion
     * ✅ CORREGIDO: Guarda logo_cotizacion_id desde la cotización
     */
    private function crearLogoPedidoDesdeAnullCotizacion(Cotizacion $cotizacion)
    {
        try {
            DB::beginTransaction();

            \Log::info('🎨 [LOGO desde Cotización] Creando logo_pedido desde cotización', [
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero
            ]);

            // ✅ Obtener el logo_cotizacion_id asociado a esta cotización
            $logoCotizacionId = DB::table('logo_cotizaciones')
                ->where('cotizacion_id', $cotizacion->id)
                ->value('id');
            
            \Log::info('🎨 [LOGO desde Cotización] logo_cotizacion encontrado', [
                'cotizacion_id' => $cotizacion->id,
                'logo_cotizacion_id' => $logoCotizacionId
            ]);

            // ✅ Generar número LOGO con formato #LOGO-00001
            $numeroLogoPedido = $this->numeracionService->generarNumeroLogoPedido();

            // Crear registro inicial en logo_pedidos
            $logoPedidoId = DB::table('logo_pedidos')->insertGetId([
                'pedido_id' => null, // NO crear en pedidos_produccion
                'logo_cotizacion_id' => $logoCotizacionId, // ✅ CORREGIDO: Guardar la relación
                'numero_pedido' => $numeroLogoPedido, // ✅ Usar número generado
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero,
                'cliente' => $cotizacion->cliente->nombre ?? 'Sin nombre',
                'asesora' => Auth::user()?->name,
                'forma_de_pago' => request()->input('forma_de_pago'),
                'encargado_orden' => Auth::user()?->name,
                'fecha_de_creacion_de_orden' => now(),
                'estado' => 'pendiente',
                'descripcion' => '',
                'tecnicas' => null,
                'observaciones_tecnicas' => '',
                'ubicaciones' => null,
                'observaciones' => '',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // ✅ Crear el proceso inicial
            \App\Models\ProcesosPedidosLogo::crearProcesoInicial($logoPedidoId, Auth::id());

            \Log::info('✅ [LOGO desde Cotización] logo_pedido creado', [
                'logo_pedido_id' => $logoPedidoId,
                'numero_logo_pedido' => $numeroLogoPedido,
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero,
                'logo_cotizacion_id' => $logoCotizacionId
            ]);

            DB::commit();

            // Retornar logo_pedido_id en lugar de pedido_id
            return response()->json([
                'success' => true,
                'message' => 'Pedido LOGO creado inicialmente',
                'logo_pedido_id' => $logoPedidoId,
                'logo_cotizacion_id' => $logoCotizacionId, // ✅ Devolver para que el frontend lo tenga
                'pedido_id' => null, // Explícitamente null
                'tipo' => 'logo'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ [LOGO desde Cotización] Error al crear logo_pedido', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido LOGO: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar los datos específicos del LOGO en un pedido LOGO existente
     * ✅ NUEVO: Actualiza logo_pedidos con los datos del formulario
     * ✅ Guarda TODOS los campos necesarios según tabla logo_pedidos
     * ✅ Calcula y guarda la cantidad total (suma de tallas)
     */
    public function guardarLogoPedido(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $pedidoId = $request->input('pedido_id');
            $logoCotizacionId = $request->input('logo_cotizacion_id');
            $cantidad = $request->input('cantidad', 0); // Suma de tallas enviada desde frontend
            $cotizacionId = $request->input('cotizacion_id');

            \Log::info('🎨 [guardarLogoPedido] Guardando datos de LOGO', [
                'pedido_id' => $pedidoId,
                'logo_cotizacion_id' => $logoCotizacionId,
                'cantidad' => $cantidad,
                'cotizacion_id' => $cotizacionId
            ]);

            // Obtener datos de la cotización si fue enviada
            $numeroCotizacion = null;
            $cliente = null;
            $asesora = null;
            $formaPago = null;
            
            // 🔍 LÓGICA: Usar cliente del request si viene, sino obtener de cotización
            $clienteDelRequest = $request->input('cliente');
            
            if ($clienteDelRequest) {
                // Usar cliente enviado desde frontend
                $cliente = $clienteDelRequest;
                \Log::info('🎨 [guardarLogoPedido] Usando cliente del REQUEST (frontend)', [
                    'cliente' => $cliente
                ]);
            } elseif ($cotizacionId) {
                // Fallback: Obtener de la cotización
                $cotizacion = DB::table('cotizaciones')
                    ->where('id', $cotizacionId)
                    ->select('id', 'numero', 'cliente_id')
                    ->first();
                
                if ($cotizacion) {
                    $numeroCotizacion = $cotizacion->numero;
                    // Obtener cliente
                    $clienteObj = DB::table('clientes')->where('id', $cotizacion->cliente_id)->first();
                    $cliente = $clienteObj?->nombre ?? 'Sin nombre';
                    \Log::info('🎨 [guardarLogoPedido] Usando cliente de la COTIZACIÓN (fallback)', [
                        'cliente' => $cliente,
                        'cliente_id' => $cotizacion->cliente_id
                    ]);
                }
            }
            
            $asesora = Auth::user()?->name;
            $formaPago = $request->input('forma_de_pago') ?? 'Por definir';

            // ✅ VERIFICAR: ¿Existe ya un logo_pedido con este ID?
            // Si $pedidoId es un número, podría ser ID de logo_pedidos o ID de pedidos_produccion
            // Buscar en logo_pedidos por ID primaria primero
            $logoPedidoExistente = null;
            
            if (is_numeric($pedidoId)) {
                // Intentar buscar por ID primaria en logo_pedidos
                $logoPedidoExistente = DB::table('logo_pedidos')->find($pedidoId);
                
                // Si no encuentra, buscar por pedido_id (para cotizaciones combinadas)
                if (!$logoPedidoExistente) {
                    $logoPedidoExistente = DB::table('logo_pedidos')
                        ->where('pedido_id', $pedidoId)
                        ->first();
                }
            }
            
            \Log::info('🎨 [guardarLogoPedido] Buscando logo_pedido existente', [
                'pedido_id_buscado' => $pedidoId,
                'encontrado' => $logoPedidoExistente ? 'SÍ' : 'NO',
                'es_primaria' => $logoPedidoExistente && $logoPedidoExistente->id == $pedidoId ? 'SÍ' : 'POR RELACION'
            ]);
            
            if (!$logoPedidoExistente) {
                // ✅ NUEVO: Si no existe, CREAR uno nuevo en logo_pedidos
                // Esto ocurre cuando es COMBINADA (PL) y es la primera vez que se guarda el logo
                \Log::info('🎨 [guardarLogoPedido] CREANDO nuevo registro en logo_pedidos (COMBINADA PL)', [
                    'pedido_id' => $pedidoId,
                    'cotizacion_id' => $cotizacionId
                ]);
                
                // Generar número LOGO
                $numeroLogoPedido = $this->numeracionService->generarNumeroLogoPedido();
                
                \Log::info('🎨 [guardarLogoPedido] Datos a insertar en INSERT', [
                    'pedido_id' => $pedidoId,
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'numero_pedido' => $numeroLogoPedido,
                    'cliente' => $cliente,
                    'asesora' => $asesora,
                    'forma_de_pago' => $formaPago
                ]);
                
                $nuevoPedidoLogoId = DB::table('logo_pedidos')->insertGetId([
                    'pedido_id' => $pedidoId,  // FK a pedidos_produccion
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'numero_pedido' => $numeroLogoPedido,
                    'cotizacion_id' => $cotizacionId,
                    'numero_cotizacion' => $numeroCotizacion,
                    'cliente' => $cliente,
                    'asesora' => $asesora,
                    'forma_de_pago' => $formaPago,
                    'encargado_orden' => $asesora,
                    'fecha_de_creacion_de_orden' => now(),
                    'estado' => 'pendiente',
                    'descripcion' => $request->input('descripcion', ''),
                    'cantidad' => $cantidad,
                    'tecnicas' => json_encode($request->input('tecnicas', [])),
                    'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                    'ubicaciones' => json_encode($request->input('ubicaciones', [])),
                    'observaciones' => $request->input('observaciones', ''),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                \Log::info('✅ [guardarLogoPedido] INSERT completado', [
                    'nuevo_logo_pedido_id' => $nuevoPedidoLogoId
                ]);
                
                // ✅ IMPORTANTE: Usar el nuevo ID devuelto por INSERT
                // Este será el ID primaria de logo_pedidos
                $pedidoId = $nuevoPedidoLogoId;
                
                // ❌ TEMPORALMENTE DESHABILITADO: Crear proceso inicial
                // TODO: Revisar validación en ProcesosPedidosLogo::crearProcesoInicial
                \Log::info('⏭️  [guardarLogoPedido] Saltando creación de proceso inicial (TEMPORALMENTE)');
                
                \Log::info('✅ [guardarLogoPedido] Nuevo logo_pedido creado', [
                    'logo_pedido_id' => $pedidoId,
                    'numero_pedido' => $numeroLogoPedido
                ]);
            } else {
                // ✅ Si ya existe, ACTUALIZAR (para LOGO SOLO)
                // Actualizar el registro en logo_pedidos con los datos del formulario
                $updateData = [
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'descripcion' => $request->input('descripcion', ''),
                    'cantidad' => $cantidad,
                    'tecnicas' => json_encode($request->input('tecnicas', [])),
                    'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                    'ubicaciones' => json_encode($request->input('ubicaciones', [])),
                    'observaciones' => $request->input('observaciones', ''),
                    'updated_at' => now(),
                ];

                // Agregar campos opcionales si están disponibles
                if ($cotizacionId) {
                    $updateData['cotizacion_id'] = $cotizacionId;
                }
                if ($numeroCotizacion) {
                    $updateData['numero_cotizacion'] = $numeroCotizacion;
                }

                $updated = DB::table('logo_pedidos')
                    ->where('id', $pedidoId)
                    ->update($updateData);

                if (!$updated) {
                    throw new \Exception('No se encontró el registro de logo_pedido con ID: ' . $pedidoId);
                }
            }

            \Log::info('✅ [guardarLogoPedido] LOGO actualizado correctamente', [
                'logo_pedido_id' => $pedidoId,
                'cantidad' => $cantidad,
                'logo_cotizacion_id' => $logoCotizacionId,
                'cotizacion_id' => $cotizacionId
            ]);

            // Procesar fotos si existen
            $fotos = $request->input('fotos', []);
            if (!empty($fotos)) {
                foreach ($fotos as $index => $fotoId) {
                    DB::table('logo_pedido_fotos')->insertOrIgnore([
                        'logo_pedido_id' => $pedidoId,
                        'logo_foto_cotizacion_id' => $fotoId,
                        'orden' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                \Log::info('✅ [guardarLogoPedido] Fotos agregadas', [
                    'total_fotos' => count($fotos)
                ]);
            }

            DB::commit();

            // Obtener el registro actualizado
            $logoPedido = DB::table('logo_pedidos')->find($pedidoId);
            
            // ✅ Si es COMBINADA (tiene pedido_id), obtener también datos del pedido de prendas
            $pedidoPrendas = null;
            if ($logoPedido->pedido_id) {
                $pedidoPrendas = DB::table('pedidos_produccion')
                    ->where('id', $logoPedido->pedido_id)
                    ->select('id', 'numero_pedido')
                    ->first();
            }

            return response()->json([
                'success' => true,
                'message' => 'LOGO Pedido guardado correctamente',
                'logo_pedido' => $logoPedido,
                'pedido_produccion' => $pedidoPrendas,  // ✅ NUEVO: Devolver datos del pedido de prendas si existe
                'numero_pedido_produccion' => $pedidoPrendas?->numero_pedido,  // Para facilitar en frontend
                'numero_pedido_logo' => $logoPedido->numero_pedido
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ [guardarLogoPedido] Error al guardar logo_pedido', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar LOGO pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Heredar variantes de una prenda de cotización a pedido
     */
    private function heredarVariantesDePrenda($cotizacion, $prendaPedido, $index)
    {
        try {
            \Log::info('🔍 [heredarVariantes] Iniciando herencia de variantes', [
                'cotizacion_id' => $cotizacion->id,
                'prenda_pedido_id' => $prendaPedido->id,
                'index' => $index,
            ]);

            // Obtener prendas de cotización desde la tabla correcta
            $prendasCot = \App\Models\PrendaCot::where('cotizacion_id', $cotizacion->id)
                ->orderBy('id')
                ->get();
            
            if (!isset($prendasCot[$index])) {
                \Log::warning('⚠️ No se encontró prenda de cotización en índice', [
                    'index' => $index,
                    'total_prendas_cot' => $prendasCot->count()
                ]);
                return;
            }
            
            $prendaCot = $prendasCot[$index];
            
            \Log::info('🔍 [heredarVariantes] Prenda de cotización encontrada', [
                'prenda_cot_id' => $prendaCot->id,
                'nombre' => $prendaCot->nombre_producto,
            ]);
            
            // Obtener variantes de la tabla prenda_variantes_cot
            $variantes = \DB::table('prenda_variantes_cot')
                ->where('prenda_cot_id', $prendaCot->id)
                ->get();
            
            \Log::info('🔍 [heredarVariantes] Variantes encontradas', [
                'total_variantes' => $variantes->count(),
            ]);
            
            if ($variantes->isEmpty()) {
                \Log::info('ℹ️ Sin variantes en prenda_variantes_cot, intentando con prenda directa');
                
                // Si no hay variantes, usar los datos de la prenda directamente
                $prendaPedido->update([
                    'color_id' => $prendaCot->color_id,
                    'tela_id' => $prendaCot->tela_id,
                    'tipo_manga_id' => $prendaCot->tipo_manga_id,
                    'tipo_broche_id' => $prendaCot->tipo_broche_id,
                    'tiene_bolsillos' => $prendaCot->tiene_bolsillos ?? 0,
                    'tiene_reflectivo' => $prendaCot->tiene_reflectivo ?? 0,
                ]);
                
                \Log::info('✅ Datos heredados desde prenda_cot directamente', [
                    'color_id' => $prendaCot->color_id,
                    'tela_id' => $prendaCot->tela_id,
                    'tipo_manga_id' => $prendaCot->tipo_manga_id,
                    'tipo_broche_id' => $prendaCot->tipo_broche_id,
                ]);
                
                return;
            }
            
            // Copiar la primera variante
            $variante = $variantes->first();
            
            $telaId = null;
            $colorId = null;
            
            // 1. Buscar o crear COLOR usando el campo directo 'color' de la variante
            if (!empty($variante->color)) {
                $color = \DB::table('colores_prenda')
                    ->where('nombre', 'LIKE', '%' . $variante->color . '%')
                    ->first();
                
                if (!$color) {
                    $colorId = \DB::table('colores_prenda')->insertGetId([
                        'nombre' => $variante->color,
                        'activo' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    \Log::info('✅ Color creado', ['nombre' => $variante->color, 'id' => $colorId]);
                } else {
                    $colorId = $color->id;
                }
            }
            
            // 2. Buscar o crear TELA usando telas_multiples JSON
            if (!empty($variante->telas_multiples)) {
                $telasMultiples = json_decode($variante->telas_multiples, true);
                if (is_array($telasMultiples) && !empty($telasMultiples)) {
                    $primeraTela = $telasMultiples[0];
                    
                    if (!empty($primeraTela['tela'])) {
                        $tela = \DB::table('telas_prenda')
                            ->where('nombre', 'LIKE', '%' . $primeraTela['tela'] . '%')
                            ->first();
                        
                        if (!$tela) {
                            $telaId = \DB::table('telas_prenda')->insertGetId([
                                'nombre' => $primeraTela['tela'],
                                'activo' => 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            \Log::info('✅ Tela creada', ['nombre' => $primeraTela['tela'], 'id' => $telaId]);
                        } else {
                            $telaId = $tela->id;
                        }
                    }
                }
            }
            
            \Log::info('🔍 [heredarVariantes] IDs obtenidos/creados', [
                'color_campo_directo' => $variante->color,
                'color_id' => $colorId,
                'tela_desde_json' => isset($telasMultiples) ? ($telasMultiples[0]['tela'] ?? null) : null,
                'tela_id' => $telaId,
            ]);
            
            $prendaPedido->update([
                'color_id' => $colorId,
                'tela_id' => $telaId,
                'tipo_manga_id' => $variante->tipo_manga_id,
                'tipo_broche_id' => $variante->tipo_broche_id,
                'tiene_bolsillos' => $variante->tiene_bolsillos ?? 0,
                'tiene_reflectivo' => $variante->tiene_reflectivo ?? 0,
                'descripcion_variaciones' => $variante->descripcion_adicional ?? null,
            ]);
            
            \Log::info('✅ Variantes heredadas exitosamente desde prenda_variantes_cot', [
                'prenda_pedido_id' => $prendaPedido->id,
                'color_id' => $colorId,
                'tela_id' => $telaId,
                'tipo_manga_id' => $variante->tipo_manga_id,
                'tipo_broche_id' => $variante->tipo_broche_id,
                'telas_multiples' => $variante->telas_multiples,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('❌ Error heredando variantes', [
                'prenda_pedido_id' => $prendaPedido->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    // MÉTODOS ELIMINADOS - Migrados a servicios de dominio
    // - generarNumeroPedido() -> NumeracionService::generarNumeroPedido()
    // - generarNumeroLogoPedido() -> NumeracionService::generarNumeroLogoPedido()
    // - construirDescripcionPrenda() -> DescripcionService::construirDescripcionPrenda()
    // - construirDescripcionPrendaCompleta() -> OBSOLETO

    /**
     * Obtener datos COMPLETOS de una cotización con todas sus prendas e información (para AJAX)
     * Responsabilidad: Validar permisos y delegar al repositorio
     */
    /**
     * Obtener datos COMPLETOS de una cotización con todas sus prendas e información (para AJAX)
     * TEMPORALMENTE usando método LEGACY hasta que el repositorio esté completo
     */
    public function obtenerDatosCotizacion(int $cotizacionId): JsonResponse
    {
        try {
            $cotizacion = Cotizacion::with([
                'cliente',
                'asesor',
                'tipoCotizacion',
                'prendas.variantes.manga',
                'prendas.variantes.broche',
                'prendas.variantes.genero',
                'prendas.tallas',
                'prendas.fotos',
                'prendas.telas',
                'prendas.telaFotos',
                'logoCotizacion.fotos',
                'logoCotizacion.prendas.tipoLogo',
                'logoCotizacion.prendas.fotos',
                'reflectivo.fotos',
            ])->findOrFail($cotizacionId);

            \Log::info('🔍 [OBTENER-DATOS-COT] Cotización cargada:', [
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero_cotizacion,
                'prendas_count' => $cotizacion->prendas->count(),
                'logo_prendas_count' => $cotizacion->logoCotizacion ? $cotizacion->logoCotizacion->prendas->count() : 0,
                'prendas_ids' => $cotizacion->prendas->pluck('id')->toArray(),
            ]);

            if ($cotizacion->asesor_id !== Auth::id()) {
                return response()->json([
                    'error' => 'No tienes permiso para acceder a esta cotización'
                ], 403);
            }

            $especificacionesConvertidas = $this->convertirEspecificacionesAlFormatoNuevo(
                $cotizacion->especificaciones ?? []
            );

            $formaPago = '';
            if (!empty($especificacionesConvertidas['forma_pago']) && is_array($especificacionesConvertidas['forma_pago'])) {
                if (count($especificacionesConvertidas['forma_pago']) > 0) {
                    $formaPago = $especificacionesConvertidas['forma_pago'][0]['valor'] ?? '';
                }
            }

            return response()->json([
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero_cotizacion,
                'tipo_cotizacion_id' => $cotizacion->tipo_cotizacion_id,
                'tipo_cotizacion_codigo' => $cotizacion->tipoCotizacion ? $cotizacion->tipoCotizacion->codigo : null,
                'cliente' => $cotizacion->cliente ? $cotizacion->cliente->nombre : '',
                'asesora' => $cotizacion->asesor ? $cotizacion->asesor->name : Auth::user()->name,
                'forma_pago' => $formaPago,
                'tipo_venta' => $cotizacion->tipo_venta ?? '',
                'especificaciones' => $especificacionesConvertidas,
                'observaciones_generales' => $cotizacion->observaciones_generales ?? [],
                'ubicaciones' => $cotizacion->ubicaciones ?? [],
                
                // Prendas con TODA la información
                'prendas' => $cotizacion->prendas->map(function($prenda) {
                    // Obtener primera variante
                    $primerVariante = $prenda->variantes->first();
                    
                    // Construir variantes con información completa
                    $variantes = [];
                    if ($primerVariante) {
                        $variantes = [
                            'id' => $primerVariante->id,
                            'prenda_cot_id' => $primerVariante->prenda_cot_id,
                            'tipo_prenda' => $primerVariante->tipo_prenda,
                            'es_jean_pantalon' => $primerVariante->es_jean_pantalon,
                            'tipo_jean_pantalon' => $primerVariante->tipo_jean_pantalon,
                            'genero_id' => $primerVariante->genero_id,
                            'genero_nombre' => $primerVariante->genero ? $primerVariante->genero->nombre : null,
                            'color' => $primerVariante->color,
                            'tipo_manga_id' => $primerVariante->tipo_manga_id,
                            'tipo_manga' => $primerVariante->manga ? $primerVariante->manga->nombre : null,
                            'obs_manga' => $primerVariante->obs_manga,
                            'tipo_broche_id' => $primerVariante->tipo_broche_id,
                            'tipo_broche' => $primerVariante->broche ? $primerVariante->broche->nombre : null,
                            'obs_broche' => $primerVariante->obs_broche,
                            'tiene_bolsillos' => $primerVariante->tiene_bolsillos,
                            'obs_bolsillos' => $primerVariante->obs_bolsillos,
                            'aplica_manga' => $primerVariante->aplica_manga,
                            'aplica_broche' => $primerVariante->aplica_broche,
                            'tiene_reflectivo' => $primerVariante->tiene_reflectivo,
                            'obs_reflectivo' => $primerVariante->obs_reflectivo,
                            'descripcion_adicional' => $primerVariante->descripcion_adicional,
                            'telas_multiples' => is_array($primerVariante->telas_multiples) ? $primerVariante->telas_multiples : (is_string($primerVariante->telas_multiples) ? json_decode($primerVariante->telas_multiples, true) : []),
                            'created_at' => $primerVariante->created_at,
                            'updated_at' => $primerVariante->updated_at,
                        ];
                    }
                    
                    // Obtener tallas
                    $tallas = $prenda->tallas->pluck('talla')->toArray();
                    
                    // Obtener fotos de prenda con URLs completas
                    $fotos = $prenda->fotos->map(function($foto) {
                        // El campo 'url' puede contener la ruta relativa o completa
                        $rutaFoto = $foto->ruta_webp ?? $foto->url;
                        // Agregar /storage/ si no lo tiene
                        if (strpos($rutaFoto, '/storage/') === false) {
                            $rutaFoto = '/storage/' . ltrim($rutaFoto, '/');
                        }
                        return asset($rutaFoto);
                    })->toArray();
                    
                    // Obtener telas
                    $telas = $prenda->telas->map(function($tela) {
                        return [
                            'id' => $tela->id,
                            'color' => $tela->color,
                            'nombre_tela' => $tela->nombre_tela,
                            'referencia' => $tela->referencia,
                            'url_imagen' => $tela->url_imagen,
                        ];
                    })->toArray();
                    
                    // Obtener fotos de telas con URLs correctas
                    // Intentar relacionar fotos con telas por color si tela_id es null
                    $telaFotos = $prenda->telaFotos->map(function($telaFoto) use ($telas) {
                        $telaId = $telaFoto->tela_id;
                        
                        // Si tela_id es null, intentar encontrar la tela por atributos
                        if (!$telaId && $telas && count($telas) > 0) {
                            // Intentar hacer matching por nombre de archivo o descripción
                            // Si la foto tiene info de tela, usarla
                            if (isset($telaFoto->nombre_tela) && $telaFoto->nombre_tela) {
                                $telaBuscada = collect($telas)->firstWhere('nombre_tela', $telaFoto->nombre_tela);
                                if ($telaBuscada) {
                                    $telaId = $telaBuscada['id'];
                                }
                            }
                        }
                        
                        // Construir URLs con asset() para que tengan permisos correctos
                        $rutaWebp = $telaFoto->ruta_webp ?? $telaFoto->url;
                        $rutaOriginal = $telaFoto->ruta_original;
                        
                        return [
                            'id' => $telaFoto->id,
                            'tela_id' => $telaId,
                            'url' => asset($rutaWebp),
                            'ruta_original' => asset($rutaOriginal),
                            'ruta_webp' => asset('storage/' . ltrim($rutaWebp, '/')),
                        ];
                    })->toArray();
                    
                    return [
                        'id' => $prenda->id,
                        'nombre_producto' => $prenda->nombre_producto,
                        'descripcion' => $prenda->descripcion,
                        'cantidad' => $prenda->cantidad,
                        'tallas' => $tallas,
                        'fotos' => $fotos,
                        'variantes' => $variantes,
                        'telas' => $telas,
                        'telaFotos' => $telaFotos,
                    ];
                })->toArray(),
                
                // Logo información COMPLETA (sin campos JSON antiguos)
                'logo' => $cotizacion->logoCotizacion ? [
                    'id' => $cotizacion->logoCotizacion->id,
                    'tipo_venta' => $cotizacion->logoCotizacion->tipo_venta,
                    'observaciones_generales' => (is_array($cotizacion->logoCotizacion->observaciones_generales) ? $cotizacion->logoCotizacion->observaciones_generales : (is_string($cotizacion->logoCotizacion->observaciones_generales) ? json_decode($cotizacion->logoCotizacion->observaciones_generales, true) : [])) ?? [],
                    'fotos' => $cotizacion->logoCotizacion->fotos->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'url' => '/storage/' . ltrim($foto->ruta_webp, '/'),
                            'ruta_original' => '/storage/' . ltrim($foto->ruta_original, '/'),
                            'ruta_webp' => '/storage/' . ltrim($foto->ruta_webp, '/'),
                        ];
                    })->toArray(),
                ] : null,
                
                // ✅ NUEVO: Prendas técnicas (estructura desde LogoCotizacionTecnicaPrenda)
                'prendas_tecnicas' => $cotizacion->logoCotizacion ? 
                    $cotizacion->logoCotizacion->prendas->map(function($prenda) {
                        return [
                            'id' => $prenda->id,
                            'logo_cotizacion_id' => $prenda->logo_cotizacion_id,
                            'tipo_logo_id' => $prenda->tipo_logo_id,
                            'tipo_logo_nombre' => $prenda->tipoLogo ? $prenda->tipoLogo->nombre : null,
                            'nombre_prenda' => $prenda->nombre_prenda,
                            'observaciones' => $prenda->observaciones,
                            'ubicaciones' => (is_array($prenda->ubicaciones) ? $prenda->ubicaciones : (is_string($prenda->ubicaciones) ? json_decode($prenda->ubicaciones, true) : [])) ?? [],
                            'talla_cantidad' => (is_array($prenda->talla_cantidad) ? $prenda->talla_cantidad : (is_string($prenda->talla_cantidad) ? json_decode($prenda->talla_cantidad, true) : [])) ?? [],
                            'grupo_combinado' => $prenda->grupo_combinado,
                            'fotos' => $prenda->fotos->map(function($foto) {
                                return [
                                    'id' => $foto->id,
                                    'ruta_webp' => '/storage/' . ltrim($foto->ruta_webp, '/'),
                                    'ruta_original' => '/storage/' . ltrim($foto->ruta_original, '/'),
                                    'orden' => $foto->orden,
                                ];
                            })->toArray(),
                        ];
                    })->toArray()
                : [],
                
                // Reflectivo INFORMACIÓN COMPLETA
                'reflectivo' => $cotizacion->reflectivo ? [
                    'id' => $cotizacion->reflectivo->id,
                    'ubicacion' => $cotizacion->reflectivo->ubicacion,
                    'descripcion' => $cotizacion->reflectivo->descripcion,
                    'observaciones' => $cotizacion->reflectivo->observaciones,
                    'fotos' => $cotizacion->reflectivo->fotos ? $cotizacion->reflectivo->fotos->map(function($foto) {
                        return [
                            'id' => $foto->id,
                            'url' => '/storage/' . ltrim($foto->ruta_webp ?? $foto->url, '/'),
                            'ruta_original' => '/storage/' . ltrim($foto->ruta_original, '/'),
                            'ruta_webp' => '/storage/' . ltrim($foto->ruta_webp, '/'),
                        ];
                    })->toArray() : [],
                ] : null,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Convierte especificaciones del formato antiguo (tabla_orden[field]) al nuevo (forma_pago, disponibilidad, etc)
     */
    private function convertirEspecificacionesAlFormatoNuevo($especificaciones)
    {
        if (!$especificaciones) {
            return [];
        }

        // Si ya es un array con estructura forma_pago, no convertir
        if (is_array($especificaciones) && isset($especificaciones['forma_pago'])) {
            return $especificaciones;
        }

        // Si es string, parsear
        if (is_string($especificaciones)) {
            $datos = json_decode($especificaciones, true) ?? [];
        } else {
            $datos = $especificaciones;
        }

        // Si ya está en formato nuevo, devolver
        if (isset($datos['forma_pago'])) {
            return $datos;
        }

        // Convertir del formato antiguo tabla_orden[field]
        $convertidas = [
            'forma_pago' => [],
            'disponibilidad' => [],
            'regimen' => [],
            'se_ha_vendido' => [],
            'ultima_venta' => [],
            'flete' => []
        ];

        // Mapeos de nombres para conversión
        $mapeoFormaPago = [
            'tabla_orden[contado]' => 'Contado',
            'tabla_orden[credito]' => 'Crédito',
        ];

        $mapeoDisponibilidad = [
            'tabla_orden[bodega]' => 'Bodega',
            'tabla_orden[cucuta]' => 'Cúcuta',
            'tabla_orden[lafayette]' => 'Lafayette',
            'tabla_orden[fabrica]' => 'Fábrica',
        ];

        $mapeoRegimen = [
            'tabla_orden[comun]' => 'Común',
            'tabla_orden[simplificado]' => 'Simplificado',
        ];

        // Procesar FORMA_PAGO
        foreach ($mapeoFormaPago as $clave => $etiqueta) {
            if (isset($datos[$clave]) && ($datos[$clave] === '1' || $datos[$clave] === true)) {
                $obsKey = str_replace(']', '_obs]', str_replace('[', '[pago_', $clave));
                $convertidas['forma_pago'][] = [
                    'valor' => $etiqueta,
                    'observacion' => $datos[$obsKey] ?? ''
                ];
            }
        }

        // Procesar DISPONIBILIDAD
        foreach ($mapeoDisponibilidad as $clave => $etiqueta) {
            if (isset($datos[$clave]) && ($datos[$clave] === '1' || $datos[$clave] === true)) {
                $obsKey = str_replace(']', '_obs]', $clave);
                $convertidas['disponibilidad'][] = [
                    'valor' => $etiqueta,
                    'observacion' => $datos[$obsKey] ?? ''
                ];
            }
        }

        // Procesar RÉGIMEN
        foreach ($mapeoRegimen as $clave => $etiqueta) {
            if (isset($datos[$clave]) && ($datos[$clave] === '1' || $datos[$clave] === true)) {
                $obsKey = str_replace(']', '_obs]', str_replace('[', '[regimen_', $clave));
                $convertidas['regimen'][] = [
                    'valor' => $etiqueta,
                    'observacion' => $datos[$obsKey] ?? ''
                ];
            }
        }

        // Remover campos vacíos
        foreach ($convertidas as $key => $value) {
            if (empty($value)) {
                unset($convertidas[$key]);
            }
        }

        return $convertidas;

    }

    /**
     * Crear procesos específicos para cotización REFLECTIVO
     * Responsabilidad: Delegar al servicio de procesos
     */
    private function crearProcesosParaReflectivo(PedidoProduccion $pedido, Cotizacion $cotizacion): void
    {
        // Delegar creación de procesos al servicio de dominio
        $this->procesosService->crearProcesosReflectivo($pedido, $cotizacion);
    }

    // MÉTODO LEGACY - Mantener temporalmente para referencia
    // TODO: Eliminar después de verificar que el servicio funciona correctamente
    private function crearProcesosParaReflectivo_LEGACY(PedidoProduccion $pedido, Cotizacion $cotizacion): void
    {
        try {
            // Verificar si es cotización tipo REFLECTIVO
            if (!$cotizacion->tipoCotizacion) {
                \Log::info('⏭️ No hay tipo de cotización asociado');
                return;
            }

            $tipoCotizacion = strtolower(trim($cotizacion->tipoCotizacion->nombre ?? ''));
            
            \Log::info('🔍 Verificando tipo de cotización', [
                'tipo_encontrado' => $tipoCotizacion,
                'es_reflectivo' => ($tipoCotizacion === 'reflectivo' ? 'SI' : 'NO'),
            ]);

            if ($tipoCotizacion !== 'reflectivo') {
                \Log::info('⏭️ Cotización no es de tipo REFLECTIVO', [
                    'tipo_actual' => $tipoCotizacion,
                ]);
                return;
            }

            \Log::info('🎯 CREAR PROCESOS PARA COTIZACIÓN REFLECTIVO', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cotizacion_id' => $cotizacion->id,
            ]);

            // Obtener prendas del pedido
            $prendas = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)->get();

            \Log::info('📋 Prendas encontradas', [
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad' => $prendas->count(),
            ]);

            if ($prendas->isEmpty()) {
                \Log::warn('⚠️ No hay prendas en el pedido', [
                    'numero_pedido' => $pedido->numero_pedido,
                ]);
                return;
            }

            // Obtener nombre de la asesora logueada
            $asesoraLogueada = Auth::user()->name ?? 'Sin Asesora';

            foreach ($prendas as $prenda) {
                \Log::info('🔍 Procesando prenda', [
                    'prenda_pedido_id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                ]);

                // Verificar si ya existen procesos para esta prenda
                $procesosExistentes = ProcesoPrenda::where('prenda_pedido_id', $prenda->id)
                    ->pluck('proceso')
                    ->toArray();

                \Log::info('🔍 Procesos existentes para prenda', [
                    'prenda_pedido_id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'procesos' => $procesosExistentes,
                ]);

                // Crear proceso de Creación de Orden asignado a la asesora logueada
                if (!in_array('Creación de Orden', $procesosExistentes)) {
                    $procsCreacion = ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $prenda->id,
                        'proceso' => 'Creación de Orden',
                        'encargado' => $asesoraLogueada,
                        'estado_proceso' => 'En Progreso',
                        'fecha_inicio' => now(),
                        'observaciones' => 'Proceso de creación asignado automáticamente a la asesora para cotización reflectivo',
                    ]);

                    \Log::info('✅ Proceso Creación de Orden creado para prenda', [
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $prenda->id,
                        'nombre_prenda' => $prenda->nombre_prenda,
                        'encargado' => $asesoraLogueada,
                        'proceso_id' => $procsCreacion->id,
                    ]);
                }

                // NO crear duplicados si ya existe Costura
                if (in_array('Costura', $procesosExistentes)) {
                    \Log::info('✅ Proceso Costura ya existe, omitiendo');
                    continue;
                }

                // Crear proceso Costura con Ramiro
                $procsCostura = ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $prenda->id,
                    'proceso' => 'Costura',
                    'encargado' => 'Ramiro',
                    'estado_proceso' => 'En Progreso',
                    'fecha_inicio' => now(),
                    'observaciones' => 'Asignado automáticamente a Ramiro para cotización reflectivo',
                ]);

                \Log::info('✅ Proceso Costura creado para prenda', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'encargado' => 'Ramiro',
                    'proceso_id' => $procsCostura->id,
                ]);
            }

            \Log::info('✅ Procesos de cotización reflectivo completados', [
                'numero_pedido' => $pedido->numero_pedido,
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error al crear procesos para cotización reflectivo', [
                'error' => $e->getMessage(),
                'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
                'trace' => $e->getTraceAsString(),
            ]);
            // No hacer nada más, el error ya fue logueado
        }
    }

    /**
     * Crear pedido sin cotización previa
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function crearSinCotizacion(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validar datos requeridos
            $cliente = $request->input('cliente');
            $formaPago = $request->input('forma_de_pago', '');
            $prendas = $request->input('prendas', []);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente es requerido'
                ], 422);
            }

            if (empty($prendas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe agregar al menos una prenda'
                ], 422);
            }

            \Log::info('📦 [SIN COTIZACIÓN] Creando pedido', [
                'cliente' => $cliente,
                'forma_de_pago' => $formaPago,
                'prendas_count' => count($prendas),
            ]);

            // Crear pedido de producción
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => null,
                'numero_cotizacion' => null,
                'numero_pedido' => $this->numeracionService->generarNumeroPedido(),
                'cliente' => $cliente,
                'asesor_id' => auth()->id(),
                'forma_de_pago' => $formaPago,
                'area' => null,
                'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
                'fecha_de_creacion_de_orden' => now(),
            ]);

            \Log::info('✅ Pedido creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // Crear prendas del pedido
            foreach ($prendas as $index => $prenda) {
                $cantidadesPorTalla = $prenda['cantidades'] ?? [];
                $cantidadTotal = array_sum($cantidadesPorTalla);

                $descripcionPrenda = $this->descripcionService->construirDescripcionPrenda(
                    $index + 1,
                    $prenda,
                    $cantidadesPorTalla
                );

                $prendaPedido = PrendaPedido::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'nombre_prenda' => $prenda['nombre_producto'] ?? 'Sin nombre',
                    'cantidad' => $cantidadTotal,
                    'descripcion' => $descripcionPrenda,
                    'cantidad_talla' => json_encode($cantidadesPorTalla),
                    'color_id' => null,
                    'tela_id' => null,
                    'tipo_manga_id' => null,
                    'tipo_broche_id' => null,
                    'tiene_bolsillos' => 0,
                    'tiene_reflectivo' => 0,
                ]);

                // Crear proceso inicial
                ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $prendaPedido->id,
                    'proceso' => 'Creación Orden',
                    'estado_proceso' => 'Completado',
                    'fecha_inicio' => now(),
                    'fecha_fin' => now(),
                ]);

                \Log::info('✅ Prenda agregada al pedido', [
                    'prenda_pedido_id' => $prendaPedido->id,
                    'nombre' => $prenda['nombre_producto'],
                    'cantidad_total' => $cantidadTotal,
                ]);
            }

            DB::commit();

            \Log::info('✅ Pedido creado exitosamente', [
                'numero_pedido' => $pedido->numero_pedido,
                'total_prendas' => count($prendas),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'numero_pedido' => $pedido->numero_pedido,
                'pedido_id' => $pedido->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('❌ Error al crear pedido sin cotización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear pedido PRENDA sin cotización
     * Similar a crearSinCotizacion pero específicamente para prendas tipo PRENDA
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function crearPrendaSinCotizacion(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validar datos requeridos
            $cliente = $request->input('cliente');
            $formaPago = $request->input('forma_de_pago', '');
            $prendas = $request->input('prendas', []);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente es requerido'
                ], 422);
            }

            if (empty($prendas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe agregar al menos una prenda'
                ], 422);
            }

            \Log::info('📦 [PRENDA SIN COTIZACIÓN] Creando pedido de prenda', [
                'cliente' => $cliente,
                'forma_de_pago' => $formaPago,
                'prendas_count' => count($prendas),
                'prendas_data' => $prendas,
            ]);

            // Crear pedido de producción
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => null,  // Sin cotización
                'numero_cotizacion' => null,
                'numero_pedido' => $this->numeracionService->generarNumeroPedido(),
                'cliente' => $cliente,
                'asesor_id' => auth()->id(),
                'forma_de_pago' => $formaPago,
                'area' => null,
                'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
                'fecha_de_creacion_de_orden' => now(),
            ]);

            \Log::info('✅ Pedido PRENDA creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // Crear prendas del pedido
            $cantidadTotalPedido = 0;
            foreach ($prendas as $index => $prenda) {
                // ✅ PROCESAR CANTIDADES CON SOPORTE A MÚLTIPLES GÉNEROS
                // Intentar primero la estructura nueva con géneros
                $cantidadesPorGeneroTalla = null;
                $cantidadesPorTalla = [];
                
                \Log::debug('🔍 [PRENDA] Buscando cantidades para prenda ' . ($prenda['nombre_producto'] ?? 'sin nombre'), [
                    'cantidad_talla' => $prenda['cantidad_talla'] ?? 'no existe',
                    'cantidades_por_genero' => $prenda['cantidades_por_genero'] ?? 'no existe',
                    'cantidades' => $prenda['cantidades'] ?? 'no existe',
                    'cantidadesPorTalla' => $prenda['cantidadesPorTalla'] ?? 'no existe',
                ]);
                
                // 1. NUEVA ESTRUCTURA: cantidad_talla = {genero: {talla: cantidad}} (desde FormData)
                if (!empty($prenda['cantidad_talla'])) {
                    \Log::debug('✅ Usando cantidad_talla de FormData');
                    $cantidadesPorGeneroTalla = $prenda['cantidad_talla'];
                    if (is_string($cantidadesPorGeneroTalla)) {
                        $cantidadesPorGeneroTalla = json_decode($cantidadesPorGeneroTalla, true);
                    }
                    // ✅ IMPORTANTE: Mantener estructura con géneros
                    $cantidadesPorTalla = is_array($cantidadesPorGeneroTalla) ? $cantidadesPorGeneroTalla : [];
                    
                    \Log::debug('📊 Decodificado cantidad_talla', [
                        'cantidadesPorGeneroTalla' => $cantidadesPorGeneroTalla,
                        'cantidadesPorTalla' => $cantidadesPorTalla,
                    ]);
                }
                // 2. ESTRUCTURA ALTERNATIVA: cantidades_por_genero
                else if (!empty($prenda['cantidades_por_genero'])) {
                    \Log::debug('✅ Usando cantidades_por_genero');
                    $cantidadesPorGeneroTalla = $prenda['cantidades_por_genero'];
                    if (is_string($cantidadesPorGeneroTalla)) {
                        $cantidadesPorGeneroTalla = json_decode($cantidadesPorGeneroTalla, true);
                    }
                    $cantidadesPorTalla = is_array($cantidadesPorGeneroTalla) ? $cantidadesPorGeneroTalla : [];
                } 
                // 3. ESTRUCTURA ANTIGUA: cantidades = {talla: cantidad}
                else {
                    \Log::debug('✅ Usando cantidades antigua');
                    $cantidadesPorTalla = $prenda['cantidades'] ?? $prenda['cantidadesPorTalla'] ?? [];
                    
                    if (is_string($cantidadesPorTalla)) {
                        $cantidadesPorTalla = json_decode($cantidadesPorTalla, true) ?? [];
                    }
                    
                    // Si cantidadesPorTalla es un array de arrays, aplanarlo
                    if (is_array($cantidadesPorTalla) && !empty($cantidadesPorTalla)) {
                        $cantidadesTemp = [];
                        foreach ($cantidadesPorTalla as $talla => $cantidad) {
                            $cantidadesTemp[$talla] = (int)$cantidad;
                        }
                        $cantidadesPorTalla = $cantidadesTemp;
                    }
                }
                
                // ✅ VALIDACIÓN: Asegurarse de que $cantidadesPorTalla es un array válido
                if (!is_array($cantidadesPorTalla)) {
                    \Log::warning('⚠️ cantidadesPorTalla no es array, inicializando vacío');
                    $cantidadesPorTalla = [];
                }
                
                \Log::debug('📊 Cantidades procesadas', [
                    'cantidadesPorGeneroTalla' => $cantidadesPorGeneroTalla,
                    'cantidadesPorTalla' => $cantidadesPorTalla,
                    'es_array' => is_array($cantidadesPorTalla),
                ]);
                
                // Calcular cantidad total
                $cantidadTotal = 0;
                if (isset($cantidadesPorGeneroTalla) && is_array($cantidadesPorGeneroTalla)) {
                    // Sumar todas las cantidades de todos los géneros
                    foreach ($cantidadesPorGeneroTalla as $genero => $tallas) {
                        if (is_array($tallas)) {
                            $cantidadTotal += array_sum($tallas);
                        }
                    }
                } else {
                    // Sumar cantidades simples
                    $cantidadTotal = array_sum($cantidadesPorTalla);
                }
                
                $cantidadTotalPedido += $cantidadTotal;

                \Log::info('📊 [PRENDA SIN COTIZACIÓN] Procesando prenda', [
                    'index' => $index,
                    'nombre' => $prenda['nombre_producto'] ?? 'Sin nombre',
                    'cantidades_por_talla' => $cantidadesPorTalla,
                    'cantidad_total' => $cantidadTotal,
                ]);

                // Construir descripción usando la misma función que para cotizaciones
                // CRÍTICO: Pasar los datos correctamente
                $descripcionPrenda = $this->descripcionService->construirDescripcionPrendaSinCotizacion($prenda, $cantidadesPorTalla);

                // Extraer variantes - IGUAL QUE EN crearDesdeCotizacion
                $colorId = null;
                $telaId = null;
                $tipoMangaId = null;
                $tipoBrocheId = null;
                $tieneBolsillos = 0;
                $tieneReflectivo = 0;

                // Si hay variantes, extraer los IDs
                $variantes = $prenda['variantes'] ?? [];
                if (is_string($variantes)) {
                    $variantes = json_decode($variantes, true) ?? [];
                }

                \Log::debug('📝 [PRENDA SIN COTIZACIÓN] Variantes recibidas', [
                    'variantes' => $variantes,
                    'telas_multiples' => $variantes['telas_multiples'] ?? 'no existe',
                ]);

                if (is_array($variantes) && !empty($variantes)) {
                    // Intentar obtener IDs directamente primero
                    $colorId = $variantes['color_id'] ?? null;
                    $telaId = $variantes['tela_id'] ?? null;
                    $tipoMangaId = $variantes['tipo_manga_id'] ?? null;
                    $tipoBrocheId = $variantes['tipo_broche_id'] ?? null;
                    
                    // Si no hay IDs pero hay nombres, crear los registros
                    // COLOR: Buscar o crear por nombre - puede venir en variantes.color o en telas_multiples
                    if (!$colorId) {
                        $colorNombre = $variantes['color'] ?? null;
                        
                        // Si el color no está en variantes.color, buscarlo en telas_multiples
                        if (!$colorNombre && !empty($variantes['telas_multiples'])) {
                            $telasMultiples = is_string($variantes['telas_multiples']) 
                                ? json_decode($variantes['telas_multiples'], true) 
                                : $variantes['telas_multiples'];
                            
                            if (is_array($telasMultiples) && !empty($telasMultiples) && !empty($telasMultiples[0]['color'])) {
                                $colorNombre = $telasMultiples[0]['color'];
                            }
                        }
                        
                        if (!empty($colorNombre)) {
                            $color = \DB::table('colores_prenda')
                                ->where('nombre', 'LIKE', '%' . $colorNombre . '%')
                                ->first();
                            
                            if (!$color) {
                                $colorId = \DB::table('colores_prenda')->insertGetId([
                                    'nombre' => $colorNombre,
                                    'activo' => 1,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                \Log::info('✅ Color creado', ['nombre' => $colorNombre, 'id' => $colorId]);
                            } else {
                                $colorId = $color->id;
                                \Log::info('✅ Color encontrado', ['nombre' => $colorNombre, 'id' => $colorId]);
                            }
                        }
                    }
                    
                    // TELA: Buscar o crear por nombre desde telas_multiples JSON
                    if (!$telaId && !empty($variantes['telas_multiples'])) {
                        $telasMultiples = is_string($variantes['telas_multiples']) 
                            ? json_decode($variantes['telas_multiples'], true) 
                            : $variantes['telas_multiples'];
                            
                        if (is_array($telasMultiples) && !empty($telasMultiples)) {
                            $primeraTela = $telasMultiples[0];
                            
                            if (!empty($primeraTela['nombre_tela'])) {
                                $tela = \DB::table('telas_prenda')
                                    ->where('nombre', 'LIKE', '%' . $primeraTela['nombre_tela'] . '%')
                                    ->first();
                                
                                if (!$tela) {
                                    $telaId = \DB::table('telas_prenda')->insertGetId([
                                        'nombre' => $primeraTela['nombre_tela'],
                                        'activo' => 1,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                    \Log::info('✅ Tela creada', ['nombre' => $primeraTela['nombre_tela'], 'id' => $telaId]);
                                } else {
                                    $telaId = $tela->id;
                                }
                            }
                        }
                    }

                    // TIPO MANGA: Buscar o crear por nombre
                    if (!$tipoMangaId && !empty($variantes['tipo_manga'])) {
                        $tipoManga = \DB::table('tipos_manga')
                            ->where('nombre', 'LIKE', '%' . $variantes['tipo_manga'] . '%')
                            ->first();
                        
                        if (!$tipoManga) {
                            $tipoMangaId = \DB::table('tipos_manga')->insertGetId([
                                'nombre' => $variantes['tipo_manga'],
                                'activo' => 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            \Log::info('✅ Tipo Manga creado', ['nombre' => $variantes['tipo_manga'], 'id' => $tipoMangaId]);
                        } else {
                            $tipoMangaId = $tipoManga->id;
                        }
                    }

                    // TIPO BROCHE: Buscar o crear por nombre
                    if (!$tipoBrocheId && !empty($variantes['tipo_broche'])) {
                        $tipoBroche = \DB::table('tipos_broche')
                            ->where('nombre', 'LIKE', '%' . $variantes['tipo_broche'] . '%')
                            ->first();
                        
                        if (!$tipoBroche) {
                            $tipoBrocheId = \DB::table('tipos_broche')->insertGetId([
                                'nombre' => $variantes['tipo_broche'],
                                'activo' => 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            \Log::info('✅ Tipo Broche creado', ['nombre' => $variantes['tipo_broche'], 'id' => $tipoBrocheId]);
                        } else {
                            $tipoBrocheId = $tipoBroche->id;
                        }
                    }
                    
                    // BOOLEANOS: Convertir correctamente
                    $tieneBolsillos = isset($variantes['tiene_bolsillos']) ? ($variantes['tiene_bolsillos'] ? 1 : 0) : 0;
                    $tieneReflectivo = isset($variantes['tiene_reflectivo']) ? ($variantes['tiene_reflectivo'] ? 1 : 0) : 0;
                }

                \Log::info('📍 [PRENDA SIN COTIZACIÓN] Variantes extraídas/creadas', [
                    'color_id' => $colorId,
                    'tela_id' => $telaId,
                    'tipo_manga_id' => $tipoMangaId,
                    'tipo_broche_id' => $tipoBrocheId,
                    'tiene_bolsillos' => $tieneBolsillos,
                    'tiene_reflectivo' => $tieneReflectivo,
                ]);

                // Crear prenda del pedido con TODOS los campos - IGUAL QUE EN crearDesdeCotizacion
                $prendaPedido = PrendaPedido::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'nombre_prenda' => $prenda['nombre_producto'] ?? 'Sin nombre',
                    'cantidad' => $cantidadTotal,
                    'descripcion' => $prenda['descripcion'] ?? '', // ✅ SOLO LA DESCRIPCIÓN DEL USUARIO
                    'descripcion_variaciones' => $this->armarDescripcionVariacionesPrendaSinCotizacion($variantes),
                    // ✅ CANTIDAD POR TALLA Y GÉNERO: {genero: {talla: cantidad}} o {talla: cantidad}
                    'cantidad_talla' => json_encode($cantidadesPorTalla),
                    // ✅ GENERO (puede ser múltiple)
                    'genero' => json_encode($this->procesarMultiplesGeneros($prenda['genero'] ?? '')),
                    'color_id' => $colorId,
                    'tela_id' => $telaId,
                    'tipo_manga_id' => $tipoMangaId,
                    'tipo_broche_id' => $tipoBrocheId,
                    'tiene_bolsillos' => $tieneBolsillos,
                    'tiene_reflectivo' => $tieneReflectivo,
                    // ✅ NUEVOS CAMPOS: Observaciones de variaciones
                    'manga_obs' => $prenda['obs_manga'] ?? $prenda['manga_obs'] ?? '',
                    'bolsillos_obs' => $prenda['obs_bolsillos'] ?? $prenda['bolsillos_obs'] ?? '',
                    'broche_obs' => $prenda['obs_broche'] ?? $prenda['broche_obs'] ?? '',
                    'reflectivo_obs' => $prenda['obs_reflectivo'] ?? $prenda['reflectivo_obs'] ?? '',
                    // ✅ CAMPO DE BODEGA
                    'de_bodega' => (int)($prenda['de_bodega'] ?? 0),
                ]);

                \Log::info('✅ Prenda PRENDA creada correctamente', [
                    'prenda_pedido_id' => $prendaPedido->id,
                    'nombre_prenda' => $prenda['nombre_producto'],
                    'genero' => $prendaPedido->genero,
                    'cantidad' => $cantidadTotal,
                    'cantidad_talla' => json_encode($cantidadesPorTalla),
                    'color_id' => $colorId,
                    'tela_id' => $telaId,
                    'tipo_manga_id' => $tipoMangaId,
                    'tipo_broche_id' => $tipoBrocheId,
                    'manga_obs' => $prendaPedido->manga_obs,
                    'bolsillos_obs' => $prendaPedido->bolsillos_obs,
                    'broche_obs' => $prendaPedido->broche_obs,
                    'reflectivo_obs' => $prendaPedido->reflectivo_obs,
                    'de_bodega' => $prendaPedido->de_bodega,
                ]);

                // Crear proceso inicial
                ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $prendaPedido->id,
                    'proceso' => 'Creación Orden',
                    'estado_proceso' => 'Completado',
                    'fecha_inicio' => now(),
                    'fecha_fin' => now(),
                ]);

                // Guardar fotos de prenda si existen - CONVERTIR A WEBP IGUAL QUE SUPERVISOR-PEDIDOS
                if ($request->hasFile("prendas.$index.fotos")) {
                    $fotos = $request->file("prendas.$index.fotos", []);
                    foreach ($fotos as $orden => $foto) {
                        if ($foto && $foto->isValid()) {
                            // Usar el mismo método que supervisor-pedidos para guardar como WebP
                            $pathWebp = $this->imagenService->guardarImagenComoWebp($foto, $pedido->numero_pedido, 'prendas');
                            
                            DB::table('prenda_fotos_pedido')->insert([
                                'prenda_pedido_id' => $prendaPedido->id,
                                'ruta_original' => $pathWebp,
                                'ruta_webp' => $pathWebp,
                                'orden' => $orden + 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                    \Log::info('✅ Fotos de prenda guardadas', [
                        'prenda_id' => $prendaPedido->id,
                        'cantidad_fotos' => count($fotos),
                    ]);
                }

                // Guardar fotos de telas si existen - CONVERTIR A WEBP IGUAL QUE SUPERVISOR-PEDIDOS
                if (!empty($prenda['telas']) && is_array($prenda['telas'])) {
                    foreach ($prenda['telas'] as $telaIdx => $tela) {
                        if ($request->hasFile("prendas.$index.telas.$telaIdx.fotos")) {
                            $fotosTela = $request->file("prendas.$index.telas.$telaIdx.fotos", []);
                            foreach ($fotosTela as $orden => $fotoTela) {
                                if ($fotoTela && $fotoTela->isValid()) {
                                    // Usar el mismo método que supervisor-pedidos para guardar como WebP
                                    $pathWebp = $this->imagenService->guardarImagenComoWebp($fotoTela, $pedido->numero_pedido, 'telas');

                                    DB::table('prenda_fotos_tela_pedido')->insert([
                                        'prenda_pedido_id' => $prendaPedido->id,
                                        'ruta_original' => $pathWebp,
                                        'ruta_webp' => $pathWebp,
                                        'orden' => $orden + 1,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                            \Log::info('✅ Fotos de tela guardadas', [
                                'prenda_id' => $prendaPedido->id,
                                'tela_index' => $telaIdx,
                            ]);
                        }
                    }
                }
            }

            // Actualizar cantidad total del pedido
            $pedido->update([
                'cantidad_total' => $cantidadTotalPedido
            ]);

            DB::commit();

            \Log::info('✅ [PRENDA SIN COTIZACIÓN] Pedido completamente creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad_total' => $cantidadTotalPedido,
                'prendas_count' => count($prendas),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido PRENDA creado exitosamente',
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad_total' => $cantidadTotalPedido,
                'redirect_url' => route('asesores.pedidos.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('❌ Error al crear pedido PRENDA sin cotización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    // MÉTODOS ELIMINADOS - Migrados a DescripcionService
    // - construirDescripcionPrendaSinCotizacion() -> DescripcionService::construirDescripcionPrendaSinCotizacion()
    // - armarDescripcionVariacionesPrendaSinCotizacion() -> DescripcionService (método privado)

    // MÉTODO ELIMINADO - Migrado a ImagenService::guardarImagenComoWebp()

    /**
     * Crear pedido REFLECTIVO sin cotización previa
     */
    public function crearReflectivoSinCotizacion(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validar datos requeridos
            $cliente = $request->input('cliente');
            $formaPago = $request->input('forma_de_pago', '');
            $asesora = $request->input('asesora', '');
            $prendas = $request->input('prendas', []);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente es requerido'
                ], 422);
            }

            if (empty($prendas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe agregar al menos una prenda'
                ], 422);
            }

            \Log::info('📦 [REFLECTIVO SIN COTIZACIÓN] Creando pedido de reflectivo', [
                'cliente' => $cliente,
                'forma_de_pago' => $formaPago,
                'asesora' => $asesora,
                'prendas_count' => count($prendas),
            ]);

            // Crear pedido de producción
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => null,  // Sin cotización
                'numero_cotizacion' => null,
                'numero_pedido' => $this->numeracionService->generarNumeroPedido(),
                'cliente' => $cliente,
                'asesor_id' => auth()->id(),
                'forma_de_pago' => $formaPago,
                'area' => null,
                'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
                'fecha_de_creacion_de_orden' => now(),
            ]);

            \Log::info('✅ Pedido REFLECTIVO creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // Crear prendas del pedido
            $cantidadTotalPedido = 0;
            foreach ($prendas as $index => $prenda) {
                // Procesar cantidad_talla con estructura género => talla => cantidad
                $cantidadTallaGenero = $prenda['cantidad_talla'] ?? [];
                
                // Aplanar para calcular total (si viene en formato anidado)
                $cantidadesTotal = [];
                $cantidadTotalPrenda = 0;
                
                if (is_array($cantidadTallaGenero)) {
                    // Si tiene estructura de género => talla => cantidad
                    foreach ($cantidadTallaGenero as $genero => $tallas) {
                        if (is_array($tallas)) {
                            foreach ($tallas as $talla => $cantidad) {
                                $cantidadTotalPrenda += (int)$cantidad;
                                if (!isset($cantidadesTotal[$talla])) {
                                    $cantidadesTotal[$talla] = 0;
                                }
                                $cantidadesTotal[$talla] += (int)$cantidad;
                            }
                        }
                    }
                }
                
                $cantidadTotalPedido += $cantidadTotalPrenda;

                \Log::info('📊 [REFLECTIVO SIN COTIZACIÓN] Procesando prenda', [
                    'index' => $index,
                    'nombre' => $prenda['nombre_producto'] ?? 'Sin nombre',
                    'generos' => $prenda['genero'] ?? '',
                    'cantidad_talla_genero' => $cantidadTallaGenero,
                    'cantidad_total' => $cantidadTotalPrenda,
                ]);

                // Crear prenda del pedido (MÍNIMO para reflectivo sin cotización)
                // No guardar descripción ni cantidad_talla aquí, irán en prendas_reflectivo
                $prendaPedido = PrendaPedido::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'nombre_prenda' => $prenda['nombre_producto'] ?? 'Sin nombre',
                    'cantidad' => $cantidadTotalPrenda,
                    // ✅ NO guardar descripción ni cantidad_talla aquí (van en prendas_reflectivo)
                ]);

                \Log::info('✅ Prenda REFLECTIVO creada correctamente', [
                    'prenda_pedido_id' => $prendaPedido->id,
                    'nombre_prenda' => $prenda['nombre_producto'],
                    'cantidad' => $cantidadTotalPrenda,
                ]);

                // ✅ NUEVO: Crear registro en tabla prendas_reflectivo con toda la información
                $prendaReflectivo = \App\Models\PrendaReflectivo::create([
                    'prenda_pedido_id' => $prendaPedido->id,
                    'nombre_producto' => $prenda['nombre_producto'] ?? 'Sin nombre',
                    'descripcion' => $prenda['descripcion'] ?? '',
                    'generos' => json_encode($this->procesarGeneros($prenda['genero'] ?? '')),
                    'cantidad_talla' => json_encode($cantidadTallaGenero), // Estructura género => talla => cantidad
                    'ubicaciones' => json_encode($prenda['ubicaciones'] ?? []),
                    'cantidad_total' => $cantidadTotalPrenda,
                ]);

                \Log::info('✅ Información REFLECTIVO guardada en tabla especializada', [
                    'prenda_reflectivo_id' => $prendaReflectivo->id,
                    'prenda_pedido_id' => $prendaPedido->id,
                    'generos' => $prendaReflectivo->generos,
                    'cantidad_talla' => $prendaReflectivo->cantidad_talla,
                ]);

                // Crear proceso inicial
                ProcesoPrenda::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $prendaPedido->id,
                    'proceso' => 'Creación Orden',
                    'estado_proceso' => 'Completado',
                    'fecha_inicio' => now(),
                    'fecha_fin' => now(),
                ]);

                // Guardar fotos de reflectivo si existen
                if ($request->hasFile("prendas.$index.fotos")) {
                    $fotos = $request->file("prendas.$index.fotos", []);
                    foreach ($fotos as $orden => $foto) {
                        if ($foto && $foto->isValid()) {
                            // Usar el mismo método que supervisor-pedidos para guardar como WebP
                            $pathWebp = $this->imagenService->guardarImagenComoWebp($foto, $pedido->numero_pedido, 'reflectivo');

                            DB::table('prenda_fotos_pedido')->insert([
                                'prenda_pedido_id' => $prendaPedido->id,
                                'ruta_original' => $pathWebp,
                                'ruta_webp' => $pathWebp,
                                'orden' => $orden + 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                    \Log::info('✅ Fotos de reflectivo guardadas', [
                        'prenda_id' => $prendaPedido->id,
                        'cantidad_fotos' => count($fotos),
                    ]);
                }
            }

            // Actualizar cantidad total del pedido
            $pedido->update([
                'cantidad_total' => $cantidadTotalPedido
            ]);

            DB::commit();

            \Log::info('✅ [REFLECTIVO SIN COTIZACIÓN] Pedido completamente creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad_total' => $cantidadTotalPedido,
                'prendas_count' => count($prendas),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido REFLECTIVO creado exitosamente',
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad_total' => $cantidadTotalPedido,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('❌ Error al crear pedido REFLECTIVO sin cotización', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido REFLECTIVO: ' . $e->getMessage()
            ], 500);
        }
    }

    // MÉTODO ELIMINADO - Migrado a DescripcionService::construirDescripcionReflectivoSinCotizacion()

    /**
     * Procesar múltiples géneros desde el input
     * Convierte string, array o JSON a un array limpio de géneros
     * 
     * @param mixed $generoInput - Puede ser string, array o JSON
     * @return array - Array de géneros sin duplicados ni vacíos
     */
    private function procesarGeneros($generoInput): array
    {
        return $this->procesarMultiplesGeneros($generoInput);
    }

    /**
     * Procesar múltiples géneros desde el input (alias antiguo)
     * Convierte string, array o JSON a un array limpio de géneros
     * 
     * @param mixed $generoInput - Puede ser string, array o JSON
     * @return array - Array de géneros sin duplicados ni vacíos
     */
    private function procesarMultiplesGeneros($generoInput): array
    {
        $generos = [];

        if (is_array($generoInput)) {
            // Si ya es array, filtrar vacíos
            $generos = array_filter($generoInput, fn($g) => !empty($g));
        } elseif (is_string($generoInput)) {
            // Si es string, intentar decodificar JSON
            if (str_starts_with($generoInput, '[')) {
                $decoded = json_decode($generoInput, true);
                $generos = is_array($decoded) ? array_filter($decoded) : (!empty($generoInput) ? [$generoInput] : []);
            } else {
                // Si es string simple, crear array
                $generos = !empty($generoInput) ? [$generoInput] : [];
            }
        }

        // Eliminar duplicados y valores vacíos, mantener índices reset
        return array_values(array_unique(array_filter($generos)));
    }

}




