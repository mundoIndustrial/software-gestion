<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TablaOriginalBodega;
use Illuminate\Support\Facades\DB;

/**
 * RegistroBodegaController - CRUD Operations Only
 * 
 * Responsabilidad: Operaciones CRUD críticas del negocio
 * - Crear, actualizar, eliminar órdenes de bodega
 * - Validar números de pedido
 * - Gestionar números consecutivos
 * 
 * Dependencias: Mínimas (solo models y DB)
 * Líneas: ~180
 */
class RegistroBodegaController extends Controller
{
    public function getNextPedido()
    {
        $lastPedido = DB::table('tabla_original_bodega')->max('pedido');
        $nextPedido = $lastPedido ? $lastPedido + 1 : 1;
        return response()->json(['next_pedido' => $nextPedido]);
    }

    public function validatePedido(Request $request)
    {
        $request->validate(['pedido' => 'required|integer']);

        $pedido = $request->input('pedido');
        $lastPedido = DB::table('tabla_original_bodega')->max('pedido');
        $nextPedido = $lastPedido ? $lastPedido + 1 : 1;

        return response()->json([
            'valid' => ($pedido == $nextPedido),
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
                    return response()->json(['success' => false, 'message' => "El número consecutivo disponible es $nextPedido"], 422);
                }
            }

            $totalCantidad = 0;
            foreach ($request->prendas as $prenda) {
                foreach ($prenda['tallas'] as $talla) {
                    $totalCantidad += $talla['cantidad'];
                }
            }

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
                }
            }

            $estado = $request->estado ?? 'No iniciado';
            $area = $request->area ?? 'Creación Orden';

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
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $pedido)
    {
        try {
            $orden = TablaOriginalBodega::where('pedido', $pedido)->firstOrFail();
            $estadoOptions = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];

            $validatedData = $request->validate([
                'estado' => 'nullable|in:' . implode(',', $estadoOptions),
            ]);

            $updates = [];
            if (array_key_exists('estado', $validatedData)) {
                $updates['estado'] = $validatedData['estado'];
            }

            if (!empty($updates)) {
                $orden->update($updates);
            }

            return response()->json(['success' => true, 'updated_fields' => $updates]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updatePedido(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'old_pedido' => 'required|integer',
                'new_pedido' => 'required|integer|min:1',
            ]);

            $oldPedido = $validatedData['old_pedido'];
            $newPedido = $validatedData['new_pedido'];

            $orden = TablaOriginalBodega::where('pedido', $oldPedido)->first();
            if (!$orden) {
                return response()->json(['success' => false, 'message' => 'La orden no existe'], 404);
            }

            $existingOrder = TablaOriginalBodega::where('pedido', $newPedido)->first();
            if ($existingOrder) {
                return response()->json(['success' => false, 'message' => "El número de pedido {$newPedido} ya está en uso"], 422);
            }

            DB::beginTransaction();
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            DB::table('tabla_original_bodega')->where('pedido', $oldPedido)->update(['pedido' => $newPedido]);
            DB::table('registros_por_orden_bodega')->where('pedido', $oldPedido)->update(['pedido' => $newPedido]);

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Número actualizado', 'old_pedido' => $oldPedido, 'new_pedido' => $newPedido]);
        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function editFullOrder(Request $request, $pedido)
    {
        DB::beginTransaction();

        try {
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

            $orden = TablaOriginalBodega::where('pedido', $pedido)->first();
            if (!$orden) {
                throw new \Exception('La orden no existe');
            }

            $totalCantidad = 0;
            foreach ($request->prendas as $prenda) {
                foreach ($prenda['tallas'] as $talla) {
                    $totalCantidad += $talla['cantidad'];
                }
            }

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
                }
            }

            $ordenData = [
                'estado' => $request->estado ?? 'No iniciado',
                'cliente' => $request->cliente,
                'fecha_de_creacion_de_orden' => $request->fecha_creacion,
                'encargado_orden' => $request->encargado,
                'forma_de_pago' => $request->forma_pago,
                'descripcion' => $descripcionCompleta,
                'cantidad' => $totalCantidad,
            ];

            DB::table('tabla_original_bodega')->where('pedido', $pedido)->update($ordenData);
            DB::table('registros_por_orden_bodega')->where('pedido', $pedido)->delete();

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
            $ordenActualizada = TablaOriginalBodega::where('pedido', $pedido)->first();

            return response()->json(['success' => true, 'message' => 'Orden actualizada', 'pedido' => $pedido, 'orden' => $ordenActualizada]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, $pedido)
    {
        try {
            DB::beginTransaction();

            DB::table('tabla_original_bodega')->where('pedido', $pedido)->delete();
            DB::table('registros_por_orden_bodega')->where('pedido', $pedido)->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Orden eliminada']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
