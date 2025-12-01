<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EntregaPedidoCostura;
use App\Models\EntregaPedidoCorte;
use App\Models\EntregaBodegaCostura;
use App\Models\EntregaBodegaCorte;
use App\Models\EntregaPrendaPedido;
use App\Models\News;
use App\Events\EntregaRegistrada;
use App\Events\EntregaEliminada;
use Carbon\Carbon;

class EntregaController extends Controller
{
    private function getModels($tipo)
    {
        if ($tipo === 'pedido') {
            return [
                'costura' => EntregaPedidoCostura::class,
                'corte' => EntregaPedidoCorte::class,
                'titulo' => 'Entregas de Pedidos',
                'seccionCostura' => 'Entregas de Costura',
                'seccionCorte' => 'Entregas de Corte'
            ];
        } elseif ($tipo === 'bodega') {
            return [
                'costura' => EntregaBodegaCostura::class,
                'corte' => EntregaBodegaCorte::class,
                'titulo' => 'Entregas de Bodega',
                'seccionCostura' => 'Entregas de Bodega Costura',
                'seccionCorte' => 'Entregas de Bodega Corte'
            ];
        }
        abort(404);
    }

    public function index(Request $request)
    {
        $tipo = $request->route('tipo'); // pedido or bodega
        $config = $this->getModels($tipo);

        $fecha = $request->get('fecha', Carbon::today()->toDateString());

        $costura = $config['costura']::where('fecha_entrega', $fecha)->get();
        $corte = $config['corte']::where('fecha_entrega', $fecha)->get();

        return view('entrega.index', compact('costura', 'corte', 'fecha', 'config', 'tipo'));
    }

    public function costuraData(Request $request)
    {
        $tipo = $request->route('tipo');
        $config = $this->getModels($tipo);
        $fecha = $request->get('fecha', Carbon::today()->toDateString());

        $data = $config['costura']::where('fecha_entrega', $fecha)->get();

        return response()->json($data);
    }

    public function corteData(Request $request)
    {
        $tipo = $request->route('tipo');
        $config = $this->getModels($tipo);
        $fecha = $request->get('fecha', Carbon::today()->toDateString());

        $data = $config['corte']::where('fecha_entrega', $fecha)->get();

        return response()->json($data);
    }

    public function orderData(Request $request)
    {
        $tipo = $request->route('tipo');
        $pedido = $request->route('pedido');

        if ($tipo === 'pedido') {
            $order = \App\Models\PedidoProduccion::where('numero_pedido', $pedido)->first();
        } elseif ($tipo === 'bodega') {
            $order = \App\Models\TablaOriginalBodega::where('pedido', $pedido)->first();
        }

        if (!$order) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }

