<?php

namespace App\Domain\Pedidos\Services;

use App\Domain\Pedidos\ValueObjects\ProcesoPrenda;
use App\Application\Pedidos\DTOs\ProcesoPrendaDTO;

/**
 * Servicio de dominio para procesamiento de procesos de prendas
 * 
 * Centraliza toda la lógica compleja de manejo de procesos que antes
 * estaba dispersa en el frontend.
 */
class ProcesoProcessorService
{
    /**
     * Procesar procesos de una prenda completa
     */
    public function procesarProcesosPrenda(array $prendaData): ProcesoPrendaDTO
    {
        $procesosNormalizados = $this->normalizarProcesos($prendaData['procesos'] ?? []);
        
        if (empty($procesosNormalizados)) {
            return new ProcesoPrendaDTO([
                'procesos_procesados' => [],
                'configuracion_ui' => [],
                'es_valido' => true,
                'errores' => [],
                'tiene_procesos' => false,
                'resumen' => $this->generarResumen([])
            ]);
        }
        
        $procesosProcesados = [];
        $configuracionUI = [];
        $erroresGlobales = [];
        
        foreach ($procesosNormalizados as $index => $procesoData) {
            try {
                $proceso = new ProcesoPrenda($procesoData);
                
                if (!$proceso->esValido()) {
                    $erroresGlobales = array_merge($erroresGlobales, $proceso->getErrores());
                }
                
                $procesosProcesados[] = $proceso->toArray();
                $configuracionUI[$proceso->getSlug()] = $proceso->getConfiguracionUI();
                
            } catch (\Exception $e) {
                $erroresGlobales[] = "Error procesando proceso {$index}: " . $e->getMessage();
            }
        }
        
        return new ProcesoPrendaDTO([
            'procesos_procesados' => $procesosProcesados,
            'configuracion_ui' => $configuracionUI,
            'es_valido' => empty($erroresGlobales),
            'errores' => $erroresGlobales,
            'tiene_procesos' => !empty($procesosProcesados),
            'resumen' => $this->generarResumen($procesosProcesados)
        ]);
    }
    
    /**
     * Normalizar procesos: maneja tanto array como objeto
     */
    private function normalizarProcesos($procesos): array
    {
        if (empty($procesos)) {
            return [];
        }
        
        // Si es objeto, convertir a array
        if (is_object($procesos) && !is_array($procesos)) {
            return [$procesos];
        }
        
        // Si es array asociativo, convertir a array indexado
        if (is_array($procesos) && array_keys($procesos) !== range(0, count($procesos) - 1)) {
            return array_values($procesos);
        }
        
        return $procesos;
    }
    
    /**
     * Generar resumen de procesos para logging/debug
     */
    public function generarResumen(array $prendaData): array
    {
        $procesosNormalizados = $this->normalizarProcesos($prendaData['procesos'] ?? []);
        
        if (empty($procesosNormalizados)) {
            return [
                'total_procesos' => 0,
                'tiene_procesos' => false,
                'tipos_detectados' => [],
                'mensaje' => 'Sin procesos en la prenda'
            ];
        }
        
        $tiposDetectados = [];
        $totalImagenes = 0;
        $totalUbicaciones = 0;
        
        foreach ($procesosNormalizados as $procesoData) {
            $proceso = new ProcesoPrenda($procesoData);
            $tiposDetectados[] = $proceso->getTipo()->value;
            $totalImagenes += count($proceso->toArray()['imagenes']);
            $totalUbicaciones += count($proceso->toArray()['ubicaciones']);
        }
        
        return [
            'total_procesos' => count($procesosNormalizados),
            'tiene_procesos' => true,
            'tipos_detectados' => array_unique($tiposDetectados),
            'total_imagenes' => $totalImagenes,
            'total_ubicaciones' => $totalUbicaciones,
            'tipos_con_imagenes' => count(array_filter($tiposDetectados, fn($tipo) => $this->tipoSoportaImagenes($tipo))),
            'tipos_con_ubicaciones' => count(array_filter($tiposDetectados, fn($tipo) => $this->tipoRequiereUbicaciones($tipo))),
            'tipos_con_tallas' => count(array_filter($tiposDetectados, fn($tipo) => $this->tipoRequiereTallas($tipo))),
            'resumen_detalle' => $this->generarResumen(array_map(fn($p) => (new ProcesoPrenda($p))->toArray(), $procesosNormalizados))
        ];
    }
    
    /**
     * Validar configuración completa de procesos
     */
    public function validarConfiguracionCompleta(array $procesosData): array
    {
        $procesosNormalizados = $this->normalizarProcesos($procesosData);
        $errores = [];
        $advertencias = [];
        
        foreach ($procesosNormalizados as $index => $procesoData) {
            $proceso = new ProcesoPrenda($procesoData);
            
            if (!$proceso->esValido()) {
                $errores = array_merge($errores, array_map(
                    fn($error) => "Proceso {$index}: {$error}",
                    $proceso->getErrores()
                ));
            }
            
            // Advertencias
            if ($proceso->getTipo()->soportaImagenes() && !$proceso->tieneImagenes()) {
                $advertencias[] = "Proceso {$proceso->getTipo()->getNombre()} podría beneficiarse de imágenes de referencia";
            }
            
            if ($proceso->getTipo()->requiereUbicaciones() && !$proceso->tieneUbicaciones()) {
                $advertencias[] = "Proceso {$proceso->getTipo()->getNombre()} requiere ubicaciones específicas";
            }
        }
        
        return [
            'es_valido' => empty($errores),
            'errores' => $errores,
            'advertencias' => $advertencias,
            'total_procesos' => count($procesosNormalizados),
            'procesos_con_errores' => count($errores),
            'procesos_con_advertencias' => count($advertencias)
        ];
    }
}
