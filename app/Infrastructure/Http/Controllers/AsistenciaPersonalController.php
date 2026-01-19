<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Personal;
use App\Models\RegistroDeHuella;
use App\Models\RegistroHorasHuella;
use App\Models\ReportePersonal;
use App\Models\HorarioPorRol;
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
            
            // Validar que el archivo existe
            if (!$file || !file_exists($file->getPathname())) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo PDF no se pudo procesar. El archivo no existe.'
                ], 400);
            }
            
            // Log de información del archivo
            \Log::info('Procesando PDF', [
                'nombre' => $file->getClientOriginalName(),
                'tamaño' => $file->getSize(),
                'ruta' => $file->getPathname(),
                'tipo' => $file->getClientMimeType(),
                'extensión' => $file->getClientOriginalExtension()
            ]);
            
            // Crear un parser
            $parser = new Parser();
            
            // Log antes de parsear
            \Log::info('Parser creado, intentando parsear archivo');
            
            // Parsear el PDF
            $pdf = $parser->parseFile($file->getPathname());
            
            \Log::info('PDF parseado exitosamente');
            
            $pages = $pdf->getPages();
            
            \Log::info('Páginas extraídas', ['total_páginas' => count($pages)]);
            
            $registros = [];
            foreach ($pages as $pageIndex => $page) {
                try {
                    $text = $page->getText();
                    $lines = explode("\n", $text);
                    
                    \Log::debug("Procesando página " . ($pageIndex + 1), ['total_líneas' => count($lines)]);
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;
                        
                        // Patrón 1: id_persona nombre fecha hora (ej: 2 Juan 2025-12-16 06:56:04)
                        if (preg_match('/^(\d+)\s+(.+?)\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})$/', $line, $matches)) {
                            $codigo_persona = intval($matches[1]);
                            $timestamp = $matches[3];
                            $personal = Personal::where('codigo_persona', $codigo_persona)->first();
                            if ($personal) {
                                $registros[] = [
                                    'id_persona' => $codigo_persona,
                                    'nombre_persona' => $personal->nombre_persona,
                                    'timestamp' => $timestamp
                                ];
                            }
                        }
                        // Patrón 2: id_persona fecha hora nombre (ej: 2 2025-12-16 06:56:04 Juan)
                        elseif (preg_match('/^(\d+)\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+(.+)$/', $line, $matches)) {
                            $codigo_persona = intval($matches[1]);
                            $timestamp = $matches[2];
                            $personal = Personal::where('codigo_persona', $codigo_persona)->first();
                            if ($personal) {
                                $registros[] = [
                                    'id_persona' => $codigo_persona,
                                    'nombre_persona' => $personal->nombre_persona,
                                    'timestamp' => $timestamp
                                ];
                            }
                        }
                        // Patrón 3: Detectar cualquier línea con id_persona y timestamp
                        elseif (preg_match('/\b(\d+)\b.*(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $line, $matches)) {
                            $codigo_persona = intval($matches[1]);
                            $timestamp = $matches[2];
                            $personal = Personal::where('codigo_persona', $codigo_persona)->first();
                            if ($personal) {
                                $registros[] = [
                                    'id_persona' => $codigo_persona,
                                    'nombre_persona' => $personal->nombre_persona,
                                    'timestamp' => $timestamp
                                ];
                            }
                        }
                    }
                } catch (\Exception $pageError) {
                    \Log::warning("Error procesando página " . ($pageIndex + 1), [
                        'error' => $pageError->getMessage()
                    ]);
                    // Continuar con la siguiente página
                    continue;
                }
            }
            
            if (empty($registros)) {
                \Log::info('No se encontraron registros válidos en el PDF');
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros válidos en el PDF'
                ]);
            }
            
            \Log::info('PDF procesado exitosamente', ['registros_encontrados' => count($registros)]);
            
            return response()->json([
                'success' => true,
                'registros' => $registros,
                'cantidad' => count($registros)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al procesar PDF', [
                'mensaje' => $e->getMessage(),
                'clase' => get_class($e),
                'archivo' => $e->getFile(),
                'línea' => $e->getLine(),
                'stack' => $e->getTraceAsString()
            ]);
            
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
            'registros.*.id_persona' => 'required|integer|exists:personal,codigo_persona',
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
                $personal = Personal::where('codigo_persona', $idPersona)->first();
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

            // Filtrar marcas duplicadas cercanas (mantener solo la más reciente)
            // Y eliminar marcas extra que estén fuera del rango esperado
            $registrosPorPersonaYDiaFiltrado = [];
            foreach ($registrosPorPersonaYDiaAgrupado as $clave => $registro) {
                $horas = $registro['horas'];
                
                // Convertir horas a segundos para comparación
                $horasEnSegundos = array_map(function($hora) {
                    $partes = explode(':', $hora);
                    return intval($partes[0]) * 3600 + intval($partes[1]) * 60 + intval($partes[2]);
                }, $horas);
                
                // Ordenar por tiempo
                asort($horasEnSegundos);
                
                // Paso 1: Filtrar duplicados muy cercanos (< 5 minutos)
                $horasValidas = [];
                foreach ($horasEnSegundos as $index => $segundos) {
                    // Si no hay marcas previas, agregar esta
                    if (empty($horasValidas)) {
                        $horasValidas[$index] = $segundos;
                        continue;
                    }
                    
                    // Comparar con la última marca válida
                    $ultimaMarca = end($horasValidas);
                    $diferencia = abs($segundos - $ultimaMarca);
                    
                    // Si la diferencia es mayor a 5 minutos (300 segundos), es una marca diferente
                    if ($diferencia > 300) {
                        $horasValidas[$index] = $segundos;
                    } else {
                        // Es una marca duplicada muy cercana, reemplazar la anterior con esta (más reciente)
                        array_pop($horasValidas);
                        $horasValidas[$index] = $segundos;
                    }
                }
                
                // Paso 2: Limpiar marcas extras en rango de entrada_manana y salida_tarde
                $umbralEntradaManana = 28800; // 08:00 en segundos
                $umbralSalidaTarde = 64800; // 18:00 en segundos
                $marcasArray = array_values($horasValidas);
                
                $marcasAntes08 = [];
                $marcasEntre08y18 = [];
                $marcasDespues18 = [];
                
                foreach ($marcasArray as $marca) {
                    if ($marca < $umbralEntradaManana) {
                        $marcasAntes08[] = $marca;
                    } elseif ($marca <= $umbralSalidaTarde) {
                        $marcasEntre08y18[] = $marca;
                    } else {
                        $marcasDespues18[] = $marca;
                    }
                }
                
                // Si hay múltiples marcas ANTES de 08:00, mantener solo la más TEMPRANA (entrada real)
                if (count($marcasAntes08) > 1) {
                    $primeraAntes08 = $marcasAntes08[0];
                    $ultimaAntes08 = $marcasAntes08[count($marcasAntes08) - 1];
                    $diferencia = $ultimaAntes08 - $primeraAntes08;
                    
                    // Si están dentro de 2 horas, es una duplicación de entrada
                    if ($diferencia < 7200) {
                        $marcasAntes08 = [$primeraAntes08]; // Mantener la más temprana
                    }
                }
                
                // Si hay múltiples marcas DESPUÉS de 18:00, mantener solo la más RECIENTE (sin importar diferencia)
                if (count($marcasDespues18) > 1) {
                    $ultimaDesp18 = $marcasDespues18[count($marcasDespues18) - 1];
                    $marcasDespues18 = [$ultimaDesp18]; // Mantener solo la más reciente
                }
                
                // Si hay múltiples marcas en el rango 08:00-18:00, verificar si alguna está fuera de rango
                // Por ejemplo, si hay marca a las 08:25 cuando entrada_manana se esperaba a las 08:00,
                // y también hay una marca antes de las 08:00, eliminar la que está en rango esperado
                if (count($marcasAntes08) > 0 && count($marcasEntre08y18) > 0) {
                    // Si la primera marca en 08:00-18:00 es muy cercana a entrada_manana (08:00)
                    // y hay marcas antes, eliminar la marca cercana de 08:00
                    $primeraEnRango = $marcasEntre08y18[0];
                    $distanciaA08 = abs($primeraEnRango - $umbralEntradaManana);
                    
                    if ($distanciaA08 < 3600) { // Si está dentro de 1 hora de 08:00
                        // Eliminar esta marca porque la entrada fue antes (está en marcasAntes08)
                        array_shift($marcasEntre08y18);
                    }
                }
                
                // Recombinar en orden: antes de 08 + entre 08-18 + después de 18
                $horasValidasLimpias = [];
                foreach ($marcasAntes08 as $marca) {
                    $horasValidasLimpias[] = $marca;
                }
                foreach ($marcasEntre08y18 as $marca) {
                    $horasValidasLimpias[] = $marca;
                }
                foreach ($marcasDespues18 as $marca) {
                    $horasValidasLimpias[] = $marca;
                }
                
                // Convertir de vuelta a formato HH:MM:SS
                $horasFormateadas = array_map(function($segundos) {
                    $horas = intdiv($segundos, 3600);
                    $minutos = intdiv($segundos % 3600, 60);
                    $secs = $segundos % 60;
                    return sprintf('%02d:%02d:%02d', $horas, $minutos, $secs);
                }, $horasValidasLimpias);
                
                $registrosPorPersonaYDiaFiltrado[$clave] = [
                    'id_persona' => $registro['id_persona'],
                    'dia' => $registro['dia'],
                    'horas' => $horasFormateadas
                ];
            }

            $guardados = 0;
            $marcasFaltantes = [];
            
            foreach ($registrosPorPersonaYDiaFiltrado as $registro) {
                $personal = Personal::where('codigo_persona', $registro['id_persona'])->first();
                if (!$personal) continue;

                // Ordenar horas cronológicamente
                $horasOrdenadas = $this->ordenarHorasCronologicamente($registro['horas']);
                
                // Obtener información del día para decidir si validar marcas faltantes
                $fechaObj = \DateTime::createFromFormat('Y-m-d', $registro['dia']);
                $esSabado = $fechaObj ? $fechaObj->format('w') == 6 : false;
                
                // Determinar cantidad esperada de marcas
                $cantidadEsperada = $esSabado ? 2 : 4;
                
                // Lógica especial para rol 21: solo agregar marcas faltantes si tiene 4 marcas
                $esRol21 = $personal->id_rol == 21;
                
                // Para rol 21 en días normales con menos de 4 marcas: NO agregar marcas faltantes
                if ($esRol21 && !$esSabado && count($horasOrdenadas) < 4) {
                    // No hacer nada, solo guardar lo que hay
                } else {
                    // Detectar y agregar marcas faltantes repetidamente hasta alcanzar la cantidad esperada
                    // Bucle: mientras haya menos marcas de las esperadas, intentar detectar más
                    while (count($horasOrdenadas) < $cantidadEsperada) {
                        // Para sábado: si ya hay 2+ marcas, no agregar más
                        if ($esSabado && count($horasOrdenadas) >= 2) {
                            break;
                        }
                        
                        $marcaFaltante = $this->detectarMarcaFaltante($personal, $horasOrdenadas, $registro['dia']);
                        
                        if ($marcaFaltante) {
                            // Agregar la marca faltante solo si realmente falta
                            $horasOrdenadas[] = $marcaFaltante['hora_esperada'];
                            
                            // Reordenar después de agregar
                            $horasOrdenadas = $this->ordenarHorasCronologicamente($horasOrdenadas);
                            
                            // Registrar la marca faltante detectada
                            $marcasFaltantes[] = [
                                'codigo_persona' => $registro['id_persona'],
                                'nombre_persona' => $personal->nombre_persona,
                                'dia' => $registro['dia'],
                                'marca' => $marcaFaltante['nombre_marca'],
                                'hora_detectada' => $marcaFaltante['hora_esperada'],
                                'status' => 'agregada_automaticamente'
                            ];
                        } else {
                            // Si no hay más marcas faltantes detectadas, romper el bucle
                            break;
                        }
                    }
                }

                // Crear array con formato "Hora 1", "Hora 2", etc.
                $horasFormato = [];
                foreach ($horasOrdenadas as $index => $hora) {
                    $horasFormato["Hora " . ($index + 1)] = $hora;
                }
                
                try {
                    RegistroHorasHuella::create([
                        'id_reporte' => $reporte->id,
                        'codigo_persona' => $registro['id_persona'],
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
                'marcas_faltantes_detectadas' => $marcasFaltantes,
                'message' => "Reporte guardado: {$guardados} registros guardados, " . count($registrosRechazados) . " rechazados" . 
                             (count($marcasFaltantes) > 0 ? ", " . count($marcasFaltantes) . " marcas faltantes detectadas y agregadas automáticamente" : "")
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
                
                // Obtener el horario del rol para obtener entrada_sabado y salida_sabado
                $horarioPorRol = \App\Models\HorarioPorRol::where('id_rol', $registro->personal->id_rol)->first();
                
                return [
                    'codigo_persona' => $registro->codigo_persona,
                    'nombre' => $registro->personal->nombre_persona ?? 'Sin nombre',
                    'id_rol' => $registro->personal->id_rol,
                    'entrada_sabado' => $horarioPorRol?->entrada_sabado ? substr($horarioPorRol->entrada_sabado, 0, 5) : null,
                    'salida_sabado' => $horarioPorRol?->salida_sabado ? substr($horarioPorRol->salida_sabado, 0, 5) : null,
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
                ->groupBy('codigo_persona');
            
            // Calcular inasistencias por persona
            $personasInasistentes = [];
            foreach ($todasLasPersonas as $persona) {
                $fechasInasistidas = [];
                
                // Verificar cada fecha disponible
                foreach ($fechasDisponibles as $fecha) {
                    // Buscar si la persona tiene registro en esa fecha
                    $tieneRegistro = $registrosPorPersona->has($persona->codigo_persona) && 
                                   $registrosPorPersona[$persona->codigo_persona]->some(function($registro) use ($fecha) {
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
                        'id' => $persona->codigo_persona,
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

    /**
     * Detectar qué marca falta según el horario de la persona
     * 
     * @param Personal $personal
     * @param array $horasRegistradas
     * @param string $dia (formato Y-m-d)
     * @return array|null ['marca_faltante' => 'campo', 'hora_esperada' => 'HH:MM:SS'] o null
     */
    private function detectarMarcaFaltante($personal, $horasRegistradas, $dia): ?array
    {
        // Fechas especiales donde NO se valida marcas faltantes
        $fechasEspeciales = [
            '2025-12-24', // Navidad - se trabajó solo 6 horas
        ];

        // Roles donde NO se aplica esta lógica
        $rolesExcluidos = [21]; // Rol 21 (mixto) no usa detección automática

        // Si es una fecha especial, no validar marcas faltantes
        if (in_array($dia, $fechasEspeciales)) {
            return null;
        }

        // Si el rol está excluido, no validar marcas faltantes
        if (in_array($personal->id_rol, $rolesExcluidos)) {
            return null;
        }

        // Si no tiene rol, no puede validar
        if (!$personal->id_rol) {
            return null;
        }

        // Obtener el horario del rol
        $horario = HorarioPorRol::where('id_rol', $personal->id_rol)->first();
        if (!$horario) {
            return null;
        }

        // Determinar si es sábado (día 6 en PHP: 0=domingo, 1=lunes...6=sábado)
        $fechaObj = \DateTime::createFromFormat('Y-m-d', $dia);
        $esSabado = $fechaObj ? $fechaObj->format('w') == 6 : false;

        // Definir marcas según el día de la semana
        if ($esSabado) {
            // Sábado: solo 2 marcas
            $mapeoMarcas = [
                'entrada_sabado' => 1,
                'salida_sabado' => 2
            ];
            $horariosEsperados = [
                'entrada_sabado' => $this->horaASegundos($horario->entrada_sabado),
                'salida_sabado' => $this->horaASegundos($horario->salida_sabado)
            ];
        } else {
            // Otros días: 4 marcas
            $mapeoMarcas = [
                'entrada_manana' => 1,
                'salida_manana' => 2,
                'entrada_tarde' => 3,
                'salida_tarde' => 4
            ];
            $horariosEsperados = [
                'entrada_manana' => $this->horaASegundos($horario->entrada_manana),
                'salida_manana' => $this->horaASegundos($horario->salida_manana),
                'entrada_tarde' => $this->horaASegundos($horario->entrada_tarde),
                'salida_tarde' => $this->horaASegundos($horario->salida_tarde)
            ];
        }

        // Convertir horas registradas a segundos para comparación
        $horasEnSegundos = [];
        foreach ($horasRegistradas as $hora) {
            if ($hora) {
                $partes = explode(':', $hora);
                $horasEnSegundos[] = intval($partes[0]) * 3600 + intval($partes[1]) * 60 + intval($partes[2]);
            }
        }
        sort($horasEnSegundos);

        // ALGORITMO SECUENCIAL: Asignar cada marca a la siguiente marca esperada en orden
        // Las marcas registradas deben estar en orden cronológico
        $marcasEsperadasOrdenadas = [];
        
        if ($esSabado) {
            $marcasEsperadasOrdenadas = [
                'entrada_sabado' => $horariosEsperados['entrada_sabado'],
                'salida_sabado' => $horariosEsperados['salida_sabado']
            ];
        } else {
            $marcasEsperadasOrdenadas = [
                'entrada_manana' => $horariosEsperados['entrada_manana'],
                'salida_manana' => $horariosEsperados['salida_manana'],
                'entrada_tarde' => $horariosEsperados['entrada_tarde'],
                'salida_tarde' => $horariosEsperados['salida_tarde']
            ];
        }
        
        // Filtrar nulls
        foreach ($marcasEsperadasOrdenadas as $key => $valor) {
            if ($valor === null) {
                unset($marcasEsperadasOrdenadas[$key]);
            }
        }
        
        // ALGORITMO MEJORADO: Detectar marcas faltantes consecutivas al inicio
        $marcasEsperadasArray = array_keys($marcasEsperadasOrdenadas);
        
        // Si no hay marcas registradas, retornar la primera marca esperada
        if (empty($horasEnSegundos)) {
            return [
                'marca_faltante' => $marcasEsperadasArray[0],
                'hora_esperada' => $this->segundosAHora($marcasEsperadasOrdenadas[$marcasEsperadasArray[0]]),
                'nombre_marca' => $this->nombreMarca($marcasEsperadasArray[0])
            ];
        }
        
        // Detectar cuántas marcas faltan al inicio
        $primeraHoraRegistrada = $horasEnSegundos[0];
        $indiceProximaEsperada = 0;
        
        // Iterar por las marcas esperadas hasta encontrar una que esté dentro de 2 horas
        while ($indiceProximaEsperada < count($marcasEsperadasArray)) {
            $marcaEsperada = $marcasEsperadasArray[$indiceProximaEsperada];
            $horaEsperada = $marcasEsperadasOrdenadas[$marcaEsperada];
            $distancia = abs($primeraHoraRegistrada - $horaEsperada);
            
            // Si la primera marca registrada está dentro de 2 horas, no hay marcas faltantes antes
            if ($distancia <= 7200) {
                break;
            }
            
            // Esta marca esperada está muy lejos, es faltante
            $indiceProximaEsperada++;
        }
        
        // Si hay marcas faltantes al inicio, retornar la primera
        if ($indiceProximaEsperada > 0) {
            $marcaFaltante = $marcasEsperadasArray[$indiceProximaEsperada - 1];
            return [
                'marca_faltante' => $marcaFaltante,
                'hora_esperada' => $this->segundosAHora($marcasEsperadasOrdenadas[$marcaFaltante]),
                'nombre_marca' => $this->nombreMarca($marcaFaltante)
            ];
        }
        
        // Ahora usar asignación por PROXIMIDAD (no secuencial)
        // Cada marca registrada se asigna a la marca esperada más cercana que esté disponible
        $marcasAsignadas = [];
        
        foreach ($horasEnSegundos as $horaRegistrada) {
            $mejorMarca = null;
            $mejorDistancia = PHP_INT_MAX;
            
            // Encontrar la marca esperada más cercana que no haya sido asignada
            foreach ($marcasEsperadasArray as $marcaEsperada) {
                // Si ya fue asignada, saltarla
                if (isset($marcasAsignadas[$marcaEsperada])) {
                    continue;
                }
                
                $horaEsperada = $marcasEsperadasOrdenadas[$marcaEsperada];
                $distancia = abs($horaRegistrada - $horaEsperada);
                
                // Debe estar dentro de 2 horas
                if ($distancia <= 7200) {
                    if ($distancia < $mejorDistancia) {
                        $mejorMarca = $marcaEsperada;
                        $mejorDistancia = $distancia;
                    }
                }
            }
            
            // Si encontró una marca cercana, asignarla
            if ($mejorMarca !== null) {
                $marcasAsignadas[$mejorMarca] = true;
            }
        }
        
        // Ver cuál marca esperada no fue asignada
        foreach ($marcasEsperadasArray as $marcaEsperada) {
            if (!isset($marcasAsignadas[$marcaEsperada])) {
                return [
                    'marca_faltante' => $marcaEsperada,
                    'hora_esperada' => $this->segundosAHora($marcasEsperadasOrdenadas[$marcaEsperada]),
                    'nombre_marca' => $this->nombreMarca($marcaEsperada)
                ];
            }
        }

        return null;
    }

    /**
     * Ordenar horas cronológicamente
     */
    private function ordenarHorasCronologicamente($horas)
    {
        // Convertir a segundos con índice
        $horasConIndice = [];
        foreach ($horas as $hora) {
            if ($hora) {
                $partes = explode(':', $hora);
                $segundos = intval($partes[0]) * 3600 + intval($partes[1]) * 60 + intval($partes[2]);
                $horasConIndice[] = [
                    'hora' => $hora,
                    'segundos' => $segundos
                ];
            }
        }

        // Ordenar por segundos
        usort($horasConIndice, function($a, $b) {
            return $a['segundos'] - $b['segundos'];
        });

        // Retornar solo las horas ordenadas
        return array_column($horasConIndice, 'hora');
    }

    /**
     * Convertir hora HH:MM:SS a segundos
     */
    private function horaASegundos($hora)
    {
        if (!$hora) return null;
        $partes = explode(':', $hora);
        return intval($partes[0]) * 3600 + intval($partes[1]) * 60 + intval($partes[2]);
    }

    /**
     * Convertir segundos a hora HH:MM:SS
     */
    private function segundosAHora($segundos)
    {
        $horas = intdiv($segundos, 3600);
        $minutos = intdiv($segundos % 3600, 60);
        $secs = $segundos % 60;
        return sprintf('%02d:%02d:%02d', $horas, $minutos, $secs);
    }

    /**
     * Guardar hora extra agregada manualmente
     */
    public function guardarHoraExtraAgregada(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'codigo_persona' => 'required|exists:personal,codigo_persona',
                'id_reporte' => 'required|exists:reportes_personal,id',
                'fecha' => 'required|date_format:Y-m-d',
                'horas_agregadas' => 'required|numeric|min:0|max:24',
                'novedad' => 'nullable|string|max:1000',
            ]);

            $horaExtraAgregada = \App\Models\HoraExtraAgregada::updateOrCreate(
                [
                    'codigo_persona' => $request->codigo_persona,
                    'id_reporte' => $request->id_reporte,
                    'fecha' => $request->fecha,
                ],
                [
                    'horas_agregadas' => $request->horas_agregadas,
                    'novedad' => $request->novedad,
                    'usuario_id' => auth()->id(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Hora extra agregada correctamente',
                'data' => $horaExtraAgregada
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la hora extra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todas las personas disponibles (para búsqueda en modal)
     */
    public function obtenerTodasLasPersonas(): JsonResponse
    {
        try {
            $personas = Personal::select('codigo_persona', 'nombre_persona', 'id_rol')
                ->orderBy('nombre_persona', 'asc')
                ->get()
                ->map(function($persona) {
                    return [
                        'codigo_persona' => $persona->codigo_persona,
                        'nombre' => $persona->nombre_persona,
                        'id_rol' => $persona->id_rol,
                        'nombre_persona' => $persona->nombre_persona
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $personas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener personas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener horas extras agregadas para múltiples personas (BATCH)
     * Optimizado para cargar datos de múltiples personas en una sola consulta
     */
    public function obtenerHorasExtrasAgregadasBatch(Request $request): JsonResponse
    {
        try {
            $codigosPersonas = $request->input('codigos_personas', []);
            
            if (empty($codigosPersonas)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            // Consulta única para todas las personas
            $horasExtras = \App\Models\HoraExtraAgregada::whereIn('codigo_persona', $codigosPersonas)
                ->get();

            // Agrupar por persona y fecha
            $agrupadas = [];
            foreach ($horasExtras as $hora) {
                $codigoPersona = $hora->codigo_persona;
                $fechaKey = $hora->fecha instanceof \Carbon\Carbon 
                    ? $hora->fecha->format('Y-m-d')
                    : $hora->fecha;
                
                if (!isset($agrupadas[$codigoPersona])) {
                    $agrupadas[$codigoPersona] = [];
                }
                
                if (!isset($agrupadas[$codigoPersona][$fechaKey])) {
                    $agrupadas[$codigoPersona][$fechaKey] = [];
                }
                
                $agrupadas[$codigoPersona][$fechaKey][] = [
                    'id' => $hora->id,
                    'horas_agregadas' => floatval($hora->horas_agregadas),
                    'novedad' => $hora->novedad,
                    'fecha' => $fechaKey
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $agrupadas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener horas extras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener horas extras agregadas para una persona
     */
    public function obtenerHorasExtrasAgregadas(string $codigoPersona): JsonResponse
    {
        try {
            $horasExtras = \App\Models\HoraExtraAgregada::where('codigo_persona', $codigoPersona)
                ->get();

            // Agrupar por fecha formateada
            $agrupadas = [];
            foreach ($horasExtras as $hora) {
                $fechaKey = $hora->fecha instanceof \Carbon\Carbon 
                    ? $hora->fecha->format('Y-m-d')
                    : $hora->fecha;
                
                if (!isset($agrupadas[$fechaKey])) {
                    $agrupadas[$fechaKey] = [];
                }
                
                $agrupadas[$fechaKey][] = [
                    'id' => $hora->id,
                    'horas_agregadas' => floatval($hora->horas_agregadas),
                    'novedad' => $hora->novedad,
                    'fecha' => $fechaKey
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $agrupadas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener horas extras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular horas trabajadas y horas extras para un registro
     * 
     * Endpoint: POST /asistencia-personal/calcular-horas
     * 
     * @param Request $request con: codigo_persona, dia, horas (array)
     * @return JsonResponse
     */
    public function calcularHoras(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'codigo_persona' => 'required|integer|exists:personal,codigo_persona',
                'dia' => 'required|date_format:Y-m-d',
                'horas' => 'required|array|min:1'
            ]);

            $personal = Personal::where('codigo_persona', $request->codigo_persona)->first();
            if (!$personal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Persona no encontrada'
                ], 404);
            }

            $resultado = $this->calcularHorasTrabajadas(
                $personal,
                $request->horas,
                $request->dia
            );

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular horas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener nombre legible de la marca
     */
    private function nombreMarca($campo)
    {
        $nombres = [
            'entrada_manana' => 'Entrada Mañana',
            'salida_manana' => 'Salida Mañana',
            'entrada_tarde' => 'Entrada Tarde',
            'salida_tarde' => 'Salida Tarde'
        ];
        return $nombres[$campo] ?? $campo;
    }

    /**
     * Calcular horas trabajadas según el rol y cantidad de marcas
     * 
     * Para Rol 21:
     * - Si tiene 4 marcas: calcular como rol normal (entrada mañana-salida mañana + entrada tarde-salida tarde)
     * - Si tiene 2 o 3 marcas: contar solo desde la primera a la última marca
     * 
     * @param Personal $personal
     * @param array $horas (array de horas en formato HH:MM:SS)
     * @param string $dia (formato Y-m-d)
     * @return array ['horas_trabajadas' => 'HH:MM:SS', 'horas_extras' => 'HH:MM:SS', 'metodo' => 'normal|rol21_parcial']
     */
    public function calcularHorasTrabajadas($personal, $horas, $dia)
    {
        // Convertir horas a segundos
        $horasEnSegundos = [];
        foreach ($horas as $hora) {
            if ($hora) {
                $partes = explode(':', $hora);
                $segundos = intval($partes[0]) * 3600 + intval($partes[1]) * 60 + intval($partes[2]);
                $horasEnSegundos[] = $segundos;
            }
        }
        
        if (empty($horasEnSegundos)) {
            return [
                'horas_trabajadas' => '00:00:00',
                'horas_extras' => '00:00:00',
                'metodo' => 'sin_marcas'
            ];
        }

        // Determinar si es sábado
        $fechaObj = \DateTime::createFromFormat('Y-m-d', $dia);
        $esSabado = $fechaObj ? $fechaObj->format('w') == 6 : false;

        $esRol21 = $personal && $personal->id_rol == 21;
        $cantidadMarcas = count($horasEnSegundos);

        // Obtener horario del rol si existe
        $horario = $personal ? HorarioPorRol::where('id_rol', $personal->id_rol)->first() : null;
        
        // Para rol 21: si tiene menos de 4 marcas (o menos de 2 en sábado), contar desde primera a última
        if ($esRol21 && !$esSabado && $cantidadMarcas < 4) {
            // Rol 21 con 2 o 3 marcas entre semana: contar desde primera a última
            $primeraHora = min($horasEnSegundos);
            $ultimaHora = max($horasEnSegundos);
            $horasTrabajadas = $ultimaHora - $primeraHora;
            
            return [
                'horas_trabajadas' => $this->segundosAHora($horasTrabajadas),
                'horas_extras' => '00:00:00',
                'metodo' => 'rol21_parcial',
                'descripcion' => "Rol 21 con {$cantidadMarcas} marcas: calculado desde {$horas[array_key_first($horas)]} a {$horas[array_key_last($horas)]}"
            ];
        }

        // Lógica normal: calcular bloques mañana y tarde
        sort($horasEnSegundos);
        
        $horasTrabajadas = 0;
        $metodoCálculo = 'normal';

        if ($esSabado) {
            // Sábado: 2 marcas (entrada - salida)
            if ($cantidadMarcas >= 2) {
                $entrada = $horasEnSegundos[0];
                $salida = $horasEnSegundos[1];
                $horasTrabajadas = $salida - $entrada;
            }
        } else {
            // Entre semana
            if ($cantidadMarcas >= 4) {
                // 4 marcas: entrada_mañana, salida_mañana, entrada_tarde, salida_tarde
                $bloqueManiana = $horasEnSegundos[1] - $horasEnSegundos[0];
                $bloqueTarde = $horasEnSegundos[3] - $horasEnSegundos[2];
                $horasTrabajadas = $bloqueManiana + $bloqueTarde;
                $metodoCálculo = '4_marcas';
            } elseif ($cantidadMarcas == 3) {
                // 3 marcas: asumir entrada_mañana, salida_mañana, entrada_tarde o salida_tarde
                // Si la tercera marca está lejos de las dos primeras, asumir que es entrada_tarde
                $diferencia1 = $horasEnSegundos[1] - $horasEnSegundos[0];
                $diferencia2 = $horasEnSegundos[2] - $horasEnSegundos[1];
                
                // Si la segunda diferencia es grande (> 1 hora), es entrada_tarde
                if ($diferencia2 > 3600) {
                    // Entrada mañana - Salida mañana - Entrada tarde (falta salida tarde)
                    $horasTrabajadas = $diferencia1; // Solo contar mañana
                    $metodoCálculo = '3_marcas_falta_salida_tarde';
                } else {
                    // Contar solo entrada a última marca
                    $horasTrabajadas = $horasEnSegundos[2] - $horasEnSegundos[0];
                    $metodoCálculo = '3_marcas_continuo';
                }
            } elseif ($cantidadMarcas == 2) {
                // 2 marcas: contar desde primera a última
                $horasTrabajadas = $horasEnSegundos[1] - $horasEnSegundos[0];
                $metodoCálculo = '2_marcas_continuo';
            } elseif ($cantidadMarcas == 1) {
                // 1 marca: no se puede calcular
                $horasTrabajadas = 0;
                $metodoCálculo = '1_marca_incompleta';
            }
        }

        // Calcular horas extras (si aplica)
        $horasExtras = 0;
        $horasNormales = $esSabado ? 4 : 8; // Jornada normal en horas
        $segundosNormales = $horasNormales * 3600;
        
        if ($horasTrabajadas > $segundosNormales) {
            $horasExtras = $horasTrabajadas - $segundosNormales;
        }

        return [
            'horas_trabajadas' => $this->segundosAHora($horasTrabajadas),
            'horas_extras' => $this->segundosAHora($horasExtras),
            'metodo' => $metodoCálculo,
            'segundos_trabajados' => $horasTrabajadas,
            'segundos_extras' => $horasExtras
        ];
    }
}

