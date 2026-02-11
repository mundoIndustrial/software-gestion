<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\PrendaTransformadorService;
use App\Domain\Pedidos\Repositories\PrendaRepositoryInterface;
use App\Domain\Pedidos\Repositories\CotizacionRepositoryInterface;
use App\Application\Pedidos\DTOs\PrendaEditadaDTO;

/**
 * Application Service: PrendaEditorService
 * 
 * Orquesta el flujo de edición de prendas
 * Coordina entre el dominio y la infraestructura
 */
class PrendaEditorService
{
    private PrendaTransformadorService $transformador;
    private PrendaRepositoryInterface $prendaRepository;
    private CotizacionRepositoryInterface $cotizacionRepository;
    
    public function __construct(
        PrendaTransformadorService $transformador,
        PrendaRepositoryInterface $prendaRepository,
        CotizacionRepositoryInterface $cotizacionRepository
    ) {
        $this->transformador = $transformador;
        $this->prendaRepository = $prendaRepository;
        $this->cotizacionRepository = $cotizacionRepository;
    }
    
    /**
     * Obtiene los datos de una prenda para edición en el modal
     */
    public function obtenerDatosEdicion(int $prendaId, ?int $cotizacionId = null): PrendaEditadaDTO
    {
        try {
            // 1. Cargar prenda
            $prenda = $this->prendaRepository->findById($prendaId);
            if (!$prenda) {
                throw new \Exception("Prenda no encontrada: {$prendaId}");
            }
            
            // 2. Cargar cotización si existe
            $cotizacion = null;
            if ($cotizacionId) {
                $cotizacion = $this->cotizacionRepository->findById($cotizacionId);
            }
            
            // 3. Transformar datos para frontend
            $datosTransformados = $this->transformador->transformarParaFrontend($prenda, $cotizacion);
            
            // 4. Cargar datos adicionales de cotización si es Reflectivo/Logo
            if ($this->esTipoReflectivoOLogo($cotizacion)) {
                $datosCotizacion = $this->transformador->cargarDatosCotizacion($cotizacionId, $prendaId);
                $datosTransformados = array_merge($datosTransformados, $datosCotizacion);
            }
            
            return new PrendaEditadaDTO($datosTransformados);
            
        } catch (\Exception $e) {
            throw new \Exception("Error obteniendo datos de edición: {$e->getMessage()}");
        }
    }
    
    /**
     * Prepara los datos para guardar cambios en una prenda
     */
    public function prepararParaGuardar(array $datosFrontend): array
    {
        try {
            // 1. Validar estructura básica
            $this->validarDatosBasicos($datosFrontend);
            
            // 2. Normalizar datos
            $datosNormalizados = $this->normalizarDatos($datosFrontend);
            
            // 3. Aplicar reglas de negocio
            $datosProcesados = $this->aplicarReglasNegocio($datosNormalizados);
            
            return $datosProcesados;
            
        } catch (\Exception $e) {
            throw new \Exception("Error preparando datos para guardar: {$e->getMessage()}");
        }
    }
    
    /**
     * Verifica si la cotización es de tipo Reflectivo o Logo
     */
    private function esTipoReflectivoOLogo(?object $cotizacion): bool
    {
        if (!$cotizacion) {
            return false;
        }
        
        $nombreTipo = $cotizacion->tipo_cotizacion->nombre ?? $cotizacion->tipo_nombre ?? null;
        $tipoId = $cotizacion->tipo_cotizacion_id ?? null;
        
        return in_array($nombreTipo, ['Reflectivo', 'Logo']) || 
               in_array($tipoId, ['Reflectivo', 'Logo', 3, 4]);
    }
    
    /**
     * Valida los datos básicos recibidos del frontend
     */
    private function validarDatosBasicos(array $datos): void
    {
        if (!isset($datos['nombre_prenda']) || empty(trim($datos['nombre_prenda']))) {
            throw new \InvalidArgumentException('El nombre de la prenda es requerido');
        }
        
        if (!isset($datos['origen']) || !in_array($datos['origen'], ['confeccion', 'bodega'])) {
            throw new \InvalidArgumentException('El origen de la prenda es inválido');
        }
        
        // Validar telas si existen
        if (isset($datos['telasAgregadas']) && is_array($datos['telasAgregadas'])) {
            foreach ($datos['telasAgregadas'] as $tela) {
                if (!isset($tela['nombre_tela']) || empty(trim($tela['nombre_tela']))) {
                    throw new \InvalidArgumentException('Todas las telas deben tener nombre');
                }
                
                if (!isset($tela['color']) || empty(trim($tela['color']))) {
                    throw new \InvalidArgumentException('Todas las telas deben tener color');
                }
            }
        }
    }
    
