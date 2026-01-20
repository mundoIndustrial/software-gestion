<?php

namespace App\Application\Services;

/**
 * ValidadorPrenda Service
 * 
 * Portabilidad de validadorprenda.js a PHP
 * Centraliza TODAS las validaciones de prendas
 * 
 * Uso:
 * $resultado = ValidadorPrenda::validarPrendaNueva($prenda);
 * if ($resultado['válido']) {
 *     // Guardar en BD
 * } else {
 *     // Mostrar errores
 * }
 */
class ValidadorPrenda
{
    // Géneros válidos
    const GENEROS_VALIDOS = ['dama', 'caballero', 'unisex'];
    
    // Orígenes válidos
    const ORIGENES_VALIDOS = ['bodega', 'proveedor', 'produccion'];
    
    // Tipos de manga
    const TIPOS_MANGA_VALIDOS = ['corta', 'larga', 'media', 'No aplica'];
    
    // Tipos de broche
    const TIPOS_BROCHE_VALIDOS = ['botón', 'cremallera', 'gancho', 'No aplica'];
    
    // Tallas numéricas válidas
    const TALLAS_NUMERICAS = [24, 26, 28, 30, 32, 34, 36, 38, 40, 42, 44, 46, 48, 50];
    
    // Tallas letra válidas
    const TALLAS_LETRA = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
    
    /**
     * Validación exhaustiva de una prenda nueva
     * 
     * @param array $prenda
     * @return array { 'válido' => boolean, 'errores' => array }
     */
    public static function validarPrendaNueva(array $prenda): array
    {
        $errores = [];
        
        // 1. Validar nombre
        if (!isset($prenda['nombre_producto']) || empty(trim($prenda['nombre_producto'] ?? ''))) {
            $errores[] = 'El nombre de la prenda es requerido';
        }
        
        // 2. Validar género
        if (!isset($prenda['genero']) || !in_array($prenda['genero'], self::GENEROS_VALIDOS)) {
            $errores[] = 'Género inválido. Debe ser: ' . implode(', ', self::GENEROS_VALIDOS);
        }
        
        // 3. Validar origen
        if (!isset($prenda['origen']) || !in_array($prenda['origen'], self::ORIGENES_VALIDOS)) {
            $errores[] = 'Origen inválido. Debe ser: ' . implode(', ', self::ORIGENES_VALIDOS);
        }
        
        // 4. Validar tallas
        $validacionTallas = self::validarTallas($prenda['tallas'] ?? []);
        if (!$validacionTallas['válido']) {
            $errores = array_merge($errores, $validacionTallas['errores']);
        }
        
        // 5. Validar cantidades por talla
        $validacionCantidades = self::validarCantidadesPorTalla($prenda['cantidadesPorTalla'] ?? []);
        if (!$validacionCantidades['válido']) {
            $errores = array_merge($errores, $validacionCantidades['errores']);
        }
        
        // 6. Validar géneros con tallas
        $validacionGeneros = self::validarGenerosConTallas($prenda['generosConTallas'] ?? []);
        if (!$validacionGeneros['válido']) {
            $errores = array_merge($errores, $validacionGeneros['errores']);
        }
        
        // 7. Validar procesos
        $validacionProcesos = self::validarProcesos($prenda['procesos'] ?? []);
        if (!$validacionProcesos['válido']) {
            $errores = array_merge($errores, $validacionProcesos['errores']);
        }
        
        // 8. Validar variaciones
        $validacionVariaciones = self::validarVariaciones($prenda['variantes'] ?? []);
        if (!$validacionVariaciones['válido']) {
            $errores = array_merge($errores, $validacionVariaciones['errores']);
        }
        
        // 9. Validar telas
        $validacionTelas = self::validarTelas($prenda['telasAgregadas'] ?? []);
        if (!$validacionTelas['válido']) {
            $errores = array_merge($errores, $validacionTelas['errores']);
        }
        
        // 10. Validar imágenes
        $validacionImagenes = self::validarImagenes($prenda['imagenes'] ?? []);
        if (!$validacionImagenes['válido']) {
            $errores = array_merge($errores, $validacionImagenes['errores']);
        }
        
        return [
            'válido' => count($errores) === 0,
            'errores' => $errores
        ];
    }
    
    /**
     * Validación rápida (frontend) - solo campos visibles
     * 
     * @param array $datos
     * @return array { 'válido' => boolean, 'errores' => array }
     */
    public static function validarFormularioRápido(array $datos = []): array
    {
        $errores = [];
        
        // Validar nombre
        if (empty(trim($datos['nombrePrenda'] ?? ''))) {
            $errores[] = 'El nombre de la prenda es requerido';
        }
        
        // Validar origen
        $origen = $datos['origen'] ?? null;
        if (!in_array($origen, self::ORIGENES_VALIDOS)) {
            $errores[] = 'Origen inválido';
        }
        
        return [
            'válido' => count($errores) === 0,
            'errores' => $errores
        ];
    }
    
