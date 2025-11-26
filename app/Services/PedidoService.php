<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\VariantePrenda;
use App\Exceptions\PedidoException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * PedidoService
 * 
 * Gestiona la creación de órdenes de producción desde cotizaciones
 * Encapsula toda la lógica de transformación cotización → pedido
 */
class PedidoService
{
    /**
     * Aceptar una cotización y crear el pedido de producción asociado
     * 
     * @param Cotizacion $cotizacion
     * @return PedidoProduccion
     * @throws PedidoException
     */
    public function aceptarCotizacion(Cotizacion $cotizacion): PedidoProduccion
    {
        try {
            return DB::transaction(function () use ($cotizacion) {
                try {
                    // Crear pedido de producción
                    $pedido = $this->crearPedidoDesdeQuotation($cotizacion);

                    // Crear prendas del pedido
                    if ($cotizacion->productos) {
                        $this->crearPrendasPedido($cotizacion, $pedido);
                    }

                    // Actualizar estado de cotización
                    $cotizacion->update([
                        'estado' => 'aceptada',
                        'es_borrador' => false
                    ]);

                    \Log::info('Cotización aceptada exitosamente', [
                        'cotizacion_id' => $cotizacion->id,
                        'pedido_id' => $pedido->id
                    ]);

                    return $pedido;
                } catch (PedidoException $e) {
                    \Log::error('PedidoException en transacción', $e->getContext());
                    throw $e;
                } catch (\Exception $e) {
                    \Log::error('Error en transacción de aceptar cotización', [
                        'cotizacion_id' => $cotizacion->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw new PedidoException(
                        'Error en transacción: ' . $e->getMessage(),
                        PedidoException::TRANSACTION_FAILED,
                        ['cotizacion_id' => $cotizacion->id]
                    );
                }
            });
        } catch (PedidoException $e) {
            \Log::error('PedidoException al aceptar cotización', $e->getContext());
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error al aceptar cotización', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage()
            ]);
            throw new PedidoException(
                'Error al aceptar cotización: ' . $e->getMessage(),
                PedidoException::INVALID_DATA,
                ['cotizacion_id' => $cotizacion->id]
            );
        }
    }

    /**
     * Crear pedido de producción desde cotización
     * 
     * @param Cotizacion $cotizacion
     * @return PedidoProduccion
     * @throws PedidoException
     */
    private function crearPedidoDesdeQuotation(Cotizacion $cotizacion): PedidoProduccion
    {
        try {
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_pedido' => $this->generarNumeroPedido(),
                'cliente' => $cotizacion->cliente,
                'asesor_id' => Auth::id(),
                'forma_de_pago' => $cotizacion->especificaciones['forma_pago'] ?? null,
                'estado' => 'No iniciado',
                'fecha_de_creacion_de_orden' => now()->toDateString(),
            ]);

            \Log::info('Pedido de producción creado', [
                'pedido_id' => $pedido->id,
                'cotizacion_id' => $cotizacion->id,
                'numero_pedido' => $pedido->numero_pedido
            ]);

