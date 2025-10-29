<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistrosPorOrden;
use App\Models\RegistrosPorOrdenBodega;
use App\Models\TablaOriginal;
use App\Models\TablaOriginalBodega;

class VistaCosturaController extends Controller
{
    public function index(Request $request)
    {
        // Determinar el tipo basado en el referer o parámetro
        $tipo = $this->determinarTipo($request);

        $query = $request->get('search', '');

        if ($tipo === 'bodega') {
            $registrosQuery = RegistrosPorOrdenBodega::query();
            $title = 'Vista Costura - Bodega';
            $icon = 'fas fa-warehouse';
        } else {
            $registrosQuery = RegistrosPorOrden::query();
            $title = 'Vista Costura - Pedidos';
            $icon = 'fas fa-shopping-cart';
        }

        // Aplicar filtro de búsqueda si hay query
        if (!empty($query)) {
            $registrosQuery->where('pedido', 'like', '%' . $query . '%');
        }

        $registros = $registrosQuery->paginate(50)->appends(['search' => $query]);

        return view('vista-costura.index', compact('registros', 'title', 'icon', 'tipo', 'query'));
    }

    public function search(Request $request)
    {
        $tipo = $request->get('tipo', $this->determinarTipo($request));
        $query = $request->get('q', '');
        $clientes = $request->get('clientes', []);
        $costureros = $request->get('costureros', []);
        $cortadores = $request->get('cortadores', []);

        if ($tipo === 'bodega') {
            $registrosQuery = RegistrosPorOrdenBodega::query();
        } else {
            $registrosQuery = RegistrosPorOrden::query();
        }

        // Aplicar filtro de búsqueda si hay query
        if (!empty($query)) {
            $registrosQuery->where('pedido', 'like', '%' . $query . '%');
        }

        // Aplicar filtros adicionales
        if (!empty($clientes)) {
            $registrosQuery->whereIn('cliente', $clientes);
        }
        if (!empty($costureros)) {
            $registrosQuery->whereIn('costurero', $costureros);
        }

        $registros = $registrosQuery->paginate(50);

        // Agrupar los registros por pedido y cliente, y obtener el encargado de corte
        $groupedRegistros = $registros->groupBy(function($item) {
            return $item->pedido . '-' . $item->cliente;
        });

        // Obtener los encargados de corte únicos por pedido desde la tabla original
        $pedidos = $groupedRegistros->keys()->map(function($key) {
            return explode('-', $key)[0];
        })->unique()->values()->toArray();

        $encargadosCorte = [];
        if ($tipo === 'bodega') {
            $encargadosCorte = TablaOriginalBodega::whereIn('pedido', $pedidos)
                ->whereNotNull('encargados_de_corte')
                ->pluck('encargados_de_corte', 'pedido')
                ->toArray();
        } else {
            $encargadosCorte = TablaOriginal::whereIn('pedido', $pedidos)
                ->whereNotNull('encargados_de_corte')
                ->pluck('encargados_de_corte', 'pedido')
                ->toArray();
        }

        // Aplicar filtro de cortadores si se especifica
        if (!empty($cortadores)) {
            $filteredGroupedRegistros = [];
            foreach ($groupedRegistros as $groupKey => $groupRegistros) {
                $pedido = explode('-', $groupKey)[0];
                $encargado = isset($encargadosCorte[$pedido]) ? $encargadosCorte[$pedido] : '';
                if (in_array($encargado, $cortadores)) {
                    $filteredGroupedRegistros[$groupKey] = $groupRegistros;
                }
            }
            $groupedRegistros = collect($filteredGroupedRegistros);
        }

        $html = '';
        if ($groupedRegistros->isEmpty()) {
            $html = '<div class="no-data">
                        <h3>No hay registros disponibles</h3>
                        <p>No se encontraron registros de costura para mostrar.</p>
                    </div>';
        } else {
            $html .= '<div class="cards-container">';
            foreach($groupedRegistros as $groupKey => $groupRegistros) {
                $pedidoCliente = explode('-', $groupKey);
                $pedido = $pedidoCliente[0];
                $cliente = $pedidoCliente[1];

                $encargadoCorte = isset($encargadosCorte[$pedido]) ? $encargadosCorte[$pedido] : '-';

                $html .= '<div class="pedido-card">
                            <div class="card-header">
                                <h3>' . ($pedido ?: '-') . ' - ' . ($cliente ?: '-') . '</h3>
                                <div class="encargado-corte">
                                    <span class="encargado-label">Encargado de Corte:</span>
                                    <span class="encargado-value">' . htmlspecialchars($encargadoCorte) . '</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="card-table">
                                    <thead>
                                        <tr>
                                            <th>Prenda</th>
                                            <th>Descripción</th>
                                            <th>Talla</th>
                                            <th>Cantidad</th>
                                            <th>Costurero</th>
                                            <th>Total Producido</th>
                                            <th>Total Pendiente</th>
                                            <th>Fecha Completado</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

                foreach($groupRegistros as $registro) {
                    $prenda = isset($registro->prenda) && $registro->prenda && $registro->prenda !== 'undefined' ? $registro->prenda : '-';
                    $descripcion = isset($registro->descripcion) && $registro->descripcion && $registro->descripcion !== 'undefined' ? $registro->descripcion : '-';
                    $talla = isset($registro->talla) && $registro->talla && $registro->talla !== 'undefined' ? $registro->talla : '-';
                    $cantidad = isset($registro->cantidad) && $registro->cantidad && $registro->cantidad !== 'undefined' ? $registro->cantidad : '-';
                    $costurero = isset($registro->costurero) && $registro->costurero && $registro->costurero !== 'undefined' ? $registro->costurero : '-';
                    $total_producido = isset($registro->total_producido_por_talla) && $registro->total_producido_por_talla && $registro->total_producido_por_talla !== 'undefined' ? $registro->total_producido_por_talla : '-';
                    $total_pendiente = isset($registro->total_pendiente_por_talla) && $registro->total_pendiente_por_talla && $registro->total_pendiente_por_talla !== 'undefined' ? $registro->total_pendiente_por_talla : '-';

                    $fecha_completado = '-';
                    if (isset($registro->fecha_completado) && $registro->fecha_completado && $registro->fecha_completado !== 'undefined') {
                        try {
                            $fecha_completado = \Carbon\Carbon::parse($registro->fecha_completado)->format('d/m/Y');
                        } catch (\Exception $e) {
                            $fecha_completado = '-';
                        }
                    }

                    $html .= '<tr>
                                <td class="prenda-cell cell-clickable" data-content="' . htmlspecialchars($prenda) . '">' . htmlspecialchars($prenda) . '</td>
                                <td class="descripcion-cell cell-clickable" data-content="' . htmlspecialchars($descripcion) . '">' . htmlspecialchars($descripcion) . '</td>
                                <td class="talla-cell">' . htmlspecialchars($talla) . '</td>
                                <td class="cantidad-cell">' . htmlspecialchars($cantidad) . '</td>
                                <td class="costurero-cell cell-clickable" data-content="' . htmlspecialchars($costurero) . '">' . htmlspecialchars($costurero) . '</td>
                                <td class="total_producido_por_talla-cell">' . htmlspecialchars($total_producido) . '</td>
                                <td class="total_pendiente_por_talla-cell">' . htmlspecialchars($total_pendiente) . '</td>
                                <td class="fecha_completado-cell">' . htmlspecialchars($fecha_completado) . '</td>
                            </tr>';
                }

                $html .= '</tbody></table></div></div>';
            }
            $html .= '</div>';
        }

        // Generar paginación
        $paginationHtml = '';
        if ($registros->hasPages()) {
            $paginationHtml = '<div style="display: flex; justify-content: center; margin-top: 20px;">' . $registros->appends(['q' => $query])->links() . '</div>';
        }

        return response()->json([
            'html' => $html,
            'pagination' => $paginationHtml,
            'info' => 'Mostrando ' . $registros->firstItem() . '-' . $registros->lastItem() . ' de ' . $registros->total() . ' registros'
        ]);
    }

    private function determinarTipo(Request $request)
    {
        // Verificar si viene de un parámetro directo
        if ($request->has('tipo')) {
            return $request->tipo;
        }

        // Verificar el referer para determinar el origen
        $referer = $request->headers->get('referer');

        if ($referer && str_contains($referer, '/bodega')) {
            return 'bodega';
        }

        // Por defecto, asumir pedidos
        return 'pedidos';
    }
}
