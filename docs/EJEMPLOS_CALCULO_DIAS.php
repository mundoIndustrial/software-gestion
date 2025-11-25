<?php

/**
 * EJEMPLO DE INTEGRACIÓN DEL CÁLCULO DE DÍAS EN CONTROLLERS
 * 
 * Este archivo muestra cómo usar el nuevo sistema de cálculo de días
 * en tus controllers existentes.
 */

namespace App\Http\Controllers\Examples;

use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Traits\CalculaDiasHelper;
use App\Services\CalculadorDiasService;

/**
 * EJEMPLO 1: Mostrar información de un pedido con días
 */
class PedidoControllerExample
{
    use CalculaDiasHelper;

    public function show($id)
    {
        $pedido = PedidoProduccion::with([
            'prendas',
            'procesos',
            'asesora',
            'clienteRelacion'
        ])->find($id);

        if (!$pedido) {
            abort(404);
        }

        // Usar el trait para obtener información de días
        $infoDias = $this->formatearRespuestaDias($pedido);

        // Retornar con información completa
        return view('pedidos.show', [
            'pedido' => $pedido,
            'dias' => $infoDias,
            'desglose' => $pedido->getDesgloseDiasPorProceso(),
        ]);
    }
}

/**
 * EJEMPLO 2: API JSON con información de días
 */
class PedidoApiControllerExample
{
    public function getDiasInfo($id)
    {
        $pedido = PedidoProduccion::find($id);

        return response()->json([
            'pedido' => [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->clienteRelacion?->nombre,
            ],
            'dias' => [
                'total' => $pedido->getTotalDias(),
                'total_numero' => $pedido->getTotalDiasNumero(),
                'en_retraso' => $pedido->estaEnRetraso(),
                'dias_retraso' => $pedido->getDiasDeRetraso(),
                'fecha_creacion' => $pedido->fecha_de_creacion_de_orden?->format('Y-m-d'),
                'fecha_estimada' => $pedido->fecha_estimada_de_entrega?->format('Y-m-d'),
            ],
            'desglose' => $pedido->getDesgloseDiasPorProceso(),
        ]);
    }
}

/**
 * EJEMPLO 3: Actualizar un proceso con cálculo automático
 */
class ProcesoControllerExample
{
    public function completarProceso($procesoId)
    {
        $proceso = ProcesoPrenda::find($procesoId);

        // Al actualizar con fecha_fin, el modelo calcula automáticamente dias_duracion
        $proceso->update([
            'fecha_fin' => now()->toDateString(),
            'estado_proceso' => 'Completado',
            'encargado' => auth()->user()->name,
        ]);

        // El campo dias_duracion ya fue calculado automáticamente
        return response()->json([
            'proceso' => $proceso,
            'dias_duracion' => $proceso->dias_duracion,
        ]);
    }
}

/**
 * EJEMPLO 4: Dashboard con estadísticas
 */
class DashboardControllerExample
{
    public function getMetricas()
    {
        $pedidos = PedidoProduccion::with('procesos')->get();

        // Calcular promedio de días
        $promedioDias = 0;
        $totalPedidos = $pedidos->count();

        if ($totalPedidos > 0) {
            $sumaDias = $pedidos->sum(fn($p) => $p->getTotalDiasNumero());
            $promedioDias = round($sumaDias / $totalPedidos, 2);
        }

        // Pedidos en retraso
        $pedidosEnRetraso = $pedidos->filter(fn($p) => $p->estaEnRetraso());

        // Área más lenta (promedio de días por proceso)
        $tiemposPorArea = [];
        foreach ($pedidos as $pedido) {
            foreach ($pedido->getDesgloseDiasPorProceso() as $area => $dias) {
                if (!isset($tiemposPorArea[$area])) {
                    $tiemposPorArea[$area] = [];
                }

                preg_match('/(\d+)/', $dias, $matches);
                $tiemposPorArea[$area][] = (int)$matches[1];
            }
        }

        $promediosPorArea = array_map(function($tiempos) {
            return round(array_sum($tiempos) / count($tiempos), 2);
        }, $tiemposPorArea);

        arsort($promediosPorArea);

        return [
            'promedio_dias_total' => $promedioDias,
            'total_pedidos' => $totalPedidos,
            'pedidos_en_retraso' => $pedidosEnRetraso->count(),
            'dias_retraso_total' => $pedidosEnRetraso->sum(fn($p) => $p->getDiasDeRetraso()),
            'area_mas_lenta' => key($promediosPorArea),
            'tiempos_promedio_por_area' => $promediosPorArea,
        ];
    }
}

/**
 * EJEMPLO 5: Trabajar con el servicio directamente
 */
