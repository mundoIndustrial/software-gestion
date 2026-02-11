<?php

namespace App\Domain\Pedidos\Services;

use App\Domain\Pedidos\ValueObjects\VariacionPrenda;
use App\Application\Pedidos\DTOs\VariacionPrendaDTO;
use App\Domain\Pedidos\Enums\TipoVariacion;

/**
 * Servicio de dominio para procesamiento de variaciones de prendas
 * 
 * Centraliza toda la lógica compleja de manejo de variaciones que antes
 * estaba dispersa en el frontend.
 */
class VariacionProcessorService
{
    /**
     * Procesar variaciones de una prenda completa
     */
    public function procesarVariacionesPrenda(array $prendaData): VariacionPrendaDTO
    {
        $variacionPrenda = new VariacionPrenda($prendaData);
        
        // Detectar tipos de variación automáticamente
        $tiposDetectados = $this->detectarTiposVariacion($prendaData);
        
        // Validar reglas de negocio
        $validacion = $this->validarReglasNegocio($variacionPrenda);
        
        // Generar configuración optimizada para UI
        $configuracionUI = $this->optimizarConfiguracionUI($variacionPrenda);
        
        return new VariacionPrendaDTO([
            'variaciones_procesadas' => $variacionPrenda->getVariaciones(),
            'configuracion_ui' => $configuracionUI,
            'genero' => $variacionPrenda->getGenero(),
            'tipos_detectados' => $tiposDetectados,
            'es_valida' => $variacionPrenda->esValida(),
            'errores' => $variacionPrenda->getErrores(),
            'tiene_variaciones' => $variacionPrenda->tieneVariaciones(),
            'resumen' => $this->generarResumen($variacionPrenda)
        ]);
    }
    
    /**
     * Detectar tipos de variación presentes en los datos
     */
    private function detectarTiposVariacion(array $prendaData): array
    {
        $tipos = [];
        
        // Detectar desde variantes principales
        if (isset($prendaData['variantes'])) {
            $tipos = array_merge($tipos, TipoVariacion::detectarDesdeDatos($prendaData['variantes']));
        }
        
        // Detectar desde procesos (Logo/Reflectivo)
        if (isset($prendaData['procesos'])) {
            $procesosArray = is_array($prendaData['procesos']) ? $prendaData['procesos'] : array_values($prendaData['procesos']);
            
            foreach ($procesosArray as $proceso) {
                if (isset($proceso['variaciones_prenda'])) {
                    $tipos = array_merge($tipos, TipoVariacion::detectarDesdeDatos($proceso['variaciones_prenda']));
                }
            }
        }
        
        // Eliminar duplicados y mantener orden
        $tipos = array_unique($tipos);
        sort($tipos);
        
        return $tipos;
    }
    
    /**
     * Validar reglas de negocio específicas
     */
    private function validarReglasNegocio(VariacionPrenda $variacionPrenda): array
    {
        $errores = [];
        
        // Regla 1: Si hay reflectivo, debe tener manga
        if ($this->tieneVariacionTipo($variacionPrenda, TipoVariacion::REFLECTIVO) && 
            !$this->tieneVariacionTipo($variacionPrenda, TipoVariacion::MANGA)) {
            $errores[] = 'Prenda con reflectivo debe tener manga definida';
        }
        
        // Regla 2: Validar combinaciones específicas
        $errores = array_merge($errores, $this->validarCombinacionesEspecificas($variacionPrenda));
        
        // Regla 3: Validar que los IDs sean consistentes
        $errores = array_merge($errores, $this->validarConsistenciaIds($variacionPrenda));
        
        return $errores;
    }
    
    /**
     * Verificar si existe una variación de tipo específico
     */
    private function tieneVariacionTipo(VariacionPrenda $variacionPrenda, TipoVariacion $tipo): bool
    {
        $variacion = $variacionPrenda->getVariacion($tipo->value);
        return $variacion && $variacion['aplicado'];
    }
    
