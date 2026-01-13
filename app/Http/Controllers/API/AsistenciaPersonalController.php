<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use App\Models\RegistroDeHuella;
use App\Models\ReportePersonal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Smalot\PdfParser\Parser;

class AsistenciaPersonalController extends Controller
{
    /**
     * Procesar archivo PDF y extraer datos
     */
    public function procesarPDF(Request $request): JsonResponse
    {
        $request->validate([
            'pdf' => 'required|mimes:pdf|max:10240'
        ]);

        try {
            $file = $request->file('pdf');
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getPathname());
            
            $pages = $pdf->getPages();
            $registros = [];

            foreach ($pages as $page) {
                $text = $page->getText();
                $lines = explode("\n", $text);

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;

                    // Intentar extraer datos - más flexible
                    // Buscamos patrones con números y horas
                    if (preg_match('/^(\d+)\s+(.+?)\s+(\d{1,2}:\d{2}(?::\d{2})?)$/', $line, $matches)) {
                        $id_persona = intval($matches[1]);
                        $nombre = trim($matches[2]);
                        $hora = $matches[3];
                        
                        // Obtener persona de la BD
                        $personal = Personal::find($id_persona);
                        
                        if ($personal && preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $hora)) {
                            $registros[] = [
                                'id_persona' => $id_persona,
                                'nombre_persona' => $personal->nombre_persona,
                                'hora' => $hora
                            ];
                        }
                    }
                    // Alternativa: ID NOMBRE HORA (sin estructura específica)
                    elseif (preg_match('/(\d+)\s+(.+)\s+(\d{1,2}:\d{2}(?::\d{2})?)/', $line, $matches)) {
                        $id_persona = intval($matches[1]);
                        $hora = $matches[3];
                        
                        $personal = Personal::find($id_persona);
                        
                        if ($personal && preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $hora)) {
                            $registros[] = [
                                'id_persona' => $id_persona,
                                'nombre_persona' => $personal->nombre_persona,
                                'hora' => $hora
                            ];
                        }
                    }
                }
            }

            if (empty($registros)) {
                // Retornar error con más detalles
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros válidos en el PDF. Verifica que el formato sea: ID NOMBRE HORA'
                ]);
            }

            return response()->json([
                'success' => true,
                'registros' => $registros,
                'cantidad' => count($registros)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar registros en la base de datos
     */
    public function guardarRegistros(Request $request): JsonResponse
    {
        $request->validate([
            'registros' => 'required|array',
            'registros.*.id_persona' => 'required|integer|exists:personal,id',
            'registros.*.hora' => 'required|date_format:H:i:s'
        ]);

        try {
            // Crear reporte
            $numeroReporte = 'REP-' . date('Ymd') . '-' . time();
            $nombreReporte = 'Reporte Asistencia ' . date('d/m/Y H:i');
            
            $reporte = ReportePersonal::create([
                'numero_reporte' => $numeroReporte,
                'nombre_reporte' => $nombreReporte
            ]);

            // Guardar registros de huella
            $guardados = 0;
            foreach ($request->input('registros') as $registro) {
                RegistroDeHuella::create([
                    'id_persona' => $registro['id_persona'],
                    'id_reporte' => $reporte->id,
                    'hora' => $registro['hora']
                ]);
                $guardados++;
            }

            return response()->json([
                'success' => true,
                'guardados' => $guardados,
                'numero_reporte' => $numeroReporte,
                'message' => 'Reporte guardado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar registros: ' . $e->getMessage()
            ], 500);
        }
    }
}
