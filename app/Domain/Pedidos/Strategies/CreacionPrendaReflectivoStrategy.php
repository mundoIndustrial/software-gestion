<?php

namespace App\Domain\Pedidos\Strategies;

use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\ProcesoPrendaDetalle;
use App\Models\PrendaPedidoTalla;
use App\Domain\Pedidos\Services\ImagenService;
use App\Domain\Pedidos\Services\UtilitariosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Estrategia de Creación de Prenda con Proceso REFLECTIVO
 * 
 * ARQUITECTURA CORRECTA:
 * 1. PRENDA (prendas_pedido) - La prenda base
 * 2. TALLAS (prenda_pedido_tallas) - Relacional por género
 * 3. PROCESO (procesos_prenda) - "Reflectivo" como proceso
 * 4. DETALLES DEL PROCESO (pedidos_procesos_prenda_detalles) - Ubicaciones, observaciones
 * 
 * Responsabilidades:
 * - Crear prenda base con nombre y descripción
 * - Guardar tallas de forma relacional
 * - Crear proceso "Reflectivo" 
 * - Guardar ubicaciones del reflectivo en detalles del proceso
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

            Log::info('🔹 [CreacionPrendaReflectivoStrategy] Procesando prenda con proceso reflectivo', [
                'nombre' => $prendaData['nombre_producto'] ?? $prendaData['nombre_prenda'] ?? 'Sin nombre',
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $numeroPedido,
            ]);

            // ===== PASO 1: PROCESAR CANTIDADES GÉNERO/TALLA =====
            $cantidadTallaGenero = $this->procesarCantidadesReflectivo($prendaData);
            $cantidadTotal = $this->calcularCantidadTotalReflectivo($cantidadTallaGenero);

            Log::debug('📊 [CreacionPrendaReflectivoStrategy] Cantidades procesadas', [
                'cantidad_total' => $cantidadTotal,
                'estructura_generos' => count($cantidadTallaGenero),
            ]);

            // ===== PASO 2: CREAR PRENDA EN prendas_pedido =====
            $prendaPedido = PrendaPedido::create([
                'pedido_produccion_id' => $pedidoProduccionId,
                'nombre_prenda' => $prendaData['nombre_producto'] ?? $prendaData['nombre_prenda'] ?? 'Sin nombre',
                'descripcion' => $prendaData['descripcion'] ?? '',
            ]);

            Log::info('✅ [CreacionPrendaReflectivoStrategy] Prenda creada', [
                'prenda_pedido_id' => $prendaPedido->id,
            ]);

            // ===== PASO 3: GUARDAR TALLAS EN prenda_pedido_tallas (RELACIONAL) =====
            $this->guardarTallasRelacional($prendaPedido->id, $cantidadTallaGenero);

            // ===== PASO 4: CREAR PROCESO "Reflectivo" =====
            $procesoReflectivo = ProcesoPrenda::create([
                'numero_pedido' => $numeroPedido,
                'prenda_pedido_id' => $prendaPedido->id,
                'proceso' => 'Reflectivo',
                'estado_proceso' => 'Pendiente',
                'fecha_inicio' => now(),
            ]);

            Log::info(' [CreacionPrendaReflectivoStrategy] Proceso Reflectivo creado', [
                'proceso_id' => $procesoReflectivo->id,
            ]);

            // ===== PASO 5: GUARDAR DETALLES DEL PROCESO (Ubicaciones, observaciones) =====
            $detallesProceso = ProcesoPrendaDetalle::create([
                'prenda_pedido_id' => $prendaPedido->id,
                'tipo_proceso_id' => $this->obtenerTipoProcesoReflectivo(),
                'ubicaciones' => $prendaData['ubicaciones'] ?? [],
                'observaciones' => $prendaData['descripcion'] ?? '',
                'estado' => 'pendiente',
            ]);

            Log::info('[CreacionPrendaReflectivoStrategy] Detalles del proceso guardados', [
                'detalle_id' => $detallesProceso->id,
                'ubicaciones' => count($prendaData['ubicaciones'] ?? []),
            ]);

            // ===== PASO 6: CREAR PROCESO INICIAL "Creación Orden" =====
            ProcesoPrenda::create([
                'numero_pedido' => $numeroPedido,
                'prenda_pedido_id' => $prendaPedido->id,
                'proceso' => 'Creación Orden',
                'estado_proceso' => 'Completado',
                'fecha_inicio' => now(),
                'fecha_fin' => now(),
            ]);

            DB::commit();

            Log::info(' [CreacionPrendaReflectivoStrategy] Prenda con proceso reflectivo completada', [
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
        // Soportar tanto nombre_producto como nombre_prenda
        $nombre = $prendaData['nombre_producto'] ?? $prendaData['nombre_prenda'] ?? null;
        if (empty($nombre)) {
            throw new \InvalidArgumentException('nombre_prenda es requerido');
        }

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
     */
    private function procesarCantidadesReflectivo(array $prendaData): array
    {
        Log::debug('🔍 [procesarCantidadesReflectivo] Buscando estructura de cantidad');

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

    /**
     * Guardar tallas de forma relacional en prenda_pedido_tallas
     */
    private function guardarTallasRelacional(int $prendaPedidoId, array $cantidadTallaGenero): void
    {
        Log::debug(' [guardarTallasRelacional] Guardando tallas', [
            'prenda_pedido_id' => $prendaPedidoId,
            'generos' => array_keys($cantidadTallaGenero),
        ]);

        foreach ($cantidadTallaGenero as $genero => $tallas) {
            if (!is_array($tallas)) {
                continue;
            }

            foreach ($tallas as $talla => $cantidad) {
                if ($cantidad > 0) {
                    PrendaPedidoTalla::create([
                        'prenda_pedido_id' => $prendaPedidoId,
                        'genero' => strtoupper($genero),
                        'talla' => strtoupper($talla),
                        'cantidad' => (int)$cantidad,
                    ]);
                }
            }
        }
    }

    /**
     * Obtener el tipo_proceso_id para "Reflectivo"
     * TODO: Esto debería venir de una tabla tipo_procesos
     */
    private function obtenerTipoProcesoReflectivo(): ?int
    {
        // Por ahora retornamos null, se debe configurar correctamente
        // cuando tengamos la tabla tipo_procesos con el ID correcto
        return null;
    }
}