    /**
     * Validar combinaciones específicas de negocio
     */
    private function validarCombinacionesEspecificas(VariacionPrenda $variacionPrenda): array
    {
        $errores = [];
        $variaciones = $variacionPrenda->getVariaciones();
        
        // Validar que broche y manga no estén vacíos si están marcados
        if (isset($variaciones[TipoVariacion::BROCHE->value]) && isset($variaciones[TipoVariacion::MANGA->value])) {
            $broche = $variaciones[TipoVariacion::BROCHE->value];
            $manga = $variaciones[TipoVariacion::MANGA->value];
            
            if ($broche['aplicado'] && empty($broche['opcion']) && empty($broche['observacion'])) {
                $errores[] = 'Broche marcado pero sin opción ni descripción';
            }
            
            if ($manga['aplicado'] && empty($manga['opcion']) && empty($manga['observacion'])) {
                $errores[] = 'Manga marcada pero sin opción ni descripción';
            }
        }
        
        // Validar que los campos obligatorios tengan datos
        foreach ($variaciones as $tipo => $variacion) {
            if ($variacion['aplicado']) {
                $esObligatorio = in_array($tipo, [TipoVariacion::MANGA->value, TipoVariacion::BROCHE->value]);
                
                if ($esObligatorio && empty($variacion['opcion']) && empty($variacion['observacion'])) {
                    $errores[] = ucfirst($tipo) . ' marcado pero sin opción ni descripción';
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar consistencia de IDs
     */
    private function validarConsistenciaIds(VariacionPrenda $variacionPrenda): array
    {
        $errores = [];
        $variantes = $variacionPrenda->getVariaciones();
        
        foreach ($variantes as $tipo => $variacion) {
            if ($variacion['aplicado'] && !empty($variacion['id'])) {
                // Validar que el ID exista en la base de datos (esto requeriría acceso al repositorio)
                // Por ahora, solo validamos que sea un ID numérico válido
                if (!is_numeric($variacion['id']) || $variacion['id'] <= 0) {
                    $errores[] = 'ID de ' . $tipo . ' inválido: ' . $variacion['id'];
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Optimizar configuración para UI frontend
     */
    private function optimizarConfiguracionUI(VariacionPrenda $variacionPrenda): array
    {
        $configuracionOriginal = $variacionPrenda->getConfiguracionUI();
        $configuracionOptimizada = [];
        
        // Optimizar cada variación para frontend
        foreach ($configuracionOriginal as $clave => $config) {
            if ($clave === 'genero') {
                // Configuración de género
                $configuracionOptimizada[$clave] = [
                    'checkbox_selector' => "input[value='{$config['checkbox_value']}']",
                    'checkbox_id' => $config['checkbox_id'],
                    'valor' => $config['nombre'],
                    'id' => $config['id'],
                    'marcado' => true,
                    'evento' => 'change'
                ];
            } else {
                // Configuración de variación
                $configuracionOptimizada[$clave] = [
                    'checkbox_id' => $config['checkbox_id'],
                    'checkbox_marcado' => $config['checked'],
                    'input_id' => $config['input_id'],
                    'input_valor' => $config['valor_normalizado'],
                    'observacion_id' => $config['observacion_id'],
                    'observacion_valor' => $config['observacion'],
                    'aplicado' => $config['aplicado'],
                    'evento_checkbox' => 'change',
                    'evento_input' => 'change',
                    'evento_observacion' => 'change',
                    'requerido' => in_array($clave, ['manga', 'broche']),
                    'tiene_input' => !empty($config['input_id']),
                    'tiene_observacion' => !empty($config['observacion_id'])
                ];
            }
        }
        
        return $configuracionOptimizada;
    }
    
    /**
     * Generar resumen de variaciones para logging/debug
     */
    public function generarResumen(array $prendaData): array
    {
        $variacionPrenda = new VariacionPrenda($prendaData);
        
        return [
            'genero_principal' => $variacionPrenda->getGenero()['nombre'] ?? 'No detectado',
            'tipos_detectados' => $this->detectarTiposVariacion($prendaData),
            'total_variaciones' => count($variacionPrenda->getVariaciones()),
            'variaciones_aplicadas' => count(array_filter($variacionPrenda->getVariaciones(), fn($v) => $v['aplicado'])),
            'es_valido' => $variacionPrenda->esValida(),
            'cantidad_errores' => count($variacionPrenda->getErrores()),
            'tiene_genero' => !is_null($variacionPrenda->getGenero()['id']),
            'configuracion_ui_lista' => array_keys($variacionPrenda->getConfiguracionUI())
        ];
    }
}
