<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrendaProcesoService
 * 
 * Responsabilidad: Guardar procesos de prendas en la BD
 * Maneja la creación de procesos y sus detalles
 */
class PrendaProcesoService
{
    private ProcesoImagenService $procesoImagenService;

    public function __construct(ProcesoImagenService $procesoImagenService = null)
    {
        $this->procesoImagenService = $procesoImagenService ?? app(ProcesoImagenService::class);
    }

    /**
     * Guardar procesos de prenda
     * 
     * Estructura esperada:
     * {
     *   "tipo_proceso_id": 1,  // O "tipo": "reflectivo"
     *   "observaciones": "...",
     *   "ubicaciones": [...],
     *   "tallas": {...},
     *   "imagenes": [...]
     * }
     */
    public function guardarProcesosPrenda(int $prendaId, int $pedidoId, array $procesos): void
    {
        Log::info(' [PrendaProcesoService::guardarProcesosPrenda] INICIO - Guardando procesos', [
            'prenda_id' => $prendaId,
            'pedido_id' => $pedidoId,
            'cantidad_procesos' => count($procesos),
            'procesos_debug' => json_encode($procesos, JSON_PRETTY_PRINT),  // ← VER EXACTAMENTE QUÉ LLEGA
        ]);

        foreach ($procesos as $procesoIndex => $proceso) {
            try {
                // Obtener tipo_proceso_id
                $tipoProcesoId = $proceso['tipo_proceso_id'] ?? $proceso['id'] ?? null;
                
                //  PROHIBIR: NO crear tipos dinámicamente
                // Solo buscar en tabla precargada tipos_procesos
                if (!$tipoProcesoId && !empty($proceso['tipo'])) {
                    $tipoNombre = strtolower(trim($proceso['tipo']));
                    
                    // Buscar EXACTO o por similitud
                    $tipoProcesoObj = DB::table('tipos_procesos')
                        ->where(DB::raw('LOWER(nombre)'), 'like', "%{$tipoNombre}%")
                        ->orWhere(DB::raw('LOWER(descripcion)'), 'like', "%{$tipoNombre}%")
                        ->first();
                    
                    if ($tipoProcesoObj) {
                        $tipoProcesoId = $tipoProcesoObj->id;
                    } else {
                        //  Tipo no precargado → Lanzar excepción (NO silenciar)
                        throw new \DomainException(
                            "Tipo de proceso '{$tipoNombre}' no existe en tabla tipos_procesos. "
                            . "Debe ser precargado. Tipos válidos: reflectivo, serigrafía, bordado, tejido."
                        );
                    }
                }
                
                if (!$tipoProcesoId) {
                    throw new \DomainException(
                        "Proceso en posición {$procesoIndex} no tiene tipo_proceso_id ni campo 'tipo'. "
                        . "Estructura requerida: {tipo_proceso_id: int} o {tipo: 'reflectivo', ...}"
                    );
                }

                // Detectar modo de tallas
                $modoTallas = $proceso['modo_tallas'] ?? 'para_todas';
                $datosExtendidos = !empty($proceso['datos_extendidos']) ? $proceso['datos_extendidos'] : null;
                if (is_string($datosExtendidos)) {
                    $datosExtendidos = json_decode($datosExtendidos, true);
                }

                // Validar que proceso tenga DATOS
                // Para modo por_tallas, los datos están en datosExtendidos, no en ubicaciones globales
                $tieneUbicaciones = !empty($proceso['ubicaciones']) && is_array($proceso['ubicaciones']) && count($proceso['ubicaciones']) > 0;
                $tieneTallas = !empty($proceso['tallas']) && is_array($proceso['tallas']);
                $tieneDatosExtendidos = !empty($datosExtendidos);
                
                if (!$tieneUbicaciones && !$tieneTallas && !$tieneDatosExtendidos) {
                    throw new \DomainException(
                        "Proceso '{$tipoProcesoId}' NO PUEDE ESTAR VACÍO. "
                        . "Debe tener ubicaciones, tallas o datosExtendidos. "
                        . "Abortar guardar proceso vacío para evitar datos inconsistentes."
                    );
                }

                // Crear detalle de proceso
                $procesoDetalleId = DB::table('pedidos_procesos_prenda_detalles')->insertGetId([
                    'prenda_pedido_id' => $prendaId,
                    'tipo_proceso_id' => $tipoProcesoId,
                    'ubicaciones' => !empty($proceso['ubicaciones']) ? json_encode($proceso['ubicaciones']) : null,
                    'observaciones' => $proceso['observaciones'] ?? null,
                    'modo_tallas' => $modoTallas,
                    // LEGACY: Mantener campos JSON marcados como deprecated
                    'tallas_dama' => !empty($proceso['tallas']['dama']) ? json_encode($proceso['tallas']['dama']) : null,
                    'tallas_caballero' => !empty($proceso['tallas']['caballero']) ? json_encode($proceso['tallas']['caballero']) : null,
                    'estado' => 'PENDIENTE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info(' [PrendaProcesoService] Detalle de proceso creado', [
                    'prenda_id' => $prendaId,
                    'proceso_detalle_id' => $procesoDetalleId,
                    'tipo_proceso_id' => $tipoProcesoId,
                    'modo_tallas' => $modoTallas,
                ]);

                // Guardar tallas en la tabla relacional
                $this->guardarTallasProceso($procesoDetalleId, $proceso, $datosExtendidos);

                // Guardar imágenes del proceso (solo modo para_todas, en por_tallas van por talla)
                if ($modoTallas !== 'por_tallas') {
                    $imagenes = $proceso['imagenes'] ?? [];
                    if (!empty($imagenes)) {
                        $this->procesoImagenService->guardarImagenesProcesos(
                            $procesoDetalleId,
                            $pedidoId,
                            $imagenes
                        );
                    }
                }

                // Guardar imágenes por talla (modo por_tallas)
                if ($modoTallas === 'por_tallas' && !empty($proceso['imagenes_por_talla'])) {
                    $this->guardarImagenesPorTalla($procesoDetalleId, $pedidoId, $proceso['imagenes_por_talla']);
                }
            } catch (\Exception $e) {
                Log::error(' [PrendaProcesoService] Error guardando proceso', [
                    'prenda_id' => $prendaId,
                    'proceso_index' => $procesoIndex,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info(' [PrendaProcesoService] Procesos guardados exitosamente', [
            'prenda_id' => $prendaId,
            'cantidad_procesos' => count($procesos),
        ]);
    }

    /**
     * Guardar tallas del proceso en la tabla relacional pedidos_procesos_prenda_tallas
     * 
     * Estructura de proceso['tallas']:
     * {
     *   "dama": { "S": 10, "M": 20 },
     *   "caballero": { "L": 15, "XL": 5 },
     *   "unisex": { "M": 8 },
     *   "sobremedida": { "CABALLERO": 100, "DAMA": 50 }
     * }
     */
    private function guardarTallasProceso(int $procesoDetalleId, array $proceso, ?array $datosExtendidos = null): void
    {
        try {
            $tallas = $proceso['tallas'] ?? [];
            
            if (empty($tallas)) {
                Log::debug(' [guardarTallasProceso] Sin tallas para guardar', [
                    'proceso_detalle_id' => $procesoDetalleId,
                ]);
                return;
            }
            
            // Mapeo de gÃ©nero: dama â†’ DAMA, caballero â†’ CABALLERO, unisex â†’ UNISEX
            $generoMap = [
                'dama' => 'DAMA',
                'caballero' => 'CABALLERO',
                'unisex' => 'UNISEX',
            ];
            
            foreach ($tallas as $generoBD => $tallasCantidades) {
                if (!is_array($tallasCantidades) || empty($tallasCantidades)) {
                    continue;
                }
                
                // CASO ESPECIAL: SOBREMEDIDA
                if (strtolower($generoBD) === 'sobremedida') {
                    // Estructura: {sobremedida: {CABALLERO: 32432, DAMA: 100}}
                    foreach ($tallasCantidades as $generoParaSobremedida => $cantidad) {
                        $cantidad = (int)$cantidad;
                        
                        if ($cantidad > 0) {
                            $generoEnum = strtoupper($generoParaSobremedida);
                            
                            $existe = DB::table('pedidos_procesos_prenda_tallas')
                                ->where('proceso_prenda_detalle_id', $procesoDetalleId)
                                ->where('genero', $generoEnum)
                                ->where('talla', null)
                                ->where('es_sobremedida', true)
                                ->exists();
                            
                            if ($existe) {
                                DB::table('pedidos_procesos_prenda_tallas')
                                    ->where('proceso_prenda_detalle_id', $procesoDetalleId)
                                    ->where('genero', $generoEnum)
                                    ->where('talla', null)
                                    ->where('es_sobremedida', true)
                                    ->update([
                                        'cantidad' => $cantidad,
                                        'updated_at' => now(),
                                    ]);
                            } else {
                                DB::table('pedidos_procesos_prenda_tallas')->insert([
                                    'proceso_prenda_detalle_id' => $procesoDetalleId,
                                    'genero' => $generoEnum,
                                    'talla' => null,
                                    'cantidad' => $cantidad,
                                    'es_sobremedida' => true,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                } else {
                    // CASO NORMAL: Género con tallas
                    $generoEnum = $generoMap[$generoBD] ?? null;
                    if (!$generoEnum) {
                        continue;
                    }
                    
                    foreach ($tallasCantidades as $talla => $cantidad) {
                        $cantidad = (int)$cantidad;
                        
                        if ($cantidad > 0) {
                            // Extraer datos por talla si existen (modo por_tallas)
                            $tallaExtendida = $datosExtendidos[$generoBD][$talla] ?? null;
                            $ubicacionesTalla = null;
                            $observacionesTalla = null;
                            if ($tallaExtendida) {
                                $ubicacionesTalla = !empty($tallaExtendida['ubicaciones']) ? json_encode($tallaExtendida['ubicaciones']) : null;
                                $observacionesTalla = $tallaExtendida['observaciones'] ?? null;
                            }

                            $existe = DB::table('pedidos_procesos_prenda_tallas')
                                ->where('proceso_prenda_detalle_id', $procesoDetalleId)
                                ->where('genero', $generoEnum)
                                ->where('talla', $talla)
                                ->exists();
                            
                            if ($existe) {
                                DB::table('pedidos_procesos_prenda_tallas')
                                    ->where('proceso_prenda_detalle_id', $procesoDetalleId)
                                    ->where('genero', $generoEnum)
                                    ->where('talla', $talla)
                                    ->update([
                                        'cantidad' => $cantidad,
                                        'ubicaciones' => $ubicacionesTalla,
                                        'observaciones' => $observacionesTalla,
                                        'updated_at' => now(),
                                    ]);
                            } else {
                                DB::table('pedidos_procesos_prenda_tallas')->insert([
                                    'proceso_prenda_detalle_id' => $procesoDetalleId,
                                    'genero' => $generoEnum,
                                    'talla' => $talla,
                                    'cantidad' => $cantidad,
                                    'ubicaciones' => $ubicacionesTalla,
                                    'observaciones' => $observacionesTalla,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }
            }
            
            Log::debug(' [guardarTallasProceso] Tallas guardadas en tabla relacional', [
                'proceso_detalle_id' => $procesoDetalleId,
                'cantidad_registros' => array_sum(array_map(fn($arr) => count($arr), $tallas)),
            ]);
        } catch (\Exception $e) {
            Log::error(' [guardarTallasProceso] Error guardando tallas', [
                'proceso_detalle_id' => $procesoDetalleId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Guardar imágenes por talla (modo por_tallas)
     * 
     * Estructura de imagenes_por_talla:
     * Cada key del request es "prendas[X][procesos][tipo][imagenes_por_talla][genero__talla][]"
     * Llega como: ['dama__S' => [UploadedFile, ...], 'caballero__L' => [UploadedFile, ...]]
     */
    private function guardarImagenesPorTalla(int $procesoDetalleId, int $pedidoId, array $imagenesPorTalla): void
    {
        $generoMap = ['dama' => 'DAMA', 'caballero' => 'CABALLERO', 'unisex' => 'UNISEX'];

        foreach ($imagenesPorTalla as $tallaKey => $imagenes) {
            if (empty($imagenes) || !is_array($imagenes)) continue;

            // Parse key: "dama__S" → genero=dama, talla=S
            $sepIdx = strpos($tallaKey, '__');
            if ($sepIdx === false) continue;

            $genero = substr($tallaKey, 0, $sepIdx);
            $talla = substr($tallaKey, $sepIdx + 2);
            $generoEnum = $generoMap[$genero] ?? null;
            if (!$generoEnum) continue;

            // Buscar el registro de talla en la BD
            $tallaRecord = DB::table('pedidos_procesos_prenda_tallas')
                ->where('proceso_prenda_detalle_id', $procesoDetalleId)
                ->where('genero', $generoEnum)
                ->where('talla', $talla)
                ->first();

            if (!$tallaRecord) {
                Log::warning('[guardarImagenesPorTalla] Talla no encontrada', [
                    'proceso_detalle_id' => $procesoDetalleId,
                    'genero' => $generoEnum,
                    'talla' => $talla,
                ]);
                continue;
            }

            // Guardar imágenes vinculadas a esta talla usando el servicio existente + talla_id
            foreach ($imagenes as $index => $imagenData) {
                try {
                    $this->procesoImagenService->guardarImagenProceso(
                        $procesoDetalleId,
                        $pedidoId,
                        $imagenData,
                        $index,
                        $tallaRecord->id
                    );
                } catch (\Exception $e) {
                    Log::error('[guardarImagenesPorTalla] Error guardando imagen', [
                        'talla_key' => $tallaKey,
                        'index' => $index,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}

