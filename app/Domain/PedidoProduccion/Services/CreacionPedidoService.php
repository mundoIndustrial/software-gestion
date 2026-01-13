<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\Domain\PedidoProduccion\Repositories\CotizacionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de dominio para creación de pedidos de producción
 * Responsabilidad: Orquestar la creación completa de pedidos desde cotizaciones o sin ellas
 */
class CreacionPedidoService
{
    public function __construct(
        private NumeracionService $numeracionService,
        private DescripcionService $descripcionService,
        private ImagenService $imagenService,
        private CotizacionRepository $cotizacionRepository,
        private LogoPedidoService $logoPedidoService
    ) {}

    /**
     * Crear pedido desde cotización (método principal)
     */
    public function crearDesdeCotizacion(int $cotizacionId): array
    {
        $cotizacion = $this->cotizacionRepository->obtenerCotizacionCompleta($cotizacionId);

        if (!$cotizacion) {
            throw new \RuntimeException('Cotización no encontrada');
        }

        // Detectar tipo de cotización
        $esLogo = $this->cotizacionRepository->esCotizacionLogo($cotizacion);
        $esReflectivo = $this->cotizacionRepository->esCotizacionReflectivo($cotizacion);

        if ($esLogo) {
            return $this->crearLogoPedido($cotizacion);
        }

        return $this->crearPedidoNormal($cotizacion, $esReflectivo);
    }

    /**
     * Crear pedido de tipo LOGO
     */
    private function crearLogoPedido(Cotizacion $cotizacion): array
    {
        $logoPedidoId = $this->logoPedidoService->crearDesdeCotizacion($cotizacion);

        return [
            'success' => true,
            'tipo' => 'logo',
            'logo_pedido_id' => $logoPedidoId,
            'message' => 'Logo pedido creado exitosamente'
        ];
    }