    /**
     * Normaliza los datos del frontend
     */
    private function normalizarDatos(array $datos): array
    {
        $normalizados = $datos;
        
        // Normalizar strings
        $normalizados['nombre_prenda'] = trim($datos['nombre_prenda'] ?? '');
        $normalizados['descripcion'] = trim($datos['descripcion'] ?? '');
        $normalizados['origen'] = $datos['origen'] ?? 'confeccion';
        
        // Normalizar telas
        if (isset($datos['telasAgregadas']) && is_array($datos['telasAgregadas'])) {
            $normalizados['telasAgregadas'] = array_map(function($tela) {
                return [
                    'id' => $tela['id'] ?? null,
                    'nombre_tela' => trim($tela['nombre_tela'] ?? ''),
                    'color' => trim($tela['color'] ?? ''),
                    'referencia' => trim($tela['referencia'] ?? ''),
                    'descripcion' => trim($tela['descripcion'] ?? ''),
                    'grosor' => trim($tela['grosor'] ?? ''),
                    'composicion' => trim($tela['composicion'] ?? ''),
                    'imagenes' => $tela['imagenes'] ?? []
                ];
            }, $datos['telasAgregadas']);
        }
        
        // Normalizar imágenes
        if (isset($datos['imagenes']) && is_array($datos['imagenes'])) {
            $normalizados['imagenes'] = array_filter($datos['imagenes'], function($img) {
                return !empty($img['previewUrl'] ?? $img['url'] ?? '');
            });
        }
        
        return $normalizados;
    }
    
    /**
     * Aplica reglas de negocio específicas
     */
    private function aplicarReglasNegocio(array $datos): array
    {
        // Regla: Si es Reflectivo/Logo, forzar origen = bodega
        if (isset($datos['cotizacion']) && $this->esTipoReflectivoOLogo($datos['cotizacion'])) {
            $datos['origen'] = 'bodega';
        }
        
        // Regla: Validar que las referencias de telas sean únicas por tela-color
        if (isset($datos['telasAgregadas']) && is_array($datos['telasAgregadas'])) {
            $referenciasUsadas = [];
            foreach ($datos['telasAgregadas'] as $tela) {
                $clave = strtolower($tela['nombre_tela'] . '|' . $tela['color']);
                if (!empty($tela['referencia'])) {
                    if (isset($referenciasUsadas[$clave])) {
                        // Ya existe una referencia para esta tela-color
                        // Podríamos lanzar advertencia o tomar acción
                    }
                    $referenciasUsadas[$clave] = $tela['referencia'];
                }
            }
        }
        
        return $datos;
    }
    
    /**
     * Obtiene tipos de manga disponibles
     */
    public function obtenerTiposMangaDisponibles(): array
    {
        try {
            return $this->prendaRepository->obtenerTiposManga();
        } catch (\Exception $e) {
            throw new \Exception("Error obteniendo tipos de manga: {$e->getMessage()}");
        }
    }
    
    /**
     * Valida una prenda completa antes de guardar
     */
    public function validarPrendaCompleta(array $datos): array
    {
        $errores = [];
        
        // Validaciones básicas
        if (empty($datos['nombre_prenda'])) {
            $errores[] = 'El nombre de la prenda es requerido';
        }
        
        if (!in_array($datos['origen'] ?? 'confeccion', ['confeccion', 'bodega'])) {
            $errores[] = 'El origen debe ser confección o bodega';
        }
        
        // Validaciones de telas
        if (isset($datos['telasAgregadas']) && is_array($datos['telasAgregadas'])) {
            foreach ($datos['telasAgregadas'] as $index => $tela) {
                if (empty($tela['nombre_tela'])) {
                    $errores[] = "La tela {$index} debe tener nombre";
                }
                
                if (empty($tela['color'])) {
                    $errores[] = "La tela {$index} debe tener color";
                }
            }
        }
        
        // Validaciones de tallas
        if (isset($datos['tallas']) && is_array($datos['tallas'])) {
            $totalTallas = 0;
            foreach ($datos['tallas'] as $genero => $tallasGenero) {
                if (is_array($tallasGenero)) {
                    $totalTallas += array_sum($tallasGenero);
                }
            }
            
            if (isset($datos['cantidad']) && $totalTallas != $datos['cantidad']) {
                $errores[] = 'La suma de tallas no coincide con la cantidad total';
            }
        }
        
        return $errores;
    }
}