        return response()->json([
            'cliente' => $order->cliente,
            'estado' => $order->estado,
        ]);
    }

    public function garments(Request $request)
    {
        $tipo = $request->route('tipo');
        $pedido = $request->route('pedido');

        try {
            if ($tipo === 'pedido') {
                // Obtener el pedido de producción por número de pedido
                $pedidoProduccion = \App\Models\PedidoProduccion::where('numero_pedido', $pedido)->first();
                
                if (!$pedidoProduccion) {
                    return response()->json(['error' => 'Pedido no encontrado'], 404);
                }
                
                // Obtener prendas relacionadas desde la tabla prendas_pedido
                $garments = $pedidoProduccion->prendas()
                    ->select('nombre_prenda')
                    ->distinct()
                    ->get()
                    ->pluck('nombre_prenda');
            } elseif ($tipo === 'bodega') {
                $garments = \App\Models\RegistrosPorOrdenBodega::where('pedido', $pedido)
                    ->select('prenda')
                    ->distinct()
                    ->get()
                    ->pluck('prenda');
            } else {
                return response()->json(['error' => 'Tipo inválido'], 400);
            }

            return response()->json($garments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener prendas: ' . $e->getMessage()], 500);
        }
    }

    public function sizes(Request $request)
    {
        $tipo = $request->route('tipo');
        $pedido = $request->route('pedido');
        $prenda = $request->route('prenda');

        try {
            if ($tipo === 'pedido') {
                // Obtener el pedido de producción por número de pedido
                $pedidoProduccion = \App\Models\PedidoProduccion::where('numero_pedido', $pedido)->first();
                
                if (!$pedidoProduccion) {
                    return response()->json(['error' => 'Pedido no encontrado'], 404);
                }
                
                // Obtener la prenda específica desde prendas_pedido
                $prendaPedido = $pedidoProduccion->prendas()
                    ->where('nombre_prenda', $prenda)
                    ->first();
                
                if (!$prendaPedido) {
                    return response()->json(['error' => 'Prenda no encontrada en el pedido'], 404);
                }
                
                // Extraer tallas desde cantidad_talla (JSON)
                $cantidadTalla = is_string($prendaPedido->cantidad_talla) 
                    ? json_decode($prendaPedido->cantidad_talla, true) 
                    : $prendaPedido->cantidad_talla;
                
                $result = [];
                if (is_array($cantidadTalla)) {
                    foreach ($cantidadTalla as $talla => $cantidad) {
                        $result[] = [
                            'talla' => $talla,
                            'total_producido_por_talla' => 0,
                            'total_pendiente_por_talla' => $cantidad,
                            'cantidad' => $cantidad,
                        ];
                    }
                }
                
                return response()->json($result);
                
            } elseif ($tipo === 'bodega') {
                $sizes = \App\Models\RegistrosPorOrdenBodega::where('pedido', $pedido)
                    ->where('prenda', $prenda)
                    ->get();
                
                $result = [];
                foreach ($sizes as $size) {
                    $totalProducido = $size->total_producido_por_talla ?? 0;
                    $totalPendiente = $size->total_pendiente_por_talla ?? 0;

                    $result[] = [
                        'talla' => $size->talla,
                        'total_producido_por_talla' => $totalProducido,
                        'total_pendiente_por_talla' => $totalPendiente,
                        'cantidad' => $size->cantidad,
                    ];
                }
                
                return response()->json($result);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener tallas: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $tipo = $request->route('tipo');
            $config = $this->getModels($tipo);
            $subtipo = $request->subtipo; // costura or corte

            $entregas = $request->entregas; // array of deliveries

            if ($subtipo === 'costura') {
                foreach ($entregas as $entrega) {
                    $request->merge($entrega);
                    $request->validate([
                        'pedido' => 'required|integer',
                        'cliente' => 'required|string',
                        'prenda' => 'required|string',
                        //'descripcion' => 'nullable|string', // Remove descripcion validation as it causes error
                        'talla' => 'nullable|string',
                        'cantidad_entregada' => 'required|integer',
                        'fecha_entrega' => 'required|date',
                        'costurero' => 'required|string',
                        'mes_ano' => 'nullable|string',
                    ]);

                    // Remove descripcion key from entrega array if exists to avoid DB error
                    if (array_key_exists('descripcion', $entrega)) {
                        unset($entrega['descripcion']);
                    }

                    // Set mes_ano automatically from fecha_entrega
                    if (!empty($entrega['fecha_entrega'])) {
                        $fecha = \Carbon\Carbon::parse($entrega['fecha_entrega']);
                        // Use format instead of formatLocalized to avoid error
                        $mesAno = strtolower($fecha->format('F Y')); // e.g. October 2025
                        // Convert English month to Spanish month manually
                        $meses = [
                            'January' => 'enero',
                            'February' => 'febrero',
                            'March' => 'marzo',
                            'April' => 'abril',
                            'May' => 'mayo',
                            'June' => 'junio',
                            'July' => 'julio',
                            'August' => 'agosto',
                            'September' => 'septiembre',
                            'October' => 'octubre',
                            'November' => 'noviembre',
                            'December' => 'diciembre',
                        ];
                        foreach ($meses as $en => $es) {
                            if (stripos($mesAno, $en) !== false) {
                                $mesAno = str_ireplace($en, $es, $mesAno);
                                break;
                            }
                        }
                        $entrega['mes_ano'] = $mesAno;
                    } else {
                        $entrega['mes_ano'] = null;
                    }

                    // Para tipo='pedido', usar EntregaPrendaPedido Y EntregaPedidoCostura
                    if ($tipo === 'pedido') {
                        // Obtener el pedido de producción
                        $pedidoProduccion = \App\Models\PedidoProduccion::where('numero_pedido', $entrega['pedido'])->first();
                        
                        if (!$pedidoProduccion) {
                            return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);
                        }

                        // Obtener la prenda_pedido
                        $prendaPedido = $pedidoProduccion->prendas()
                            ->where('nombre_prenda', $entrega['prenda'])
                            ->first();

                        if (!$prendaPedido) {
                            return response()->json(['success' => false, 'message' => 'Prenda no encontrada en el pedido'], 404);
                        }

                        // Obtener cantidad_original de la talla desde cantidad_talla JSON
                        $cantidadTalla = is_string($prendaPedido->cantidad_talla) 
                            ? json_decode($prendaPedido->cantidad_talla, true) 
                            : $prendaPedido->cantidad_talla;

                        if (!isset($cantidadTalla[$entrega['talla']])) {
                            return response()->json(['success' => false, 'message' => 'Talla no encontrada para esta prenda'], 404);
                        }

                        $cantidadOriginal = $cantidadTalla[$entrega['talla']];

                        // Buscar o crear la entrega_prenda_pedido para esta talla
                        $entregaPrendaPedido = EntregaPrendaPedido::where('numero_pedido', $entrega['pedido'])
                            ->where('nombre_prenda', $entrega['prenda'])
                            ->where('talla', $entrega['talla'])
                            ->first();

                        if (!$entregaPrendaPedido) {
                            // Crear nuevo registro en entrega_prenda_pedido
                            $entregaPrendaPedido = EntregaPrendaPedido::create([
                                'numero_pedido' => $entrega['pedido'],
                                'nombre_prenda' => $entrega['prenda'],
                                'talla' => $entrega['talla'],
                                'cantidad_original' => $cantidadOriginal,
                                'costurero' => $entrega['costurero'],
                                'total_producido_por_talla' => $entrega['cantidad_entregada'],
                                'total_pendiente_por_talla' => $cantidadOriginal - $entrega['cantidad_entregada'],
                                'fecha_completado' => ($cantidadOriginal - $entrega['cantidad_entregada']) == 0 ? now() : null,
                            ]);
                        } else {
                            // Actualizar registro existente en entrega_prenda_pedido
                            $entregaPrendaPedido->costurero = $entrega['costurero'];
                            $entregaPrendaPedido->total_producido_por_talla += $entrega['cantidad_entregada'];
                            $entregaPrendaPedido->calcularTotalPendiente();
                            $entregaPrendaPedido->save();
                        }

                        // TAMBIÉN guardar en entrega_pedido_costura (tabla legacy)
                        $entregaPedidoCostura = \App\Models\EntregaPedidoCostura::create([
                            'pedido' => $entrega['pedido'],
                            'cliente' => $entrega['cliente'],
                            'prenda' => $entrega['prenda'],
                            'descripcion' => $prendaPedido->descripcion ?? null,
                            'talla' => $entrega['talla'],
                            'cantidad_entregada' => $entrega['cantidad_entregada'],
                            'fecha_entrega' => $entrega['fecha_entrega'],
                            'costurero' => $entrega['costurero'],
                            'mes_ano' => $entrega['mes_ano'] ?? null,
                        ]);

                        $nuevaEntrega = $entregaPrendaPedido;
                    } else {
                        // Para tipo='bodega', usar EntregaBodegaCostura
                        // Add descripcion from registros_por_orden_bodega
                        $descripcion = null;
                        $registro = \App\Models\RegistrosPorOrdenBodega::where('pedido', $entrega['pedido'])
                            ->where('prenda', $entrega['prenda'])
                            ->where('talla', $entrega['talla'])
                            ->first();
                        if ($registro) {
                            $descripcion = $registro->descripcion ?? null;
                        }
                        if ($descripcion !== null) {
                            $entrega['descripcion'] = $descripcion;
                        }

                        $nuevaEntrega = $config['costura']::create($entrega);

                        // Update total_pendiente_por_talla, total_producido_por_talla, and costurero
                        \App\Models\RegistrosPorOrdenBodega::where('pedido', $entrega['pedido'])
                            ->where('prenda', $entrega['prenda'])
                            ->where('talla', $entrega['talla'])
                            ->decrement('total_pendiente_por_talla', $entrega['cantidad_entregada']);

                        \App\Models\RegistrosPorOrdenBodega::where('pedido', $entrega['pedido'])
                            ->where('prenda', $entrega['prenda'])
                            ->where('talla', $entrega['talla'])
                            ->increment('total_producido_por_talla', $entrega['cantidad_entregada']);

                        // Update costurero in production table
                        \App\Models\RegistrosPorOrdenBodega::where('pedido', $entrega['pedido'])
                            ->where('prenda', $entrega['prenda'])
                            ->where('talla', $entrega['talla'])
                            ->update(['costurero' => $entrega['costurero']]);
                    }

                    // Broadcast event
                    broadcast(new EntregaRegistrada($tipo, 'costura', $nuevaEntrega, $entrega['fecha_entrega']));

                    // Log news
                    News::create([
                        'event_type' => 'delivery_registered',
                        'description' => "Entrega de costura registrada: Pedido {$entrega['pedido']}, Prenda {$entrega['prenda']}, Cantidad {$entrega['cantidad_entregada']}",
                        'user_id' => auth()->id(),
                        'pedido' => $entrega['pedido'],
                        'metadata' => ['tipo' => $tipo, 'subtipo' => 'costura', 'cantidad' => $entrega['cantidad_entregada'], 'costurero' => $entrega['costurero']]
                    ]);
                }

            } elseif ($subtipo === 'corte') {
                foreach ($entregas as $entrega) {
                    $request->merge($entrega);
                    $request->validate([
                        'pedido' => 'required|integer',
                        'cortador' => 'required|string',
                        'piezas' => 'required|integer',
                        'pasadas' => 'required|integer',
                        'etiquetador' => 'nullable|string',
                        'fecha_entrega' => 'required|date',
                    ]);

                    // Log para depuración
                    \Log::info('Tipo: ' . $tipo);
                    \Log::info('Pedido: ' . $entrega['pedido']);

                    // Get prendas internally
                    $prendas = [];
                    if ($tipo === 'pedido') {
                        $prendas = \App\Models\RegistrosPorOrden::where('pedido', $entrega['pedido'])
                            ->select('prenda')
                            ->distinct()
                            ->get()
                            ->pluck('prenda')
                            ->toArray();
                    } elseif ($tipo === 'bodega') {
                        $prendas = \App\Models\RegistrosPorOrdenBodega::where('pedido', $entrega['pedido'])
                            ->select('prenda')
                            ->distinct()
                            ->get()
                            ->pluck('prenda')
                            ->toArray();
                    }

                    // Log prendas obtenidas
                    \Log::info('Prendas obtenidas: ' . json_encode($prendas));

                    // Concatenate prendas as PRENDA 1: PRENDA 2: ...
                    $prendaString = '';
                    foreach ($prendas as $index => $prenda) {
                        $prendaString .= 'PRENDA ' . ($index + 1) . ': ' . $prenda . ' ';
                    }
                    $entrega['prenda'] = trim($prendaString);

                    // Use piezas and pasadas directly from the form
                    $piezas = $entrega['piezas'] ?? 1;
                    $pasadas = $entrega['pasadas'] ?? 1;

                    // Calculate etiqueteadas as piezas * pasadas (calculated internally, to be saved in DB)
                    $etiqueteadas = $piezas * $pasadas;

                    // Add 'etiqueteadas' to entrega array to avoid DB error
                    $entrega['etiqueteadas'] = $etiqueteadas;

                    // Calculate 'mes' from 'fecha_entrega' and add to entrega
                    if (!empty($entrega['fecha_entrega'])) {
                        $fecha = \Carbon\Carbon::parse($entrega['fecha_entrega']);
                        $mesAno = strtolower($fecha->format('F Y')); // e.g. October 2025
                        $meses = [
                            'January' => 'enero',
                            'February' => 'febrero',
                            'March' => 'marzo',
                            'April' => 'abril',
                            'May' => 'mayo',
                            'June' => 'junio',
                            'July' => 'julio',
                            'August' => 'agosto',
                            'September' => 'septiembre',
                            'October' => 'octubre',
                            'November' => 'noviembre',
                            'December' => 'diciembre',
                        ];
                        foreach ($meses as $en => $es) {
                            if (stripos($mesAno, $en) !== false) {
                                $mesAno = str_ireplace($en, $es, $mesAno);
                                break;
                            }
                        }
                        $entrega['mes'] = $mesAno;
                    } else {
                        $entrega['mes'] = null;
                    }

                    $nuevaEntrega = $config['corte']::create($entrega);

                    // Broadcast event
                    broadcast(new EntregaRegistrada($tipo, 'corte', $nuevaEntrega, $entrega['fecha_entrega']));

                    // Log news
                    News::create([
                        'event_type' => 'delivery_registered',
                        'description' => "Entrega de corte registrada: Pedido {$entrega['pedido']}, Piezas {$entrega['piezas']}, Etiquetadas {$etiqueteadas}",
                        'user_id' => auth()->id(),
                        'pedido' => $entrega['pedido'],
                        'metadata' => ['tipo' => $tipo, 'subtipo' => 'corte', 'piezas' => $entrega['piezas'], 'etiqueteadas' => $etiqueteadas, 'cortador' => $entrega['cortador'], 'etiquetador' => $entrega['etiquetador']]
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Entregas registradas exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $tipo = $request->route('tipo');
            $subtipo = $request->route('subtipo');
            $id = $request->route('id');
            $config = $this->getModels($tipo);

            if ($subtipo === 'costura') {
                $entrega = $config['costura']::findOrFail($id);
                
                // Validate only the fields that are being updated
                $validated = $request->validate([
                    'pedido' => 'sometimes|integer',
                    'cliente' => 'sometimes|string',
                    'prenda' => 'sometimes|string',
                    'talla' => 'sometimes|string',
                    'cantidad_entregada' => 'sometimes|integer',
                    'fecha_entrega' => 'sometimes|date',
                    'costurero' => 'sometimes|string',
                ]);

                // If cantidad_entregada is being updated, adjust the production records
                if (isset($validated['cantidad_entregada']) && $validated['cantidad_entregada'] != $entrega->cantidad_entregada) {
                    $diferencia = $validated['cantidad_entregada'] - $entrega->cantidad_entregada;
                    
                    if ($tipo === 'pedido') {
                        \App\Models\RegistrosPorOrden::where('pedido', $entrega->pedido)
                            ->where('prenda', $entrega->prenda)
                            ->where('talla', $entrega->talla)
                            ->decrement('total_pendiente_por_talla', $diferencia);
                        
                        \App\Models\RegistrosPorOrden::where('pedido', $entrega->pedido)
                            ->where('prenda', $entrega->prenda)
                            ->where('talla', $entrega->talla)
                            ->increment('total_producido_por_talla', $diferencia);
                    } elseif ($tipo === 'bodega') {
                        \App\Models\RegistrosPorOrdenBodega::where('pedido', $entrega->pedido)
                            ->where('prenda', $entrega->prenda)
                            ->where('talla', $entrega->talla)
                            ->decrement('total_pendiente_por_talla', $diferencia);
                        
                        \App\Models\RegistrosPorOrdenBodega::where('pedido', $entrega->pedido)
                            ->where('prenda', $entrega->prenda)
                            ->where('talla', $entrega->talla)
                            ->increment('total_producido_por_talla', $diferencia);
                    }
                }

                $entrega->update($validated);

                return response()->json([
                    'success' => true,
                    'message' => 'Entrega actualizada exitosamente',
                    'entrega' => $entrega->fresh()
                ]);

            } elseif ($subtipo === 'corte') {
                $entrega = $config['corte']::findOrFail($id);
                
                $validated = $request->validate([
                    'pedido' => 'sometimes|integer',
                    'cortador' => 'sometimes|string',
                    'piezas' => 'sometimes|integer',
                    'pasadas' => 'sometimes|integer',
                    'etiquetador' => 'sometimes|string',
                    'fecha_entrega' => 'sometimes|date',
                ]);

                // Recalculate etiqueteadas: piezas * pasadas
                if (isset($validated['piezas']) || isset($validated['pasadas'])) {
                    $piezas = $validated['piezas'] ?? $entrega->piezas;
                    $pasadas = $validated['pasadas'] ?? $entrega->pasadas;
                    $validated['etiqueteadas'] = $piezas * $pasadas;
                }

                $entrega->update($validated);

                return response()->json([
                    'success' => true,
                    'message' => 'Entrega actualizada exitosamente',
                    'entrega' => $entrega->fresh()
                ]);
            }

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $tipo = $request->route('tipo');
            $subtipo = $request->route('subtipo');
            $id = $request->route('id');
            $config = $this->getModels($tipo);

            if ($subtipo === 'costura') {
                if ($tipo === 'pedido') {
                    // Para tipo='pedido', usar EntregaPrendaPedido
                    $entrega = EntregaPrendaPedido::findOrFail($id);
                    
                    // Revertir los cambios: restar lo producido y sumar lo pendiente
                    $entrega->total_producido_por_talla -= $entrega->total_producido_por_talla;
                    $entrega->calcularTotalPendiente();
                    $entrega->costurero = null;
                    $entrega->fecha_completado = null;
                    $entrega->save();

                    // Log news
                    News::create([
                        'event_type' => 'delivery_deleted',
                        'description' => "Entrega de costura eliminada: Pedido {$entrega->prendaPedido->pedido->numero_pedido}, Prenda {$entrega->prendaPedido->nombre_prenda}, Cantidad {$entrega->total_producido_por_talla}",
                        'user_id' => auth()->id(),
                        'pedido' => $entrega->prendaPedido->pedido->numero_pedido,
                        'metadata' => ['tipo' => $tipo, 'subtipo' => 'costura', 'cantidad' => $entrega->total_producido_por_talla]
                    ]);
                } else {
                    // Para tipo='bodega', usar EntregaBodegaCostura
                    $entrega = $config['costura']::findOrFail($id);
                    
                    \App\Models\RegistrosPorOrdenBodega::where('pedido', $entrega->pedido)
                        ->where('prenda', $entrega->prenda)
                        ->where('talla', $entrega->talla)
                        ->increment('total_pendiente_por_talla', $entrega->cantidad_entregada);
                    
                    \App\Models\RegistrosPorOrdenBodega::where('pedido', $entrega->pedido)
                        ->where('prenda', $entrega->prenda)
                        ->where('talla', $entrega->talla)
                        ->decrement('total_producido_por_talla', $entrega->cantidad_entregada);

                    $entrega->delete();

                    // Log news
                    News::create([
                        'event_type' => 'delivery_deleted',
                        'description' => "Entrega de costura eliminada: Pedido {$entrega->pedido}, Prenda {$entrega->prenda}, Cantidad {$entrega->cantidad_entregada}",
                        'user_id' => auth()->id(),
                        'pedido' => $entrega->pedido,
                        'metadata' => ['tipo' => $tipo, 'subtipo' => 'costura', 'cantidad' => $entrega->cantidad_entregada]
                    ]);
                }

                // Broadcast event
                broadcast(new EntregaEliminada($tipo, 'costura', $id, $entrega));

            } elseif ($subtipo === 'corte') {
                $entrega = $config['corte']::findOrFail($id);
                $entrega->delete();

                // Broadcast event
                broadcast(new EntregaEliminada($tipo, 'corte', $id, $entrega));

                // Log news
                News::create([
                    'event_type' => 'delivery_deleted',
                    'description' => "Entrega de corte eliminada: Pedido {$entrega->pedido}, Piezas {$entrega->piezas}",
                    'user_id' => auth()->id(),
                    'pedido' => $entrega->pedido,
                    'metadata' => ['tipo' => $tipo, 'subtipo' => 'corte', 'piezas' => $entrega->piezas]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Entrega eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }
}

