<?php

namespace App\Infrastructure\Services\RegistrosOrdenes;

use App\Domain\RegistrosOrdenes\Contracts\DescripcionOrdenService;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * DescripcionOrdenServiceImpl
 * 
 * Implementación para construcción de descripciones
 * Extrae la lógica de buildDescripcionConTallas del controlador
 */
class DescripcionOrdenServiceImpl implements DescripcionOrdenService
{
    public function construirConTallas(PedidoProduccion $orden): string
    {
        $descripcionBase = $orden->descripcion_prendas ?? '';

        Log::info('[DescripcionOrdenService] Descripción recibida', [
            'pedido' => $orden->numero_pedido,
            'longitud' => strlen($descripcionBase),
            'es_html' => strpos($descripcionBase, '<span') !== false,
        ]);

        // Si ya está en HTML, devolverla tal cual
        if (strpos($descripcionBase, '<span') !== false) {
            return $descripcionBase;
        }

        // Verificar si es reflectivo
        $esReflectivo = $orden->cotizacion && $orden->cotizacion->tipoCotizacion 
            ? ($orden->cotizacion->tipoCotizacion->codigo === 'RF')
            : false;

        // Generar dinámicamente si está vacía
        if (empty($descripcionBase) && $orden->prendas && $orden->prendas->count() > 0) {
            $descripcionBase = $this->generarDesdePrendas($orden);
        }

        if (empty($descripcionBase)) {
            return '';
        }

        return $esReflectivo 
            ? $this->procesarReflectivo($orden)
            : $this->procesarNormal($descripcionBase);
    }

    public function generarDesdePrendas(PedidoProduccion $orden): string
    {
        $descripciones = $orden->prendas->map(function($prenda, $index) {
            return $prenda->generarDescripcionDetallada($index + 1);
        })->toArray();

        return implode("\n\n", $descripciones);
    }

    public function procesarReflectivo(PedidoProduccion $orden): string
    {
        $html = '<div style="font-size: 15px !important; line-height: 1.6 !important;">';

        if ($orden->prendas && $orden->prendas->count() > 0) {
            foreach ($orden->prendas as $index => $prenda) {
                $html .= '<div style="margin-bottom: 15px !important;">';
                $html .= '<strong>PRENDA ' . ($index + 1) . ': ' . strtoupper($prenda->nombre_prenda) . '</strong><br>';
                
                if ($prenda->tallas && $prenda->tallas->count() > 0) {
                    foreach ($prenda->tallas as $talla) {
                        $html .= 'Talla ' . $talla->talla . ': ' . $talla->cantidad . ' unidades<br>';
                    }
                }
                
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    public function procesarNormal(string $descripcionBase): string
    {
        // Parsear por "PRENDA X:"
        if (strpos($descripcionBase, 'PRENDA ') !== false) {
            return $descripcionBase;
        }

        // Si no tiene estructura de prenda, devolverla tal cual
        return $descripcionBase;
    }
}
