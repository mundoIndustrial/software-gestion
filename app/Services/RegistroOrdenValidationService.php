<?php

namespace App\Services;

use App\Constants\AreaOptions;
use Illuminate\Http\Request;

/**
 * RegistroOrdenValidationService
 * 
 * Responsabilidad: Validación centralizada para todas las operaciones CRUD de órdenes
 * Cumple con SRP: Solo valida datos, no los modifica ni persiste
 * Cumple con DIP: Inyecta dependencias necesarias
 */
class RegistroOrdenValidationService
{
    /**
     * Validar datos para crear una nueva orden
     * Retorna array con datos validados o lanza exception
     */
    public function validateStoreRequest(Request $request): array
    {
        return $request->validate([
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
    }

    /**
     * Validar datos para actualizar una orden (update method)
     * Retorna array con datos validados
     */
    public function validateUpdateRequest(Request $request): array
    {
        $areaRecibida = $request->input('area');
        $estadoOptions = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];

        // Validar área manualmente
        if ($areaRecibida && !in_array($areaRecibida, AreaOptions::getArray())) {
            throw new \InvalidArgumentException(
                "El área '{$areaRecibida}' no es válida. Áreas válidas: " . implode(', ', AreaOptions::getArray())
            );
        }

        $validatedData = $request->validate([
            'estado' => 'nullable|in:' . implode(',', $estadoOptions),
            'dia_de_entrega' => 'nullable|integer|in:1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35',
        ]);

        // Agregar el área validada manualmente
        if ($areaRecibida) {
            $validatedData['area'] = $areaRecibida;
        }

        // Convertir string vacío a null para dia_de_entrega
        if (isset($validatedData['dia_de_entrega']) && $validatedData['dia_de_entrega'] === '') {
            $validatedData['dia_de_entrega'] = null;
        }

        // Validar columnas adicionales permitidas
        $allowedColumns = $this->getAllowedColumns();
        $additionalValidation = [];
        
        foreach ($allowedColumns as $col) {
            if ($request->has($col) && $col !== 'estado' && $col !== 'area' && $col !== 'dia_de_entrega') {
                if ($col === 'descripcion' || $col === 'novedades') {
                    $additionalValidation[$col] = 'nullable|string|max:65535';
                } else {
                    $additionalValidation[$col] = 'nullable|string|max:255';
                }
            }
        }

        $additionalData = $request->validate($additionalValidation);
        
        return array_merge($validatedData, $additionalData);
    }

    /**
     * Validar datos para editar orden completa (editFullOrder)
     */
    public function validateEditFullOrderRequest(Request $request): array
    {
        return $request->validate([
            'pedido' => 'required|integer',
            'estado' => 'nullable|in:No iniciado,En Ejecución,Entregado,Anulada',
            'cliente' => 'required|string|max:255',
            'fecha_creacion' => 'required|date',
            'encargado' => 'nullable|string|max:255',
            'forma_pago' => 'nullable|string|max:255',
            'prendas' => 'required|array',
            'prendas.*.prenda' => 'required|string|max:255',
            'prendas.*.descripcion' => 'nullable|string|max:1000',
            'prendas.*.tallas' => 'required|array',
            'prendas.*.tallas.*.talla' => 'required|string|max:50',
            'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
        ]);
    }

    /**
     * Validar datos para actualizar descripción de prendas
     */
    public function validateUpdateDescripcionRequest(Request $request): array
    {
        return $request->validate([
            'pedido' => 'required|integer',
            'descripcion' => 'required|string'
        ]);
    }

    /**
     * Validar número de pedido consecutivo
     */
    public function validatePedidoNumber(int $pedido, int $nextExpected): bool
    {
        return $pedido === $nextExpected;
    }

    /**
     * Obtener columnas permitidas para edición
     */
    private function getAllowedColumns(): array
    {
        return [
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
    }

    /**
     * Obtener columnas de tipo fecha
     */
    public function getDateColumns(): array
    {
        return [
            'fecha_de_creacion_de_orden', 'insumos_y_telas', 'corte', 'costura',
            'lavanderia', 'arreglos', 'control_de_calidad', 'entrega', 'despacho'
        ];
    }
}
