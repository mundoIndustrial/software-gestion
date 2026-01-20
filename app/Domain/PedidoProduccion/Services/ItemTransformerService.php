<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Support\Facades\Log;

/**
 * Servicio de Dominio para transformar datos de items
 * Convierte datos del frontend al formato esperado por los servicios de aplicación
 */
class ItemTransformerService
{
    /**
     * Procesar cantidad_talla desde el frontend
     * Maneja dos formatos:
     * - Antiguo: {genero-talla: cantidad} -> Convierte a {genero: {talla: cantidad}}
     * - Nuevo: {genero: {talla: cantidad}} -> Retorna como está
     */
    public function procesarCantidadTalla($cantidadTalla): array
    {
        // Si es string JSON, decodificar
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
        }

        // Si no es array, retornar vacío
        if (!is_array($cantidadTalla)) {
            return [];
        }

        // Si está vacío, retornar vacío
        if (empty($cantidadTalla)) {
            return [];
        }

        $resultado = [];
        $esFormatoNuevo = false;

        // Detectar formato: si la primera clave es un género (sin guiones) y su valor es array, es formato nuevo
        foreach ($cantidadTalla as $clave => $valor) {
            if (is_array($valor) && !strpos($clave, '-')) {
                // Formato nuevo: {genero: {talla: cantidad}}
                $esFormatoNuevo = true;
                break;
            }
        }

        if ($esFormatoNuevo) {
            // Ya está en formato correcto, solo validar y retornar
            foreach ($cantidadTalla as $genero => $tallas) {
                if (is_array($tallas)) {
                    $resultado[$genero] = [];
                    foreach ($tallas as $talla => $cantidad) {
                        $resultado[$genero][$talla] = (int)$cantidad;
                    }
                }
            }
        } else {
            // Formato antiguo: {genero-talla: cantidad} -> Convertir a nuevo
            foreach ($cantidadTalla as $claveTalla => $cantidad) {
                if (strpos($claveTalla, '-') !== false) {
                    [$genero, $talla] = explode('-', $claveTalla, 2);
                } else {
                    $genero = 'U';
                    $talla = $claveTalla;
                }

                $genero = trim($genero);
                $talla = trim($talla);
                $cantidad = (int)$cantidad;

                if (!isset($resultado[$genero])) {
                    $resultado[$genero] = [];
                }
                $resultado[$genero][$talla] = $cantidad;
            }
        }

