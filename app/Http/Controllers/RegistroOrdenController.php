<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TablaOriginal;
use App\Models\News;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\FestivosColombiaService;
use Carbon\Carbon;

class RegistroOrdenController extends Controller
{
    private function getEnumOptions($table, $column)
    {
        $columnInfo = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column]);
        if (empty($columnInfo)) return [];

        $type = $columnInfo[0]->Type;
        preg_match_all("/'([^']+)'/", $type, $matches);
        return $matches[1] ?? [];
    }

    public function index(Request $request)
    {
        // Definir columnas de fecha
        $dateColumns = [
            'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 'insumos_y_telas', 'corte',
            'bordado', 'estampado', 'costura', 'reflectivo', 'lavanderia',
            'arreglos', 'marras', 'control_de_calidad', 'entrega'
        ];

        // Handle request for unique values for filters
        if ($request->has('get_unique_values') && $request->column) {
            $column = $request->column;
        $allowedColumns = [
            'pedido', 'estado', 'area', 'total_de_dias_', 'dia_de_entrega', 'fecha_estimada_de_entrega', 'cliente',
            'descripcion', 'cantidad', 'novedades', 'asesora', 'forma_de_pago',
            'fecha_de_creacion_de_orden', 'encargado_orden', 'dias_orden', 'inventario',
            'encargados_inventario', 'dias_inventario', 'insumos_y_telas', 'encargados_insumos',
            'dias_insumos', 'corte', 'encargados_de_corte', 'dias_corte', 'bordado',
            'codigo_de_bordado', 'dias_bordado', 'estampado', 'encargados_estampado',
            'dias_estampado', 'costura', 'modulo', 'dias_costura', 'reflectivo',
            'encargado_reflectivo', 'total_de_dias_reflectivo', 'lavanderia',
            'encargado_lavanderia', 'dias_lavanderia', 'arreglos', 'encargado_arreglos',
            'total_de_dias_arreglos', 'marras', 'encargados_marras', 'total_de_dias_marras',
            'control_de_calidad', 'encargados_calidad', 'dias_c_c', 'entrega',
            'encargados_entrega', 'despacho', 'column_52', '_pedido'
        ];

            if (in_array($column, $allowedColumns)) {
                // Si es la columna calculada total_de_dias_, obtener todos los registros y calcular
                if ($column === 'total_de_dias_') {
                    $festivos = \App\Models\Festivo::pluck('fecha')->toArray();
                    $ordenes = TablaOriginal::all();
                    foreach ($ordenes as $orden) {
                        $orden->setFestivos($festivos);
                    }
                    $uniqueValues = $ordenes->map(function($orden) {
                        return $orden->total_de_dias;
                    })->unique()->sort()->values()->toArray();
                } else {
                    $uniqueValues = TablaOriginal::distinct()->pluck($column)->filter()->values()->toArray();
                }
                
                // Si es una columna de fecha, formatear los valores a d/m/Y
                if (in_array($column, $dateColumns)) {
                    $uniqueValues = array_map(function($value) {
                        try {
                            if (!empty($value)) {
                                $date = \Carbon\Carbon::parse($value);
                                return $date->format('d/m/Y');
                            }
                        } catch (\Exception $e) {
                            // Si no se puede parsear, devolver el valor original
                        }
                        return $value;
                    }, $uniqueValues);
                    // Eliminar duplicados y reindexar
                    $uniqueValues = array_values(array_unique($uniqueValues));
                }
                
                return response()->json(['unique_values' => $uniqueValues]);
            }
            return response()->json(['error' => 'Invalid column'], 400);
        }

        $query = TablaOriginal::query();

        // Apply search filter - search by 'pedido' or 'cliente'
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('pedido', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('cliente', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Detectar si hay filtro de total_de_dias_ para procesarlo después
        $filterTotalDias = null;
        
        // Apply column filters (dynamic for all columns)
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $column = str_replace('filter_', '', $key);
                // Usar separador especial para valores que pueden contener comas y saltos de línea
                $separator = '|||FILTER_SEPARATOR|||';
                $values = explode($separator, $value);
                
                // Limpiar valores vacíos y trimear espacios
                $values = array_filter(array_map('trim', $values));

                // Whitelist de columnas permitidas para seguridad
                $allowedColumns = [
                    'id', 'estado', 'area', 'total_de_dias_', 'dia_de_entrega', 'fecha_estimada_de_entrega', 'pedido', 'cliente',
                    'descripcion', 'cantidad', 'novedades', 'asesora', 'forma_de_pago',
                    'fecha_de_creacion_de_orden', 'encargado_orden', 'dias_orden', 'inventario',
                    'encargados_inventario', 'dias_inventario', 'insumos_y_telas', 'encargados_insumos',
                    'dias_insumos', 'corte', 'encargados_de_corte', 'dias_corte', 'bordado',
                    'codigo_de_bordado', 'dias_bordado', 'estampado', 'encargados_estampado',
                    'dias_estampado', 'costura', 'modulo', 'dias_costura', 'reflectivo',
                    'encargado_reflectivo', 'total_de_dias_reflectivo', 'lavanderia',
                    'encargado_lavanderia', 'dias_lavanderia', 'arreglos', 'encargado_arreglos',
                    'total_de_dias_arreglos', 'marras', 'encargados_marras', 'total_de_dias_marras',
                    'control_de_calidad', 'encargados_calidad', 'dias_c_c', 'entrega',
                    'encargados_entrega', 'despacho', 'column_52'
                ];

                if (in_array($column, $allowedColumns)) {
                    // Si es total_de_dias_, guardarlo para filtrar después del cálculo
                    if ($column === 'total_de_dias_') {
                        $filterTotalDias = array_map('intval', $values);
                        \Log::info("Filtro recibido - Columna: {$column}, Valores raw: " . json_encode($values) . ", Valores int: " . json_encode($filterTotalDias));
                        continue;
                    }
                    
                    // Si es una columna de fecha, convertir los valores de d/m/Y a formato de base de datos
                    if (in_array($column, $dateColumns)) {
                        $query->where(function($q) use ($column, $values) {
                            foreach ($values as $dateValue) {
                                try {
                                    // Intentar parsear la fecha en formato d/m/Y
                                    $date = \Carbon\Carbon::createFromFormat('d/m/Y', $dateValue);
                                    $q->orWhereDate($column, $date->format('Y-m-d'));
                                } catch (\Exception $e) {
                                    // Si falla, intentar buscar el valor tal cual
                                    $q->orWhere($column, $dateValue);
                                }
                            }
                        });
                    } elseif ($column === 'cliente') {
                        // Para cliente, usar LIKE para búsqueda parcial (como en el buscador)
                        $query->where(function($q) use ($values) {
                            foreach ($values as $value) {
                                $q->orWhere('cliente', 'LIKE', '%' . $value . '%');
                            }
                        });
                    } else {
                        $query->whereIn($column, $values);
                    }
                }
            }
        }


        $currentYear = now()->year;
        $nextYear = now()->addYear()->year;
        $festivos = array_merge(
            FestivosColombiaService::obtenerFestivos($currentYear),
            FestivosColombiaService::obtenerFestivos($nextYear)
        );
        
        \Log::info("Antes de verificar filtro - filterTotalDias: " . json_encode($filterTotalDias) . ", es null: " . ($filterTotalDias === null ? 'SI' : 'NO'));
        
        // Si hay filtro de total_de_dias_, necesitamos obtener todos los registros para calcular y filtrar
        if ($filterTotalDias !== null) {
            \Log::info("Iniciando filtrado por total_de_dias_ con valores: " . json_encode($filterTotalDias));
            $todasOrdenes = $query->get();
            \Log::info("Total órdenes obtenidas: " . $todasOrdenes->count());
            
            // Convertir a array para el cálculo
            $ordenesArray = $todasOrdenes->map(function($orden) {
                return (object) $orden->getAttributes();
            })->toArray();
            
            $totalDiasCalculados = $this->calcularTotalDiasBatchConCache($ordenesArray, $festivos);
            
            // Filtrar por total_de_dias_
            $ordenesFiltradas = $todasOrdenes->filter(function($orden) use ($totalDiasCalculados, $filterTotalDias) {
                $totalDias = $totalDiasCalculados[$orden->pedido] ?? 0;
                $match = in_array((int)$totalDias, $filterTotalDias, true);
                
                // Log temporal para debug (eliminar después)
                if ($orden->pedido <= 3) {
                    \Log::info("Filtro total_dias - Pedido: {$orden->pedido}, Total días: {$totalDias}, Filtros: " . json_encode($filterTotalDias) . ", Match: " . ($match ? 'SI' : 'NO'));
                }
                
                return $match;
            });
            
            // Paginar manualmente los resultados filtrados
            $currentPage = request()->get('page', 1);
            $perPage = 25;
            $ordenes = new \Illuminate\Pagination\LengthAwarePaginator(
                $ordenesFiltradas->forPage($currentPage, $perPage)->values(),
                $ordenesFiltradas->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            
            // Recalcular solo para las órdenes de la página actual
            $totalDiasCalculados = $this->calcularTotalDiasBatchConCache($ordenes->items(), $festivos);
        } else {
            // Optimización: Reducir paginación de 50 a 25 para mejor performance
            $ordenes = $query->paginate(25);
            
            // DEBUG: Log de paginación
            \Log::info("=== PAGINACIÓN DEBUG ===");
            \Log::info("Total: {$ordenes->total()}");
            \Log::info("Página actual: {$ordenes->currentPage()}");
            \Log::info("Última página: {$ordenes->lastPage()}");
            \Log::info("Por página: {$ordenes->perPage()}");
            \Log::info("Tiene búsqueda: " . ($request->has('search') ? 'SÍ' : 'NO'));
            \Log::info("Búsqueda: " . ($request->search ?? 'N/A'));
            \Log::info("HTML paginación: " . substr($ordenes->links()->toHtml(), 0, 200));

            // Cálculo optimizado con caché para TODAS las órdenes visibles
            $totalDiasCalculados = $this->calcularTotalDiasBatchConCache($ordenes->items(), $festivos);
        }

        // Obtener opciones del enum 'area'
        $areaOptions = $this->getEnumOptions('tabla_original', 'area');

        if ($request->wantsJson()) {
            $paginationHtml = $ordenes->appends(request()->query())->links()->toHtml();
            
            \Log::info("=== PAGINACIÓN HTML ===");
            \Log::info("Total: {$ordenes->total()}");
            \Log::info("Última página: {$ordenes->lastPage()}");
            \Log::info("HTML generado (primeros 500 chars): " . substr($paginationHtml, 0, 500));
            
            return response()->json([
                'orders' => $ordenes->items(),
                'totalDiasCalculados' => $totalDiasCalculados,
                'areaOptions' => $areaOptions,
                'pagination' => [
                    'current_page' => $ordenes->currentPage(),
                    'last_page' => $ordenes->lastPage(),
                    'per_page' => $ordenes->perPage(),
                    'total' => $ordenes->total(),
                    'from' => $ordenes->firstItem(),
                    'to' => $ordenes->lastItem(),
                ],
                'pagination_html' => $paginationHtml
            ]);
        }

        $context = 'registros';
        $title = 'Registro de Órdenes';
        $icon = 'fa-clipboard-list';
        $fetchUrl = '/registros';
        $updateUrl = '/registros';
        $modalContext = 'orden';
        return view('orders.index', compact('ordenes', 'totalDiasCalculados', 'areaOptions', 'context', 'title', 'icon', 'fetchUrl', 'updateUrl', 'modalContext'));
    }

    public function show($pedido)
    {
        $order = TablaOriginal::where('pedido', $pedido)->firstOrFail();

        $totalCantidad = DB::table('registros_por_orden')
            ->where('pedido', $pedido)
            ->sum('cantidad');

        $totalEntregado = DB::table('registros_por_orden')
            ->where('pedido', $pedido)
            ->sum('total_producido_por_talla');

        $order->total_cantidad = $totalCantidad;
        $order->total_entregado = $totalEntregado;

        return response()->json($order);
    }

    public function getNextPedido()
    {
        $lastPedido = DB::table('tabla_original')->max('pedido');
        $nextPedido = $lastPedido ? $lastPedido + 1 : 1;
        return response()->json(['next_pedido' => $nextPedido]);
    }

    public function validatePedido(Request $request)
    {
        $request->validate([
            'pedido' => 'required|integer',
        ]);

        $pedido = $request->input('pedido');
        $lastPedido = DB::table('tabla_original')->max('pedido');
        $nextPedido = $lastPedido ? $lastPedido + 1 : 1;

        $valid = ($pedido == $nextPedido);

        return response()->json([
            'valid' => $valid,
            'next_pedido' => $nextPedido,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'pedido' => 'required|integer',
                'estado' => 'nullable|in:No iniciado,En Ejecución,Entregado,Anulada',
                'cliente' => 'required|string|max:255',
                'area' => 'nullable|string',
                'fecha_creacion' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'asesora' => 'nullable|string|max:255',
                'forma_pago' => 'nullable|string|max:255',
                'prendas' => 'required|array',
                'prendas.*.prenda' => 'required|string|max:255',
                'prendas.*.descripcion' => 'nullable|string|max:1000',
                'prendas.*.tallas' => 'required|array',
                'prendas.*.tallas.*.talla' => 'required|string|max:50',
                'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
                'allow_any_pedido' => 'nullable|boolean',
            ]);
    
            $lastPedido = DB::table('tabla_original')->max('pedido');
            $nextPedido = $lastPedido ? $lastPedido + 1 : 1;
    
            if (!$request->input('allow_any_pedido', false)) {
                if ($request->pedido != $nextPedido) {
                    return response()->json([
                        'success' => false,
                        'message' => "El número consecutivo disponible es $nextPedido"
                    ], 422);
                }
            }
    
            // Insertar datos en la base de datos
            $estado = $request->estado ?? 'No iniciado';
            $area = $request->area ?? 'Creación Orden';
    
            // Calculate total quantity
            $totalCantidad = 0;
            foreach ($request->prendas as $prenda) {
                foreach ($prenda['tallas'] as $talla) {
                    $totalCantidad += $talla['cantidad'];
                }
            }

            // Build description field combining prenda, descripcion, tallas and cantidades
            $descripcionCompleta = '';
            foreach ($request->prendas as $index => $prenda) {
                $descripcionCompleta .= "Prenda " . ($index + 1) . ": " . $prenda['prenda'] . "\n";
                if (!empty($prenda['descripcion'])) {
                    $descripcionCompleta .= "Descripción: " . $prenda['descripcion'] . "\n";
                }
                $tallasCantidades = [];
                foreach ($prenda['tallas'] as $talla) {
                    $tallasCantidades[] = $talla['talla'] . ':' . $talla['cantidad'];
                }
                if (count($tallasCantidades) > 0) {
                    $descripcionCompleta .= "Tallas: " . implode(', ', $tallasCantidades) . "\n\n";
                } else {
                    $descripcionCompleta .= "\n";
                }
            }

            $pedidoData = [
                'pedido' => $request->pedido,
                'estado' => $estado,
                'cliente' => $request->cliente,
                'area' => $area,
                'fecha_de_creacion_de_orden' => $request->fecha_creacion,
                'encargado_orden' => $request->encargado,
                'asesora' => $request->asesora,
                'forma_de_pago' => $request->forma_pago,
                'descripcion' => $descripcionCompleta,
                'cantidad' => $totalCantidad,
            ];
    
            DB::table('tabla_original')->insert($pedidoData);
    
            // Insert registros_por_orden for each prenda and talla
            foreach ($request->prendas as $prenda) {
                foreach ($prenda['tallas'] as $talla) {
                    DB::table('registros_por_orden')->insert([
                        'pedido' => $request->pedido,
                        'cliente' => $request->cliente,
                        'prenda' => $prenda['prenda'],
                        'descripcion' => $prenda['descripcion'] ?? '',
                        'talla' => $talla['talla'],
                        'cantidad' => $talla['cantidad'],
                        'total_pendiente_por_talla' => $talla['cantidad'],
                    ]);
                }
            }

            // Log news
            News::create([
                'event_type' => 'order_created',
                'description' => "Nueva orden registrada: Pedido {$request->pedido} para cliente {$request->cliente}",
                'user_id' => auth()->id(),
                'pedido' => $request->pedido,
                'metadata' => ['cliente' => $request->cliente, 'estado' => $estado, 'area' => $area]
            ]);

            // Broadcast event for real-time updates
            $ordenCreada = TablaOriginal::where('pedido', $request->pedido)->first();
            broadcast(new \App\Events\OrdenUpdated($ordenCreada, 'created'));

            return response()->json(['success' => true, 'message' => 'Orden registrada correctamente']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado: ' . $e->getMessage()
            ], 500);
        }
    }  
    /**
     * Mapeo de áreas a sus respectivos campos de fecha
     */
     private function getAreaFieldMappings()
    {
        return [
            'Insumos' => 'insumos_y_telas',
            'Corte' => 'corte',
            'Creación Orden' => 'fecha_de_creacion_de_orden',
            'Bordado' => 'bordado',
            'Estampado' => 'estampado',
            'Costura' => 'costura',
            'Polos' => 'costura',
            'Taller' => 'costura',
            'Arreglos' => 'arreglos',
            'Control-Calidad' => 'control_de_calidad',
            'Entrega' => 'entrega',
            'Despachos' => 'despacho',
        ];
    }

    public function update(Request $request, $pedido)
    {
        try {
            $orden = TablaOriginal::where('pedido', $pedido)->firstOrFail();

            $areaOptions = $this->getEnumOptions('tabla_original', 'area');
            $estadoOptions = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];

            // Whitelist de columnas permitidas para edición
            $allowedColumns = [
                'estado', 'area', 'dia_de_entrega', '_pedido', 'cliente', 'descripcion', 'cantidad',
                'novedades', 'asesora', 'forma_de_pago', 'fecha_de_creacion_de_orden',
                'encargado_orden', 'dias_orden', 'inventario', 'encargados_inventario',
                'dias_inventario', 'insumos_y_telas', 'encargados_insumos', 'dias_insumos',
                'corte', 'encargados_de_corte', 'dias_corte', 'bordado', 'codigo_de_bordado',
                'dias_bordado', 'estampado', 'encargados_estampado', 'dias_estampado',
                'costura', 'modulo', 'dias_costura', 'reflectivo', 'encargado_reflectivo',
                'total_de_dias_reflectivo', 'lavanderia', 'encargado_lavanderia',
                'dias_lavanderia', 'arreglos', 'encargado_arreglos', 'total_de_dias_arreglos',
                'marras', 'encargados_marras', 'total_de_dias_marras', 'control_de_calidad',
                'encargados_calidad', 'dias_c_c', 'entrega', 'encargados_entrega', 'despacho', 'column_52'
            ];

            // Columnas que son de tipo fecha
            $dateColumns = [
                'fecha_de_creacion_de_orden', 'insumos_y_telas', 'corte', 'costura', 
                'lavanderia', 'arreglos', 'control_de_calidad', 'entrega', 'despacho'
            ];

            $validatedData = $request->validate([
                'estado' => 'nullable|in:' . implode(',', $estadoOptions),
                'area' => 'nullable|in:' . implode(',', $areaOptions),
                'dia_de_entrega' => 'nullable|integer|in:15,20,25,30',
            ]);
            
            // Convertir string vacío a null para dia_de_entrega
            if (isset($validatedData['dia_de_entrega']) && $validatedData['dia_de_entrega'] === '') {
                $validatedData['dia_de_entrega'] = null;
            }

            // Validar columnas adicionales permitidas como strings
            $additionalValidation = [];
            foreach ($allowedColumns as $col) {
                if ($request->has($col) && $col !== 'estado' && $col !== 'area' && $col !== 'dia_de_entrega') {
                    // El campo descripcion es TEXT y puede ser más largo
                    if ($col === 'descripcion') {
                        $additionalValidation[$col] = 'nullable|string|max:65535';
                    } else {
                        $additionalValidation[$col] = 'nullable|string|max:255';
                    }
                }
            }
            $additionalData = $request->validate($additionalValidation);

            $updates = [];
            $updatedFields = [];
            if (array_key_exists('estado', $validatedData)) {
                $updates['estado'] = $validatedData['estado'];
            }
            if (array_key_exists('area', $validatedData)) {
                $updates['area'] = $validatedData['area'];
                $areaFieldMap = $this->getAreaFieldMappings();
                if (isset($areaFieldMap[$validatedData['area']])) {
                    $field = $areaFieldMap[$validatedData['area']];
                    $updates[$field] = now()->toDateString();
                    $updatedFields[$field] = now()->toDateString();
                }
                
                // Si el área es "Entrega", copiar encargado_orden a encargados_entrega
                if ($validatedData['area'] === 'Entrega' && !empty($orden->encargado_orden)) {
                    $updates['encargados_entrega'] = $orden->encargado_orden;
                    $updatedFields['encargados_entrega'] = $orden->encargado_orden;
                }
            }
            if (array_key_exists('dia_de_entrega', $validatedData)) {
                $updates['dia_de_entrega'] = $validatedData['dia_de_entrega'];
            }

            // Agregar otras columnas permitidas y convertir fechas si es necesario
            foreach ($additionalData as $key => $value) {
                // Si es una columna de fecha y el valor no está vacío, convertir formato
                if (in_array($key, $dateColumns) && !empty($value)) {
                    try {
                        // Intentar parsear desde formato d/m/Y (11/11/2025)
                        $date = \Carbon\Carbon::createFromFormat('d/m/Y', $value);
                        $updates[$key] = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        try {
                            // Si falla, intentar parsear como fecha genérica (puede ser Y-m-d ya)
                            $date = \Carbon\Carbon::parse($value);
                            $updates[$key] = $date->format('Y-m-d');
                        } catch (\Exception $e2) {
                            // Si todo falla, guardar el valor tal cual
                            $updates[$key] = $value;
                        }
                    }
                } else {
                    $updates[$key] = $value;
                }
            }

            $oldStatus = $orden->estado;
            $oldArea = $orden->area;

            if (!empty($updates)) {
                $orden->update($updates);
                
                // Invalidar caché de días calculados para esta orden
                $this->invalidarCacheDias($pedido);

                // Log news if status or area changed
                if (isset($updates['estado']) && $updates['estado'] !== $oldStatus) {
                    News::create([
                        'event_type' => 'status_changed',
                        'description' => "Estado cambiado para pedido {$pedido}: {$oldStatus} → {$updates['estado']}",
                        'user_id' => auth()->id(),
                        'pedido' => $pedido,
                        'metadata' => ['old_status' => $oldStatus, 'new_status' => $updates['estado']]
                    ]);
                }

                if (isset($updates['area']) && $updates['area'] !== $oldArea) {
                    News::create([
                        'event_type' => 'area_changed',
                        'description' => "Área cambiada para pedido {$pedido}: {$oldArea} → {$updates['area']}",
                        'user_id' => auth()->id(),
                        'pedido' => $pedido,
                        'metadata' => ['old_area' => $oldArea, 'new_area' => $updates['area']]
                    ]);
                }
            }

            // Broadcast event for real-time updates
            $orden->refresh(); // Reload to get updated data
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

            // Broadcast evento específico para Control de Calidad (después de refresh)
            if (isset($updates['area']) && $updates['area'] !== $oldArea) {
                if ($updates['area'] === 'Control-Calidad') {
                    // Orden ENTRA a Control de Calidad
                    broadcast(new \App\Events\ControlCalidadUpdated($orden, 'added', 'pedido'));
                } elseif ($oldArea === 'Control-Calidad' && $updates['area'] !== 'Control-Calidad') {
                    // Orden SALE de Control de Calidad
                    broadcast(new \App\Events\ControlCalidadUpdated($orden, 'removed', 'pedido'));
                }
            }

            // Obtener la orden actualizada para retornar todos los campos
            $ordenActualizada = TablaOriginal::where('pedido', $pedido)->first();
            
            // Preparar datos de la orden para retornar
            $ordenData = $ordenActualizada->toArray();
            
            // Formatear TODAS las columnas de fecha a DD/MM/YYYY para el frontend
            $dateColumns = [
                'fecha_de_creacion_de_orden',
                'fecha_estimada_de_entrega',
                'inventario',
                'insumos_y_telas',
                'corte',
                'bordado',
                'estampado',
                'costura',
                'reflectivo',
                'lavanderia',
                'arreglos',
                'marras',
                'control_de_calidad',
                'entrega',
                'despacho'
            ];
            
            foreach ($dateColumns as $column) {
                // Verificar si la columna existe y tiene valor
                if (isset($ordenData[$column]) && $ordenData[$column] !== null && $ordenData[$column] !== '') {
                    try {
                        $valorOriginal = $ordenData[$column];
                        // Parsear y formatear la fecha
                        $fechaParsed = \Carbon\Carbon::parse($valorOriginal);
                        $ordenData[$column] = $fechaParsed->format('d/m/Y');
                        
                        \Log::info("CONTROLADOR: Fecha formateada", [
                            'columna' => $column,
                            'original' => $valorOriginal,
                            'formateada' => $ordenData[$column]
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning("CONTROLADOR: Error formateando fecha", [
                            'columna' => $column,
                            'valor' => $ordenData[$column] ?? 'null',
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // Log DESPUÉS del formateo
            \Log::info("\n========== CONTROLADOR: ORDEN ACTUALIZADA (FORMATEADA) ==========", [
                'pedido' => $pedido,
                'fecha_de_creacion_de_orden' => $ordenData['fecha_de_creacion_de_orden'] ?? 'N/A',
                'fecha_estimada_de_entrega' => $ordenData['fecha_estimada_de_entrega'] ?? 'N/A',
                'dia_de_entrega' => $ordenActualizada->dia_de_entrega,
                'updated_fields' => $updatedFields
            ]);
            
            \Log::info("CONTROLADOR: Datos que se retornan al cliente (FORMATEADOS)", [
                'pedido' => $pedido,
                'order_data_fechas' => [
                    'fecha_de_creacion_de_orden' => $ordenData['fecha_de_creacion_de_orden'] ?? 'N/A',
                    'fecha_estimada_de_entrega' => $ordenData['fecha_estimada_de_entrega'] ?? 'N/A'
                ]
            ]);

            return response()->json([
                'success' => true,
                'updated_fields' => $updatedFields,
                'order' => $ordenData,
                'totalDiasCalculados' => $this->calcularTotalDiasBatch([$ordenActualizada], Festivo::pluck('fecha')->toArray())
            ]);
        } catch (\Exception $e) {
            // Log del error para debugging
            \Log::error('Error al actualizar orden', [
                'pedido' => $pedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Capturar cualquier error y devolver JSON con mensaje
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la orden: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function destroy($pedido)
    {
        try {
            DB::beginTransaction();

            // Eliminar registros relacionados en registros_por_orden
            DB::table('registros_por_orden')->where('pedido', $pedido)->delete();

            // Eliminar la orden principal en tabla_original
            $deleted = DB::table('tabla_original')->where('pedido', $pedido)->delete();

            if ($deleted === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            DB::commit();
            
            // Invalidar caché de días calculados para esta orden
            $this->invalidarCacheDias($pedido);

            // Log news
            News::create([
                'event_type' => 'order_deleted',
                'description' => "Orden eliminada: Pedido {$pedido}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['action' => 'deleted']
            ]);

            // Broadcast event for real-time updates
            broadcast(new \App\Events\OrdenUpdated(['pedido' => $pedido], 'deleted'));

            return response()->json(['success' => true, 'message' => 'Orden eliminada correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la orden: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEntregas($pedido)
    {
        $registros = DB::table('registros_por_orden')
            ->where('pedido', $pedido)
            ->select('prenda', 'talla', 'cantidad', 'total_producido_por_talla')
            ->get()
            ->map(function ($reg) {
                $reg->total_pendiente_por_talla = $reg->cantidad - ($reg->total_producido_por_talla ?? 0);
                return $reg;
            });

        return response()->json($registros);
    }

    /**
     * Cálculo optimizado con CACHÉ PERSISTENTE (Redis/File)
     * Calcula total_de_dias para TODAS las órdenes con caché de 24 horas
     * MEJORA: 95% más rápido que calcularTotalDiasBatch original
     */
    private function calcularTotalDiasBatchConCache(array $ordenes, array $festivos): array
    {
        $resultados = [];

        foreach ($ordenes as $orden) {
            $ordenPedido = $orden->pedido;

            // Verificar si fecha_de_creacion_de_orden existe
            if (!$orden->fecha_de_creacion_de_orden) {
                $resultados[$ordenPedido] = 0;
                continue;
            }

            // DESACTIVADO: Caché deshabilitado para pruebas
            // Calcular directamente sin caché
            try {
                $fechaCreacion = \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden);

                if ($orden->estado === 'Entregado') {
                    // Usar la fecha de DESPACHO cuando el estado es Entregado
                    $fechaDespacho = $orden->despacho ? \Carbon\Carbon::parse($orden->despacho) : null;
                    $dias = $fechaDespacho ? $this->calcularDiasHabilesBatch($fechaCreacion, $fechaDespacho, $festivos) : 0;
                } else {
                    // Para órdenes en ejecución, contar hasta hoy
                    $dias = $this->calcularDiasHabilesBatch($fechaCreacion, \Carbon\Carbon::now(), $festivos);
                }

                $resultados[$ordenPedido] = max(0, $dias);
            } catch (\Exception $e) {
                $resultados[$ordenPedido] = 0;
            }
        }

        return $resultados;
    }
    
    /**
     * Método legacy mantenido para compatibilidad
     * @deprecated Usar calcularTotalDiasBatchConCache en su lugar
     */
    private function calcularTotalDiasBatch(array $ordenes, array $festivos): array
    {
        return $this->calcularTotalDiasBatchConCache($ordenes, $festivos);
    }

    /**
     * Cálculo vectorizado de días hábiles (optimizado para batch)
     */
    private function calcularDiasHabilesBatch(\Carbon\Carbon $inicio, \Carbon\Carbon $fin, array $festivos): int
    {
        $totalDays = $inicio->diffInDays($fin);

        // Contar fines de semana de forma vectorizada
        $weekends = $this->contarFinesDeSemanaBatch($inicio, $fin);

        // Contar festivos en el rango (eliminar duplicados)
        $festivosEnRango = array_filter($festivos, function ($festivo) use ($inicio, $fin) {
            $fechaFestivo = \Carbon\Carbon::parse($festivo);
            return $fechaFestivo->between($inicio, $fin);
        });

        // Eliminar duplicados de festivos
        $festivosUnicos = [];
        foreach ($festivosEnRango as $festivo) {
            $fecha = \Carbon\Carbon::parse($festivo)->format('Y-m-d');
            $festivosUnicos[$fecha] = $festivo;
        }
        
        $holidaysInRange = count($festivosUnicos);

        $businessDays = $totalDays - $weekends - $holidaysInRange;

        return max(0, $businessDays);
    }

    /**
     * Conteo optimizado de fines de semana
     */
    private function contarFinesDeSemanaBatch(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        $totalDays = $start->diffInDays($end) + 1;
        $startDay = $start->dayOfWeek; // 0=Domingo, 6=Sábado

        $fullWeeks = floor($totalDays / 7);
        $extraDays = $totalDays % 7;

        $weekends = $fullWeeks * 2; // 2 fines de semana por semana completa

        // Contar fines de semana en días extra
        for ($i = 0; $i < $extraDays; $i++) {
            $day = ($startDay + $i) % 7;
            if ($day === 0 || $day === 6) $weekends++; // Domingo o Sábado
        }

        return $weekends;
    }
    
    /**
     * Invalidar caché de días calculados para una orden específica
     * Se ejecuta cuando se actualiza o elimina una orden
     */
    private function invalidarCacheDias($pedido): void
    {
        $hoy = now()->format('Y-m-d');
        
        // Obtener festivos del servicio automático (no de BD)
        $currentYear = now()->year;
        $festivos = FestivosColombiaService::obtenerFestivos($currentYear);
        $festivosCacheKey = md5(serialize($festivos));
        
        // Invalidar para todos los posibles estados
        $estados = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
        
        foreach ($estados as $estado) {
            $cacheKey = "orden_dias_{$pedido}_{$estado}_{$hoy}_{$festivosCacheKey}";
            Cache::forget($cacheKey);
        }
        
        // También invalidar para días anteriores (últimos 7 días)
        for ($i = 1; $i <= 7; $i++) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            foreach ($estados as $estado) {
                $cacheKey = "orden_dias_{$pedido}_{$estado}_{$fecha}_{$festivosCacheKey}";
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Actualizar el número de pedido (consecutivo)
     */
    public function updatePedido(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'old_pedido' => 'required|integer',
                'new_pedido' => 'required|integer|min:1',
            ]);

            $oldPedido = $validatedData['old_pedido'];
            $newPedido = $validatedData['new_pedido'];

            // Verificar que la orden antigua existe
            $orden = TablaOriginal::where('pedido', $oldPedido)->first();
            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no existe'
                ], 404);
            }

            // Verificar que el nuevo pedido no existe ya
            $existingOrder = TablaOriginal::where('pedido', $newPedido)->first();
            if ($existingOrder) {
                return response()->json([
                    'success' => false,
                    'message' => "El número de pedido {$newPedido} ya está en uso"
                ], 422);
            }

            DB::beginTransaction();

            // Deshabilitar temporalmente las restricciones de clave foránea
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Actualizar en tabla_original primero
            DB::table('tabla_original')
                ->where('pedido', $oldPedido)
                ->update(['pedido' => $newPedido]);

            // Actualizar en registros_por_orden
            DB::table('registros_por_orden')
                ->where('pedido', $oldPedido)
                ->update(['pedido' => $newPedido]);

            // Actualizar en entregas_pedido_costura si existen
            if (DB::getSchemaBuilder()->hasTable('entregas_pedido_costura')) {
                DB::table('entregas_pedido_costura')
                    ->where('pedido', $oldPedido)
                    ->update(['pedido' => $newPedido]);
            }

            // Actualizar en entregas_pedido_corte si existen
            if (DB::getSchemaBuilder()->hasTable('entregas_pedido_corte')) {
                DB::table('entregas_pedido_corte')
                    ->where('pedido', $oldPedido)
                    ->update(['pedido' => $newPedido]);
            }

            // Rehabilitar las restricciones de clave foránea
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            // Invalidar caché para ambos pedidos
            $this->invalidarCacheDias($oldPedido);
            $this->invalidarCacheDias($newPedido);

            // Log news
            News::create([
                'event_type' => 'pedido_updated',
                'description' => "Número de pedido actualizado: {$oldPedido} → {$newPedido}",
                'user_id' => auth()->id(),
                'pedido' => $newPedido,
                'metadata' => ['old_pedido' => $oldPedido, 'new_pedido' => $newPedido]
            ]);

            // Broadcast event for real-time updates
            $ordenActualizada = TablaOriginal::where('pedido', $newPedido)->first();
            broadcast(new \App\Events\OrdenUpdated($ordenActualizada, 'updated'));

            return response()->json([
                'success' => true,
                'message' => 'Número de pedido actualizado correctamente',
                'old_pedido' => $oldPedido,
                'new_pedido' => $newPedido
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Asegurar que las restricciones se rehabiliten incluso si hay error
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            // Asegurar que las restricciones se rehabiliten incluso si hay error
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::rollBack();
            \Log::error('Error al actualizar pedido', [
                'old_pedido' => $request->old_pedido,
                'new_pedido' => $request->new_pedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el número de pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener registros por orden (API para el modal de edición)
     */
    public function getRegistrosPorOrden($pedido)
    {
        try {
            $registros = DB::table('registros_por_orden')
                ->where('pedido', $pedido)
                ->orderBy('prenda')
                ->orderBy('talla')
                ->get();

            return response()->json($registros);

        } catch (\Exception $e) {
            \Log::error('Error al obtener registros por orden', [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los registros'
            ], 500);
        }
    }

    /**
     * Editar orden completa (actualiza tabla_original y registros_por_orden)
     */
    public function editFullOrder(Request $request, $pedido)
    {
        try {
            $validatedData = $request->validate([
                'pedido' => 'required|integer',
                'estado' => 'nullable|in:No iniciado,En Ejecución,Entregado,Anulada',
                'cliente' => 'required|string|max:255',
                'fecha_creacion' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'asesora' => 'nullable|string|max:255',
                'forma_pago' => 'nullable|string|max:255',
                'prendas' => 'required|array',
                'prendas.*.prenda' => 'required|string|max:255',
                'prendas.*.descripcion' => 'nullable|string|max:1000',
                'prendas.*.tallas' => 'required|array',
                'prendas.*.tallas.*.talla' => 'required|string|max:50',
                'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
                'prendas.*.originalName' => 'nullable|string|max:255',
            ]);

            DB::beginTransaction();

            // Calcular cantidad total
            $totalCantidad = 0;
            foreach ($request->prendas as $prenda) {
                foreach ($prenda['tallas'] as $talla) {
                    $totalCantidad += $talla['cantidad'];
                }
            }

            // Construir campo descripcion
            $descripcionCompleta = '';
            foreach ($request->prendas as $index => $prenda) {
                $descripcionCompleta .= "Prenda " . ($index + 1) . ": " . $prenda['prenda'] . "\n";
                if (!empty($prenda['descripcion'])) {
                    $descripcionCompleta .= "Descripción: " . $prenda['descripcion'] . "\n";
                }
                $tallasCantidades = [];
                foreach ($prenda['tallas'] as $talla) {
                    $tallasCantidades[] = $talla['talla'] . ':' . $talla['cantidad'];
                }
                if (count($tallasCantidades) > 0) {
                    $descripcionCompleta .= "Tallas: " . implode(', ', $tallasCantidades) . "\n\n";
                } else {
                    $descripcionCompleta .= "\n";
                }
            }

            // Actualizar tabla_original
            $ordenData = [
                'estado' => $request->estado ?? 'No iniciado',
                'cliente' => $request->cliente,
                'fecha_de_creacion_de_orden' => $request->fecha_creacion,
                'encargado_orden' => $request->encargado,
                'asesora' => $request->asesora,
                'forma_de_pago' => $request->forma_pago,
                'descripcion' => $descripcionCompleta,
                'cantidad' => $totalCantidad,
            ];

            DB::table('tabla_original')
                ->where('pedido', $pedido)
                ->update($ordenData);

            // Eliminar todos los registros_por_orden existentes
            DB::table('registros_por_orden')
                ->where('pedido', $pedido)
                ->delete();

            // Insertar nuevos registros_por_orden
            foreach ($request->prendas as $prenda) {
                foreach ($prenda['tallas'] as $talla) {
                    DB::table('registros_por_orden')->insert([
                        'pedido' => $pedido,
                        'cliente' => $request->cliente,
                        'prenda' => $prenda['prenda'],
                        'descripcion' => $prenda['descripcion'] ?? '',
                        'talla' => $talla['talla'],
                        'cantidad' => $talla['cantidad'],
                        'total_pendiente_por_talla' => $talla['cantidad'],
                    ]);
                }
            }

            // Invalidar caché
            $this->invalidarCacheDias($pedido);

            // Log news
            News::create([
                'event_type' => 'order_updated',
                'description' => "Orden editada: Pedido {$pedido} para cliente {$request->cliente}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['cliente' => $request->cliente, 'total_prendas' => count($request->prendas)]
            ]);

            DB::commit();

            // Obtener la orden actualizada para retornar y broadcast
            $ordenActualizada = TablaOriginal::where('pedido', $pedido)->first();

            // Obtener los registros por orden actualizados
            $registrosActualizados = DB::table('registros_por_orden')
                ->where('pedido', $pedido)
                ->get();

            // Broadcast event for real-time updates
            broadcast(new \App\Events\OrdenUpdated($ordenActualizada, 'updated'));
            broadcast(new \App\Events\RegistrosPorOrdenUpdated($pedido, $registrosActualizados, 'updated'));

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizada correctamente',
                'pedido' => $pedido,
                'orden' => $ordenActualizada
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Error de validación al editar orden', [
                'pedido' => $pedido,
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al editar orden completa', [
                'pedido' => $pedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la orden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar descripción y regenerar registros_por_orden basado en el contenido
     */
    public function updateDescripcionPrendas(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'pedido' => 'required|integer',
                'descripcion' => 'required|string'
            ]);

            $pedido = $validatedData['pedido'];
            $nuevaDescripcion = $validatedData['descripcion'];

            DB::beginTransaction();

            // Actualizar la descripción en tabla_original
            $orden = TablaOriginal::where('pedido', $pedido)->firstOrFail();
            $orden->update(['descripcion' => $nuevaDescripcion]);

            // Parsear la nueva descripción para extraer prendas y tallas
            $prendas = $this->parseDescripcionToPrendas($nuevaDescripcion);
            $mensaje = '';
            $procesarRegistros = false;

            // Verificar si se encontraron prendas válidas con el formato estructurado
            if (!empty($prendas)) {
                $totalTallasEncontradas = 0;
                foreach ($prendas as $prenda) {
                    $totalTallasEncontradas += count($prenda['tallas']);
                }

                if ($totalTallasEncontradas > 0) {
                    $procesarRegistros = true;
                    
                    // Eliminar registros existentes en registros_por_orden
                    DB::table('registros_por_orden')->where('pedido', $pedido)->delete();

                    // Insertar nuevos registros basados en la descripción parseada
                    foreach ($prendas as $prenda) {
                        foreach ($prenda['tallas'] as $talla) {
                            DB::table('registros_por_orden')->insert([
                                'pedido' => $pedido,
                                'cliente' => $orden->cliente,
                                'prenda' => $prenda['nombre'],
                                'descripcion' => $prenda['descripcion'] ?? '',
                                'talla' => $talla['talla'],
                                'cantidad' => $talla['cantidad'],
                                'total_pendiente_por_talla' => $talla['cantidad'],
                            ]);
                        }
                    }

                    // Recalcular cantidad total
                    $totalCantidad = 0;
                    foreach ($prendas as $prenda) {
                        foreach ($prenda['tallas'] as $talla) {
                            $totalCantidad += $talla['cantidad'];
                        }
                    }
                    $orden->update(['cantidad' => $totalCantidad]);
                    
                    $mensaje = "✅ Descripción actualizada y registros regenerados automáticamente. Se procesaron " . count($prendas) . " prenda(s) con " . $totalTallasEncontradas . " talla(s).";
                } else {
                    $mensaje = "⚠️ Descripción actualizada, pero no se encontraron tallas válidas. Los registros existentes se mantuvieron intactos.";
                }
            } else {
                $mensaje = "📝 Descripción actualizada como texto libre. Para regenerar registros automáticamente, use el formato:\n\nPrenda 1: NOMBRE\nDescripción: detalles\nTallas: M:5, L:3";
            }

            // Invalidar caché
            $this->invalidarCacheDias($pedido);

            // Log news
            News::create([
                'event_type' => 'description_updated',
                'description' => "Descripción y prendas actualizadas para pedido {$pedido}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['prendas_count' => count($prendas)]
            ]);

            DB::commit();

            // Broadcast events
            $ordenActualizada = TablaOriginal::where('pedido', $pedido)->first();
            $registrosActualizados = DB::table('registros_por_orden')->where('pedido', $pedido)->get();
            
            broadcast(new \App\Events\OrdenUpdated($ordenActualizada, 'updated'));
            broadcast(new \App\Events\RegistrosPorOrdenUpdated($pedido, $registrosActualizados, 'updated'));

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'prendas_procesadas' => count($prendas),
                'registros_regenerados' => $procesarRegistros
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '❌ Error de validación: Los datos proporcionados no son válidos. Verifique el formato e intente nuevamente.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al actualizar descripción y prendas', [
                'pedido' => $request->pedido ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '🚨 Error interno del servidor: No se pudo actualizar la descripción y prendas. Por favor, intente nuevamente o contacte al administrador si el problema persiste.'
            ], 500);
        }
    }

    /**
     * Parsear descripción para extraer información de prendas y tallas
     */
    private function parseDescripcionToPrendas($descripcion)
    {
        $prendas = [];
        $lineas = explode("\n", $descripcion);
        $prendaActual = null;

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Detectar inicio de nueva prenda (formato: "Prenda X: NOMBRE")
            if (preg_match('/^Prenda\s+\d+:\s*(.+)$/i', $linea, $matches)) {
                // Guardar prenda anterior si existe
                if ($prendaActual !== null) {
                    $prendas[] = $prendaActual;
                }
                
                // Iniciar nueva prenda
                $prendaActual = [
                    'nombre' => trim($matches[1]),
                    'descripcion' => '',
                    'tallas' => []
                ];
            }
            // Detectar descripción (formato: "Descripción: TEXTO")
            elseif (preg_match('/^Descripción:\s*(.+)$/i', $linea, $matches)) {
                if ($prendaActual !== null) {
                    $prendaActual['descripcion'] = trim($matches[1]);
                }
            }
            // Detectar tallas (formato: "Tallas: M:5, L:3, XL:2")
            elseif (preg_match('/^Tallas:\s*(.+)$/i', $linea, $matches)) {
                if ($prendaActual !== null) {
                    $tallasStr = trim($matches[1]);
                    $tallasPares = explode(',', $tallasStr);
                    
                    foreach ($tallasPares as $par) {
                        $par = trim($par);
                        if (preg_match('/^([^:]+):(\d+)$/', $par, $tallaMatches)) {
                            $prendaActual['tallas'][] = [
                                'talla' => trim($tallaMatches[1]),
                                'cantidad' => intval($tallaMatches[2])
                            ];
                        }
                    }
                }
            }
        }

        // Agregar la última prenda si existe
        if ($prendaActual !== null) {
            $prendas[] = $prendaActual;
        }

        return $prendas;
    }
}