    /**
     * Crear pedido normal (PRENDA, REFLECTIVO, etc.)
     */
    private function crearPedidoNormal(Cotizacion $cotizacion, bool $esReflectivo): array
    {
        return DB::transaction(function () use ($cotizacion, $esReflectivo) {
            // Generar número de pedido
            $numeroPedido = $this->numeracionService->generarNumeroPedido();

            // Extraer forma de pago
            $formaPago = $this->extraerFormaPago($cotizacion);

            // Crear pedido
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'numero_pedido' => $numeroPedido,
                'cliente' => $cotizacion->cliente->nombre ?? 'Sin nombre',
                'asesor_id' => Auth::id(),
                'forma_de_pago' => $formaPago,
                'estado' => 'PENDIENTE_SUPERVISOR',
                'fecha_de_creacion_de_orden' => now(),
            ]);

            // Procesar prendas
            $this->procesarPrendasDeCotizacion($pedido, $cotizacion);

            // Si es reflectivo, crear procesos específicos
            if ($esReflectivo) {
                $this->crearProcesosReflectivo($pedido, $cotizacion);
            }

            return [
                'success' => true,
                'tipo' => 'normal',
                'pedido_id' => $pedido->id,
                'numero_pedido' => $numeroPedido,
                'message' => 'Pedido creado exitosamente'
            ];
        });
    }

    /**
     * Crear pedido sin cotización (PRENDA)
     */
    public function crearPrendaSinCotizacion(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $numeroPedido = $this->numeracionService->generarNumeroPedido();

            $pedido = PedidoProduccion::create([
                'cotizacion_id' => null,
                'numero_cotizacion' => null,
                'numero_pedido' => $numeroPedido,
                'cliente' => $data['cliente'] ?? '',
                'asesor_id' => Auth::id(),
                'forma_de_pago' => $data['forma_de_pago'] ?? '',
                'estado' => 'PENDIENTE_SUPERVISOR',
                'fecha_de_creacion_de_orden' => now(),
            ]);

            // Procesar prendas
            $this->procesarPrendasNuevas($pedido, $data['prendas'] ?? []);

            return [
                'success' => true,
                'pedido_id' => $pedido->id,
                'numero_pedido' => $numeroPedido,
                'message' => 'Pedido de prenda creado exitosamente'
            ];
        });
    }

    /**
     * Crear pedido sin cotización (REFLECTIVO)
     */
    public function crearReflectivoSinCotizacion(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $numeroPedido = $this->numeracionService->generarNumeroPedido();

            $pedido = PedidoProduccion::create([
                'cotizacion_id' => null,
                'numero_cotizacion' => null,
                'numero_pedido' => $numeroPedido,
                'cliente' => $data['cliente'] ?? '',
                'asesor_id' => Auth::id(),
                'forma_de_pago' => $data['forma_de_pago'] ?? '',
                'estado' => 'PENDIENTE_SUPERVISOR',
                'fecha_de_creacion_de_orden' => now(),
            ]);

            // Procesar prendas reflectivas
            $this->procesarPrendasReflectivas($pedido, $data['prendas'] ?? []);

            return [
                'success' => true,
                'pedido_id' => $pedido->id,
                'numero_pedido' => $numeroPedido,
                'message' => 'Pedido reflectivo creado exitosamente'
            ];
        });
    }

    /**
     * Extraer forma de pago de cotización
     */
    private function extraerFormaPago(Cotizacion $cotizacion): string
    {
        $especificaciones = $this->cotizacionRepository->obtenerEspecificaciones($cotizacion);
        
        if (!empty($especificaciones['forma_pago']) && is_array($especificaciones['forma_pago'])) {
            if (count($especificaciones['forma_pago']) > 0) {
                return $especificaciones['forma_pago'][0]['valor'] ?? '';
            }
        }
        
        return '';
    }

    /**
     * Procesar prendas de cotización
     */
    private function procesarPrendasDeCotizacion(PedidoProduccion $pedido, Cotizacion $cotizacion): void
    {
        $prendas = $this->cotizacionRepository->obtenerPrendasCotizacion($cotizacion->id);

        foreach ($prendas as $index => $prendaCotizacion) {
            $cantidadesPorTalla = $this->calcularCantidadesPorTalla($prendaCotizacion);
            
            $descripcion = $this->descripcionService->construirDescripcionPrenda(
                $index + 1,
                [
                    'descripcion' => $prendaCotizacion->descripcion,
                    'variantes' => $prendaCotizacion->variantes->toArray()
                ],
                $cantidadesPorTalla
            );

            $pedido->prendas()->create([
                'descripcion' => $descripcion,
                'cantidad_total' => array_sum($cantidadesPorTalla),
                'cantidades_por_talla' => $cantidadesPorTalla,
                'prenda_cotizacion_id' => $prendaCotizacion->id,
            ]);
        }
    }

    /**
     * Procesar prendas nuevas (sin cotización)
     */
    private function procesarPrendasNuevas(PedidoProduccion $pedido, array $prendas): void
    {
        foreach ($prendas as $index => $prenda) {
            $cantidadesPorTalla = $prenda['cantidades'] ?? [];
            
            $descripcion = $this->descripcionService->construirDescripcionPrendaSinCotizacion(
                $prenda,
                $cantidadesPorTalla
            );

            $pedido->prendas()->create([
                'nombre_prenda' => $prenda['nombre_producto'] ?? '',
                'descripcion' => $descripcion,
                'cantidad' => array_sum($cantidadesPorTalla),
            ]);
        }
    }

    /**
     * Procesar prendas reflectivas
     */
    private function procesarPrendasReflectivas(PedidoProduccion $pedido, array $prendas): void
    {
        foreach ($prendas as $index => $prenda) {
            $cantidadesPorTalla = $prenda['cantidades'] ?? [];
            
            $descripcion = $this->descripcionService->construirDescripcionReflectivoSinCotizacion(
                $prenda,
                $cantidadesPorTalla
            );

            $pedido->prendas()->create([
                'nombre_prenda' => $prenda['tipo'] ?? '',
                'descripcion' => $descripcion,
                'cantidad' => array_sum($cantidadesPorTalla),
            ]);
        }
    }

    /**
     * Calcular cantidades por talla
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
     * Crear procesos para reflectivo
     */
    private function crearProcesosReflectivo(PedidoProduccion $pedido, Cotizacion $cotizacion): void
    {
        // Lógica de creación de procesos para reflectivo
        // (Mantener la lógica existente del controlador)
    }
}
