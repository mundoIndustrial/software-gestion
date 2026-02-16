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
     * Obtener el Ãºltimo pedido creado (para secuencial de nÃºmeros)
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
            ->with(['cotizacion', 'prendas']);

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
}

