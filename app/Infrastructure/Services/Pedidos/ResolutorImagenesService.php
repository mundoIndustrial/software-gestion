<?php

namespace App\Infrastructure\Services\Pedidos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Application\Services\ImageUploadService;

/**
 * ResolutorImagenesService
 * 
 * Resuelve referencias de imagenes usando UIDs
 * 
 * FLUJO:
 * 1. FormData llega con archivos en rutas: prendas.0.imagenes.0 = File
 * 2. Extrae archivos de Request y mapea a UIDs
 * 3. Procesa y guarda archivos, registra rutas en mapeo
 * 4. Los handlers CQRS usan los UIDs para resolver referencias
 * 
 * VENTAJA: Los archivos nunca pierden su identificacion anadida (UID)
 */

class ResolutorImagenesService
{
    public function __construct(
        private ImageUploadService $imageUploadService
    ) {}

    /**
     * Extraer y procesar todas las imagenes de la Request
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param array $datosPrendas - Prendas normalizadas con UIDs
     * @param callable $registrarUID - Callback para registrar mapeo UID  ruta
     * 
     * @return array Mapeo de UID  ruta final
     */
    public function extraerYProcesarImagenes(
        $request,
        int $pedidoId,
        array $datosPrendas,
        callable $registrarUID
    ): array {
        $inicioTotal = microtime(true);
        $mapeoUidARuta = [];

        // CLAVE: Buscar archivos anidados en todos los inputs (no solo allFiles())
        $inicioExtraccion = microtime(true);
        $todosLosArchivos = $this->buscarArchivosAnidados($request->all());
        $archivosTotal = count($todosLosArchivos);
        $tiempoExtraccion = round((microtime(true) - $inicioExtraccion) * 1000, 2);
        
        Log::info('[RESOLVER-IMAGENES] ¸ INICIANDO EXTRACCION DE IMAGENES', [
            'pedido_id' => $pedidoId,
            'prendas_count' => count($datosPrendas),
            'archivos_en_request' => $archivosTotal,
            'tiempo_extraccion_ms' => $tiempoExtraccion,
            'timestamp' => now(),
        ]);

        // Validacion: Verificar que hay archivos en la request si hay imagenes en el DTO
        $totalImagenesEnDTO = $this->contarImagenesEnDTO($datosPrendas);
        if ($totalImagenesEnDTO > 0 && $archivosTotal === 0) {
            Log::error('[RESOLVER-IMAGENES]  ERROR CRITICO: Se esperan imagenes pero FormData vacio', [
                'imagenes_en_dto' => $totalImagenesEnDTO,
                'archivos_en_request' => $archivosTotal,
                'esto_explicara_por_que_no_se_guardan_imagenes' => 'Los archivos no llegaron en FormData'
            ]);
        }

        // ========================================
        // PROCESAR IMAGENES DE PRENDAS
        // ========================================
        $inicioProceso = microtime(true);
        foreach ($datosPrendas as $prendaIdx => $prenda) {
            $prendaUID = $prenda['uid'] ?? null;
            
            if (!$prendaUID) {
                Log::warning('[RESOLVER-IMAGENES] Prenda sin UID', ['idx' => $prendaIdx]);
                continue;
            }

            // IMAGENES DE PRENDA
            if (!empty($prenda['imagenes'])) {
                $this->procesarImagenesDeGrupo(
                    $request,
                    $todosLosArchivos,
                    $pedidoId,
                    "prendas.{$prendaIdx}.imagenes",
                    'prendas',
                    $prenda['imagenes'],
                    $prendaUID,
                    $mapeoUidARuta,
                    $registrarUID
                );
            }

            // IMAGENES DE TELAS
            if (!empty($prenda['telas'])) {
                foreach ($prenda['telas'] as $telaIdx => $tela) {
                    $telaUID = $tela['uid'] ?? null;
                    
                    if (!$telaUID) {
                        Log::warning('[RESOLVER-IMAGENES] Tela sin UID', [
                            'prenda_idx' => $prendaIdx,
                            'tela_idx' => $telaIdx
                        ]);
                        continue;
                    }

                    // IMAGENES DE TELAS - Solo procesar si hay imagenes
                    if (!empty($tela['imagenes'])) {
                        $this->procesarImagenesDeGrupo(
                            $request,
                            $todosLosArchivos,
                            $pedidoId,
                            "prendas.{$prendaIdx}.telas.{$telaIdx}.imagenes",
                            'telas',
                            $tela['imagenes'],
                            $telaUID,
                            $mapeoUidARuta,
                            $registrarUID
                        );
                    }
                }
            }

            // IMAGENES DE PROCESOS
            if (!empty($prenda['procesos'])) {
                foreach ($prenda['procesos'] as $procesoIdx => $proceso) {
                    $procesoUID = $proceso['uid'] ?? null;
                    
                    if (!$procesoUID) {
                        Log::warning('[RESOLVER-IMAGENES] Proceso sin UID', [
                            'prenda_idx' => $prendaIdx,
                            'proceso_idx' => $procesoIdx
                        ]);
                        continue;
                    }

                    // IMAGENES DE PROCESOS - Modo para_todas
                    if (!empty($proceso['imagenes'])) {
                        $this->procesarImagenesDeGrupo(
                            $request,
                            $todosLosArchivos,
                            $pedidoId,
                            "prendas.{$prendaIdx}.procesos.{$procesoIdx}.imagenes",
                            'procesos',
                            $proceso['imagenes'],
                            $procesoUID,
                            $mapeoUidARuta,
                            $registrarUID
                        );
                    }
                    
                    // NUEVO: IMAGENES POR TALLA - Modo por_tallas
                    if (!empty($proceso['imagenes_por_talla']) && is_array($proceso['imagenes_por_talla'])) {
                        foreach ($proceso['imagenes_por_talla'] as $tallaKey => $imagenesDetalles) {
                            if (!empty($imagenesDetalles) && is_array($imagenesDetalles)) {
                                $this->procesarImagenesDeGrupo(
                                    $request,
                                    $todosLosArchivos,
                                    $pedidoId,
                                    "prendas.{$prendaIdx}.procesos.{$procesoIdx}.imagenes_por_talla.{$tallaKey}",
                                    'procesos',
                                    $imagenesDetalles,
                                    $procesoUID,
                                    $mapeoUidARuta,
                                    $registrarUID
                                );
                            }
                        }
                    }
                }
            }
        }
        
        $tiempoProceso = round((microtime(true) - $inicioProceso) * 1000, 2);
        $tiempoTotal = round((microtime(true) - $inicioTotal) * 1000, 2);

        Log::info('[RESOLVER-IMAGENES]  Extraccion completada', [
            'pedido_id' => $pedidoId,
            'imagenes_procesadas' => count($mapeoUidARuta),
            'imagenes_esperadas' => $totalImagenesEnDTO,
            'diferencia' => $totalImagenesEnDTO - count($mapeoUidARuta),
            'tiempo_total_ms' => $tiempoTotal,
            'tiempo_proceso_ms' => $tiempoProceso,
            'resumen' => "Extraccion archivos: {$tiempoExtraccion}ms | Procesamiento: {$tiempoProceso}ms | TOTAL: {$tiempoTotal}ms",
        ]);

        return $mapeoUidARuta;
    }

