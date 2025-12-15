<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TablaOriginalBodega;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;
use App\Services\RegistroBodegaQueryService;
use App\Services\RegistroBodegaSearchService;
use App\Services\RegistroBodegaFilterService;

class RegistroBodegaController extends Controller
{
    protected $queryService;
    protected $searchService;
    protected $filterService;

    public function __construct(
        RegistroBodegaQueryService $queryService,
        RegistroBodegaSearchService $searchService,
        RegistroBodegaFilterService $filterService
    )
    {
        $this->queryService = $queryService;
        $this->searchService = $searchService;
        $this->filterService = $filterService;
    }

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
        // Handle request for unique values for filters
        if ($request->has('get_unique_values') && $request->column) {
            try {
                $values = $this->queryService->getUniqueValues($request->column);
                
                // Si es descripcion, devolver también los IDs asociados
                if ($request->column === 'descripcion') {
                    $result = [];
                    foreach ($values as $desc) {
                        $ids = TablaOriginalBodega::where('descripcion', $desc)->pluck('pedido')->toArray();
                        $result[] = [
                            'value' => $desc,
                            'ids' => $ids
                        ];
                    }
                    return response()->json(['unique_values' => $values, 'value_ids' => $result]);
                }
                
                return response()->json(['unique_values' => $values]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['error' => 'Invalid column'], 400);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error fetching values: ' . $e->getMessage()], 500);
            }
        }

        $query = $this->queryService->buildBaseQuery();
        $query = $this->searchService->applySearchFilter($query, $request->input('search'));

        // Extraer y aplicar filtros dinámicos
        $filterData = $this->filterService->extractFiltersFromRequest($request);
        $query = $this->filterService->applyFiltersToQuery($query, $filterData['filters']);
        $query = $this->filterService->applyPedidoIdFilter($query, $filterData['pedidoIds']);
        $filterTotalDias = $filterData['totalDiasFilter'];

        $festivos = Festivo::pluck('fecha')->toArray();
        
