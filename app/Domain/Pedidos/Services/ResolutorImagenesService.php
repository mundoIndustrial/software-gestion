<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Application\Services\ImageUploadService;

/**
 * ResolutorImagenesService
 * 
 * Resuelve referencias de imágenes usando UIDs
 * 
 * FLUJO:
 * 1. FormData llega con archivos en rutas: prendas.0.imagenes.0 = File
 * 2. Extrae archivos de Request y mapea a UIDs
 * 3. Procesa y guarda archivos, registra rutas en mapeo
 * 4. Los handlers CQRS usan los UIDs para resolver referencias
 * 
 * VENTAJA: Los archivos nunca pierden su identificación única (UID)
 */

class ResolutorImagenesService
{
    public function __construct(
        private ImageUploadService $imageUploadService
    ) {}

    /**
     * Extraer y procesar todas las imágenes de la Request
     * 
     * @param Request $request
     * @param int $pedidoId
     * @param array $datosPrendas - Prendas normalizadas con UIDs
     * @param callable $registrarUID - Callback para registrar mapeo UID → ruta
     * 
     * @return array Mapeo de UID → ruta final
     */
    public function extraerYProcesarImagenes(
        $request,
        int $pedidoId,
        array $datosPrendas,
        callable $registrarUID
    ): array {
        $mapeoUidARuta = [];

        Log::info('[ResolutorImagenesService] Iniciando extracción de imágenes', [
            'pedido_id' => $pedidoId,
            'prendas_count' => count($datosPrendas),
            'archivos_en_request' => count($request->allFiles()),
        ]);

        // Validación: Verificar que hay archivos en la request si hay imágenes en el DTO
        $totalImagenesEnDTO = $this->contarImagenesEnDTO($datosPrendas);
        if ($totalImagenesEnDTO > 0 && count($request->allFiles()) === 0) {
            Log::warning('[ResolutorImagenesService] Advertencia: Se esperan imágenes en FormData pero Request vacío', [
                'imagenes_en_dto' => $totalImagenesEnDTO,
                'archivos_en_request' => count($request->allFiles()),
            ]);
        }

        // ========================================
        // PROCESAR IMÁGENES DE PRENDAS
        // ========================================
        foreach ($datosPrendas as $prendaIdx => $prenda) {
            $prendaUID = $prenda['uid'] ?? null;
            
            if (!$prendaUID) {
                Log::warning('[ResolutorImagenesService] Prenda sin UID', ['idx' => $prendaIdx]);
                continue;
            }

            // IMÁGENES DE PRENDA
            $this->procesarImagenesDeGrupo(
                $request,
                $pedidoId,
                "prendas.{$prendaIdx}.imagenes",
                'prendas',
                $prenda['imagenes'] ?? [],
                $prendaUID,
                $mapeoUidARuta,
                $registrarUID
            );

            // IMÁGENES DE TELAS
            if (!empty($prenda['telas'])) {
                foreach ($prenda['telas'] as $telaIdx => $tela) {
                    $telaUID = $tela['uid'] ?? null;
                    
                    if (!$telaUID) {
                        Log::warning('[ResolutorImagenesService] Tela sin UID', [
                            'prenda_idx' => $prendaIdx,
                            'tela_idx' => $telaIdx
                        ]);
                        continue;
                    }

                    $this->procesarImagenesDeGrupo(
                        $request,
                        $pedidoId,
                        "prendas.{$prendaIdx}.telas.{$telaIdx}.imagenes",
                        'telas',
                        $tela['imagenes'] ?? [],
                        $telaUID,
                        $mapeoUidARuta,
                        $registrarUID
                    );
                }
            }

            // IMÁGENES DE PROCESOS
            if (!empty($prenda['procesos'])) {
                foreach ($prenda['procesos'] as $procesoIdx => $proceso) {
                    $procesoUID = $proceso['uid'] ?? null;
                    $nombreProceso = $proceso['nombre'] ?? 'proceso';
                    
                    if (!$procesoUID) {
                        Log::warning('[ResolutorImagenesService] Proceso sin UID', [
                            'prenda_idx' => $prendaIdx,
                            'proceso_idx' => $procesoIdx
                        ]);
                        continue;
                    }

                    $this->procesarImagenesDeGrupo(
                        $request,
                        $pedidoId,
                        "prendas.{$prendaIdx}.procesos.{$procesoIdx}.imagenes",
                        "procesos/{$nombreProceso}",
                        $proceso['imagenes'] ?? [],
                        $procesoUID,
                        $mapeoUidARuta,
                        $registrarUID
                    );
                }
            }
        }

        Log::info('[ResolutorImagenesService] Extracción completada', [
            'pedido_id' => $pedidoId,
            'imagenes_procesadas' => count($mapeoUidARuta),
        ]);

        return $mapeoUidARuta;
    }

