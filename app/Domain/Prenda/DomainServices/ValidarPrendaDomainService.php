<?php

namespace App\Domain\Prenda\DomainServices;

use App\Domain\Prenda\Entities\Prenda;

class ValidarPrendaDomainService
{
    /**
     * Valida completamente una prenda antes de persistirse
     * Agrupa todas las reglas de validación del dominio
     */
    public function validar(Prenda $prenda): array
    {
        $errores = [];

        // 1. Validación de VO (ya hecha constructores, pero aquí es level agregado)
        $erroresVO = $prenda->validar();
        if (!empty($erroresVO)) {
            $errores = array_merge($errores, $erroresVO);
        }

        // 2. Validación de estado: Prenda debe tener telas
        if ($prenda->telas()->contar() === 0) {
            $errores[] = "Prenda debe tener al menos una tela definida";
        }

        // 3. Validación de bodega: Si origen es bodega, necesita variaciones
        if ($prenda->origen()->esBodega()) {
            if ($prenda->variaciones()->contar() === 0) {
                $errores[] = "Prendas de bodega deben tener variaciones (tallas y colores)";
            }

            if ($prenda->procesos()->contar() === 0) {
                // En bodega, procesos pueden ser opcionales según política
                // Pero al menos el tipo de cotización fue validado
            }
        }

        // 4. Validación de confección: Diferentes reglas
        if ($prenda->origen()->esConfeccion()) {
            // Confección puede no tener variaciones todavía (se crean después)
            // Pero sí debe tener tipo de cotización válido
        }

        // 5. Validación de consistencia: origen debe coincidir con tipo cotización
        $servicioOrigen = new AplicarOrigenAutomaticoDomainService();
        if (!$servicioOrigen->esOrigenesConsistente($prenda)) {
            $errores[] = "El origen no coincide con el tipo de cotización especificado";
        }

        return $errores;
    }

    /**
     * Valida solo datos de cotización (fase temprana)
     */
    public function validarCotizacion(Prenda $prenda): array
    {
        $errores = [];

        if ($prenda->telas()->contar() === 0) {
            $errores[] = "Debe seleccionar al menos una tela";
        }

        // Otras validaciones de fase inicial...

        return $errores;
    }

    /**
     * Valida que Prenda esté lista para producción (bodega)
     */
    public function validarParaBodega(Prenda $prenda): array
    {
        $errores = [];

        if (!$prenda->origen()->esBodega()) {
            $errores[] = "Solo prendas de bodega pueden pasar a producción";
            return $errores;
        }

        if ($prenda->variaciones()->contar() === 0) {
            $errores[] = "Debe definir variaciones (tallas y colores) antes de enviar a bodega";
        }

        if ($prenda->telas()->contar() === 0) {
            $errores[] = "Debe seleccionar al menos una tela";
        }

        return $errores;
    }

    public function esValida(Prenda $prenda): bool
    {
        return count($this->validar($prenda)) === 0;
    }
}