        // Si hay filtro de total_de_dias_, necesitamos obtener todos los registros para calcular y filtrar
        if ($filterTotalDias !== null) {
            $todasOrdenes = $query->get();
            
            // Convertir a array para el cálculo
            $ordenesArray = $todasOrdenes->map(function($orden) {
                return (object) $orden->getAttributes();
            })->toArray();
            
            $totalDiasCalculados = $this->calcularTotalDiasBatch($ordenesArray, $festivos);
            
            // Filtrar por total_de_dias_
            $ordenesFiltradas = $todasOrdenes->filter(function($orden) use ($totalDiasCalculados, $filterTotalDias) {
                $totalDias = $totalDiasCalculados[$orden->pedido] ?? 0;
                return in_array((int)$totalDias, $filterTotalDias, true);
            });
            
            // Paginar manualmente los resultados filtrados
            $currentPage = request()->get('page', 1);
            $perPage = 50;
            $ordenes = new \Illuminate\Pagination\LengthAwarePaginator(
                $ordenesFiltradas->forPage($currentPage, $perPage)->values(),
                $ordenesFiltradas->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            
            // Recalcular solo para las órdenes de la página actual
            $totalDiasCalculados = $this->calcularTotalDiasBatch($ordenes->items(), $festivos);
        } else {
            $ordenes = $query->paginate(50);

            // LOG: Registrar cantidad de resultados
            \Log::info("📊 RESULTADOS DEL FILTRO", [
                'total_registros' => $ordenes->total(),
                'registros_en_pagina' => count($ordenes->items()),
                'pagina_actual' => $ordenes->currentPage(),
                'sql_query' => $query->toSql(),
                'sql_bindings' => $query->getBindings()
            ]);

            // Cálculo optimizado tipo fórmula array (como Google Sheets)
            // Una sola operación para calcular TODAS las órdenes visibles
            $totalDiasCalculados = $this->calcularTotalDiasBatch($ordenes->items(), $festivos);
        }

        // Obtener opciones del enum 'area'
        $areaOptions = $this->getEnumOptions('tabla_original_bodega', 'area');

        if ($request->wantsJson()) {
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
                'pagination_html' => $ordenes->appends(request()->query())->links()->toHtml()
            ]);
        }

        $context = 'bodega';
        $title = 'Registro Órdenes Bodega';
        $icon = 'fa-warehouse';
        $fetchUrl = '/bodega';
        $updateUrl = '/bodega';
        $modalContext = 'bodega';
        return view('bodega.index', compact('ordenes', 'totalDiasCalculados', 'areaOptions', 'context', 'title', 'icon', 'fetchUrl', 'updateUrl', 'modalContext'));
    }



    public function show($pedido)
    {
        $order = TablaOriginalBodega::where('pedido', $pedido)->firstOrFail();

        $totalCantidad = DB::table('registros_por_orden_bodega')
            ->where('pedido', $pedido)
            ->sum('cantidad');

        $totalEntregado = DB::table('registros_por_orden_bodega')
            ->where('pedido', $pedido)
            ->sum('total_producido_por_talla');

        $order->total_cantidad = $totalCantidad;
        $order->total_entregado = $totalEntregado;

        // Asegurar que se devuelven todos los atributos incluyendo área
        return response()->json($order->toArray());
    }

    /**
     * Obtener prendas de un pedido desde registros_por_orden_bodega
     */
    public function getPrendas($pedido)
    {
        try {
            $prendas = DB::table('registros_por_orden_bodega')
                ->where('pedido', $pedido)
                ->get([
                    'prenda',
                    'descripcion',
                    'talla',
                    'cantidad'
                ]);

            return response()->json($prendas);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar prendas'], 500);
        }
    }

    public function getNextPedido()
    {
        $lastPedido = DB::table('tabla_original_bodega')->max('pedido');
        $nextPedido = $lastPedido ? $lastPedido + 1 : 1;
        return response()->json(['next_pedido' => $nextPedido]);
    }

    public function validatePedido(Request $request)
    {
        $request->validate([
            'pedido' => 'required|integer',
        ]);

        $pedido = $request->input('pedido');
        $lastPedido = DB::table('tabla_original_bodega')->max('pedido');
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
                'forma_pago' => 'nullable|string|max:255',
                'prendas' => 'required|array',
                'prendas.*.prenda' => 'required|string|max:255',
                'prendas.*.descripcion' => 'nullable|string|max:1000',
                'prendas.*.tallas' => 'required|array',
                'prendas.*.tallas.*.talla' => 'required|string|max:50',
                'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
                'allow_any_pedido' => 'nullable|boolean',
            ]);

            $lastPedido = DB::table('tabla_original_bodega')->max('pedido');
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
                'forma_de_pago' => $request->forma_pago,
                'descripcion' => $descripcionCompleta,
                'cantidad' => $totalCantidad,
            ];

            DB::table('tabla_original_bodega')->insert($pedidoData);

            // Insert registros_por_orden_bodega for each prenda and talla
            foreach ($request->prendas as $prenda) {
                foreach ($prenda['tallas'] as $talla) {
                    DB::table('registros_por_orden_bodega')->insert([
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
            \Log::info('📝 UPDATE REQUEST RECIBIDO', [
                'pedido' => $pedido,
                'all_data' => $request->all(),
                'area_recibida' => $request->get('area'),
            ]);

            $orden = TablaOriginalBodega::where('pedido', $pedido)->firstOrFail();

            $areaOptions = $this->getEnumOptions('tabla_original_bodega', 'area');
            \Log::info('📋 AREA OPTIONS DISPONIBLES', ['areaOptions' => $areaOptions]);

            $estadoOptions = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];

            // Whitelist de columnas permitidas para edición
            $allowedColumns = [
                'estado', 'area', '_pedido', 'cliente', 'descripcion', 'cantidad',
                'novedades', 'forma_de_pago', 'fecha_de_creacion_de_orden',
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
            ]);

            \Log::info('✅ VALIDACIÓN EXITOSA', ['validatedData' => $validatedData]);

            // Validar columnas adicionales permitidas como strings
            $additionalValidation = [];
            foreach ($allowedColumns as $col) {
                if ($request->has($col) && $col !== 'estado' && $col !== 'area') {
                    // Campos TEXT que pueden ser más largos
                    if ($col === 'descripcion' || $col === 'novedades') {
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

            $oldArea = $orden->area;

            if (!empty($updates)) {
                $orden->update($updates);
                $orden->refresh(); // Reload to get updated data
                
                // Broadcast evento específico para Control de Calidad
                if (isset($updates['area']) && $updates['area'] !== $oldArea) {
                    if ($updates['area'] === 'Control-Calidad') {
                        // Orden ENTRA a Control de Calidad
                        broadcast(new \App\Events\ControlCalidadUpdated($orden, 'added', 'bodega'));
                    } elseif ($oldArea === 'Control-Calidad' && $updates['area'] !== 'Control-Calidad') {
                        // Orden SALE de Control de Calidad
                        broadcast(new \App\Events\ControlCalidadUpdated($orden, 'removed', 'bodega'));
                    }
                }
            }

            // Procesar prendas si se enviaron
            if ($request->has('prendas') && is_array($request->get('prendas'))) {
                $prendas = $request->get('prendas');
                \Log::info('📦 PRENDAS RECIBIDAS PARA GUARDAR', ['prendas' => $prendas, 'pedido' => $pedido]);
                
                // Eliminar prendas existentes y reemplazar con las nuevas
                DB::table('registros_por_orden_bodega')
                    ->where('pedido', $pedido)
                    ->delete();
                
                // Insertar nuevas prendas
                foreach ($prendas as $prenda) {
                    if (!empty($prenda['prenda']) && is_array($prenda['tallas'])) {
                        foreach ($prenda['tallas'] as $talla) {
                            DB::table('registros_por_orden_bodega')->insert([
                                'pedido' => $pedido,
                                'prenda' => $prenda['prenda'],
                                'descripcion' => $prenda['descripcion'] ?? '',
                                'talla' => $talla['talla'],
                                'cantidad' => $talla['cantidad'] ?? 0
                            ]);
                        }
                    }
                }
                
                \Log::info('✅ PRENDAS GUARDADAS EXITOSAMENTE', ['pedido' => $pedido]);
            }

            return response()->json(['success' => true, 'updated_fields' => $updatedFields]);
        } catch (\Exception $e) {
            // Capturar cualquier error y devolver JSON con mensaje
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la orden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cálculo optimizado tipo fórmula array (como Google Sheets)
     * Calcula total_de_dias para TODAS las órdenes en una sola operación batch
     */
    private function calcularTotalDiasBatch(array $ordenes, array $festivos): array
    {
        $resultados = [];

        // DESACTIVADO: Cache deshabilitado para pruebas
        // Calcular directamente sin cache

        foreach ($ordenes as $orden) {
            $ordenPedido = $orden->pedido;

            // Verificar si fecha_de_creacion_de_orden existe
            if (!$orden->fecha_de_creacion_de_orden) {
                $resultados[$ordenPedido] = 0;
                continue;
            }

            try {
                // Cálculo optimizado para esta orden
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
                // Si hay error en el cálculo, poner 0
                $resultados[$ordenPedido] = 0;
            }
        }

        return $resultados;
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

    public function getEntregas($pedido)
    {
        $registros = DB::table('registros_por_orden_bodega')
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
     * Actualizar el número de pedido (consecutivo) para bodega
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
            $orden = TablaOriginalBodega::where('pedido', $oldPedido)->first();
            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no existe'
                ], 404);
            }

            // Verificar que el nuevo pedido no existe ya
            $existingOrder = TablaOriginalBodega::where('pedido', $newPedido)->first();
            if ($existingOrder) {
                return response()->json([
                    'success' => false,
                    'message' => "El número de pedido {$newPedido} ya está en uso"
                ], 422);
            }

            DB::beginTransaction();

            // Deshabilitar temporalmente las restricciones de clave foránea
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Actualizar en tabla_original_bodega
            DB::table('tabla_original_bodega')
                ->where('pedido', $oldPedido)
                ->update(['pedido' => $newPedido]);

            // Actualizar en registros_por_orden_bodega
            DB::table('registros_por_orden_bodega')
                ->where('pedido', $oldPedido)
                ->update(['pedido' => $newPedido]);

            // Actualizar en entregas_bodega_costura si existen
            if (DB::getSchemaBuilder()->hasTable('entregas_bodega_costura')) {
                DB::table('entregas_bodega_costura')
                    ->where('pedido', $oldPedido)
                    ->update(['pedido' => $newPedido]);
            }

            // Actualizar en entregas_bodega_corte si existen
            if (DB::getSchemaBuilder()->hasTable('entregas_bodega_corte')) {
                DB::table('entregas_bodega_corte')
                    ->where('pedido', $oldPedido)
                    ->update(['pedido' => $newPedido]);
            }

            // Rehabilitar las restricciones de clave foránea
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

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
            \Log::error('Error al actualizar pedido bodega', [
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
     * Obtener registros por orden bodega para el modal de edición
     */
    public function getRegistrosPorOrden($pedido)
    {
        try {
            $registros = DB::table('registros_por_orden_bodega')
                ->where('pedido', $pedido)
                ->select('prenda', 'descripcion', 'talla', 'cantidad')
                ->get();

            return response()->json($registros);
        } catch (\Exception $e) {
            \Log::error('Error al obtener registros por orden bodega', [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros'
            ], 500);
        }
    }

    /**
     * Editar orden completa de bodega (tabla_original_bodega + registros_por_orden_bodega)
     */
    public function editFullOrder(Request $request, $pedido)
    {
        DB::beginTransaction();

        try {
            // Validar datos de entrada
            $validatedData = $request->validate([
                'pedido' => 'required|integer',
                'estado' => 'nullable|in:No iniciado,En Ejecución,Entregado,Anulada',
                'cliente' => 'required|string|max:255',
                'fecha_creacion' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'forma_pago' => 'nullable|string|max:255',
                'prendas' => 'required|array|min:1',
                'prendas.*.prenda' => 'required|string|max:255',
                'prendas.*.descripcion' => 'nullable|string|max:1000',
                'prendas.*.tallas' => 'required|array|min:1',
                'prendas.*.tallas.*.talla' => 'required|string|max:50',
                'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
            ]);

            // Verificar que la orden existe
            $orden = TablaOriginalBodega::where('pedido', $pedido)->first();
            if (!$orden) {
                throw new \Exception('La orden no existe');
            }

            // Calcular cantidad total
            $totalCantidad = 0;
            foreach ($request->prendas as $prenda) {
                foreach ($prenda['tallas'] as $talla) {
                    $totalCantidad += $talla['cantidad'];
                }
            }

            // Construir descripción completa
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

            // Actualizar tabla_original_bodega
            $ordenData = [
                'estado' => $request->estado ?? 'No iniciado',
                'cliente' => $request->cliente,
                'fecha_de_creacion_de_orden' => $request->fecha_creacion,
                'encargado_orden' => $request->encargado,
                'forma_de_pago' => $request->forma_pago,
                'descripcion' => $descripcionCompleta,
                'cantidad' => $totalCantidad,
            ];

            DB::table('tabla_original_bodega')
                ->where('pedido', $pedido)
                ->update($ordenData);

            // Eliminar todos los registros_por_orden_bodega existentes
            DB::table('registros_por_orden_bodega')
                ->where('pedido', $pedido)
                ->delete();

            // Insertar nuevos registros_por_orden_bodega
            foreach ($request->prendas as $prenda) {
                foreach ($prenda['tallas'] as $talla) {
                    DB::table('registros_por_orden_bodega')->insert([
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

            DB::commit();

            // Obtener la orden actualizada para retornar
            $ordenActualizada = TablaOriginalBodega::where('pedido', $pedido)->first();

            // Obtener los registros por orden actualizados
            $registrosActualizados = DB::table('registros_por_orden_bodega')
                ->where('pedido', $pedido)
                ->get();

            // Broadcast event for real-time updates (si existe el evento)
            if (class_exists('\App\Events\OrdenBodegaUpdated')) {
                broadcast(new \App\Events\OrdenBodegaUpdated($ordenActualizada, 'updated'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Orden de bodega actualizada correctamente',
                'pedido' => $pedido,
                'orden' => $ordenActualizada
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Error de validación al editar orden bodega', [
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
            \Log::error('Error al editar orden completa bodega', [
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
     * Actualizar descripción y regenerar registros_por_orden_bodega basado en el contenido
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

            // Actualizar la descripción en tabla_original_bodega
            $orden = TablaOriginalBodega::where('pedido', $pedido)->firstOrFail();
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
                    
                    // Eliminar registros existentes en registros_por_orden_bodega
                    DB::table('registros_por_orden_bodega')->where('pedido', $pedido)->delete();

                    // Insertar nuevos registros basados en la descripción parseada
                    foreach ($prendas as $prenda) {
                        foreach ($prenda['tallas'] as $talla) {
                            DB::table('registros_por_orden_bodega')->insert([
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

            DB::commit();

            // Broadcast events si existen
            $ordenActualizada = TablaOriginalBodega::where('pedido', $pedido)->first();
            $registrosActualizados = DB::table('registros_por_orden_bodega')->where('pedido', $pedido)->get();
            
            if (class_exists('\App\Events\OrdenBodegaUpdated')) {
                broadcast(new \App\Events\OrdenBodegaUpdated($ordenActualizada, 'updated'));
            }

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
            \Log::error('Error al actualizar descripción y prendas bodega', [
                'pedido' => $request->pedido ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '🚨 Error interno del servidor: No se pudo actualizar la descripción y prendas en bodega. Por favor, intente nuevamente o contacte al administrador si el problema persiste.'
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

    /**
     * Obtener procesos de una orden desde tabla_original_bodega
     */
    public function getProcesosTablaOriginal($numeroPedido)
    {
        try {
            // Buscar la orden en tabla_original_bodega
            $orden = TablaOriginalBodega::where('pedido', $numeroPedido)->firstOrFail();

            // Obtener festivos
            $festivos = \App\Models\Festivo::pluck('fecha')->toArray();

            // Construir procesos desde las columnas de la tabla
            $procesosMap = [
                'Orden' => 'fecha_de_creacion_de_orden',
                'Inventario' => 'inventario',
                'Insumos y Telas' => 'insumos_y_telas',
                'Corte' => 'corte',
                'Bordado' => 'bordado',
                'Estampado' => 'estampado',
                'Costura' => 'costura',
                'Reflectivo' => 'reflectivo',
                'Lavandería' => 'lavanderia',
                'Arreglos' => 'arreglos',
                'Marras' => 'marras',
                'Control Calidad' => 'control_de_calidad',
                'Entrega' => 'entrega',
                'Despacho' => 'despacho'
            ];

            $procesos = collect();
            foreach ($procesosMap as $nombreProceso => $columnaFecha) {
                if (!empty($orden->$columnaFecha)) {
                    $procesos->push((object)[
                        'proceso' => $nombreProceso,
                        'fecha_inicio' => $orden->$columnaFecha,
                        'encargado' => null,
                        'estado_proceso' => null
                    ]);
                }
            }

            // Ordenar por fecha
            $procesos = $procesos->sortBy(function($p) {
                return \Carbon\Carbon::parse($p->fecha_inicio);
            })->values();

            // Calcular días hábiles totales
            $totalDiasHabiles = 0;
            if ($procesos->count() > 0) {
                $fechaInicio = \Carbon\Carbon::parse($procesos->first()->fecha_inicio);
                $fechaFin = \Carbon\Carbon::parse($procesos->last()->fecha_inicio);
                
                $totalDiasHabiles = $this->calcularDiasHabilesBatch($fechaInicio, $fechaFin, $festivos);
            }

            return response()->json([
                'numero_pedido' => $orden->pedido,
                'cliente' => $orden->cliente,
                'fecha_inicio' => $orden->fecha_de_creacion_de_orden,
                'fecha_estimada_de_entrega' => $orden->entrega ?? null,
                'procesos' => $procesos,
                'total_dias_habiles' => $totalDiasHabiles,
                'festivos' => $festivos
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getProcesosTablaOriginal Bodega: ' . $e->getMessage());
            return response()->json([
                'error' => 'No se encontró la orden o no tiene permiso para verla'
            ], 404);
        }
    }

    /**
     * Calcular días de una orden desde bodega
     */
    public function calcularDiasAPI(Request $request, $numeroPedido)
    {
        try {
            // Validar entrada
            if (!$numeroPedido) {
                return response()->json(['error' => 'Número de pedido requerido'], 400);
            }

            // Obtener festivos
            $festivos = \App\Models\Festivo::pluck('fecha')->toArray();
            
            // Obtener la orden de bodega
            $orden = TablaOriginalBodega::where('pedido', $numeroPedido)->first();
            if (!$orden) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            // Calcular días usando el método existente
            $resultado = $this->calcularTotalDiasBatch([$orden], $festivos);
            $diasCalculados = $resultado[$numeroPedido] ?? 0;

            return response()->json([
                'success' => true,
                'numero_pedido' => $numeroPedido,
                'total_dias' => intval($diasCalculados),
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en calcularDiasAPI Bodega: ' . $e->getMessage());
            return response()->json(['error' => 'Error al calcular días'], 500);
        }
    }

    /**
     * Buscar órdenes de bodega por número de pedido o cliente
     */
    public function searchOrders(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $limit = $request->input('limit', 25);
            $page = $request->input('page', 1);
            $isTableSearch = $request->input('isTableSearch', false);

            if (strlen($search) < 1) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'ordenes' => []
                ]);
            }

            // Buscar por número de pedido o cliente en tabla_original_bodega
            $query = TablaOriginalBodega::where('pedido', 'LIKE', '%' . $search . '%')
                ->orWhere('cliente', 'LIKE', '%' . $search . '%');

            // Si es búsqueda de tabla, retornar todos los campos con paginación
            if ($isTableSearch) {
                // Obtener total antes de paginar
                $total = $query->count();

                // Paginar
                $ordenes = $query->paginate($limit, ['*'], 'page', $page);

                // Mapear datos para incluir total_dias calculado
                $ordenesMapeadas = $ordenes->getCollection()->map(function($orden) {
                    // Calcular días hábiles usando el método del modelo
                    $diasCalculados = 0;
                    if ($orden->fecha_de_creacion_de_orden) {
                        $diasCalculados = $orden->getTotalDeDiasAttribute();
                    }
                    
                    return [
                        'numero_pedido' => $orden->pedido,
                        'pedido' => $orden->pedido,
                        'cliente' => $orden->cliente,
                        'estado' => $orden->estado,
                        'area' => $orden->area,
                        'dia_de_entrega' => $orden->dia_de_entrega,
                        'fecha_de_creacion_de_orden' => $orden->fecha_de_creacion_de_orden,
                        'control_de_calidad' => $orden->control_de_calidad,
                        'novedades' => $orden->novedades,
                        'total_dias_calculado' => $diasCalculados
                    ];
                });

                // Reemplazar la colección con los datos mapeados
                $ordenes->setCollection($ordenesMapeadas);

                return response()->json([
                    'success' => true,
                    'ordenes' => $ordenes->items(),
                    'data' => $ordenes->items(),
                    'pagination' => [
                        'current_page' => $ordenes->currentPage(),
                        'last_page' => $ordenes->lastPage(),
                        'per_page' => $ordenes->perPage(),
                        'total' => $ordenes->total(),
                        'from' => $ordenes->firstItem(),
                        'to' => $ordenes->lastItem()
                    ]
                ]);
            }

            // Si es búsqueda de dropdown, retornar solo lo necesario
            $ordenes = $query->select('pedido', 'cliente', 'estado', 'area')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'ordenes' => $ordenes,
                'data' => $ordenes
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en searchOrders Bodega: ' . $e->getMessage());
            return response()->json(['error' => 'Error al buscar'], 500);
        }
    }

    /**
     * Actualiza novedades en tabla_original_bodega (reemplazo total)
     * Endpoint: POST /api/bodega/{pedido}/novedades
     */
    public function updateNovedadesBodega(Request $request, $pedido)
    {
        try {
            \Log::info('📝 updateNovedadesBodega iniciado', ['pedido' => $pedido]);
            
            // Validar entrada
            $request->validate([
                'novedades' => 'nullable|string|max:5000'
            ]);

            // Buscar el registro en tabla_original_bodega
            $registro = TablaOriginalBodega::where('pedido', $pedido)->firstOrFail();
            
            \Log::info('✅ Registro encontrado', ['pedido' => $pedido]);

            // Actualizar novedades
            $registro->update([
                'novedades' => $request->input('novedades', '')
            ]);
            
            \Log::info('✅ Novedades actualizadas', ['novedades' => $request->input('novedades', '')]);

            // Registrar en auditoría si existe
            if (class_exists('App\Models\AuditLog')) {
                \App\Models\AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'update_novedades_bodega',
                    'auditable_type' => TablaOriginalBodega::class,
                    'auditable_id' => $registro->id,
                    'changes' => [
                        'novedades' => $request->input('novedades', '')
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Novedades actualizadas correctamente',
                'data' => [
                    'pedido' => $registro->pedido,
                    'novedades' => $registro->novedades
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('❌ Registro no encontrado en bodega', ['pedido' => $pedido]);
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('❌ Error al actualizar novedades bodega: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar las novedades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agrega una nueva novedad en tabla_original_bodega
     * Endpoint: POST /api/bodega/{pedido}/novedades/add
     */
    public function addNovedadBodega(Request $request, $pedido)
    {
        try {
            \Log::info('📝 addNovedadBodega iniciado', ['pedido' => $pedido]);
            
            // Validar entrada
            $request->validate([
                'novedad' => 'required|string|max:500'
            ]);

            // Buscar el registro en tabla_original_bodega
            $registro = TablaOriginalBodega::where('pedido', $pedido)->firstOrFail();
            
            // Obtener usuario autenticado
            $usuario = auth()->user()->name ?? auth()->user()->email ?? 'Usuario';
            
            // Obtener fecha y hora actual en formato d-m-Y h:i:s A (hora normal con AM/PM)
            $fechaHora = \Carbon\Carbon::now()->format('d-m-Y h:i:s A');
            
            // Crear la novedad con formato [usuario - fecha hora] novedad
            $novedadFormato = "[{$usuario} - {$fechaHora}] " . $request->input('novedad');
            
            // Obtener novedades actuales
            $novedadesActuales = $registro->novedades ?? '';
            
            // Concatenar con salto de línea si hay novedades anteriores
            if (!empty($novedadesActuales)) {
                $novedadesNuevas = $novedadesActuales . "\n\n" . $novedadFormato;
            } else {
                $novedadesNuevas = $novedadFormato;
            }
            
            // Actualizar novedades
            $registro->update([
                'novedades' => $novedadesNuevas
            ]);
            
            \Log::info('✅ Novedad agregada en bodega', [
                'usuario' => $usuario,
                'fecha_hora' => $fechaHora,
                'novedad' => $request->input('novedad')
            ]);

            // Registrar en auditoría si existe
            if (class_exists('App\Models\AuditLog')) {
                \App\Models\AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'add_novedad_bodega',
                    'auditable_type' => TablaOriginalBodega::class,
                    'auditable_id' => $registro->id,
                    'changes' => [
                        'novedad_agregada' => $novedadFormato
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Novedad agregada correctamente',
                'data' => [
                    'pedido' => $registro->pedido,
                    'novedades' => $registro->novedades
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('❌ Registro no encontrado en bodega', ['pedido' => $pedido]);
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('❌ Error al agregar novedad en bodega: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la novedad: ' . $e->getMessage()
            ], 500);
        }
    }
}
