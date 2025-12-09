<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExportarCorteController extends Controller
{
    /**
     * Mostrar la vista para exportar datos de corte
     */
    public function index()
    {
        return view('exportar-corte.index');
    }

    /**
     * Generar el reporte de corte para un mes específico
     */
    public function generate(Request $request)
    {
        $request->validate([
            'mes' => 'required|integer|min:1|max:12',
            'año' => 'required|integer|min:2020|max:2099',
        ]);

        $mes = $request->input('mes');
        $año = $request->input('año');

        // Obtener todos los pedidos que pasaron a corte en el mes especificado
        $pedidos = DB::table('tabla_original')
            ->whereNotNull('corte')
            ->get();

        // Filtrar por mes y año en PHP (por si el campo está en formato string)
        $pedidosFiltrados = [];
        foreach ($pedidos as $pedido) {
            try {
                $fechaCorte = Carbon::parse($pedido->corte);
                if ($fechaCorte->month == $mes && $fechaCorte->year == $año) {
                    $pedidosFiltrados[] = $pedido;
                }
            } catch (\Exception $e) {
                // Ignorar fechas inválidas
                continue;
            }
        }

        if (empty($pedidosFiltrados)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay pedidos en corte para el mes y año especificados',
                'data' => ''
            ]);
        }

        // Ordenar por fecha de corte
        usort($pedidosFiltrados, function ($a, $b) {
            $fechaA = Carbon::parse($a->corte);
            $fechaB = Carbon::parse($b->corte);
            return $fechaA->timestamp <=> $fechaB->timestamp;
        });

        // Generar el reporte
        $lineas = [];
        $lineas[] = $this->generarEncabezados();

        $contadorRegistros = 0;
        foreach ($pedidosFiltrados as $pedido) {
            $linea = $this->generarLinea($pedido);
            if ($linea !== null) {
                // Validar que la línea tenga exactamente 10 columnas
                $columnas = explode("\t", $linea);
                if (count($columnas) === 10) {
                    $lineas[] = $linea;
                    $contadorRegistros++;
                }
            }
        }

        $contenido = implode("\n", $lineas);

        return response()->json([
            'success' => true,
            'data' => $contenido,
            'message' => "Reporte generado exitosamente. {$contadorRegistros} registros encontrados."
        ]);
    }

    /**
     * Generar la línea de encabezados
     */
    private function generarEncabezados()
    {
        return implode("\t", [
            'Fecha de Ingreso a Corte',
            'Número Pedido',
            'Cliente',
            'Prendas',
            'Descripción',
            'Tallas',
            'Total',
            'Cortador',
            'Fecha Terminación',
            'Género'
        ]);
    }

    /**
     * Generar una línea de datos para un pedido
     */
    private function generarLinea($pedido)
    {
        // Obtener los registros por orden
        $registros = DB::table('registros_por_orden')
            ->where('pedido', $pedido->pedido)
            ->get();

        if ($registros->isEmpty()) {
            return null;
        }

        // Fecha de ingreso a corte
        $fechaIngreso = Carbon::parse($pedido->corte)->format('d/m/Y');

        // Número de pedido
        $numeroPedido = $pedido->pedido;

        // Cliente
        $cliente = $this->limpiarDato($pedido->cliente ?? '');

        // Prendas (nombres únicos)
        $prendas = $this->limpiarDato(
            $registros->pluck('prenda')->unique()->implode(', ')
        );

        // Descripciones (concatenadas)
        $descripciones = $this->limpiarDato(
            $registros->pluck('descripcion')->unique()->implode(' | ')
        );

        // Tallas (todas las tallas)
        $tallas = $this->limpiarDato(
            $registros->pluck('talla')->unique()->implode(', ')
        );

        // Total (suma de cantidades)
        $total = $registros->sum(function ($r) {
            return (int) $r->cantidad;
        });

        // Cortador
        $cortador = $this->limpiarDato($pedido->encargados_de_corte ?? '');

        // Fecha de terminación (siguiente fecha de cambio de área después de corte)
        $fechaTerminacion = $this->obtenerFechaTerminacion($pedido);

        // Género (analizar prendas y descripciones)
        $genero = $this->detectarGenero($prendas, $descripciones);

        return implode("\t", [
            $fechaIngreso,
            $numeroPedido,
            $cliente,
            $prendas,
            $descripciones,
            $tallas,
            $total,
            $cortador,
            $fechaTerminacion,
            $genero
        ]);
    }

    /**
     * Limpiar datos para evitar que rompan el formato TSV
     */
    private function limpiarDato($dato)
    {
        // Remover saltos de línea, tabulaciones y espacios múltiples
        $dato = preg_replace('/[\r\n\t]+/', ' ', $dato);
        // Remover espacios múltiples
        $dato = preg_replace('/\s+/', ' ', $dato);
        // Trim
        $dato = trim($dato);
        return $dato;
    }

    /**
     * Obtener la fecha de terminación (siguiente cambio de área después de corte)
     */
    private function obtenerFechaTerminacion($pedido)
    {
        try {
            $fechaCorte = Carbon::parse($pedido->corte);
        } catch (\Exception $e) {
            return '';
        }

        // Buscar la siguiente fecha de cambio de área después de corte
        $campos = [
            'costura' => $pedido->costura,
            'bordado' => $pedido->bordado,
            'estampado' => $pedido->estampado,
            'lavanderia' => $pedido->lavanderia,
            'arreglos' => $pedido->arreglos,
            'control_de_calidad' => $pedido->control_de_calidad,
            'entrega' => $pedido->entrega,
        ];

        $fechasSiguientes = [];

        foreach ($campos as $campo => $fecha) {
            if (!empty($fecha)) {
                try {
                    $fechaObj = Carbon::parse($fecha);
                    // Solo considerar fechas posteriores a la de corte
                    if ($fechaObj->isAfter($fechaCorte)) {
                        $fechasSiguientes[] = $fechaObj;
                    }
                } catch (\Exception $e) {
                    // Ignorar fechas inválidas
                    continue;
                }
            }
        }

        if (empty($fechasSiguientes)) {
            return '';
        }

        // Obtener la fecha más cercana (la primera después de corte)
        $fechaMasCercana = min($fechasSiguientes);

        return $fechaMasCercana->format('d/m/Y');
    }

    /**
     * Detectar el género basado en prendas y descripciones
     */
    private function detectarGenero($prendas, $descripciones)
    {
        $texto = strtolower($prendas . ' ' . $descripciones);

        $tieneDama = preg_match('/\bdama\b|\bmujer\b|\bfemenino\b/', $texto);
        $tieneCaballero = preg_match('/\bcaballero\b|\bhombre\b|\bmasculino\b/', $texto);

        if ($tieneDama && $tieneCaballero) {
            return 'Dama, Caballero';
        } elseif ($tieneDama) {
            return 'Dama';
        } elseif ($tieneCaballero) {
            return 'Caballero';
        } else {
            return 'No aplica';
        }
    }
}