        return $resultado;
    }

    /**
     * Procesar tallas desde el frontend
     * Convierte array de tallas a estructura {genero: {talla: cantidad}}
     */
    public function procesarTallas(array $tallas): array
    {
        $resultado = [];

        foreach ($tallas as $talla) {
            if (isset($talla['genero']) && isset($talla['talla']) && isset($talla['cantidad'])) {
                if (!isset($resultado[$talla['genero']])) {
                    $resultado[$talla['genero']] = [];
                }
                $resultado[$talla['genero']][$talla['talla']] = (int)$talla['cantidad'];
            }
        }

        return $resultado;
    }

    /**
     * Calcular cantidad total desde cantidad_talla
     */
    public function calcularCantidadDeCantidadTalla(array $cantidadTalla): int
    {
        $total = 0;
        foreach ($cantidadTalla as $cantidad) {
            $total += (int)$cantidad;
        }
        return $total;
    }

    /**
     * Calcular cantidad total de tallas
     */
    public function calcularCantidadDeTallas(array $tallas): int
    {
        $total = 0;
        foreach ($tallas as $talla) {
            if (isset($talla['cantidad'])) {
                $total += (int)$talla['cantidad'];
            }
        }
        return $total;
    }

    /**
     * Extraer observaciones desde variaciones JSON
     */
    public function extraerObservacionesDeVariaciones(array $item): array
    {
        $obs_manga = $item['obs_manga'] ?? '';
        $obs_bolsillos = $item['obs_bolsillos'] ?? '';
        $obs_broche = $item['obs_broche'] ?? '';
        $obs_reflectivo = $item['obs_reflectivo'] ?? '';

        $variaciones_data = $item['variaciones'] ?? [];

        if (is_string($variaciones_data)) {
            $variaciones_parsed = json_decode($variaciones_data, true);

            if (is_array($variaciones_parsed)) {
                if (empty($obs_manga) && isset($variaciones_parsed['manga']['observacion'])) {
                    $obs_manga = $variaciones_parsed['manga']['observacion'];
                }
                if (empty($obs_bolsillos) && isset($variaciones_parsed['bolsillos']['observacion'])) {
                    $obs_bolsillos = $variaciones_parsed['bolsillos']['observacion'];
                }
                if (empty($obs_broche) && isset($variaciones_parsed['broche']['observacion'])) {
                    $obs_broche = $variaciones_parsed['broche']['observacion'];
                }
                if (empty($obs_reflectivo) && isset($variaciones_parsed['reflectivo']['observacion'])) {
                    $obs_reflectivo = $variaciones_parsed['reflectivo']['observacion'];
                }
            }
        }

        return [
            'obs_manga' => $obs_manga,
            'obs_bolsillos' => $obs_bolsillos,
            'obs_broche' => $obs_broche,
            'obs_reflectivo' => $obs_reflectivo,
        ];
    }

    /**
     * Determinar de_bodega desde origen
     */
    public function determinardeBodega(array $item): int
    {
        if (isset($item['de_bodega'])) {
            return (int)$item['de_bodega'];
        }

        $origen = $item['origen'] ?? 'bodega';
        return $origen === 'bodega' ? 1 : 0;
    }

    /**
     * Transformar item a formato esperado por PedidoPrendaService
     */
    public function transformarItemAPrenda(
        array $item,
        array $fotosFiltered,
        array $procesosReconstruidos,
        array $telasConImagenes,
        ?int $tipo_manga_id = null,
        ?int $tipo_broche_boton_id = null
    ): array
    {
        $observaciones = $this->extraerObservacionesDeVariaciones($item);
        $deBodega = $this->determinardeBodega($item);

        $prendaData = [
            'nombre_producto' => $item['nombre_producto'],
            'descripcion' => $item['descripcion'] ?? '',
            'variaciones' => $item['variaciones'] ?? [],
            'fotos' => $fotosFiltered,
            'procesos' => $procesosReconstruidos,
            'origen' => $item['origen'] ?? 'bodega',
            'de_bodega' => $deBodega,
            'obs_manga' => $observaciones['obs_manga'],
            'obs_bolsillos' => $observaciones['obs_bolsillos'],
            'obs_broche' => $observaciones['obs_broche'],
            'obs_reflectivo' => $observaciones['obs_reflectivo'],
            'tipo_manga_id' => $tipo_manga_id,
            'tipo_broche_boton_id' => $tipo_broche_boton_id,
            'telas' => $telasConImagenes,
        ];

        // Procesar tallas según el tipo de item
        $tipo = $item['tipo'] ?? 'cotizacion';
        if ($tipo === 'nuevo' || $tipo === 'prenda_nueva') {
            $prendaData['cantidad_talla'] = $this->procesarCantidadTalla($item['cantidad_talla'] ?? []);
        } else {
            $prendaData['cantidad_talla'] = $this->procesarTallas($item['tallas'] ?? []);
        }

        return $prendaData;
    }

    /**
     * Copiar tallas desde cantidad_talla a procesos
     * Maneja ambos formatos:
     * - Nuevo: {genero: {talla: cantidad}}
     * - Antiguo: {genero-talla: cantidad}
     */
    public function copiarTallasAProcesos(array $procesos, array $cantidadTalla): array
    {
        if (empty($cantidadTalla)) {
            return $procesos;
        }

        // Si es string JSON, decodificar
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
        }

        $tallas_dama = [];
        $tallas_caballero = [];

        // Detectar formato: si la primera clave es un género (sin guiones) y su valor es array, es formato nuevo
        $esFormatoNuevo = false;
        foreach ($cantidadTalla as $clave => $valor) {
            if (is_array($valor) && !strpos($clave, '-')) {
                $esFormatoNuevo = true;
                break;
            }
        }

        if ($esFormatoNuevo) {
            // Formato nuevo: {genero: {talla: cantidad}}
            if (isset($cantidadTalla['dama']) && is_array($cantidadTalla['dama'])) {
                $tallas_dama = $cantidadTalla['dama'];
            }
            if (isset($cantidadTalla['caballero']) && is_array($cantidadTalla['caballero'])) {
                $tallas_caballero = $cantidadTalla['caballero'];
            }
        } else {
            // Formato antiguo: {genero-talla: cantidad}
            foreach ($cantidadTalla as $clave => $cantidad) {
                if (is_string($clave) && strpos($clave, 'dama-') === 0) {
                    $talla = str_replace('dama-', '', $clave);
                    $tallas_dama[$talla] = $cantidad;
                } elseif (is_string($clave) && strpos($clave, 'caballero-') === 0) {
                    $talla = str_replace('caballero-', '', $clave);
                    $tallas_caballero[$talla] = $cantidad;
                }
            }
        }

        foreach ($procesos as &$proceso) {
            if (!isset($proceso['tallas'])) {
                $proceso['tallas'] = [];
            }
            if (!empty($tallas_dama)) {
                $proceso['tallas']['dama'] = $tallas_dama;
            }
            if (!empty($tallas_caballero)) {
                $proceso['tallas']['caballero'] = $tallas_caballero;
            }
        }

        return $procesos;
    }
}