    /**
     * Procesar un grupo de imagenes (de prenda, tela, proceso, etc)
     * 
     * @param $request
     * @param int $pedidoId
     * @param string $formPrefix - Ej: "prendas.0.imagenes" o "prendas.0.telas.0.imagenes"
     * @param string $carpetaTipo - Tipo de carpeta: 'prendas', 'telas', 'procesos/{tipo}'
     * @param array $imagenesMetadata - Metadata de imagenes con UIDs
     * @param string $parentUID - UID del padre (prenda, tela o proceso)
     * @param array &$mapeoUidARuta - Referencia al mapeo a actualizar
     * @param callable $registrarUID - Callback para registrar mapeo
     */
    private function procesarImagenesDeGrupo(
        $request,
        array $todosLosArchivos,
        int $pedidoId,
        string $formPrefix,
        string $carpetaTipo,
        array $imagenesMetadata,
        string $parentUID,
        array &$mapeoUidARuta,
        callable $registrarUID
    ): void {
        $imagenIdx = 0;
        $inicioGrupo = microtime(true);
        $imagenesGrupoProcesadas = 0;

        foreach ($imagenesMetadata as $imagenMetadata) {
            $imagenUID = $imagenMetadata['uid'] ?? null;
            $nombreArchivo = $imagenMetadata['nombre_archivo'] ?? null;
            $formDataKey = $imagenMetadata['formdata_key'] ?? null;

            if (!$imagenUID || !$nombreArchivo) {
                Log::warning('[RESOLVER-IMAGENES] Imagen sin UID o nombre', [
                    'form_prefix' => $formPrefix,
                    'imagen_idx' => $imagenIdx,
                    'parent_uid' => $parentUID
                ]);
                $imagenIdx++;
                continue;
            }

            // Intentar obtener archivo de FormData
            // Primero intentar con formdata_key (generado por frontend)
            $archivo = null;
            
            if ($formDataKey && isset($todosLosArchivos[$formDataKey])) {
                $archivo = $todosLosArchivos[$formDataKey];
            }
            
            // Si no encontro con formdata_key, intentar formato antiguo
            if (!$archivo) {
                $formKey = "{$formPrefix}.{$imagenIdx}";
                $archivo = $request->file($formKey);
            }

            if ($archivo && $archivo instanceof UploadedFile) {
                try {
                    $inicioGuardado = microtime(true);
                    // Procesar y guardar imagen
                    $resultado = $this->imageUploadService->guardarImagenDirecta(
                        $archivo,
                        $pedidoId,
                        $carpetaTipo,
                        null,
                        null
                    );
                    $tiempoGuardado = round((microtime(true) - $inicioGuardado) * 1000, 2);

                    // Mapear UID ruta final
                    // IMPORTANTE: tambien mapear por formdata_key para evitar colisiones de UIDs
                    $rutaFinal = $resultado['webp'];
                    $mapeoUidARuta[$imagenUID] = $rutaFinal;
                    if ($formDataKey) {
                        $mapeoUidARuta[$formDataKey] = $rutaFinal;
                    }

                    Log::debug('[RESOLVER-IMAGENES] ‘ MAPEO CREADO', [
                        'imagen_uid' => $imagenUID,
                        'formdata_key' => $formDataKey,
                        'ruta_webp' => $rutaFinal,
                        'form_prefix' => $formPrefix,
                        'mapeado_por_uid' => true,
                        'mapeado_por_formdata_key' => !empty($formDataKey),
                    ]);

                    // Notificar al callback (para actualizar DTO u otros)
                    $registrarUID($imagenUID, $rutaFinal);

                    Log::debug('[RESOLVER-IMAGENES]  Imagen procesada', [
                        'imagen_uid' => $imagenUID,
                        'ruta' => $rutaFinal,
                        'parent_uid' => $parentUID,
                        'tiempo_guardado_ms' => $tiempoGuardado,
                    ]);
                    
                    $imagenesGrupoProcesadas++;

                } catch (\Exception $e) {
                    Log::error('[RESOLVER-IMAGENES]  Error procesando imagen', [
                        'imagen_uid' => $imagenUID,
                        'error' => $e->getMessage(),
                        'form_key' => $formKey ?? 'N/A',
                    ]);
                }
            } else {
                Log::warning('[RESOLVER-IMAGENES]  Archivo no encontrado en Request', [
                    'form_key' => $formKey ?? 'N/A',
                    'imagen_uid' => $imagenUID,
                    'nombre_archivo' => $nombreArchivo,
                ]);
            }

            $imagenIdx++;
        }
        
        $tiempoGrupo = round((microtime(true) - $inicioGrupo) * 1000, 2);
        if ($imagenesGrupoProcesadas > 0) {
            Log::debug('[RESOLVER-IMAGENES]  Grupo completado', [
                'form_prefix' => $formPrefix,
                'imagenes_procesadas' => $imagenesGrupoProcesadas,
                'tiempo_grupo_ms' => $tiempoGrupo,
            ]);
        }
    }

