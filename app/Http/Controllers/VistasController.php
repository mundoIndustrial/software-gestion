<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegistrosPorOrden;
use App\Models\RegistrosPorOrdenBodega;
use App\Models\TablaOriginalBodega;
use App\Models\EntregaPedidoCorte;
use App\Models\EntregaBodegaCorte;
use App\Models\PedidoProduccion;

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
            // Para Costura - Pedidos: usar prendas_pedido con relación a pedidos_produccion
            $title = 'Vista Costura - Pedidos';
            $icon = 'fas fa-shopping-cart';
            
            // Solo mostrar estos estados
            $estadosPermitidos = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
            
            $prendaQuery = \App\Models\PrendaPedido::with([
                'pedido',
                'tallas',
                'variantes.tipoManga',
                'variantes.tipoBroche'
            ])
                ->whereHas('pedido', function($q) use ($estadosPermitidos) {
                    // Solo prendas de pedidos con estados permitidos
                    $q->whereIn('estado', $estadosPermitidos);
                });
            
            // Aplicar filtro de búsqueda si hay query
            if (!empty($query)) {
                $prendaQuery->whereHas('pedido', function($q) use ($query) {
                    $q->where('numero_pedido', 'like', '%' . $query . '%')
                      ->orWhere('cliente', 'like', '%' . $query . '%');
                });
            }
            
            // Obtener registros sin paginar primero para agrupar
            $allPrendas = $prendaQuery->get();
            
            // Agrupar por pedido-cliente en el controlador
            $groupedRegistros = $allPrendas->groupBy(function($prenda) {
                return ($prenda->pedido->numero_pedido ?? 'P-' . $prenda->pedido->id) . '-' . $prenda->pedido->cliente;
            });
            
            // Crear un objeto collection que simule la paginación
            $registros = collect();
            
            return view('vistas.index', compact('registros', 'groupedRegistros', 'title', 'icon', 'tipo', 'query'));
        }

        // Aplicar filtro de búsqueda si hay query (para bodega y corte)
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
            // Para Costura - Pedidos: usar prendas_pedido con relación a pedidos_produccion
            // Solo mostrar estos estados
            $estadosPermitidos = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
            
            $registrosQuery = \App\Models\PrendaPedido::with('pedido', 'tallas')
                ->whereHas('pedido', function($q) use ($estadosPermitidos) {
                    $q->whereIn('estado', $estadosPermitidos);
                });
        }

        // Aplicar filtro de búsqueda si hay query
        if (!empty($query)) {
            if ($tipo === 'corte') {
                $registrosQuery->where('pedido', 'like', '%' . $query . '%');
            } elseif ($tipo === 'pedidos') {
                // Para prendas_pedido: buscar por numero_pedido o cliente del pedido
                $registrosQuery->whereHas('pedido', function($q) use ($query) {
                    $q->where('numero_pedido', 'like', '%' . $query . '%')
                      ->orWhere('cliente', 'like', '%' . $query . '%');
                });
            } else {
                // Buscar por pedido o por cliente (bodega)
                $registrosQuery->where(function($q) use ($query) {
                    $q->where('pedido', 'like', '%' . $query . '%')
                      ->orWhere('cliente', 'like', '%' . $query . '%');
                });
            }
        }

        // Aplicar filtros adicionales solo para bodega
        if (!empty($clientes) && $tipo === 'bodega') {
            $registrosQuery->whereIn('cliente', $clientes);
        }
        if (!empty($costureros) && $tipo === 'bodega') {
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
            // Para Costura - Pedidos: agrupar prendas_pedido por pedido
            // Expandir tallas desde cantidad_talla (JSON)
            $html = '';
            
            if ($registros->isEmpty()) {
                $html = '<div class="no-data">
                            <h3>No hay registros disponibles</h3>
                            <p>No se encontraron registros de costura para mostrar.</p>
                        </div>';
            } else {
                // Agrupar prendas por pedido
                $groupedByPedido = $registros->groupBy(function($prenda) {
                    return $prenda->pedido->numero_pedido . '-' . $prenda->pedido->cliente;
                });
                
                $html .= '<div class="cards-container">';
                
                foreach($groupedByPedido as $groupKey => $prendas) {
                    $pedidoCliente = explode('-', $groupKey);
                    $numeroPedido = $pedidoCliente[0];
                    $cliente = $pedidoCliente[1];
                    
                    // Para Costura - Pedidos, no hay encargado de corte en tabla_original
                    $encargadoCorte = '-';
                    
                    $html .= '<div class="pedido-card">
                                <div class="card-header">
                                    <h3>' . htmlspecialchars($numeroPedido ?: '-') . ' - ' . htmlspecialchars($cliente ?: '-') . '</h3>
                                    <div class="encargado-corte">
                                        <span class="encargado-label">Encargado de Corte:</span>
                                        <span class="encargado-value">' . htmlspecialchars($encargadoCorte) . '</span>
                                        <button class="btn-toggle-edit" data-card-id="' . htmlspecialchars($numeroPedido . '-' . $cliente) . '" title="Activar edición">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
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
                    
                    // Expandir cada prenda por sus tallas
                    foreach($prendas as $prenda) {
                        $nombrePrenda = htmlspecialchars($prenda->nombre_prenda ?: '-');
                        $descripcion = htmlspecialchars($prenda->descripcion ?: '-');
                        
                        // Extraer tallas desde cantidad_talla (JSON)
                        $cantidadTalla = is_string($prenda->cantidad_talla) 
                            ? json_decode($prenda->cantidad_talla, true) 
                            : $prenda->cantidad_talla;
                        
                        if (is_array($cantidadTalla) && !empty($cantidadTalla)) {
                            // Crear una fila por cada talla
                            foreach($cantidadTalla as $talla => $cantidad) {
                                // Buscar entrega_prenda_pedido relacionada
                                $entrega = \App\Models\EntregaPrendaPedido::where('numero_pedido', $prenda->pedido->numero_pedido)
                                    ->where('nombre_prenda', $prenda->nombre_prenda)
                                    ->where('talla', $talla)
                                    ->first();
                                
                                $costurero = $entrega ? htmlspecialchars($entrega->costurero ?: '-') : '-';
                                $totalProducido = $entrega ? htmlspecialchars($entrega->total_producido_por_talla) : '-';
                                $totalPendiente = $entrega ? htmlspecialchars($entrega->total_pendiente_por_talla) : '-';
                                $fechaCompletado = $entrega && $entrega->fecha_completado ? $entrega->fecha_completado->format('d/m/Y') : '-';
                                
                                $html .= '<tr data-id="' . $prenda->id . '" data-tipo="pedidos" data-talla="' . htmlspecialchars($talla) . '">
                                            <td class="prenda-cell cell-clickable" data-content="' . $nombrePrenda . '">' . $nombrePrenda . '</td>
                                            <td class="descripcion-cell cell-clickable" data-content="' . $descripcion . '">' . $descripcion . '</td>
                                            <td class="talla-cell">' . htmlspecialchars($talla) . '</td>
                                            <td class="cantidad-cell editable" data-field="cantidad" data-value="' . htmlspecialchars($cantidad) . '">' . htmlspecialchars($cantidad) . '</td>
                                            <td class="costurero-cell cell-clickable editable" data-field="costurero" data-content="' . $costurero . '" data-value="' . ($entrega ? htmlspecialchars($entrega->costurero ?? '') : '') . '">' . $costurero . '</td>
                                            <td class="total_producido_por_talla-cell editable" data-field="total_producido_por_talla" data-value="' . ($entrega ? htmlspecialchars($entrega->total_producido_por_talla) : '') . '">' . $totalProducido . '</td>
                                            <td class="total_pendiente_por_talla-cell editable" data-field="total_pendiente_por_talla" data-value="' . ($entrega ? htmlspecialchars($entrega->total_pendiente_por_talla) : '') . '">' . $totalPendiente . '</td>
                                            <td class="fecha_completado-cell">' . $fechaCompletado . '</td>
                                        </tr>';
                            }
                        } else {
                            // Si no hay tallas, mostrar una fila sin talla
                            $html .= '<tr data-id="' . $prenda->id . '" data-tipo="pedidos">
                                        <td class="prenda-cell cell-clickable" data-content="' . $nombrePrenda . '">' . $nombrePrenda . '</td>
                                        <td class="descripcion-cell cell-clickable" data-content="' . $descripcion . '">' . $descripcion . '</td>
                                        <td class="talla-cell">-</td>
                                        <td class="cantidad-cell editable" data-field="cantidad" data-value="">-</td>
                                        <td class="costurero-cell cell-clickable editable" data-field="costurero" data-content="-" data-value="">-</td>
                                        <td class="total_producido_por_talla-cell editable" data-field="total_producido_por_talla" data-value="">-</td>
                                        <td class="total_pendiente_por_talla-cell editable" data-field="total_pendiente_por_talla" data-value="">-</td>
                                        <td class="fecha_completado-cell">-</td>
                                    </tr>';
                        }
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

        // Obtener órdenes de pedidos de la tabla PedidoProduccion donde area = 'Control Calidad'
        // con JOIN a procesos_prenda para obtener la fecha de ingreso a control calidad
        $ordenesPedidosQuery = PedidoProduccion::where('area', 'Control Calidad')
            ->leftJoinSub(
                \DB::table('procesos_prenda')
                    ->where('proceso', 'Control Calidad')
                    ->select('numero_pedido', 'fecha_inicio as fecha_ingreso_control_calidad'),
                'procesos',
                'pedidos_produccion.numero_pedido',
                '=',
                'procesos.numero_pedido'
            );

        // Aplicar búsqueda si existe
        if ($query) {
            $ordenesPedidosQuery->where(function($q) use ($query) {
                $q->where('numero_pedido', 'like', "%{$query}%")
                  ->orWhere('cliente', 'like', "%{$query}%");
            });
        }

        $ordenesPedidos = $ordenesPedidosQuery->get();

        // Obtener órdenes de bodega de tabla_original_bodega donde area = 'Control-Calidad'
        $ordenesBodegaQuery = TablaOriginalBodega::where('area', 'Control-Calidad');
        
        if ($query) {
            $ordenesBodegaQuery->where(function($q) use ($query) {
                $q->where('pedido', 'like', "%{$query}%")
                  ->orWhere('cliente', 'like', "%{$query}%");
            });
        }

        $ordenesBodega = $ordenesBodegaQuery->get();

        return view('vistas.control-calidad', compact('ordenesPedidos', 'ordenesBodega', 'query'));
    }

    public function controlCalidadFullscreen(Request $request)
    {
        $query = $request->input('search');

        // Obtener órdenes de pedidos de la tabla PedidoProduccion donde area = 'Control Calidad'
        // con JOIN a procesos_prenda para obtener la fecha de ingreso a control calidad
        $ordenesPedidosQuery = PedidoProduccion::where('area', 'Control Calidad')
            ->leftJoinSub(
                \DB::table('procesos_prenda')
                    ->where('proceso', 'Control Calidad')
                    ->select('numero_pedido', 'fecha_inicio as fecha_ingreso_control_calidad'),
                'procesos',
                'pedidos_produccion.numero_pedido',
                '=',
                'procesos.numero_pedido'
            );

        // Aplicar búsqueda si existe
        if ($query) {
            $ordenesPedidosQuery->where(function($q) use ($query) {
                $q->where('numero_pedido', 'like', "%{$query}%")
                  ->orWhere('cliente', 'like', "%{$query}%");
            });
        }

        $ordenesPedidos = $ordenesPedidosQuery->get();

        // Obtener órdenes de bodega de tabla_original_bodega donde area = 'Control-Calidad'
        $ordenesBodegaQuery = TablaOriginalBodega::where('area', 'Control-Calidad');
        
        if ($query) {
            $ordenesBodegaQuery->where(function($q) use ($query) {
                $q->where('pedido', 'like', "%{$query}%")
                  ->orWhere('cliente', 'like', "%{$query}%");
            });
        }

        $ordenesBodega = $ordenesBodegaQuery->get();

        return view('vistas.control-calidad-fullscreen', compact('ordenesPedidos', 'ordenesBodega', 'query'));
    }

    public function updateCell(Request $request)
    {
        try {
            $id = $request->input('id');
            $field = $request->input('field');
            $value = $request->input('value');
            $tipo = $request->input('tipo');

            // Validate inputs
            if (!$id || !$field) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID y campo son requeridos'
                ], 400);
            }

            // Determine which model to use based on tipo
            if ($tipo === 'bodega') {
                $model = RegistrosPorOrdenBodega::class;
            } else {
                $model = RegistrosPorOrden::class;
            }

            // Find the record
            $registro = $model::find($id);
            
            if (!$registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado'
                ], 404);
            }

            // Validate field is allowed to be edited
            $allowedFields = ['cantidad', 'costurero', 'total_producido_por_talla', 'total_pendiente_por_talla'];
            if (!in_array($field, $allowedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo no permitido para edición'
                ], 403);
            }

            // Convert empty string to null for numeric fields
            if (in_array($field, ['cantidad', 'total_producido_por_talla', 'total_pendiente_por_talla'])) {
                $value = $value === '' ? null : $value;
            }

            // Update the field without triggering model events
            $registro->$field = $value;
            $registro->saveQuietly();

            return response()->json([
                'success' => true,
                'message' => 'Campo actualizado correctamente',
                'value' => $value
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating cell: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
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
