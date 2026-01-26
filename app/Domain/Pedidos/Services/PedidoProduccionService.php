<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\Domain\Pedidos\Repositories\CotizacionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de dominio para lógica de negocio de Pedidos de Producción
 * Responsabilidad: Orquestar la creación y gestión de pedidos
 */
class PedidoProduccionService
{
    public function __construct(
        private NumeracionService $numeracionService,
        private DescripcionService $descripcionService,
        private CotizacionRepository $cotizacionRepository
    ) {}

    /**
     * Crear pedido de producción desde cotización
     */
    public function crearDesdeCotizacion(int $cotizacionId): PedidoProduccion
    {
        $cotizacion = $this->cotizacionRepository->obtenerCotizacionCompleta($cotizacionId);

        if (!$cotizacion) {
            throw new \RuntimeException('Cotización no encontrada');
        }

        // Verificar que la cotización estÃ© aprobada
        if (!in_array($cotizacion->estado, ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])) {
            throw new \RuntimeException('La cotización debe estar aprobada para crear un pedido');
        }

        return DB::transaction(function () use ($cotizacion) {
            // Generar nÃºmero de pedido
            $numeroPedido = $this->numeracionService->generarNumeroPedido();

            // Crear pedido
            $pedido = PedidoProduccion::create([
                'numero_pedido' => $numeroPedido,
                'cotizacion_id' => $cotizacion->id,
                'asesor_id' => Auth::id(),
                'cliente_id' => $cotizacion->cliente_id,
                'estado' => 'PENDIENTE',
                'fecha_pedido' => now(),
                'especificaciones' => $this->cotizacionRepository->obtenerEspecificaciones($cotizacion),
            ]);

            // Procesar prendas de la cotización
            $this->procesarPrendasDeCotizacion($pedido, $cotizacion);

            // Actualizar estado de cotización
            $cotizacion->update(['estado' => 'PEDIDO_CREADO']);

            \Log::info('Pedido de producción creado exitosamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $numeroPedido,
                'cotizacion_id' => $cotizacion->id
            ]);

            return $pedido;
        });
    }

    /**
     * Procesar prendas de la cotización y agregarlas al pedido
     */
    private function procesarPrendasDeCotizacion(PedidoProduccion $pedido, Cotizacion $cotizacion): void
    {
        $prendas = $this->cotizacionRepository->obtenerPrendasCotizacion($cotizacion->id);

        foreach ($prendas as $index => $prendaCotizacion) {
            // Calcular cantidades por talla
            $cantidadesPorTalla = $this->calcularCantidadesPorTalla($prendaCotizacion);

            // Construir descripción
            $descripcion = $this->descripcionService->construirDescripcionPrenda(
                $index + 1,
                [
                    'descripcion' => $prendaCotizacion->descripcion,
                    'variantes' => $prendaCotizacion->variantes->toArray()
                ],
                $cantidadesPorTalla
            );

            // Crear prenda del pedido
            $pedido->prendas()->create([
                'descripcion' => $descripcion,
                'cantidad_total' => array_sum($cantidadesPorTalla),
                'cantidades_por_talla' => $cantidadesPorTalla,
                'prenda_cotizacion_id' => $prendaCotizacion->id,
            ]);
        }
    }

    /**
     * Calcular cantidades por talla de una prenda
     */
    private function calcularCantidadesPorTalla($prendaCotizacion): array
    {
        $cantidades = [];

        foreach ($prendaCotizacion->tallas as $talla) {
            $cantidades[$talla->talla] = $talla->cantidad;
        }

        return $cantidades;
    }

    /**
     * Obtener pedidos del asesor actual con filtros
     */
    public function obtenerPedidosAsesor(array $filtros = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = PedidoProduccion::query()
            ->with([
                'cotizacion.cliente',
                'cotizacion.tipoCotizacion',
                'prendas',
                'asesor'
            ])
            ->where('asesor_id', Auth::id());

        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_pedido', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_pedido', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Actualizar estado de un pedido
     */
    public function actualizarEstado(int $pedidoId, string $nuevoEstado): PedidoProduccion
    {
        $pedido = PedidoProduccion::findOrFail($pedidoId);

        // Verificar que el asesor sea el dueÃ±o del pedido
        if ($pedido->asesor_id !== Auth::id()) {
            throw new \RuntimeException('No tienes permiso para actualizar este pedido');
        }

        $pedido->update(['estado' => $nuevoEstado]);

        \Log::info('Estado de pedido actualizado', [
            'pedido_id' => $pedidoId,
            'estado_anterior' => $pedido->getOriginal('estado'),
            'estado_nuevo' => $nuevoEstado
        ]);

        return $pedido;
    }
}

