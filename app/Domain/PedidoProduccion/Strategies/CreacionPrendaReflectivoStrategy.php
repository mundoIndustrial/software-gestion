<?php

namespace App\Domain\PedidoProduccion\Strategies;

use App\Models\PrendaPedido;
use App\Models\PrendaReflectivo;
use App\Models\ProcesoPrenda;
use App\Domain\PedidoProduccion\Services\ImagenService;
use App\Domain\PedidoProduccion\Services\UtilitariosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Estrategia de Creación de Prenda REFLECTIVO SIN COTIZACIÓN
 * 
 * Encapsula la lógica del método controller::crearReflectivoSinCotizacion() (~300 líneas)
 * 
 * Responsabilidades:
 * - Procesar cantidades con estructura género => talla => cantidad
 * - Crear registros en prendas_reflectivo (tabla especializada)
 * - Guardar prenda en prendas_pedido
 * - Procesar fotos de reflectivo
 * - Crear proceso inicial
 * 
 * Nota: Reflectivo tiene tabla especializada prendas_reflectivo con más campos
 */
class CreacionPrendaReflectivoStrategy implements CreacionPrendaStrategy
{
    private ImagenService $imagenService;
    private UtilitariosService $utilitariosService;

    /**
     * {@inheritDoc}
     */
    public function procesar(
        array $prendaData,
        int $pedidoProduccionId,
        array $servicios
    ): PrendaPedido {
        $this->imagenService = $servicios['imagenService'] ?? throw new \RuntimeException('ImagenService requerido');
        $this->utilitariosService = $servicios['utilitariosService'] ?? throw new \RuntimeException('UtilitariosService requerido');

        // Validar datos
        $this->validar($prendaData);

        try {
            DB::beginTransaction();

            // Obtener número de pedido desde el ID para auditoría
            $pedido = \App\Models\PedidoProduccion::find($pedidoProduccionId);
            if (!$pedido) {
                throw new \Exception("Pedido no encontrado con ID: {$pedidoProduccionId}");
            }
            $numeroPedido = $pedido->numero_pedido;

            Log::info(' [CreacionPrendaReflectivoStrategy] Procesando prenda reflectivo', [
                'nombre' => $prendaData['nombre_producto'] ?? 'Sin nombre',
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $numeroPedido,
            ]);

            // ===== PASO 1: PROCESAR CANTIDADES GÉNERO/TALLA (ANTES LÍNEA 1530-1580) =====
            $cantidadTallaGenero = $this->procesarCantidadesReflectivo($prendaData);
            $cantidadTotal = $this->calcularCantidadTotalReflectivo($cantidadTallaGenero);

            Log::debug(' [CreacionPrendaReflectivoStrategy] Cantidades procesadas', [
                'cantidad_total' => $cantidadTotal,
                'estructura_generos' => count($cantidadTallaGenero),
            ]);

            // ===== PASO 2: CREAR PRENDA EN prendas_pedido (ANTES LÍNEA 1600-1615) =====
            $prendaPedido = PrendaPedido::create([
                'pedido_produccion_id' => $pedidoProduccionId,
                'nombre_prenda' => $prendaData['nombre_producto'] ?? 'Sin nombre',
                'cantidad' => $cantidadTotal,
                // NO guardar descripción ni cantidad_talla aquí (van en prendas_reflectivo)
            ]);

            Log::info(' [CreacionPrendaReflectivoStrategy] Prenda creada en prendas_pedido', [
                'prenda_pedido_id' => $prendaPedido->id,
            ]);

            // ===== PASO 3: CREAR REGISTRO EN prendas_reflectivo (ANTES LÍNEA 1620-1635) =====
            $prendaReflectivo = PrendaReflectivo::create([
                'prenda_pedido_id' => $prendaPedido->id,
                'nombre_producto' => $prendaData['nombre_producto'] ?? 'Sin nombre',
                'descripcion' => $prendaData['descripcion'] ?? '',
                'generos' => json_encode($this->utilitariosService->procesarGeneros($prendaData['genero'] ?? '')),
                'cantidad_talla' => json_encode($cantidadTallaGenero), // Estructura género => talla => cantidad
                'ubicaciones' => json_encode($prendaData['ubicaciones'] ?? []),
                'cantidad_total' => $cantidadTotal,
            ]);

            Log::info(' [CreacionPrendaReflectivoStrategy] Información guardada en prendas_reflectivo', [
                'prenda_reflectivo_id' => $prendaReflectivo->id,
            ]);

            // ===== PASO 4: CREAR PROCESO INICIAL (ANTES LÍNEA 1640-1650) =====
            ProcesoPrenda::create([
                'numero_pedido' => $numeroPedido,
                'prenda_pedido_id' => $prendaPedido->id,
                'proceso' => 'Creación Orden',
                'estado_proceso' => 'Completado',
                'fecha_inicio' => now(),
                'fecha_fin' => now(),
            ]);

            DB::commit();

            Log::info(' [CreacionPrendaReflectivoStrategy] Prenda reflectivo completamente procesada', [
                'prenda_id' => $prendaPedido->id,
                'cantidad_total' => $cantidadTotal,
            ]);

            return $prendaPedido;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error(' [CreacionPrendaReflectivoStrategy] Error al procesar prenda reflectivo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validar(array $prendaData): bool
    {
        if (empty($prendaData['nombre_producto'])) {
            throw new \InvalidArgumentException('nombre_producto es requerido');
        }

        // Para reflectivo es opcional tener cantidades estructuradas
        // Pero debe tener al menos ubicaciones o descripción

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getNombre(): string
    {
        return 'CreacionPrendaReflectivo';
    }

    /**
     * ===== MÉTODOS PRIVADOS =====
     */

    /**
     * Procesar cantidades para reflectivo
     * Formato esperado: cantidad_talla = {genero: {talla: cantidad}}
     * 
     * ANTES: Línea 1530-1580 en controller (50 líneas)
     */
    private function procesarCantidadesReflectivo(array $prendaData): array
    {
        Log::debug(' [procesarCantidadesReflectivo] Buscando estructura de cantidad');

        $cantidad = $prendaData['cantidad_talla'] ?? [];

        // Si viene como string JSON, decodificar
        if (is_string($cantidad)) {
            $cantidad = json_decode($cantidad, true) ?? [];
        }

        // Validar que sea array
        if (!is_array($cantidad)) {
            return [];
        }

        // Si está vacío, intentar otras fuentes
        if (empty($cantidad)) {
            // Intentar cantidades_por_genero
            $cantidad = $prendaData['cantidades_por_genero'] ?? [];
            if (is_string($cantidad)) {
                $cantidad = json_decode($cantidad, true) ?? [];
            }
        }

        return is_array($cantidad) ? $cantidad : [];
    }

    /**
     * Calcular cantidad total para reflectivo
     * Maneja estructura: {genero: {talla: cantidad}}
     */
    private function calcularCantidadTotalReflectivo(array $cantidadTallaGenero): int
    {
        $total = 0;

        foreach ($cantidadTallaGenero as $genero => $tallas) {
            if (is_array($tallas)) {
                foreach ($tallas as $talla => $cantidad) {
                    $total += (int)$cantidad;
                }
            }
        }

        return $total;
    }
}
