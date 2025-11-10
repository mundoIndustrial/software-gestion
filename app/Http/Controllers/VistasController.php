<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistrosPorOrden;
use App\Models\RegistrosPorOrdenBodega;
use App\Models\TablaOriginal;
use App\Models\TablaOriginalBodega;
use App\Models\EntregaPedidoCorte;
use App\Models\EntregaBodegaCorte;

class VistasController extends Controller
{
    public function index(Request $request)
    {
        // Determinar el tipo basado en el referer o parámetro
        $tipo = $this->determinarTipo($request);

        $query = $request->get('search', '');
        $origen = $request->get('origen', 'pedido'); // pedido o bodega

        if ($tipo === 'bodega') {
            $registrosQuery = RegistrosPorOrdenBodega::query();
            $title = 'Vista Costura - Bodega';
            $icon = 'fas fa-warehouse';
        } elseif ($tipo === 'corte') {
            if ($origen === 'bodega') {
                $registrosQuery = EntregaBodegaCorte::query();
                $title = 'Vista Corte - Bodega';
                $icon = 'fas fa-cut';
            } else {
                $registrosQuery = EntregaPedidoCorte::query();
                $title = 'Vista Corte - Pedidos';
                $icon = 'fas fa-cut';
            }
        } else {
            $registrosQuery = RegistrosPorOrden::query();
            $title = 'Vista Costura - Pedidos';
            $icon = 'fas fa-shopping-cart';
        }

        // Aplicar filtro de búsqueda si hay query
        if (!empty($query)) {
            if ($tipo === 'corte') {
                $registrosQuery->where('pedido', 'like', '%' . $query . '%');
            } else {
                // Buscar por pedido o por cliente
                $registrosQuery->where(function($q) use ($query) {
                    $q->where('pedido', 'like', '%' . $query . '%')
                      ->orWhere('cliente', 'like', '%' . $query . '%');
                });
            }
        }

        $registros = $registrosQuery->paginate(50)->appends(['search' => $query]);

        return view('vistas.index', compact('registros', 'title', 'icon', 'tipo', 'query'));
    }