            return $pedido;
        } catch (\Exception $e) {
            \Log::error('Error al crear pedido de producción', [
                'cotizacion_id' => $cotizacion->id,
                'error' => $e->getMessage()
            ]);
            throw new PedidoException(
                'Error al crear pedido: ' . $e->getMessage(),
                PedidoException::INVALID_DATA,
                ['cotizacion_id' => $cotizacion->id]
            );
        }
    }

    /**
     * Crear prendas del pedido desde las prendas de la cotización
     * 
     * @param Cotizacion $cotizacion
     * @param PedidoProduccion $pedido
     * @throws PedidoException
     */
    private function crearPrendasPedido(Cotizacion $cotizacion, PedidoProduccion $pedido): void
    {
        try {
            $productos = $cotizacion->productos;
            
            // Null-safe check
            if (!$productos || count($productos) === 0) {
                \Log::info('No hay productos en la cotización', [
                    'cotizacion_id' => $cotizacion->id
                ]);
                return;
            }
            
            foreach ($cotizacion->productos as $index => $producto) {
                $prenda = $this->crearPrendaPedido($pedido, $producto);
                
                // Crear proceso inicial
                $this->crearProcesoPrendaInicial($prenda);
                
                // Heredar variantes de la cotización
                $this->heredarVariantesPrendaCotizacion($cotizacion, $prenda, $index);
            }

            \Log::info('Prendas del pedido creadas exitosamente', [
                'pedido_id' => $pedido->id,
                'cantidad_prendas' => count($cotizacion->productos)
            ]);
        } catch (PedidoException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error al crear prendas del pedido', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage()
            ]);
            throw new PedidoException(
                'Error al crear prendas del pedido: ' . $e->getMessage(),
                PedidoException::INVALID_DATA,
                ['pedido_id' => $pedido->id]
            );
        }
    }

    /**
     * Crear una prenda del pedido
     * 
     * @param PedidoProduccion $pedido
     * @param array $producto
     * @return PrendaPedido
     * @throws PedidoException
     */
    private function crearPrendaPedido(PedidoProduccion $pedido, array $producto): PrendaPedido
    {
        try {
            $prenda = PrendaPedido::create([
                'pedido_produccion_id' => $pedido->id,
                'nombre_prenda' => $producto['nombre_producto'] ?? 'Sin nombre',
                'cantidad' => $producto['cantidad'] ?? 1,
                'descripcion' => $producto['descripcion'] ?? null,
            ]);

            \Log::info('Prenda del pedido creada', [
                'prenda_id' => $prenda->id,
                'pedido_id' => $pedido->id,
                'nombre' => $prenda->nombre_prenda
            ]);

            return $prenda;
        } catch (\Exception $e) {
            \Log::error('Error al crear prenda del pedido', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage()
            ]);
            throw new PedidoException(
                'Error al crear prenda: ' . $e->getMessage(),
                PedidoException::INVALID_DATA,
                ['pedido_id' => $pedido->id]
            );
        }
    }

    /**
     * Crear proceso inicial de la prenda
     * 
     * @param PrendaPedido $prenda
     * @throws PedidoException
     */
    private function crearProcesoPrendaInicial(PrendaPedido $prenda): void
    {
        try {
            ProcesoPrenda::create([
                'prenda_pedido_id' => $prenda->id,
                'proceso' => 'Creación Orden',
                'estado_proceso' => 'Completado',
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin' => now()->toDateString(),
            ]);

            \Log::info('Proceso inicial de prenda creado', [
                'prenda_id' => $prenda->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al crear proceso de prenda', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage()
            ]);
            throw new PedidoException(
                'Error al crear proceso: ' . $e->getMessage(),
                PedidoException::INVALID_DATA,
                ['prenda_id' => $prenda->id]
            );
        }
    }

    /**
     * Heredar variantes de la prenda de cotización a la de pedido
     * 
     * @param Cotizacion $cotizacion
     * @param PrendaPedido $prenda
     * @param int $index
     */
    private function heredarVariantesPrendaCotizacion(
        Cotizacion $cotizacion,
        PrendaPedido $prenda,
        int $index
    ): void {
        try {
            // Null-safe prendasCotizaciones access
            $prendasCotizacion = $cotizacion->prendasCotizaciones;
            
            if (!$prendasCotizacion) {
                \Log::warning('prendasCotizaciones es null', [
                    'cotizacion_id' => $cotizacion->id
                ]);
                return;
            }
            
            $prendaCotizacion = $prendasCotizacion->get($index);

            if (!$prendaCotizacion) {
                \Log::warning('Prenda de cotización no encontrada en índice', [
                    'cotizacion_id' => $cotizacion->id,
                    'index' => $index
                ]);
                return;
            }

            // Null-safe variantes access
            $variantes = $prendaCotizacion->variantes;
            if (!$variantes) {
                \Log::info('Prenda sin variantes', [
                    'prenda_cotizacion_id' => $prendaCotizacion->id
                ]);
                return;
            }

            foreach ($variantes as $variante) {
                VariantePrenda::create([
                    'prenda_pedido_id' => $prenda->id,
                    'tipo_prenda_id' => $variante->tipo_prenda_id,
                    'color_id' => $variante->color_id,
                    'tela_id' => $variante->tela_id,
                    'tipo_manga_id' => $variante->tipo_manga_id,
                    'tipo_broche_id' => $variante->tipo_broche_id,
                    'tiene_bolsillos' => $variante->tiene_bolsillos,
                    'tiene_reflectivo' => $variante->tiene_reflectivo,
                    'descripcion_adicional' => $variante->descripcion_adicional,
                    'cantidad_talla' => $variante->cantidad_talla
                ]);
            }

            \Log::info('Variantes heredadas', [
                'prenda_pedido_id' => $prenda->id,
                'cantidad_variantes' => count($variantes)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al heredar variantes', [
                'error' => $e->getMessage(),
                'cotizacion_id' => $cotizacion->id,
                'prenda_pedido_id' => $prenda->id
            ]);
        }
    }

    /**
     * Generar número único para pedido
     * 
     * @return int
     */
    private function generarNumeroPedido(): int
    {
        return (PedidoProduccion::max('numero_pedido') ?? 0) + 1;
    }
}