    /**
     * Validar tallas
     * 
     * @param array $tallas
     * @return array { 'válido' => boolean, 'errores' => array }
     */
    public static function validarTallas(array $tallas): array
    {
        $errores = [];
        
        if (empty($tallas)) {
            $errores[] = 'Debe seleccionar al menos una talla';
            return ['válido' => false, 'errores' => $errores];
        }
        
        foreach ($tallas as $tallaData) {
            // Validar género
            if (!isset($tallaData['genero']) || !in_array($tallaData['genero'], self::GENEROS_VALIDOS)) {
                $errores[] = "Género inválido en tallas: {$tallaData['genero']}";
            }
            
            // Validar que hay tallas
            if (empty($tallaData['tallas'] ?? [])) {
                $errores[] = "El género {$tallaData['genero']} debe tener al menos una talla";
            }
            
            // Validar tipo de talla
            if (!isset($tallaData['tipo']) || !in_array($tallaData['tipo'], ['letra', 'numero'])) {
                $errores[] = "Tipo de talla inválido: {$tallaData['tipo']}";
            }
        }
        
        return [
            'válido' => count($errores) === 0,
            'errores' => $errores
        ];
    }
    
    /**
     * Validar cantidades por talla
     * 
     * @param array $cantidades
     * @return array { 'válido' => boolean, 'errores' => array }
     */
    public static function validarCantidadesPorTalla(array $cantidades): array
    {
        $errores = [];
        
        if (empty($cantidades)) {
            $errores[] = 'Debe especificar cantidades para al menos una talla';
            return ['válido' => false, 'errores' => $errores];
        }
        
        foreach ($cantidades as $tallaKey => $cantidad) {
            // Validar que es numérico
            if (!is_numeric($cantidad)) {
                $errores[] = "La cantidad para $tallaKey debe ser numérica, recibido: $cantidad";
            }
            
            // Validar que no es negativa
            if ($cantidad < 0) {
                $errores[] = "La cantidad para $tallaKey no puede ser negativa";
            }
            
            // Validar que no es decimal
            if (!is_int($cantidad)) {
                $errores[] = "La cantidad para $tallaKey debe ser un número entero";
            }
        }
        
        return [
            'válido' => count($errores) === 0,
            'errores' => $errores
        ];
    }
    
    /**
     * Validar géneros con tallas
     * 
     * @param array $generosConTallas
     * @return array { 'válido' => boolean, 'errores' => array }
     */
    public static function validarGenerosConTallas(array $generosConTallas): array
    {
        $errores = [];
        
        if (empty($generosConTallas)) {
            $errores[] = 'Debe seleccionar géneros con tallas';
            return ['válido' => false, 'errores' => $errores];
        }
        
        foreach ($generosConTallas as $genero => $tallas) {
            // Validar género
            if (!in_array($genero, self::GENEROS_VALIDOS)) {
                $errores[] = "Género inválido: $genero";
            }
            
            // Validar que el género tiene tallas
            if (empty($tallas)) {
                $errores[] = "El género $genero debe tener al menos una talla";
            }
        }
        
        return [
            'válido' => count($errores) === 0,
            'errores' => $errores
        ];
    }
    
    /**
     * Validar procesos
     * 
     * @param array $procesos
     * @return array { 'válido' => boolean, 'errores' => array }
     */
    public static function validarProcesos(array $procesos): array
    {
        $errores = [];
        
        // Los procesos pueden estar vacíos (es opcional)
        
        foreach ($procesos as $nombreProceso => $valor) {
            // Validar que los valores son booleanos
            if (!is_bool($valor) && $valor !== 1 && $valor !== 0) {
                $errores[] = "El proceso '$nombreProceso' debe ser booleano";
            }
        }
        
        return [
            'válido' => count($errores) === 0,
            'errores' => $errores
        ];
    }
    