    public function search(Request $request)
    {
        $tipo = $request->get('tipo', $this->determinarTipo($request));
        $query = $request->get('q', '');
        $clientes = $request->get('clientes', []);
        $costureros = $request->get('costureros', []);
        $cortadores = $request->get('cortadores', []);
        $origen = $request->get('origen', 'pedido'); // pedido o bodega

        if ($tipo === 'bodega') {
            $registrosQuery = RegistrosPorOrdenBodega::query();
        } elseif ($tipo === 'corte') {
            if ($origen === 'bodega') {
                $registrosQuery = EntregaBodegaCorte::query();
            } else {
                $registrosQuery = EntregaPedidoCorte::query();
            }
        } else {
            $registrosQuery = RegistrosPorOrden::query();
        }

        // Aplicar filtro de búsqueda si hay query
        if (!empty($query)) {
            if ($tipo === 'corte') {
                $registrosQuery->where('pedido', 'like', '%' . $query . '%');
            } else {
                // Buscar por pedido o por cliente
                $registrosQuery->where(function($q) use ($query) {
                    $q->where('pedido', 'like', '%' . $query . '%')
                      ->orWhere('cliente', 'like', '%' . $query . '%');
                });
            }
        }

        // Aplicar filtros adicionales
        if (!empty($clientes) && $tipo !== 'corte') {
            $registrosQuery->whereIn('cliente', $clientes);
        }
        if (!empty($costureros) && $tipo !== 'corte') {
            $registrosQuery->whereIn('costurero', $costureros);
        }

        $registros = $registrosQuery->paginate(50);

        if ($tipo === 'corte') {
            // Agrupar los registros por pedido para corte
            $groupedRegistros = $registros->groupBy('pedido');

            $html = '';
            if ($groupedRegistros->isEmpty()) {
                $html = '<div class="no-data">
                            <h3>No hay registros disponibles</h3>
                            <p>No se encontraron registros de corte para mostrar.</p>
                        </div>';
            } else {
                $html .= '<div class="cards-container">';
                foreach($groupedRegistros as $pedido => $groupRegistros) {
                    $html .= '<div class="pedido-card">
                                <div class="card-header">
                                    <h3>' . ($pedido ?: '-') . '</h3>
                                </div>
                                <div class="card-body">
                                    <table class="card-table">
                                        <thead>
                                            <tr>
                                                <th>Prenda</th>
                                                <th>Cortador</th>
                                                <th>Cantidad Prendas</th>
                                                <th>Piezas</th>
                                                <th>Pasadas</th>
                                                <th>Etiquetadas</th>
                                                <th>Etiquetador</th>
                                                <th>Fecha Entrega</th>
                                                <th>Mes</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

                    foreach($groupRegistros as $registro) {
                        $prenda = isset($registro->prenda) && $registro->prenda && $registro->prenda !== 'undefined' ? $registro->prenda : '-';
                        $cortador = isset($registro->cortador) && $registro->cortador && $registro->cortador !== 'undefined' ? $registro->cortador : '-';
                        $cantidad_prendas = isset($registro->cantidad_prendas) && $registro->cantidad_prendas && $registro->cantidad_prendas !== 'undefined' ? $registro->cantidad_prendas : '-';
                        $piezas = isset($registro->piezas) && $registro->piezas && $registro->piezas !== 'undefined' ? $registro->piezas : '-';
                        $pasadas = isset($registro->pasadas) && $registro->pasadas && $registro->pasadas !== 'undefined' ? $registro->pasadas : '-';
                        $etiqueteadas = isset($registro->etiqueteadas) && $registro->etiqueteadas && $registro->etiqueteadas !== 'undefined' ? $registro->etiqueteadas : '-';
                        $etiquetador = isset($registro->etiquetador) && $registro->etiquetador && $registro->etiquetador !== 'undefined' ? $registro->etiquetador : '-';

                        $fecha_entrega = '-';
                        if (isset($registro->fecha_entrega) && $registro->fecha_entrega && $registro->fecha_entrega !== 'undefined') {
                            try {
                                $fecha_entrega = \Carbon\Carbon::parse($registro->fecha_entrega)->format('d/m/Y');
                            } catch (\Exception $e) {
                                $fecha_entrega = '-';
                            }
                        }

                        $mes = isset($registro->mes) && $registro->mes && $registro->mes !== 'undefined' ? $registro->mes : '-';

                        $html .= '<tr>
                                    <td class="prenda-cell cell-clickable" data-content="' . htmlspecialchars($prenda) . '">' . htmlspecialchars($prenda) . '</td>
                                    <td class="cortador-cell">' . htmlspecialchars($cortador) . '</td>
                                    <td class="cantidad_prendas-cell">' . htmlspecialchars($cantidad_prendas) . '</td>
                                    <td class="piezas-cell">' . htmlspecialchars($piezas) . '</td>
                                    <td class="pasadas-cell">' . htmlspecialchars($pasadas) . '</td>
                                    <td class="etiqueteadas-cell">' . htmlspecialchars($etiqueteadas) . '</td>
                                    <td class="etiquetador-cell">' . htmlspecialchars($etiquetador) . '</td>
                                    <td class="fecha_entrega-cell">' . htmlspecialchars($fecha_entrega) . '</td>
                                    <td class="mes-cell">' . htmlspecialchars($mes) . '</td>
                                </tr>';
                    }

                    $html .= '</tbody></table></div></div>';
                }
                $html .= '</div>';
            }
        } else {
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

    public function controlCalidad(Request $request)
    {
        $query = $request->input('search');

        // Obtener órdenes de ambas tablas donde area = 'Control-Calidad'
        $ordenesPedido = TablaOriginal::where('area', 'Control-Calidad');
        $ordenesBodega = TablaOriginalBodega::where('area', 'Control-Calidad');

        // Aplicar búsqueda si existe
        if ($query) {
            $ordenesPedido->where(function($q) use ($query) {
                $q->where('pedido', 'like', "%{$query}%")
                  ->orWhere('cliente', 'like', "%{$query}%");
            });
            $ordenesBodega->where(function($q) use ($query) {
                $q->where('pedido', 'like', "%{$query}%")
                  ->orWhere('cliente', 'like', "%{$query}%");
            });
        }

        // Obtener resultados y combinarlos
        $ordenes = $ordenesPedido->get()->merge($ordenesBodega->get());

        return view('vistas.control-calidad', compact('ordenes', 'query'));
    }

    public function controlCalidadFullscreen(Request $request)
    {
        $query = $request->input('search');

        // Obtener órdenes de ambas tablas donde area = 'Control-Calidad'
        $ordenesPedido = TablaOriginal::where('area', 'Control-Calidad');
        $ordenesBodega = TablaOriginalBodega::where('area', 'Control-Calidad');

        // Aplicar búsqueda si existe
        if ($query) {
            $ordenesPedido->where(function($q) use ($query) {
                $q->where('pedido', 'like', "%{$query}%")
                  ->orWhere('cliente', 'like', "%{$query}%");
            });
            $ordenesBodega->where(function($q) use ($query) {
                $q->where('pedido', 'like', "%{$query}%")
                  ->orWhere('cliente', 'like', "%{$query}%");
            });
        }

        // Obtener resultados y combinarlos
        $ordenes = $ordenesPedido->get()->merge($ordenesBodega->get());

        return view('vistas.control-calidad-fullscreen', compact('ordenes', 'query'));
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
        } elseif ($referer && str_contains($referer, '/corte')) {
            return 'corte';
        }

        // Por defecto, asumir pedidos
        return 'pedidos';
    }
}
