<?php

namespace App\Application\Services;

use App\Models\Prenda;
use Illuminate\Validation\ValidationException;

/**
 * PrendaService
 * 
 * Servicio que usa ValidadorPrenda para operaciones CRUD
 * Centraliza la lógica de negocio relacionada con prendas
 */
class PrendaService
{
    /**
     * Crear una nueva prenda con validación
     * 
     * @param array $datos
     * @return Prenda
     * @throws ValidationException
     */
    public function crearPrenda(array $datos): Prenda
    {
        // Validar datos usando ValidadorPrenda
        $validacion = ValidadorPrenda::validarPrendaNueva($datos);
        
        if (!$validacion['válido']) {
            throw ValidationException::withMessages([
                'prenda' => $validacion['errores']
            ]);
        }
        
        // Si pasa validación, crear la prenda
        $prenda = new Prenda();
        $prenda->nombre_producto = $datos['nombre_producto'];
        $prenda->descripcion = $datos['descripcion'] ?? null;
        $prenda->genero = $datos['genero'];
        $prenda->origen = $datos['origen'];
        
        // Guardar datos JSON si es necesario
        $prenda->tallas = json_encode($datos['tallas'] ?? []);
        $prenda->cantidadesPorTalla = json_encode($datos['cantidadesPorTalla'] ?? []);
        $prenda->variantes = json_encode($datos['variantes'] ?? []);
        $prenda->procesos = json_encode($datos['procesos'] ?? []);
        
        $prenda->save();
        
        return $prenda;
    }
    
    /**
     * Actualizar una prenda con validación
     * 
     * @param Prenda $prenda
     * @param array $datos
     * @return Prenda
     * @throws ValidationException
     */
    public function actualizarPrenda(Prenda $prenda, array $datos): Prenda
    {
        // Validar datos usando ValidadorPrenda
        $validacion = ValidadorPrenda::validarPrendaNueva($datos);
        
        if (!$validacion['válido']) {
            throw ValidationException::withMessages([
                'prenda' => $validacion['errores']
            ]);
        }
        
        // Si pasa validación, actualizar la prenda
        $prenda->nombre_producto = $datos['nombre_producto'];
        $prenda->descripcion = $datos['descripcion'] ?? null;
        $prenda->genero = $datos['genero'];
        $prenda->origen = $datos['origen'];
        
        // Actualizar datos JSON
        $prenda->tallas = json_encode($datos['tallas'] ?? []);
        $prenda->cantidadesPorTalla = json_encode($datos['cantidadesPorTalla'] ?? []);
        $prenda->variantes = json_encode($datos['variantes'] ?? []);
        $prenda->procesos = json_encode($datos['procesos'] ?? []);
        
        $prenda->save();
        
        return $prenda;
    }
    
    /**
     * Validar una prenda antes de operación crítica
     * Retorna errores sin lanzar excepción
     * 
     * @param array $datos
     * @return array
     */
    public function validarPrenda(array $datos): array
    {
        return ValidadorPrenda::validarPrendaNueva($datos);
    }
    
    /**
     * Obtener validaciones pendientes para una prenda
     * 
     * @param Prenda $prenda
     * @return array
     */
    public function obtenerValidacionesPendientes(Prenda $prenda): array
    {
        $datos = [
            'nombre_producto' => $prenda->nombre_producto,
            'genero' => $prenda->genero,
            'origen' => $prenda->origen,
            'tallas' => json_decode($prenda->tallas ?? '[]', true),
            'cantidadesPorTalla' => json_decode($prenda->cantidadesPorTalla ?? '{}', true),
            'telasAgregadas' => $prenda->telas ?? [],
            'imagenes' => $prenda->imagenes ?? [],
            'variantes' => json_decode($prenda->variantes ?? '{}', true),
            'procesos' => json_decode($prenda->procesos ?? '{}', true)
        ];
        
        return ValidadorPrenda::obtenerValidacionesPendientes($datos);
    }
    
    /**
     * Validar formulario rápido (campos visibles)
     * 
     * @param array $datos
     * @return array
     */
    public function validarFormularioRápido(array $datos): array
    {
        return ValidadorPrenda::validarFormularioRápido($datos);
    }
    
    /**
     * Obtener resumen de validación para UI
     * 
     * @param array $datos
     * @return array
     */
    public function obtenerResumenValidacion(array $datos): array
    {
        $validacion = ValidadorPrenda::validarPrendaNueva($datos);
        $pendientes = ValidadorPrenda::obtenerValidacionesPendientes($datos);
        
        return [
            'válido' => $validacion['válido'],
            'errores' => $validacion['errores'],
            'pendientes' => $pendientes,
            'total_errores' => count($validacion['errores']),
            'total_pendientes' => count($pendientes),
            'porcentaje_completado' => round((10 - count($pendientes)) / 10 * 100)
        ];
    }
}
