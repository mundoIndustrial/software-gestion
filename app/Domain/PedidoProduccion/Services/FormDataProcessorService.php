<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Servicio de Dominio para procesar FormData
 * Extrae imágenes, procesos y telas desde FormData
 */
class FormDataProcessorService
{
    /**
     * Procesar imágenes de prenda desde FormData
     * @param Request $request
     * @param int $itemIndex
     * @return UploadedFile[]
     */
    public function extraerImagenesPrenda(Request $request, int $itemIndex): array
    {
        $patronBuscado = "items_" . $itemIndex . "_imagenes_files";
        $imagenesDelPrenda = $request->file($patronBuscado) ?? [];

        if (!is_array($imagenesDelPrenda)) {
            $imagenesDelPrenda = [$imagenesDelPrenda];
        }

        return array_filter($imagenesDelPrenda, function($img) {
            return $img instanceof UploadedFile;
        });
    }

    /**
     * Procesar imágenes de telas desde FormData
     * @param Request $request
     * @param int $itemIndex
     * @param array $telas - Array de telas con estructura básica
     * @return array - Telas con imágenes adjuntas
     */
    public function extraerImagenesTelas(Request $request, int $itemIndex, array $telas): array
    {
        $telasConImagenes = [];

        \Log::info('[FormDataProcessorService] Extrayendo imágenes de telas', [
            'itemIndex' => $itemIndex,
            'cantidad_telas' => count($telas),
        ]);

        // Primero, copiar datos de telas existentes
        foreach ($telas as $telaIdx => $telaDatos) {
            $telasConImagenes[$telaIdx] = is_array($telaDatos) ? $telaDatos : [];
            if (!isset($telasConImagenes[$telaIdx]['fotos'])) {
                $telasConImagenes[$telaIdx]['fotos'] = [];
            }
        }

        // Buscar imágenes en FormData con patrón items_index_telas_telaIdx_imagenes_files
        foreach ($telas as $telaIdx => $telaDatos) {
            $patronTela = "items_" . $itemIndex . "_telas_" . $telaIdx . "_imagenes_files";
            $fotosUploadedFiles = $request->file($patronTela) ?? [];
            
            if (!is_array($fotosUploadedFiles)) {
                $fotosUploadedFiles = [$fotosUploadedFiles];
            }
            
            $imagenesFiltered = array_filter($fotosUploadedFiles, function($img) {
                return $img instanceof UploadedFile;
            });

            if (!empty($imagenesFiltered)) {
                \Log::info('[FormDataProcessorService] ✅ Encontrada clave de imágenes de tela', [
                    'patron' => $patronTela,
                    'telaIdx' => $telaIdx,
                    'cantidad_archivos' => count($imagenesFiltered),
                ]);
                $telasConImagenes[$telaIdx]['fotos'] = array_values($imagenesFiltered);
            }
        }

        \Log::info('[FormDataProcessorService] Imágenes de telas extraídas', [
            'itemIndex' => $itemIndex,
            'telas_con_imagenes' => count(array_filter($telasConImagenes, fn($t) => !empty($t['fotos']))),
        ]);

        return $telasConImagenes;
    }

    /**
     * Procesar imágenes de procesos desde FormData
     * @param Request $request
     * @param int $itemIndex
     * @param array $procesos - Array de procesos
     * @return array - Procesos con imágenes adjuntas
     */
    public function extraerImagenesProcesos(Request $request, int $itemIndex, array $procesos): array
    {
        $procesosConImagenes = [];

        foreach ($procesos as $tipoProceso => $procesoData) {
            $procesosConImagenes[$tipoProceso] = $procesoData;
            $procesosConImagenes[$tipoProceso]['imagenes'] = [];

            // Buscar imágenes en FormData con patrón items_index_procesos_tipoProceso_imagenes_files
            $patronProceso = "items_" . $itemIndex . "_procesos_" . $tipoProceso . "_imagenes_files";
            $fotosUploadedFiles = $request->file($patronProceso) ?? [];
            
            if (!is_array($fotosUploadedFiles)) {
                $fotosUploadedFiles = [$fotosUploadedFiles];
            }
            
            $imagenesFiltered = array_filter($fotosUploadedFiles, function($img) {
                return $img instanceof UploadedFile;
            });

            if (!empty($imagenesFiltered)) {
                \Log::info('[FormDataProcessorService] ✅ Encontrada clave de imágenes de proceso', [
                    'patron' => $patronProceso,
                    'tipoProceso' => $tipoProceso,
                    'cantidad_archivos' => count($imagenesFiltered),
                ]);
                $procesosConImagenes[$tipoProceso]['imagenes'] = array_values($imagenesFiltered);
            }
        }

        return $procesosConImagenes;
    }

