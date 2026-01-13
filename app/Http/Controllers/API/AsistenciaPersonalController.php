<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use App\Models\RegistroDeHuella;
use App\Models\RegistroHorasHuella;
use App\Models\ReportePersonal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Smalot\PdfParser\Parser;

class AsistenciaPersonalController extends Controller
{
    public function procesarPDF(Request $request): JsonResponse
    {
        $request->validate(['pdf' => 'required|mimes:pdf|max:10240']);
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
                    
                    // Patrón 1: id_persona nombre fecha hora (ej: 2 Juan 2025-12-16 06:56:04)
                    if (preg_match('/^(\d+)\s+(.+?)\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})$/', $line, $matches)) {
                        $id_persona = intval($matches[1]);
                        $timestamp = $matches[3];
                        $personal = Personal::find($id_persona);
                        if ($personal) {
                            $registros[] = [
                                'id_persona' => $id_persona,
                                'nombre_persona' => $personal->nombre_persona,
                                'timestamp' => $timestamp
                            ];
                        }
                    }
                    // Patrón 2: id_persona fecha hora nombre (ej: 2 2025-12-16 06:56:04 Juan)
                    elseif (preg_match('/^(\d+)\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+(.+)$/', $line, $matches)) {
                        $id_persona = intval($matches[1]);
                        $timestamp = $matches[2];
                        $personal = Personal::find($id_persona);
                        if ($personal) {
                            $registros[] = [
                                'id_persona' => $id_persona,
                                'nombre_persona' => $personal->nombre_persona,
                                'timestamp' => $timestamp
                            ];
                        }
                    }
                    // Patrón 3: Detectar cualquier línea con id_persona y timestamp
                    elseif (preg_match('/\b(\d+)\b.*(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $line, $matches)) {
                        $id_persona = intval($matches[1]);
                        $timestamp = $matches[2];
                        $personal = Personal::find($id_persona);
                        if ($personal) {
                            $registros[] = [
                                'id_persona' => $id_persona,
                                'nombre_persona' => $personal->nombre_persona,
                                'timestamp' => $timestamp
                            ];
                        }
                    }
                }
            }
            if (empty($registros)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros válidos en el PDF'
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

    public function validarRegistros(Request $request): JsonResponse
    {
        try {
            $registros = $request->input('registros', []);
            
            if (empty($registros)) {
                return response()->json([
                    'success' => true,
                    'validos' => 0,
                    'rechazados' => 0,
                    'personas_no_encontradas' => [],
                    'registros_rechazados' => []
                ]);
            }

            $registrosPorPersonaYDia = [];
            $registrosRechazados = [];
            $personasNoEncontradas = []; // IDs de personas no encontradas (sin duplicar)

            foreach ($registros as $index => $registro) {
                $idPersona = intval($registro['id_persona']);
                $timestamp = $registro['timestamp'];
                
                // Validar que la persona existe
                $personal = Personal::find($idPersona);
                if (!$personal) {
                    // Agregar a personas no encontradas (sin duplicar)
                    if (!in_array($idPersona, array_column($personasNoEncontradas, 'id_persona'))) {
                        $personasNoEncontradas[] = [
                            'id_persona' => $idPersona,
                            'registros_intento' => 1
                        ];
                    } else {
                        // Incrementar contador
                        foreach ($personasNoEncontradas as &$p) {
                            if ($p['id_persona'] === $idPersona) {
                                $p['registros_intento']++;
                                break;
                            }
                        }
                    }
                    
                    $registrosRechazados[] = [
                        'indice' => $index,
                        'id_persona' => $idPersona,
                        'razon' => 'Persona no encontrada en la base de datos'
                    ];
                    continue;
                }
                
                // Extraer fecha y hora de la timestamp
                $partes = explode(' ', $timestamp);
                if (count($partes) < 2) {
                    $registrosRechazados[] = [
                        'indice' => $index,
                        'id_persona' => $idPersona,
                        'razon' => 'Formato de timestamp inválido: ' . $timestamp
                    ];
                    continue;
                }
                
                $fecha = $partes[0];
                $hora = $partes[1];
                
                // Validar formato de fecha (YYYY-MM-DD)
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    $registrosRechazados[] = [
                        'indice' => $index,
                        'id_persona' => $idPersona,
                        'razon' => 'Formato de fecha inválido: ' . $fecha
                    ];
                    continue;
                }
                
                // Validar formato de hora (HH:MM:SS)
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
                    $registrosRechazados[] = [
                        'indice' => $index,
                        'id_persona' => $idPersona,
                        'razon' => 'Formato de hora inválido: ' . $hora
                    ];
                    continue;
                }
                
                // Clave para agrupar
                $clave = "{$idPersona}_{$fecha}_{$hora}";
                
                if (!isset($registrosPorPersonaYDia[$clave])) {
                    $registrosPorPersonaYDia[$clave] = [
                        'id_persona' => $idPersona,
                        'nombre_persona' => $personal->nombre_persona,
                        'dia' => $fecha,
                        'hora' => $hora
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'validos' => count($registrosPorPersonaYDia),
                'rechazados' => count($registrosRechazados),
                'personas_no_encontradas' => $personasNoEncontradas,
                'registros_rechazados' => $registrosRechazados,
                'registros_validos' => array_values($registrosPorPersonaYDia)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar registros: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarRegistros(Request $request): JsonResponse
    {
        $request->validate([
            'registros' => 'required|array',
            'registros.*.id_persona' => 'required|integer|exists:personal,id',
            'registros.*.timestamp' => 'required|date_format:Y-m-d H:i:s'
        ]);

        try {
            $numeroReporte = 'REP-' . date('Ymd') . '-' . time();
            $nombreReporte = 'Reporte Asistencia ' . date('d/m/Y H:i');
            $reporte = ReportePersonal::create([
                'numero_reporte' => $numeroReporte,
                'nombre_reporte' => $nombreReporte
            ]);

            $registrosPorPersonaYDia = [];
            $registrosRechazados = [];
            
            foreach ($request->input('registros') as $index => $registro) {
                $idPersona = intval($registro['id_persona']);
                $timestamp = $registro['timestamp'];
                
                // Validar que la persona existe
                $personal = Personal::find($idPersona);
                if (!$personal) {
                    $registrosRechazados[] = [
                        'indice' => $index,
                        'id_persona' => $idPersona,
                        'razon' => 'Persona no encontrada en la base de datos'
                    ];
                    continue;
                }
                
                // Extraer fecha y hora de la timestamp (ej: 2025-12-16 06:56:04)
                $partes = explode(' ', $timestamp);
                if (count($partes) < 2) {
                    $registrosRechazados[] = [
                        'indice' => $index,
                        'id_persona' => $idPersona,
                        'razon' => 'Formato de timestamp inválido: ' . $timestamp
                    ];
                    continue;
                }
                
                $fecha = $partes[0];
                $hora = $partes[1];
                
                // Validar formato de fecha (YYYY-MM-DD)
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    $registrosRechazados[] = [
                        'indice' => $index,
                        'id_persona' => $idPersona,
                        'razon' => 'Formato de fecha inválido: ' . $fecha
                    ];
                    continue;
                }
                
                // Validar formato de hora (HH:MM:SS)
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
                    $registrosRechazados[] = [
                        'indice' => $index,
                        'id_persona' => $idPersona,
                        'razon' => 'Formato de hora inválido: ' . $hora
                    ];
                    continue;
                }
                
                // Clave para agrupar: id_persona_fecha_hora (para evitar duplicados)
                $clave = "{$idPersona}_{$fecha}_{$hora}";
                
                // Solo guardar si no existe una clave idéntica
                if (!isset($registrosPorPersonaYDia[$clave])) {
                    $registrosPorPersonaYDia[$clave] = [
                        'id_persona' => $idPersona,
                        'dia' => $fecha,
                        'hora' => $hora
                    ];
                }
            }

            // Agrupar horas por persona y día
            $registrosPorPersonaYDiaAgrupado = [];
            foreach ($registrosPorPersonaYDia as $clave => $registro) {
                $claveAgrupada = $registro['id_persona'] . '_' . $registro['dia'];
                
                if (!isset($registrosPorPersonaYDiaAgrupado[$claveAgrupada])) {
                    $registrosPorPersonaYDiaAgrupado[$claveAgrupada] = [
                        'id_persona' => $registro['id_persona'],
                        'dia' => $registro['dia'],
                        'horas' => []
                    ];
                }
                
                $registrosPorPersonaYDiaAgrupado[$claveAgrupada]['horas'][] = $registro['hora'];
            }

            $guardados = 0;
            foreach ($registrosPorPersonaYDiaAgrupado as $registro) {
                $horasFormato = [];
                foreach ($registro['horas'] as $index => $hora) {
                    $horasFormato["Hora " . ($index + 1)] = $hora;
                }
                
                try {
                    RegistroHorasHuella::create([
                        'id_reporte' => $reporte->id,
                        'id_persona' => $registro['id_persona'],
                        'dia' => $registro['dia'],
                        'horas' => $horasFormato
                    ]);
                    $guardados++;
                } catch (\Exception $e) {
                    $registrosRechazados[] = [
                        'id_persona' => $registro['id_persona'],
                        'dia' => $registro['dia'],
                        'razon' => 'Error al guardar en BD: ' . $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'guardados' => $guardados,
                'numero_reporte' => $numeroReporte,
                'procesados' => count($registrosPorPersonaYDia),
                'rechazados' => count($registrosRechazados),
                'registros_rechazados' => $registrosRechazados,
                'message' => "Reporte guardado: {$guardados} registros guardados, " . count($registrosRechazados) . " rechazados"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar registros: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getReportDetails(string $id): JsonResponse
    {
        try {
            $reporte = ReportePersonal::findOrFail($id);
            $registros = RegistroHorasHuella::where('id_reporte', $id)
                ->with('personal')
                ->orderBy('dia')
                ->get();

            $registrosPorPersona = $registros->map(function($registro) {
                $fecha = $registro->dia;
                if (is_object($fecha)) {
                    $fecha = $fecha->format('Y-m-d');
                }
                return [
                    'id_persona' => $registro->id_persona,
                    'nombre' => $registro->personal->nombre_persona ?? 'Sin nombre',
                    'fecha' => $fecha,
                    'horas' => $registro->horas ?? []
                ];
            })->values();

            return response()->json([
                'success' => true,
                'reporte' => [
                    'id' => $reporte->id,
                    'numero_reporte' => $reporte->numero_reporte,
                    'nombre_reporte' => $reporte->nombre_reporte,
                    'created_at' => $reporte->created_at->format('d/m/Y H:i'),
                    'registros_por_persona' => $registrosPorPersona
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el reporte: ' . $e->getMessage()
            ], 404);
        }
    }

    public function getAbsenciasDelDia(string $id): JsonResponse
    {
        try {
            $reporte = ReportePersonal::findOrFail($id);
            
            // Obtener todas las fechas disponibles en este reporte
            $fechasDisponibles = RegistroHorasHuella::where('id_reporte', $id)
                ->distinct()
                ->pluck('dia')
                ->map(function($date) {
                    return $date->format('Y-m-d');
                })
                ->toArray();
            
            // Obtener todas las personas y sus asistencias
            $todasLasPersonas = Personal::all();
            $registrosPorPersona = RegistroHorasHuella::where('id_reporte', $id)
                ->get()
                ->groupBy('id_persona');
            
            // Calcular inasistencias por persona
            $personasInasistentes = [];
            foreach ($todasLasPersonas as $persona) {
                $fechasInasistidas = [];
                
                // Verificar cada fecha disponible
                foreach ($fechasDisponibles as $fecha) {
                    // Buscar si la persona tiene registro en esa fecha
                    $tieneRegistro = $registrosPorPersona->has($persona->id) && 
                                   $registrosPorPersona[$persona->id]->some(function($registro) use ($fecha) {
                                       $diaRegistro = $registro->dia;
                                       if (is_object($diaRegistro)) {
                                           $diaRegistro = $diaRegistro->format('Y-m-d');
                                       }
                                       return $diaRegistro === $fecha;
                                   });
                    
                    if (!$tieneRegistro) {
                        $fechasInasistidas[] = $fecha;
                    }
                }
                
                // Si la persona tiene inasistencias, agregarla a la lista
                if (count($fechasInasistidas) > 0) {
                    $personasInasistentes[] = [
                        'id' => $persona->id,
                        'nombre' => $persona->nombre_persona,
                        'total_inasistencias' => count($fechasInasistidas),
                        'fechas_inasistidas' => $fechasInasistidas
                    ];
                }
            }
            
            // Ordenar por nombre
            usort($personasInasistentes, function($a, $b) {
                return strcmp($a['nombre'], $b['nombre']);
            });

            return response()->json([
                'success' => true,
                'ausencias' => $personasInasistentes,
                'total_fechas' => count($fechasDisponibles),
                'fechas_disponibles' => $fechasDisponibles,
                'total_personal' => count($todasLasPersonas),
                'total_inasistentes' => count($personasInasistentes)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ausencias: ' . $e->getMessage()
            ], 500);
        }
    }
}