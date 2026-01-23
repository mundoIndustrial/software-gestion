<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\Domain\Pedidos\Repositories\CotizacionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de dominio para lÃ³gica de negocio de Pedidos de ProducciÃ³n
 * Responsabilidad: Orquestar la creaciÃ³n y gestiÃ³n de pedidos
 */
class PedidoProduccionService
{
    public function __construct(
        private NumeracionService $numeracionService,
        private DescripcionService $descripcionService,
        private CotizacionRepository $cotizacionRepository
    ) {}

    /**
     * Crear pedido de producciÃ³n desde cotizaciÃ³n
     */
    public function crearDesdeCotizacion(int $cotizacionId): PedidoProduccion
    {
        $cotizacion = $this->cotizacionRepository->obtenerCotizacionCompleta($cotizacionId);

        if (!$cotizacion) {
            throw new \RuntimeException('CotizaciÃ³n no encontrada');
        }

        // Verificar que la cotizaciÃ³n estÃ© aprobada
        if (!in_array($cotizacion->estado, ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])) {
            throw new \RuntimeException('La cotizaciÃ³n debe estar aprobada para crear un pedido');
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

            // Procesar prendas de la cotizaciÃ³n
            $this->procesarPrendasDeCotizacion($pedido, $cotizacion);

            // Actualizar estado de cotizaciÃ³n
            $cotizacion->update(['estado' => 'PEDIDO_CREADO']);

            \Log::info('Pedido de producciÃ³n creado exitosamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $numeroPedido,
                'cotizacion_id' => $cotizacion->id
            ]);

            return $pedido;
        });
    }

    /**
     * Procesar prendas de la cotizaciÃ³n y agregarlas al pedido
     */
    private function procesarPrendasDeCotizacion(PedidoProduccion $pedido, Cotizacion $cotizacion): void
    {
        $prendas = $this->cotizacionRepository->obtenerPrendasCotizacion($cotizacion->id);

        foreach ($prendas as $index => $prendaCotizacion) {
            // Calcular cantidades por talla
            $cantidadesPorTalla = $this->calcularCantidadesPorTalla($prendaCotizacion);

            // Construir descripciÃ³n
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

