<?php

namespace App\Domain\PedidoProduccion\Services;

/**
 * Servicio para utilidades y conversiones
 * Responsabilidad: Funciones helper para convertir formatos y procesar datos
 */
class UtilitariosService
{
    /**
     * Convertir especificaciones del formato antiguo al nuevo
     */
    public function convertirEspecificacionesAlFormatoNuevo($especificaciones): array
    {
        if (!$especificaciones) {
            return [];
        }

        // Si ya está en formato nuevo
        if (is_array($especificaciones) && isset($especificaciones['forma_pago'])) {
            return $especificaciones;
        }

        // Parsear si es string
        if (is_string($especificaciones)) {
            $datos = json_decode($especificaciones, true) ?? [];
        } else {
            $datos = $especificaciones;
        }

        // Si ya está en formato nuevo, devolver
        if (isset($datos['forma_pago'])) {
            return $datos;
        }

        // Convertir del formato antiguo tabla_orden[field]
        $convertidas = [
            'forma_pago' => [],
            'disponibilidad' => [],
            'regimen' => [],
            'se_ha_vendido' => [],
            'ultima_venta' => [],
            'flete' => []
        ];

        // Mapeos de nombres para conversión
        $mapeoFormaPago = [
            'tabla_orden[contado]' => 'Contado',
            'tabla_orden[credito]' => 'Crédito',
        ];

        $mapeoDisponibilidad = [
            'tabla_orden[bodega]' => 'Bodega',
            'tabla_orden[cucuta]' => 'Cúcuta',
            'tabla_orden[lafayette]' => 'Lafayette',
            'tabla_orden[fabrica]' => 'Fábrica',
        ];

        $mapeoRegimen = [
            'tabla_orden[comun]' => 'Común',
            'tabla_orden[simplificado]' => 'Simplificado',
        ];

        // Procesar FORMA_PAGO
        foreach ($mapeoFormaPago as $clave => $etiqueta) {
            if (isset($datos[$clave]) && ($datos[$clave] === '1' || $datos[$clave] === true)) {
                $convertidas['forma_pago'][] = $etiqueta;
            }
        }

        // Procesar DISPONIBILIDAD
        foreach ($mapeoDisponibilidad as $clave => $etiqueta) {
            if (isset($datos[$clave]) && ($datos[$clave] === '1' || $datos[$clave] === true)) {
                $convertidas['disponibilidad'][] = $etiqueta;
            }
        }

        // Procesar RÉGIMEN
        foreach ($mapeoRegimen as $clave => $etiqueta) {
            if (isset($datos[$clave]) && ($datos[$clave] === '1' || $datos[$clave] === true)) {
                $convertidas['regimen'][] = $etiqueta;
            }
        }

        // Remover campos vacíos
        foreach ($convertidas as $key => $value) {
            if (empty($value)) {
                unset($convertidas[$key]);
            }
        }

        return $convertidas;
    }

    /**
     * Procesar múltiples géneros desde el input
     * Convierte string, array o JSON a un array limpio
     */
    public function procesarGeneros($generoInput): array
    {
        $generos = [];

        if (is_array($generoInput)) {
            $generos = array_filter($generoInput, fn($g) => !empty($g));
        } elseif (is_string($generoInput)) {
            // Intentar parsear como JSON
            $parsed = json_decode($generoInput, true);
            if (is_array($parsed)) {
                $generos = array_filter($parsed, fn($g) => !empty($g));
            } else {
                // Si no es JSON, dividir por comas
                $generos = array_filter(
                    array_map('trim', explode(',', $generoInput)),
                    fn($g) => !empty($g)
                );
            }
        }

        // Eliminar duplicados y resetear índices
        return array_values(array_unique(array_filter($generos)));
    }
}
