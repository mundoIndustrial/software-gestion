<?php

namespace App\Application\Services;

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
        ]);

        foreach ($procesos as $procesoIndex => $proceso) {
            try {
                // Obtener tipo_proceso_id
                $tipoProcesoId = $proceso['tipo_proceso_id'] ?? $proceso['id'] ?? null;
                
                // Si viene como nombre (string), buscar o crear
                if ($tipoProcesoId === null && !empty($proceso['tipo'])) {
                    $tipoNombre = $proceso['tipo'];
                    $tipoProcesoObj = DB::table('tipos_procesos')
                        ->where('nombre', 'like', "%{$tipoNombre}%")
                        ->first();
                    
                    if ($tipoProcesoObj) {
                        $tipoProcesoId = $tipoProcesoObj->id;
                    } else {
                        $tipoProcesoId = DB::table('tipos_procesos')->insertGetId([
                            'nombre' => $tipoNombre,
                            'descripcion' => "Proceso: {$tipoNombre}",
                            'activo' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                
                if ($tipoProcesoId === null) {
                    Log::warning(' [PrendaProcesoService] Tipo de proceso no especificado', [
                        'prenda_id' => $prendaId,
                        'proceso_index' => $procesoIndex,
                        'proceso_data' => $proceso,
                    ]);
                    continue;
                }

                // Crear detalle de proceso
                $procesoDetalleId = DB::table('pedidos_procesos_prenda_detalles')->insertGetId([
                    'prenda_pedido_id' => $prendaId,
                    'tipo_proceso_id' => $tipoProcesoId,
                    'ubicaciones' => !empty($proceso['ubicaciones']) ? $this->normalizarUbicaciones($proceso['ubicaciones']) : json_encode([]),
                    'observaciones' => $proceso['observaciones'] ?? null,
                    'tallas_dama' => null,  // LEGACY - usar tabla relacional
                    'tallas_caballero' => null,  // LEGACY - usar tabla relacional
                    'estado' => 'PENDIENTE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info(' [PrendaProcesoService] Detalle de proceso creado', [
                    'prenda_id' => $prendaId,
                    'proceso_detalle_id' => $procesoDetalleId,
                    'tipo_proceso_id' => $tipoProcesoId,
                ]);

                // Guardar TALLAS en tabla relacional (NUEVO MODELO)
                if (!empty($proceso['tallas']) && is_array($proceso['tallas'])) {
                    $generoMap = ['dama' => 'DAMA', 'caballero' => 'CABALLERO', 'unisex' => 'UNISEX'];
                    
                    // Obtener datosExtendidos si existen (para "por tallas")
                    $datosExtendidos = $proceso['datosExtendidos'] ?? [];

                    foreach ($proceso['tallas'] as $generoBD => $tallasArray) {
                        if (!is_array($tallasArray) || empty($tallasArray)) {
                            continue;
                        }

                        // CASO ESPECIAL: SOBREMEDIDA
                        if (strtolower($generoBD) === 'sobremedida') {
                            // Estructura: {sobremedida: {CABALLERO: 100, DAMA: 50}}
                            foreach ($tallasArray as $generoParaSobremedida => $cantidad) {
                                $cantidad = (int)$cantidad;
                                if ($cantidad > 0) {
                                    $generoEnum = strtoupper($generoParaSobremedida);
                                    
                                    // Obtener datos extendidos para sobremedida
                                    $detallesExtendidos = $datosExtendidos['sobremedida'][$generoParaSobremedida] ?? [];
                                    
                                    DB::table('pedidos_procesos_prenda_tallas')->insert([
                                        'proceso_prenda_detalle_id' => $procesoDetalleId,
                                        'genero' => $generoEnum,
                                        'talla' => null,
                                        'cantidad' => $cantidad,
                                        'es_sobremedida' => true,
                                        'ubicaciones' => !empty($detallesExtendidos['ubicaciones']) ? json_encode($detallesExtendidos['ubicaciones']) : null,
                                        'observaciones' => $detallesExtendidos['observaciones'] ?? null,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        } else {
                            // CASO NORMAL: Género con tallas específicas
                            $generoEnum = $generoMap[strtolower($generoBD)] ?? null;
                            if (!$generoEnum) {
                                continue;
                            }

                            // 🔧 FIX: Agrupar tallas por GENERO + TALLA (ignorando colores) para evitar duplicados
                            $tallasAgrupadas = [];
                            $coloresPorTalla = [];

                            foreach ($tallasArray as $tallaKey => $cantidad) {
                                $cantidad = (int)$cantidad;
                                if ($cantidad > 0) {
                                    $partes = explode('__', (string)$tallaKey);
                                    $tallaReal = $partes[0];
                                    $colorNombre = isset($partes[1]) ? $partes[1] : null;

                                    $claveAgrupar = strtoupper($tallaReal);

                                    if (!isset($tallasAgrupadas[$claveAgrupar])) {
                                        $tallasAgrupadas[$claveAgrupar] = [
                                            'talla' => $tallaReal,
                                            'cantidad_total' => 0,
                                            'tallaKey' => $tallaKey, // Para datosExtendidos
                                        ];
                                        $coloresPorTalla[$claveAgrupar] = [];
                                    }

                                    $tallasAgrupadas[$claveAgrupar]['cantidad_total'] += $cantidad;

                                    if (!empty($colorNombre)) {
                                        $coloresPorTalla[$claveAgrupar][] = [
                                            'color_nombre' => $colorNombre,
                                            'cantidad' => $cantidad,
                                            'tallaKey' => $tallaKey,
                                        ];
                                    }
                                }
                            }

                            // Crear tallas ÚNICAS (UNA sola fila por GENERO+TALLA)
                            foreach ($tallasAgrupadas as $claveAgrupar => $datosTalla) {
                                $tallaReal = $datosTalla['talla'];
                                $cantidadTotal = $datosTalla['cantidad_total'];

                                // Obtener datos extendidos (usar el primer tallaKey disponible)
                                $tallaKeyParaBuscar = !empty($coloresPorTalla[$claveAgrupar])
                                    ? $coloresPorTalla[$claveAgrupar][0]['tallaKey']
                                    : $datosTalla['tallaKey'];
                                $detallesExtendidos = $datosExtendidos[strtolower($generoBD)][$tallaKeyParaBuscar] ?? [];

                                $tallaProcesoPrendaId = DB::table('pedidos_procesos_prenda_tallas')->insertGetId([
                                    'proceso_prenda_detalle_id' => $procesoDetalleId,
                                    'genero' => $generoEnum,
                                    'talla' => $tallaReal,
                                    'cantidad' => $cantidadTotal,
                                    'ubicaciones' => !empty($detallesExtendidos['ubicaciones']) ? json_encode($detallesExtendidos['ubicaciones']) : null,
                                    'observaciones' => $detallesExtendidos['observaciones'] ?? null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);

                                // Crear registros de colores para esta talla
                                if (!empty($coloresPorTalla[$claveAgrupar])) {
                                    foreach ($coloresPorTalla[$claveAgrupar] as $colorInfo) {
                                            $detallesColor = $datosExtendidos[strtolower($generoBD)][$colorInfo['tallaKey']] ?? [];
                                            $ubicacionesColor = !empty($detallesColor['ubicaciones']) ? json_encode($detallesColor['ubicaciones']) : (!empty($detallesExtendidos['ubicaciones']) ? json_encode($detallesExtendidos['ubicaciones']) : null);
                                            $observacionesColor = array_key_exists('observaciones', $detallesColor) ? $detallesColor['observaciones'] : ($detallesExtendidos['observaciones'] ?? null);
                                        DB::table('pedidos_procesos_prenda_talla_colores')->insert([
                                            'pedidos_procesos_prenda_talla_id' => $tallaProcesoPrendaId,
                                            'color_nombre' => $colorInfo['color_nombre'],
                                            'tela_nombre' => null,
                                            'cantidad' => $colorInfo['cantidad'],
                                                'ubicaciones' => $ubicacionesColor,
                                                'observaciones' => $observacionesColor,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }

                // Guardar imágenes del proceso
                $imagenes = $proceso['imagenes'] ?? [];
                if (!empty($imagenes)) {
                    $this->procesoImagenService->guardarImagenesProcesos(
                        $procesoDetalleId,
                        $pedidoId,
                        $imagenes
                    );
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
     * Normalizar ubicaciones: Decodificar si es JSON string y volver a codificar
     * Evita la doble codificación JSON
     */
    private function normalizarUbicaciones($ubicaciones)
    {
        if (is_string($ubicaciones)) {
            $decodificada = json_decode($ubicaciones, true);
            if (is_array($decodificada)) {
                return json_encode($decodificada);
            }
            return json_encode([$ubicaciones]);
        }
        
        if (is_array($ubicaciones)) {
            return json_encode($ubicaciones);
        }
        
        return json_encode([]);
    }
}