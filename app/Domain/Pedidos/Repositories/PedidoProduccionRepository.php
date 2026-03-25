<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\Services\FacturaPedidoService;
use App\Domain\Pedidos\Services\ReciboPedidoService;
use App\Domain\Pedidos\Traits\GestionaTallasRelacional;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repositorio para acceso a datos de Pedidos de Producción
 * Responsabilidad: Encapsular todas las queries de pedidos
 */
class PedidoProduccionRepository
{
    use GestionaTallasRelacional;
    
    private FacturaPedidoService $facturaService;
    private ReciboPedidoService $reciboService;

    public function __construct(
        FacturaPedidoService $facturaService,
        ReciboPedidoService $reciboService
    ) {
        $this->facturaService = $facturaService;
        $this->reciboService = $reciboService;
    }
    /**
     * Obtener pedido por número de pedido
     */
    public function findByNumeroPedido(string $numeroPedido): PedidoProduccion
    {
        return PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
    }

    /**
     * Recargar relaciones de prendas en el pedido
     */
    public function cargarPrendas(PedidoProduccion $orden): PedidoProduccion
    {
        return $orden->load('prendas');
    }

    /**
     * Obtener pedido por ID con relaciones
     */
    public function obtenerPorId(int $id): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'cotizacion.cliente',
            'cotizacion.tipoCotizacion',
            'prendas.variantes.tipoManga',
            'prendas.variantes.tipoBrocheBoton',
            'prendas.fotos',
            'prendas.fotosTelas',
            'prendas.coloresTelas.color',  // NUEVO: Cargar colores y telas desde tabla intermedia
            'prendas.coloresTelas.tela',   // NUEVO: Cargar telas con sus detalles (nombre, referencia)
            'prendas.coloresTelas.fotos', // NUEVO: Cargar fotos de telas para cada combinación color-tela
            'prendas.tallas',  // NUEVA: Cargar tallas relacionales
            'prendas.tallas.coloresAsignados',  //  NUEVA RELACIÓN: Colores asignados por talla
            'prendas.procesos',
            'prendas.procesos.tipoProceso',  //  NUEVO: Cargar el nombre del tipo de proceso
            'prendas.procesos.imagenes',
            'prendas.procesos.tallas',  // NUEVO: Cargar tallas de cada proceso (desde pedidos_procesos_prenda_tallas)
            'epps.imagenes',  // NO cargar categoria: es opcional
        ])->find($id);
    }

    /**
     * Obtener el último pedido creado (para secuencial de numeros)
     */
    public function obtenerUltimoPedido(): ?PedidoProduccion
    {
        return PedidoProduccion::orderBy('id', 'desc')->first();
    }

    /**
     * Obtener pedidos del asesor con filtros
     */
    public function obtenerPedidosAsesor(array $filtros = []): LengthAwarePaginator
    {
        $query = PedidoProduccion::query()
            ->select([
                'pedidos_produccion.*',
                'pedidos_produccion.area'  // Asegurar que se incluye el campo area
            ])
            ->with(['cotizacion', 'prendas'])
            ->where('estado', '!=', 'Borrador');

        // Si el usuario es asesor, solo mostrar sus pedidos
        // Otros roles pueden ver todos los pedidos
        $user = Auth::user();
        if ($user && $user->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Verificar si el pedido pertenece al asesor
     */
    public function perteneceAlAsesor(int $pedidoId, int $asesorId): bool
    {
        return PedidoProduccion::where('id', $pedidoId)
            ->where('asesor_id', $asesorId)
            ->exists();
    }

    /**
     * Actualizar cantidad total del pedido
     */
    public function actualizarCantidadTotal(string $numeroPedido): void
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        
        if ($pedido) {
            $cantidadTotal = $pedido->prendas()->sum('cantidad');
            $pedido->update(['cantidad_total' => $cantidadTotal]);
        }
    }

    /**
     * MEJORADO: Obtener datos completos de factura de un pedido
     * Delegado a FacturaPedidoService para mejor organización
     */
    public function obtenerDatosFactura(int $pedidoId): array
    {
        return $this->facturaService->obtenerDatosFactura($pedidoId);
    }

    /**
     * Obtener datos para los recibos dinámicos
     * Delegado a ReciboPedidoService para mejor organización
     */
    public function obtenerDatosRecibos(int $pedidoId, bool $filtrarProcesosPendientes = false): array
    {
        return $this->reciboService->obtenerDatosRecibos($pedidoId, $filtrarProcesosPendientes);
    }

    /**
     * Normalizar ruta de imagen para asegurar que comience con /storage/
     * Convierte rutas relativas en rutas absolutas con prefijo /storage/
     */
    private function normalizarRutaImagen(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }
        
        // Si ya comienza con /storage/, devolver tal cual
        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }
        
        // Si comienza con storage/, agregar / al inicio
        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }
        
        // Si no comienza ni con /storage/ ni con storage/, agregar /storage/ al inicio
        return '/storage/' . ltrim($ruta, '/');
    }

    /**
     * REFACTOR FASE 8: Obtener pedido por ID y validar que pertenece al asesor
     * 
     * Responsabilidad: Encapsular la validación de seguridad + obtención
     * Uso: En los UseCases (ActualizarBorradorUseCase, etc.)
     * 
     * @param int $pedidoId
     * @param int $asesorId
     * @return PedidoProduccion|null
     */
    public function obtenerPorIdYAsesor(int $pedidoId, int $asesorId): ?PedidoProduccion
    {
        return PedidoProduccion::where('id', $pedidoId)
            ->where('asesor_id', $asesorId)
            ->first();
    }

    /**
     * REFACTOR FASE 8: Actualizar datos básicos de un pedido
     * 
     * Responsabilidad: Encapsular la lógica de actualización de campos simples
     * Uso: En ActualizarBorradorUseCase
     * 
     * @param PedidoProduccion $pedido
     * @param array $datos Campos a actualizar: ['cliente', 'forma_de_pago', 'observaciones', etc]
     * @return void
     */
    public function actualizarDatosBasicos(PedidoProduccion $pedido, array $datos): void
    {
        $pedido->update($datos);
    }

    /**
     * REFACTOR FASE 8: Obtener pedido con EPPs e imágenes
     * 
     * Responsabilidad: Encapsular la query para obtener EPPs con sus imágenes
     * Uso: En ActualizarBorradorUseCase al procesar EPPs
     * 
     * @param int $pedidoId
     * @param int $eppId
     * @return \App\Models\PedidoEpp|null
     */
    public function obtenerEppConImagenes(int $pedidoId, int $eppId): ?\App\Models\PedidoEpp
    {
        return \App\Models\PedidoEpp::where('pedido_produccion_id', $pedidoId)
            ->where('epp_id', $eppId)
            ->with(['imagenes'])
            ->first();
    }

    /**
     * REFACTOR FASE 8: Eliminar imágenes de EPP
     * 
     * Responsabilidad: Encapsular la lógica de eliminación de imágenes
     * Maneja:
     * - Eliminar archivos del storage
     * - Eliminar registros de BD
     * 
     * Uso: En ActualizarBorradorUseCase
     * 
     * @param int $pedidoEppId
     * @return int Cantidad de imágenes eliminadas
     */
    public function eliminarImagenesEpp(int $pedidoEppId): int
    {
        $imagenes = \App\Models\PedidoEppImagen::where('pedido_epp_id', $pedidoEppId)->get();
        $cantidad = 0;

        foreach ($imagenes as $imagen) {
            // Eliminar archivos del storage
            if ($imagen->ruta_original && \Illuminate\Support\Facades\Storage::disk('public')->exists($imagen->ruta_original)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($imagen->ruta_original);
            }

            if ($imagen->ruta_web && $imagen->ruta_web !== $imagen->ruta_original && 
                \Illuminate\Support\Facades\Storage::disk('public')->exists($imagen->ruta_web)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($imagen->ruta_web);
            }

            // Eliminar registro
            $imagen->delete();
            $cantidad++;
        }

        return $cantidad;
    }
}

