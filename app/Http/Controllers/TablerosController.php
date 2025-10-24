<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;

class TablerosController extends Controller
{
    public function index()
    {
        $registros = RegistroPisoProduccion::paginate(50);
        $columns = Schema::getColumnListing('registro_piso_produccion');
        $columns = array_diff($columns, ['id', 'created_at', 'updated_at', 'producida']);

        // Recalcular tiempo_disponible, meta y eficiencia para cada registro
        foreach ($registros as $registro) {
            $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                               ($registro->tiempo_parada_no_programada ?? 0) -
                               ($registro->tiempo_para_programada ?? 0);

            $meta = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;
            $eficiencia = $meta == 0 ? 0 : ($registro->cantidad / $meta);

            $registro->tiempo_disponible = $tiempo_disponible;
            $registro->meta = $meta;
            $registro->eficiencia = $eficiencia;
            $registro->save();
        }

        $registrosPolos = RegistroPisoPolo::paginate(50);
        $columnsPolos = Schema::getColumnListing('registro_piso_polo');
        $columnsPolos = array_diff($columnsPolos, ['id', 'created_at', 'updated_at', 'producida']);

        // Recalcular tiempo_disponible, meta y eficiencia para cada registro de polos
        foreach ($registrosPolos as $registro) {
            $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                               ($registro->tiempo_parada_no_programada ?? 0) -
                               ($registro->tiempo_para_programada ?? 0);

            $meta = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;
            $eficiencia = $meta == 0 ? 0 : ($registro->cantidad / $meta);

            $registro->tiempo_disponible = $tiempo_disponible;
            $registro->meta = $meta;
            $registro->eficiencia = $eficiencia;
            $registro->save();
        }

        if (request()->wantsJson()) {
            return response()->json([
                'registros' => $registros->items(),
                'columns' => array_values($columns),
                'registrosPolos' => $registrosPolos->items(),
                'columnsPolos' => array_values($columnsPolos),
                'pagination' => [
                    'current_page' => $registros->currentPage(),
                    'last_page' => $registros->lastPage(),
                    'per_page' => $registros->perPage(),
                    'total' => $registros->total()
                ],
                'paginationPolos' => [
                    'current_page' => $registrosPolos->currentPage(),
                    'last_page' => $registrosPolos->lastPage(),
                    'per_page' => $registrosPolos->perPage(),
                    'total' => $registrosPolos->total()
                ]
            ]);
        }

        return view('tableros', compact('registros', 'columns', 'registrosPolos', 'columnsPolos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'registros' => 'required|array',
            'registros.*.fecha' => 'required|date',
            'registros.*.modulo' => 'required|string',
            'registros.*.orden_produccion' => 'required|string',
            'registros.*.hora' => 'required|string',
            'registros.*.tiempo_ciclo' => 'required|numeric',
            'registros.*.porcion_tiempo' => 'required|numeric|min:0|max:1',
            'registros.*.cantidad' => 'nullable|integer',
            'registros.*.producida' => 'nullable|integer',
            'registros.*.paradas_programadas' => 'required|string',
            'registros.*.paradas_no_programadas' => 'nullable|string',
            'registros.*.tiempo_parada_no_programada' => 'nullable|numeric',
            'registros.*.numero_operarios' => 'required|integer',
            'registros.*.tiempo_para_programada' => 'nullable|numeric',
            'registros.*.meta' => 'nullable|numeric',
            'registros.*.eficiencia' => 'nullable|numeric',
            'section' => 'required|string|in:produccion,polos',
        ]);

        $model = match($request->section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
        };

        try {
            $createdRecords = [];
            foreach ($request->registros as $registroData) {
                $tiempo_disponible = (3600 * $registroData['porcion_tiempo'] * $registroData['numero_operarios']) -
                                   ($registroData['tiempo_parada_no_programada'] ?? 0) -
                                   ($registroData['tiempo_para_programada'] ?? 0);

                $meta = ($tiempo_disponible / $registroData['tiempo_ciclo']) * 0.9;
                $eficiencia = $meta == 0 ? 0 : (($registroData['cantidad'] ?? 0) / $meta);

                $record = $model::create([
                    'fecha' => $registroData['fecha'],
                    'modulo' => $registroData['modulo'],
                    'orden_produccion' => $registroData['orden_produccion'],
                    'hora' => $registroData['hora'],
                    'tiempo_ciclo' => $registroData['tiempo_ciclo'],
                    'porcion_tiempo' => $registroData['porcion_tiempo'],
                    'cantidad' => $registroData['cantidad'] ?? 0,
                    'producida' => $registroData['producida'] ?? 0,
                    'paradas_programadas' => $registroData['paradas_programadas'],
                    'paradas_no_programadas' => $registroData['paradas_no_programadas'] ?? null,
                    'tiempo_parada_no_programada' => $registroData['tiempo_parada_no_programada'] ?? null,
                    'numero_operarios' => $registroData['numero_operarios'],
                    'tiempo_para_programada' => $registroData['tiempo_para_programada'] ?? 0.00,
                    'tiempo_disponible' => $tiempo_disponible,
                    'meta' => $meta,
                    'eficiencia' => $eficiencia,
                ]);

                $createdRecords[] = $record;
            }

            return response()->json([
                'success' => true,
                'message' => 'Registros guardados correctamente.',
                'registros' => $createdRecords,
                'section' => $request->section
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los registros: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'section' => 'required|string|in:produccion,polos',
        ]);

        $model = match($request->section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
        };

        $registro = $model::findOrFail($id);

        $validated = $request->validate([
            'fecha' => 'sometimes|date',
            'modulo' => 'sometimes|string',
            'orden_produccion' => 'sometimes|string',
            'hora' => 'sometimes|string',
            'tiempo_ciclo' => 'sometimes|numeric',
            'porcion_tiempo' => 'sometimes|numeric|min:0|max:1',
            'cantidad' => 'sometimes|integer',
            'paradas_programadas' => 'sometimes|string',
            'paradas_no_programadas' => 'sometimes|string',
            'tiempo_parada_no_programada' => 'sometimes|numeric',
            'numero_operarios' => 'sometimes|integer',
            'tiempo_para_programada' => 'sometimes|numeric',
            'meta' => 'sometimes|numeric',
            'eficiencia' => 'sometimes|numeric',
        ]);

        try {
            $registro->update($validated);

            // Recalcular tiempo_disponible después de la actualización
            $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                               ($registro->tiempo_parada_no_programada ?? 0) -
                               ($registro->tiempo_para_programada ?? 0);

            // Recalcular meta después de la actualización
            $meta = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;

            // Recalcular eficiencia después de la actualización
            $eficiencia = $meta == 0 ? 0 : ($registro->cantidad / $meta);

            $registro->tiempo_disponible = $tiempo_disponible;
            $registro->meta = $meta;
            $registro->eficiencia = $eficiencia;
            $registro->save();

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado correctamente.',
                'data' => [
                    'tiempo_disponible' => $tiempo_disponible,
                    'meta' => $meta,
                    'eficiencia' => $eficiencia
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $request = request();
        $section = $request->query('section');

        $model = match($section) {
            'produccion' => RegistroPisoProduccion::class,
            'polos' => RegistroPisoPolo::class,
        };

        try {
            $registro = $model::findOrFail($id);
            $registro->delete();

            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el registro: ' . $e->getMessage()
            ], 500);
        }
    }
}
