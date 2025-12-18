<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\VariantePrenda;
use App\Exceptions\PedidoException;
use App\Enums\EstadoPedido;
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

                    // NO cambiar el estado de la cotización para permitir crear múltiples pedidos
                    // La cotización mantiene su estado actual (enviada, aceptada, etc.)

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
                'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
                'fecha_de_creacion_de_orden' => now(),
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
            
            // Cargar prendas_cotizaciones para obtener variantes
            $prendasCotizaciones = $cotizacion->prendasCotizaciones()
                ->with('variantes')
                ->get();
            
            foreach ($cotizacion->productos as $index => $producto) {
                // Obtener la prenda_cotizacion correspondiente para las variantes
                $prendaCotizacion = $prendasCotizaciones->get($index);
                $variante = $prendaCotizacion?->variantes?->first();
                
                // Preparar datos para construir descripción
                $datosProducto = array_merge($producto, [
                    'tela' => $variante?->tela?->nombre,
                    'tela_referencia' => $variante?->tela?->referencia,
                    'color' => $variante?->color?->nombre,
                    'manga' => $variante?->tipoManga?->nombre,
                    'broche' => $variante?->tipoBroche?->nombre,
                    'tiene_bolsillos' => $variante?->tiene_bolsillos ?? false,
                    'tiene_reflectivo' => $variante?->tiene_reflectivo ?? false,
                    // Parsear observaciones del formato "Manga: xxx | Bolsillos: xxx"
                    'manga_obs' => $this->extraerObservacion($variante?->descripcion_adicional, 'Manga'),
                    'bolsillos_obs' => $this->extraerObservacion($variante?->descripcion_adicional, 'Bolsillos'),
                    'broche_obs' => $this->extraerObservacion($variante?->descripcion_adicional, 'Broche'),
                    'reflectivo_obs' => $this->extraerObservacion($variante?->descripcion_adicional, 'Reflectivo'),
                ]);
                
                // Obtener cantidades por talla desde la variante
                $cantidadesPorTalla = $this->extraerCantidadesPorTalla($variante);
                
                // Construir descripción formateada
                $descripcionPrenda = $this->construirDescripcionPrenda(
                    $index + 1,
                    $datosProducto,
                    $cantidadesPorTalla
                );
                
                // Calcular cantidad total
                $cantidadTotal = array_sum($cantidadesPorTalla);
                
                $prenda = $this->crearPrendaPedidoConDescripcion(
                    $pedido,
                    $datosProducto,
                    $descripcionPrenda,
                    $cantidadTotal,
                    $cantidadesPorTalla
                );
                
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
     * Crear una prenda del pedido con descripción ya formateada
     * 
     * @param PedidoProduccion $pedido
     * @param array $producto
     * @param string $descripcion
     * @param int $cantidadTotal
     * @param array $cantidadesPorTalla
     * @return PrendaPedido
     * @throws PedidoException
     */
    private function crearPrendaPedidoConDescripcion(
        PedidoProduccion $pedido,
        array $producto,
        string $descripcion,
        int $cantidadTotal,
        array $cantidadesPorTalla
    ): PrendaPedido {
        try {
            // Guardar los datos de la prenda
            \Log::info('DEBUG: Guardando prenda', [
                'numero_pedido' => $pedido->numero_pedido,
                'nombre_prenda' => $producto['nombre_producto'] ?? 'Sin nombre',
                'descripcion' => $descripcion,
                'cantidad_talla' => json_encode($cantidadesPorTalla)
            ]);
            
            $prenda = PrendaPedido::create([
                'numero_pedido' => $pedido->numero_pedido,
                'nombre_prenda' => $producto['nombre_producto'] ?? 'Sin nombre',
                'cantidad' => $cantidadTotal,
                'descripcion' => $descripcion,
                'cantidad_talla' => json_encode($cantidadesPorTalla)
            ]);

            \Log::info('Prenda del pedido creada', [
                'prenda_id' => $prenda->id,
                'pedido_id' => $pedido->id,
                'nombre' => $prenda->nombre_prenda,
                'descripcion_guardada' => $prenda->descripcion
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
                'fecha_inicio' => now(),
                'fecha_fin' => now(),
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

    /**
     * Construir descripción formateada de prenda
     * 
     * @param int $numeroPrenda
     * @param array $producto
     * @param array $cantidadesPorTalla
     * @return string
     */
    private function construirDescripcionPrenda($numeroPrenda, $producto, $cantidadesPorTalla)
    {
        $lineas = [];
        
        // 1. Prenda número y nombre
        $nombrePrenda = strtoupper($producto['nombre_producto'] ?? 'PRENDA');
        $lineas[] = "Prenda $numeroPrenda: $nombrePrenda";
        
        // 2. Descripción
        if (!empty($producto['descripcion'])) {
            $lineas[] = "Descripción: " . strtoupper($producto['descripcion']);
        }
        
        // 3. Tela con referencia
        if (!empty($producto['tela'])) {
            $tela = strtoupper($producto['tela']);
            if (!empty($producto['tela_referencia'])) {
                $tela .= ' REF:' . strtoupper($producto['tela_referencia']);
            }
            $lineas[] = "Tela: " . $tela;
        }
        
        // 4. Color
        if (!empty($producto['color'])) {
            $lineas[] = "Color: " . strtoupper($producto['color']);
        }
        
        // 5. Género
        if (!empty($producto['genero'])) {
            $lineas[] = "Genero: " . strtoupper($producto['genero']);
        }
        
        // 6. Manga + observación
        if (!empty($producto['manga'])) {
            $manga = "Manga: " . strtoupper($producto['manga']);
            if (!empty($producto['manga_obs'])) {
                $manga .= ' - ' . strtoupper($producto['manga_obs']);
            }
            $lineas[] = $manga;
        }
        
        // 7. Bolsillos + observación
        if (!empty($producto['tiene_bolsillos']) && $producto['tiene_bolsillos']) {
            $bolsillos = "Bolsillos: SI";
            if (!empty($producto['bolsillos_obs'])) {
                $bolsillos .= ' - ' . strtoupper($producto['bolsillos_obs']);
            }
            $lineas[] = $bolsillos;
        }
        
        // 8. Broche + observación
        if (!empty($producto['broche'])) {
            $broche = "Broche: " . strtoupper($producto['broche']);
            if (!empty($producto['broche_obs'])) {
                $broche .= ' - ' . strtoupper($producto['broche_obs']);
            }
            $lineas[] = $broche;
        }
        
        // 9. Reflectivo + observación
        if (!empty($producto['tiene_reflectivo']) && $producto['tiene_reflectivo']) {
            $reflectivo = "Reflectivo: SI";
            if (!empty($producto['reflectivo_obs'])) {
                $reflectivo .= ' - ' . strtoupper($producto['reflectivo_obs']);
            }
            $lineas[] = $reflectivo;
        }
        
        // 10. Talla con cantidades (AL FINAL)
        if (!empty($cantidadesPorTalla) && is_array($cantidadesPorTalla)) {
            $tallas = [];
            foreach ($cantidadesPorTalla as $talla => $cantidad) {
                if ($cantidad > 0) {
                    $tallas[] = "{$talla}:{$cantidad}";
                }
            }
            if (!empty($tallas)) {
                $lineas[] = "Tallas: " . implode(', ', $tallas);
            }
        }
        
        // Retornar con saltos de línea entre cada elemento
        return implode("\n", $lineas);
    }

    /**
     * Extraer observación específica del campo de observaciones formateadas
     * Formato esperado: "Manga: obs1 | Bolsillos: obs2 | Broche: obs3 | Reflectivo: obs4"
     * 
     * @param string|null $observaciones
     * @param string $tipo
     * @return string|null
     */
    private function extraerObservacion($observaciones, $tipo): ?string
    {
        if (empty($observaciones)) {
            return null;
        }
        
        // Buscar el patrón "Tipo: contenido"
        if (preg_match("/{$tipo}:\s*(.+?)(?:\||$)/i", $observaciones, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Extraer cantidades por talla desde prendas_cotizaciones
     * 
     * @param mixed $prendaCotizacion
     * @return array
     */
    /**
     * Extraer cantidades por talla desde una variante
     * Puede retornar un array asociativo {talla => cantidad} o un array simple de tallas
     * 
     * @param mixed $variante La variante prenda
     * @return array {talla => cantidad}
     */
    private function extraerCantidadesPorTalla($variante): array
    {
        if (!$variante) {
            return [];
        }
        
        // Obtener cantidad_talla (puede ser array o JSON string)
        $cantidadTalla = $variante->cantidad_talla ?? $variante->tallas ?? null;
        
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
        }
        
        if (!is_array($cantidadTalla)) {
            return [];
        }
        
        // Si el array está indexado numéricamente, asumir que son solo nombres de tallas sin cantidades
        if (!empty($cantidadTalla) && isset($cantidadTalla[0]) && is_string($cantidadTalla[0])) {
            // Array de tallas sin cantidades: ["S", "M", "L"] 
            // Retornar vacío para que se maneje en otro lugar
            return [];
        }
        
        // Si es un array asociativo, asumir que está en formato {talla => cantidad}
        // o {talla => {cantidad: X}} dependiendo de la estructura
        $resultado = [];
        foreach ($cantidadTalla as $key => $value) {
            if (is_array($value) && isset($value['cantidad'])) {
                // Formato: {talla => {cantidad: X}}
                $resultado[$key] = (int) $value['cantidad'];
            } elseif (is_int($value) || is_numeric($value)) {
                // Formato: {talla => cantidad}
                $resultado[$key] = (int) $value;
            }
        }
        
        return $resultado;
    }
}