    /**
     * Validar variaciones
     * 
     * @param array $variaciones
     * @return array { 'válido' => boolean, 'errores' => array }
     */
    public static function validarVariaciones(array $variaciones): array
    {
        $errores = [];
        
        // Validar tipo de manga
        if (isset($variaciones['tipo_manga'])) {
            if (!in_array($variaciones['tipo_manga'], self::TIPOS_MANGA_VALIDOS)) {
                $errores[] = "Tipo de manga inválido: {$variaciones['tipo_manga']}";
            }
        }
        
        // Validar tipo de broche
        if (isset($variaciones['tipo_broche'])) {
            if (!in_array($variaciones['tipo_broche'], self::TIPOS_BROCHE_VALIDOS)) {
                $errores[] = "Tipo de broche inválido: {$variaciones['tipo_broche']}";
            }
        }
        
        // Validar booleanos
        $booleanFields = ['tiene_bolsillos', 'tiene_reflectivo'];
        foreach ($booleanFields as $field) {
            if (isset($variaciones[$field]) && !is_bool($variaciones[$field]) && $variaciones[$field] !== 1 && $variaciones[$field] !== 0) {
                $errores[] = "El campo '$field' debe ser booleano";
            }
        }
        
        return [
            'válido' => count($errores) === 0,
            'errores' => $errores
        ];
    }
    
    /**
     * Validar telas
     * 
     * @param array $telas
     * @return array { 'válido' => boolean, 'errores' => array }
     */
    public static function validarTelas(array $telas): array
    {
        $errores = [];
        
        // Las telas pueden estar vacías (es opcional)
        
        foreach ($telas as $tela) {
            // Validar nombre
            if (empty(trim($tela['nombre'] ?? ''))) {
                $errores[] = 'Cada tela debe tener un nombre';
            }
            
            // Validar color
            if (empty(trim($tela['color'] ?? ''))) {
                $errores[] = 'Cada tela debe tener un color';
            }
        }
        
        return [
            'válido' => count($errores) === 0,
            'errores' => $errores
        ];
    }
    
    /**
     * Validar imágenes
     * 
     * @param array $imagenes
     * @return array { 'válido' => boolean, 'errores' => array }
     */
    public static function validarImagenes(array $imagenes): array
    {
        $errores = [];
        
        // Las imágenes pueden estar vacías (es opcional)
        
        foreach ($imagenes as $imagen) {
            $url = $imagen['url'] ?? $imagen['ruta'] ?? '';
            
            // Validar URL
            if (empty(trim($url))) {
                $errores[] = 'Cada imagen debe tener una URL válida';
            }
            
            // Validar que es una URL válida
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errores[] = "URL de imagen inválida: $url";
            }
        }
        
        return [
            'válido' => count($errores) === 0,
            'errores' => $errores
        ];
    }
    
    /**
     * Obtener validaciones pendientes
     * 
     * @param array $prenda
     * @return array de campos incompletos
     */
    public static function obtenerValidacionesPendientes(array $prenda): array
    {
        $pendientes = [];
        
        // Campos básicos
        if (empty($prenda['nombre_producto'] ?? null)) {
            $pendientes[] = 'nombre_producto';
        }
        if (empty($prenda['genero'] ?? null)) {
            $pendientes[] = 'genero';
        }
        if (empty($prenda['origen'] ?? null)) {
            $pendientes[] = 'origen';
        }
        
        // Tallas
        if (empty($prenda['tallas'] ?? null)) {
            $pendientes[] = 'tallas';
        }
        
        // Cantidades
        if (empty($prenda['cantidadesPorTalla'] ?? null)) {
            $pendientes[] = 'cantidadesPorTalla';
        }
        
        // Telas
        if (empty($prenda['telasAgregadas'] ?? null)) {
            $pendientes[] = 'telasAgregadas';
        }
        
        // Imágenes
        if (empty($prenda['imagenes'] ?? null)) {
            $pendientes[] = 'imagenes';
        }
        
        return $pendientes;
    }
    
    /**
     * Comparar validaciones: frontend vs backend
     * Útil para debugging de discrepancias
     * 
     * @param array $prendaFrontend
     * @param array $prendaBackend
     * @return array { 'coinciden' => boolean, 'diferencias' => array }
     */
    public static function compararValidaciones(array $prendaFrontend, array $prendaBackend): array
    {
        $validacionFrontend = self::validarPrendaNueva($prendaFrontend);
        $validacionBackend = self::validarPrendaNueva($prendaBackend);
        
        $diferencias = [];
        
        if ($validacionFrontend['válido'] !== $validacionBackend['válido']) {
            $diferencias[] = 'Estados de validez no coinciden';
        }
        
        if ($validacionFrontend['errores'] !== $validacionBackend['errores']) {
            $diferencias[] = 'Errores de validación no coinciden';
        }
        
        return [
            'coinciden' => count($diferencias) === 0,
            'diferencias' => $diferencias,
            'validacionFrontend' => $validacionFrontend,
            'validacionBackend' => $validacionBackend
        ];
    }
}