class CalculoDirectoExample
{
    public function calcularEjemplo()
    {
        // Calcular días entre dos fechas
        $dias = CalculadorDiasService::calcularDiasHabiles('2025-01-15', '2025-01-20');
        // Retorna: 4 (excluyendo sábado 18 y domingo 19)

        // Formatear a texto
        $formato = CalculadorDiasService::formatearDias($dias);
        // Retorna: "4 días"

        // Calcular días hasta hoy
        $diasHastaHoy = CalculadorDiasService::calcularDiasHastahoy('2025-01-15');
        // Retorna: número de días desde 2025-01-15 hasta hoy

        // Verificar si es fin de semana
        $esFinDeSemana = CalculadorDiasService::esFinDeSemana('2025-01-18');
        // Retorna: true

        // Verificar si es festivo
        $esFestivo = CalculadorDiasService::esFestivo('2025-01-01');
        // Retorna: true (Año Nuevo)

        // Obtener próximo día hábil
        $proximoDiaHabil = CalculadorDiasService::proximoDiaHabil('2025-01-18');
        // Retorna: Carbon object para 2025-01-20 (salta el fin de semana)

        return [
            'dias' => $dias,
            'formato' => $formato,
            'dias_hasta_hoy' => $diasHastaHoy,
            'es_fin_de_semana' => $esFinDeSemana,
            'es_festivo' => $esFestivo,
            'proximo_dia_habil' => $proximoDiaHabil->format('Y-m-d'),
        ];
    }
}

/**
 * EJEMPLO 6: Actualizar información de días en blade
 */
class VistasExample
{
    /**
     * Vista: pedidos.show.blade.php
     */
    public function mostrarPedido()
    {
        // En tu blade file:
        $html = <<<'BLADE'
<div class="pedido-container">
    <h1>Pedido #{{ $pedido->numero_pedido }}</h1>
    
    <!-- Información de tiempos -->
    <div class="tiempo-info">
        <div class="tiempo-card">
            <label>Días Totales:</label>
            <span class="valor">{{ $dias['total_dias'] }}</span>
        </div>
        
        @if($dias['en_retraso'])
            <div class="tiempo-card warning">
                <label>En Retraso:</label>
                <span class="valor">{{ $dias['dias_retraso'] }} días</span>
            </div>
        @endif
    </div>

    <!-- Desglose por proceso -->
    <table class="procesos-table">
        <thead>
            <tr>
                <th>Proceso</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Días</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedido->procesos as $proceso)
                <tr>
                    <td>{{ $proceso->proceso }}</td>
                    <td>{{ $proceso->fecha_inicio?->format('d/m/Y') }}</td>
                    <td>{{ $proceso->fecha_fin?->format('d/m/Y') }}</td>
                    <td>
                        @if($proceso->dias_duracion)
                            {{ $proceso->dias_duracion }}
                        @else
                            @if($proceso->estáEnProgreso())
                                {{ $proceso->getDiasHastaHoy() }}
                            @else
                                -
                            @endif
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Resumen de desglose -->
    <div class="desglose">
        <h3>Desglose por Área:</h3>
        <ul>
            @foreach($desglose as $area => $dias)
                <li><strong>{{ $area }}:</strong> {{ $dias }}</li>
            @endforeach
        </ul>
    </div>
</div>
BLADE;
        return $html;
    }
}

/**
 * EJEMPLO 7: Reportes con información de días
 */
class ReporteControllerExample
{
    public function reporteProductividad($mes = null)
    {
        $pedidos = PedidoProduccion::all();

        $datos = [];

        foreach ($pedidos as $pedido) {
            $datos[] = [
                'pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->clienteRelacion?->nombre,
                'dias_totales' => $pedido->getTotalDiasNumero(),
                'fecha_entrega' => $pedido->fecha_estimada_de_entrega?->format('d/m/Y'),
                'retraso' => $pedido->estaEnRetraso() ? 'Sí' : 'No',
                'dias_retraso' => $pedido->getDiasDeRetraso(),
                'desglose' => $pedido->getDesgloseDiasPorProceso(),
            ];
        }

        return response()->json($datos);
    }
}

/**
 * EJEMPLO 8: Usar con Query Builder
 */
class QueryBuilderExample
{
    public function pedidosAtrasados()
    {
        // Obtener pedidos que superan la fecha estimada
        $hoy = now()->toDateString();

        $pedidos = PedidoProduccion::where('fecha_estimada_de_entrega', '<', $hoy)
            ->where('estado', '!=', 'Entregado')
            ->with(['procesos', 'clienteRelacion'])
            ->get();

        // Calcular días de retraso para cada uno
        $pedidosConRetraso = $pedidos->map(function($pedido) {
            return [
                'numero_pedido' => $pedido->numero_pedido,
                'cliente' => $pedido->clienteRelacion?->nombre,
                'dias_retraso' => $pedido->getDiasDeRetraso(),
                'fecha_entrega_estimada' => $pedido->fecha_estimada_de_entrega,
                'estado' => $pedido->estado,
            ];
        });

        return response()->json($pedidosConRetraso);
    }
}