    /**
     * Procesar un grupo de imágenes (de prenda, tela, proceso, etc)
     * 
     * @param $request
     * @param int $pedidoId
     * @param string $formPrefix - Ej: "prendas.0.imagenes" o "prendas.0.telas.0.imagenes"
     * @param string $carpetaTipo - Tipo de carpeta: 'prendas', 'telas', 'procesos/{tipo}'
     * @param array $imagenesMetadata - Metadata de imágenes con UIDs
     * @param string $parentUID - UID del padre (prenda, tela o proceso)
     * @param array &$mapeoUidARuta - Referencia al mapeo a actualizar
     * @param callable $registrarUID - Callback para registrar mapeo
     */
    private function procesarImagenesDeGrupo(
        $request,
        int $pedidoId,
        string $formPrefix,
        string $carpetaTipo,
        array $imagenesMetadata,
        string $parentUID,
        array &$mapeoUidARuta,
        callable $registrarUID
    ): void {
        $imagenIdx = 0;

        foreach ($imagenesMetadata as $imagenMetadata) {
            $imagenUID = $imagenMetadata['uid'] ?? null;
            $nombreArchivo = $imagenMetadata['nombre_archivo'] ?? null;
            $formDataKey = $imagenMetadata['formdata_key'] ?? null;

            if (!$imagenUID || !$nombreArchivo) {
                Log::warning('[ResolutorImagenesService] Imagen sin UID o nombre', [
                    'form_prefix' => $formPrefix,
                    'imagen_idx' => $imagenIdx,
                    'parent_uid' => $parentUID
                ]);
                $imagenIdx++;
                continue;
            }

            // Intentar obtener archivo de Request
            // Primero intentar con formdata_key (generado por frontend)
            $archivo = null;
            
            if ($formDataKey) {
                $archivo = $request->file($formDataKey);
            }
            
            // Si no encontró con formdata_key, intentar formato antiguo
            if (!$archivo) {
                $formKey = "{$formPrefix}.{$imagenIdx}";
                $archivo = $request->file($formKey);
            }

            if ($archivo && $archivo instanceof UploadedFile) {
                try {
                    // Procesar y guardar imagen
                    $resultado = $this->imageUploadService->guardarImagenDirecta(
                        $archivo,
                        $pedidoId,
                        $carpetaTipo,
                        null,
                        null
                    );

                    // Mapear UID → ruta final
                    $rutaFinal = $resultado['webp'];
                    $mapeoUidARuta[$imagenUID] = $rutaFinal;

                    // Notificar al callback (para actualizar DTO u otros)
                    $registrarUID($imagenUID, $rutaFinal);

                    Log::debug('[ResolutorImagenesService] Imagen procesada', [
                        'imagen_uid' => $imagenUID,
                        'ruta' => $rutaFinal,
                        'parent_uid' => $parentUID,
                    ]);

                } catch (\Exception $e) {
                    Log::error('[ResolutorImagenesService] Error procesando imagen', [
                        'imagen_uid' => $imagenUID,
                        'error' => $e->getMessage(),
                        'form_key' => $formKey,
                    ]);
                }
            } else {
                Log::warning('[ResolutorImagenesService] Archivo no encontrado en Request', [
                    'form_key' => $formKey,
                    'imagen_uid' => $imagenUID,
                ]);
            }

            $imagenIdx++;
        }
    }

    /**
     * Contar total de imágenes esperadas en el DTO
     * Útil para validación de consistencia con FormData
     */
    private function contarImagenesEnDTO(array $datosPrendas): int
    {
        $total = 0;
        
        foreach ($datosPrendas as $prenda) {
            // Imágenes de prenda
            $total += count($prenda['imagenes'] ?? []);
            
            // Imágenes de telas
            foreach ($prenda['telas'] ?? [] as $tela) {
                $total += count($tela['imagenes'] ?? []);
            }
            
            // Imágenes de procesos
            foreach ($prenda['procesos'] ?? [] as $proceso) {
                $total += count($proceso['imagenes'] ?? []);
            }
        }
        
        return $total;
    }
}