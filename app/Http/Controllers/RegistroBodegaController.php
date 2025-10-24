<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TablaOriginalBodega;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;

class RegistroBodegaController extends Controller
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
        // Handle request for unique values for filters
        if ($request->has('get_unique_values') && $request->column) {
            $column = $request->column;
        $allowedColumns = [
            'pedido', 'estado', 'area', 'tiempo', 'total_de_dias_', 'cliente',
            'hora', 'descripcion', 'cantidad', 'novedades', 'asesora', 'forma_de_pago',
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
                $uniqueValues = TablaOriginalBodega::distinct()->pluck($column)->filter()->values()->toArray();
                return response()->json(['unique_values' => $uniqueValues]);
            }
            return response()->json(['error' => 'Invalid column'], 400);
        }

        $query = TablaOriginalBodega::query();

        // Apply search filter - ONLY search by 'pedido'
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where('pedido', 'LIKE', '%' . $searchTerm . '%');
        }

        // Apply column filters (dynamic for all columns)
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_') && !empty($value)) {
                $column = str_replace('filter_', '', $key);
                $values = explode(',', $value);

                // Whitelist de columnas permitidas para seguridad
                $allowedColumns = [
                    'id', 'estado', 'area', 'tiempo', 'total_de_dias_', '_pedido', 'cliente',
                    'hora', 'descripcion', 'cantidad', 'novedades', 'asesora', 'forma_de_pago',
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
                    $query->whereIn($column, $values);
                }
            }
        }

        $festivos = Festivo::pluck('fecha')->toArray();
        $ordenes = $query->paginate(50);

        // Cálculo optimizado tipo fórmula array (como Google Sheets)
        // Una sola operación para calcular TODAS las órdenes visibles
        $totalDiasCalculados = $this->calcularTotalDiasBatch($ordenes->items(), $festivos);

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
        return view('orders.index', compact('ordenes', 'totalDiasCalculados', 'areaOptions', 'context', 'title', 'icon', 'fetchUrl', 'updateUrl', 'modalContext'));
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

        return response()->json($order);
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
                'asesora' => $request->asesora,
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
            $orden = TablaOriginalBodega::where('pedido', $pedido)->firstOrFail();

            $areaOptions = $this->getEnumOptions('tabla_original_bodega', 'area');
            $estadoOptions = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];

            // Whitelist de columnas permitidas para edición
            $allowedColumns = [
                'estado', 'area', '_pedido', 'cliente', 'hora', 'descripcion', 'cantidad',
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

            $validatedData = $request->validate([
                'estado' => 'nullable|in:' . implode(',', $estadoOptions),
                'area' => 'nullable|in:' . implode(',', $areaOptions),
            ]);

            // Validar columnas adicionales permitidas como strings
            $additionalValidation = [];
            foreach ($allowedColumns as $col) {
                if ($request->has($col) && $col !== 'estado' && $col !== 'area') {
                    $additionalValidation[$col] = 'nullable|string|max:255';
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

            // Agregar otras columnas permitidas
            foreach ($additionalData as $key => $value) {
                $updates[$key] = $value;
            }

            if (!empty($updates)) {
                $orden->update($updates);
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

        // Cache de cálculos para evitar repeticiones
        static $cacheCalculos = [];

        // Generar clave de cache basada en festivos y fechas
        $cacheKey = md5(serialize($festivos) . now()->format('Y-m-d'));

        foreach ($ordenes as $orden) {
            $ordenPedido = $orden->pedido;

            // Verificar si ya está en cache
            if (isset($cacheCalculos[$cacheKey][$ordenPedido])) {
                $resultados[$ordenPedido] = $cacheCalculos[$cacheKey][$ordenPedido];
                continue;
            }

            // Verificar si fecha_de_creacion_de_orden existe
            if (!$orden->fecha_de_creacion_de_orden) {
                $resultados[$ordenPedido] = 0;
                continue;
            }

            try {
                // Cálculo optimizado para esta orden
                $fechaCreacion = \Carbon\Carbon::parse($orden->fecha_de_creacion_de_orden);

                if ($orden->estado === 'Entregado') {
                    $fechaEntrega = $orden->entrega ? \Carbon\Carbon::parse($orden->entrega) : null;
                    $dias = $fechaEntrega ? $this->calcularDiasHabilesBatch($fechaCreacion, $fechaEntrega, $festivos) : 0;
                } else {
                    $dias = $this->calcularDiasHabilesBatch($fechaCreacion, \Carbon\Carbon::now(), $festivos);
                }

                // Cachear resultado
                $cacheCalculos[$cacheKey][$ordenPedido] = max(0, $dias);
                $resultados[$ordenPedido] = $cacheCalculos[$cacheKey][$ordenPedido];
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
        $totalDays = $inicio->diffInDays($fin) + 1;

        // Contar fines de semana de forma vectorizada
        $weekends = $this->contarFinesDeSemanaBatch($inicio, $fin);

        // Contar festivos en el rango
        $festivosEnRango = array_filter($festivos, function ($festivo) use ($inicio, $fin) {
            $fechaFestivo = \Carbon\Carbon::parse($festivo);
            return $fechaFestivo->between($inicio, $fin);
        });

        $holidaysInRange = count($festivosEnRango);

        $businessDays = $totalDays - $weekends - $holidaysInRange;

        // Ajustes finos para inicio/fin en fines de semana o festivos
        if ($inicio->isWeekend() || in_array($inicio->toDateString(), $festivos)) $businessDays--;
        if ($fin->isWeekend() || in_array($fin->toDateString(), $festivos)) $businessDays--;

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
}
