<?php

namespace App\Http\Controllers\Api_temp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistroHorasHuella;
use Illuminate\Support\Facades\DB;

class AsistenciaDetalladaController extends Controller
{
    /**
     * Obtener asistencias de un personal en un período
     */
    public function obtenerAsistencias(Request $request)
    {
        $validated = $request->validate([
            'persona_id' => 'required|integer',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date'
        ]);

        $asistencias = RegistroHorasHuella::where('codigo_persona', $validated['persona_id'])
            ->whereBetween('dia', [$validated['fecha_inicio'], $validated['fecha_fin']])
            ->get();

        return response()->json(['success' => true, 'data' => $asistencias]);
    }

    /**
     * Obtener asistencia de un día específico
     */
    public function obtenerAsistenciaDelDia(Request $request)
    {
        $validated = $request->validate([
            'persona_id' => 'required|integer',
            'fecha' => 'required|date'
        ]);

        $asistencia = RegistroHorasHuella::where('codigo_persona', $validated['persona_id'])
            ->where('dia', $validated['fecha'])
            ->get();

        return response()->json(['success' => true, 'data' => $asistencia]);
    }

    /**
     * Guardar cambios de asistencia (marcas)
     */
    public function guardarCambios(Request $request)
    {
        try {
            \Log::info('=== GUARDANDO CAMBIOS DE ASISTENCIA ===');
            \Log::info('Request completo:', $request->all());
            
            $validated = $request->validate([
                'persona_id' => 'required|integer',
                'cambios' => 'required|array'
            ]);

            $personaId = $validated['persona_id'];
            $cambios = $validated['cambios'];

            \Log::info('Datos validados:', [
                'persona_id' => $personaId,
                'cambios' => $cambios
            ]);

            // Procesar cada fecha y sus cambios
            DB::beginTransaction();
            
            foreach ($cambios as $fecha => $marcas) {
                \Log::info("Procesando cambios para fecha: $fecha", $marcas);
                
                // Obtener registro existente
                $registro = RegistroHorasHuella::where('codigo_persona', $personaId)
                    ->where('dia', $fecha)
                    ->first();

                if (!$registro) {
                    \Log::warning("No se encontró registro para persona $personaId en fecha $fecha");
                    continue;
                }

                \Log::info("Registro encontrado. Horas actuales:", (array)$registro->horas);

                // Obtener las horas existentes (ya están en formato array por el cast)
                $horasActuales = $registro->horas ?? [];
                if (!is_array($horasActuales)) {
                    $horasActuales = json_decode($horasActuales, true) ?? [];
                }

                \Log::info("Horas actuales después de decode:", $horasActuales);

                // Mapear las marcas a las claves "Hora 1", "Hora 2", etc.
                // entrada_manana -> Hora 1
                // salida_manana -> Hora 2
                // entrada_tarde -> Hora 3
                // salida_tarde -> Hora 4

                $mapeoMarcas = [
                    'entrada_manana' => 'Hora 1',
                    'salida_manana' => 'Hora 2',
                    'entrada_tarde' => 'Hora 3',
                    'salida_tarde' => 'Hora 4'
                ];

                // Actualizar solo las marcas que vinieron en los cambios
                foreach ($mapeoMarcas as $nombreCambio => $nombreHora) {
                    if (isset($marcas[$nombreCambio]) && $marcas[$nombreCambio]) {
                        $horasActuales[$nombreHora] = $marcas[$nombreCambio];
                        \Log::info("Actualizado $nombreHora = " . $marcas[$nombreCambio]);
                    }
                }

                \Log::info("Horas finales después de actualizar:", $horasActuales);

                // Guardar las horas actualizadas
                $registro->horas = $horasActuales;
                $registro->save();

                \Log::info("Registro actualizado para $fecha", ['horas' => $horasActuales, 'id' => $registro->id]);
            }

            DB::commit();

            \Log::info('✓ Cambios guardados correctamente');

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Cambios guardados correctamente'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            \Log::error('Error de validación al guardar cambios:', [
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            
            \Log::error('Error al guardar cambios de asistencia', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Error al guardar los cambios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rellenar inteligentemente marcas faltantes
     */
    public function rellenarInteligente(Request $request)
    {
        $validated = $request->validate([
            'persona_id' => 'required|integer',
            'fecha' => 'required|date'
        ]);

        return response()->json(['success' => true, 'message' => 'Método no implementado aún']);
    }

    /**
     * Obtener resumen del mes
     */
    public function obtenerMes(Request $request)
    {
        $validated = $request->validate([
            'persona_id' => 'required|integer',
            'mes' => 'required|integer',
            'año' => 'required|integer'
        ]);

        return response()->json(['success' => true, 'data' => []]);
    }
}