    /**
     * Contar total de imagenes esperadas en el DTO
     * Util para validacion de consistencia con FormData
     */
    private function contarImagenesEnDTO(array $datosPrendas): int
    {
        $total = 0;
        
        foreach ($datosPrendas as $prenda) {
            // imagenes de prenda
            $total += count($prenda['imagenes'] ?? []);
            
            // imagenes de telas
            foreach ($prenda['telas'] ?? [] as $tela) {
                $total += count($tela['imagenes'] ?? []);
            }
            
            // imagenes de procesos
            foreach ($prenda['procesos'] ?? [] as $proceso) {
                // imagenes modo para_todas
                $total += count($proceso['imagenes'] ?? []);
                
                // NUEVO: imagenes por talla (modo por_tallas)
                if (!empty($proceso['imagenes_por_talla']) && is_array($proceso['imagenes_por_talla'])) {
                    foreach ($proceso['imagenes_por_talla'] as $tallaKey => $imagenesDetalles) {
                        if (is_array($imagenesDetalles)) {
                            $total += count($imagenesDetalles);
                        }
                    }
                }
            }
        }
        
        return $total;
    }

    /**
     * Buscar archivos anidados recursivamente en la estructura de inputs
     * Los archivos con claves como "prendas[0][imagenes][0]" se devuelven con su clave completa
     */
    private function buscarArchivosAnidados($datos, $prefijo = ''): array
    {
        $archivos = [];
        
        foreach ($datos as $key => $valor) {
            $nuevaPrefijo = $prefijo ? "{$prefijo}[{$key}]" : $key;
            
            if ($valor instanceof UploadedFile) {
                $archivos[$nuevaPrefijo] = $valor;
            } elseif (is_array($valor)) {
                $archivos = array_merge($archivos, $this->buscarArchivosAnidados($valor, $nuevaPrefijo));
            }
        }
        
        return $archivos;
    }
}