    /**
     * Procesar imágenes de EPP desde FormData
     * @param Request $request
     * @param int $itemIndex
     * @return UploadedFile[]
     */
    public function extraerImagenesEpp(Request $request, int $itemIndex): array
    {
        $allFiles = $request->allFiles();
        $imagenesDelEpp = [];

        // Buscar con patrón items_index_imagenes_files (clave plana)
        $patronBuscado = "items_{$itemIndex}_imagenes_files";

        foreach ($allFiles as $key => $files) {
            if ($key === $patronBuscado) {
                // Si $files es un array, usarlo directamente; si es un objeto, envolverlo en array
                $fotosUploadedFiles = is_array($files) ? $files : [$files];
                
                $imagenesFiltered = array_filter($fotosUploadedFiles, function($foto) {
                    return $foto instanceof UploadedFile;
                });
                
                $imagenesDelEpp = array_merge($imagenesDelEpp, $imagenesFiltered);
            }
        }

        return $imagenesDelEpp;
    }

    /**
     * Reconstruir procesos desde input() con datos parseados
     * @param Request $request
     * @param int $itemIndex
     * @return array
     */
    public function reconstruirProcesos(Request $request, int $itemIndex): array
    {
        $procesosReconstruidos = [];

        // Obtener datos de procesos desde input()
        $items_input = $request->input('items');
        $prendas = $request->input('prendas');

        $procesosDatos = null;
        if ($items_input && isset($items_input[$itemIndex]) && isset($items_input[$itemIndex]['procesos'])) {
            $procesosDatos = $items_input[$itemIndex]['procesos'];
        } elseif ($prendas && isset($prendas[$itemIndex]) && isset($prendas[$itemIndex]['procesos'])) {
            $procesosDatos = $prendas[$itemIndex]['procesos'];
        }

        if (!$procesosDatos) {
            return [];
        }

        foreach ($procesosDatos as $tipoProceso => $procesoData) {
            $datosProceso = [];

            // Copiar campos básicos
            if (isset($procesoData['tipo'])) {
                $datosProceso['tipo'] = $procesoData['tipo'];
            }
            if (isset($procesoData['ubicaciones'])) {
                $datosProceso['ubicaciones'] = is_string($procesoData['ubicaciones']) 
                    ? json_decode($procesoData['ubicaciones'], true) 
                    : $procesoData['ubicaciones'];
            }
            if (isset($procesoData['observaciones'])) {
                $datosProceso['observaciones'] = $procesoData['observaciones'];
            }

            // Procesar tallas
            $datosProceso['tallas'] = [];
            if (isset($procesoData['tallas_dama'])) {
                $tallasDama = is_string($procesoData['tallas_dama']) 
                    ? json_decode($procesoData['tallas_dama'], true) 
                    : $procesoData['tallas_dama'];
                $datosProceso['tallas']['dama'] = $tallasDama ?? [];
            }
            if (isset($procesoData['tallas_caballero'])) {
                $tallasCapallero = is_string($procesoData['tallas_caballero']) 
                    ? json_decode($procesoData['tallas_caballero'], true) 
                    : $procesoData['tallas_caballero'];
                $datosProceso['tallas']['caballero'] = $tallasCapallero ?? [];
            }

            $procesosReconstruidos[$tipoProceso] = $datosProceso;
        }

        return $procesosReconstruidos;
    }
}
