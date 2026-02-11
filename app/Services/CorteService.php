<?php

namespace App\Services;

use App\Models\RegistroPisoCorte;
use App\Models\TiempoCiclo;
use App\Events\CorteRecordCreated;
use Illuminate\Http\Request;

class CorteService extends BaseService
{
    private HoraService $horaService;

    public function __construct(HoraService $horaService)
    {
        $this->horaService = $horaService;
    }

    /**
     * Validación centralizada para storeCorte
     */
    private function getValidationRules()
    {
        return [
            'fecha' => 'required|date',
            'orden_produccion' => 'required|string',
            'tela_id' => 'required|exists:telas,id',
            'hora_id' => 'required|exists:horas,id',
            'operario_id' => 'required|exists:users,id',
            'actividad' => 'required|string',
            'maquina_id' => 'required|exists:maquinas,id',
            'tiempo_ciclo' => 'required|numeric|min:0.01',
            'porcion_tiempo' => 'required|numeric|min:0|max:1',
            'cantidad_producida' => 'required|integer|min:0',
            'paradas_programadas' => 'required|string',
            'paradas_no_programadas' => 'nullable|string',
            'tiempo_parada_no_programada' => 'nullable|numeric|min:0',
            'tipo_extendido' => 'required|string',
            'numero_capas' => 'required|integer|min:0',
            'trazado' => 'required|string',
            'tiempo_trazado' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Mensajes de validación personalizados
     */
    private function getValidationMessages()
    {
        return [
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida (formato: YYYY-MM-DD).',
            'orden_produccion.required' => 'La orden de producción es obligatoria.',
            'tela_id.required' => 'Debe seleccionar una tela válida.',
            'tela_id.exists' => 'La tela seleccionada no existe en el sistema. Intenta crear una nueva.',
            'hora_id.required' => 'Debe seleccionar una hora válida.',
            'hora_id.exists' => 'La hora seleccionada no existe en el sistema.',
            'operario_id.required' => 'Debe seleccionar un operario válido.',
            'operario_id.exists' => 'El operario seleccionado no existe en el sistema.',
            'actividad.required' => 'La actividad es obligatoria.',
            'maquina_id.required' => 'Debe seleccionar una máquina válida.',
            'maquina_id.exists' => 'La máquina seleccionada no existe en el sistema.',
            'tiempo_ciclo.required' => 'El tiempo de ciclo es obligatorio.',
            'tiempo_ciclo.numeric' => 'El tiempo de ciclo debe ser un número válido.',
            'tiempo_ciclo.min' => 'El tiempo de ciclo debe ser mayor a 0.',
            'porcion_tiempo.required' => 'La porción de tiempo es obligatoria.',
            'porcion_tiempo.numeric' => 'La porción de tiempo debe ser un número válido.',
            'porcion_tiempo.min' => 'La porción de tiempo no puede ser negativa.',
            'porcion_tiempo.max' => 'La porción de tiempo no puede ser mayor a 1 (100%).',
            'cantidad_producida.required' => 'La cantidad producida es obligatoria.',
            'cantidad_producida.integer' => 'La cantidad producida debe ser un número entero.',
            'cantidad_producida.min' => 'La cantidad producida no puede ser negativa.',
            'paradas_programadas.required' => 'Debe seleccionar un tipo de parada programada.',
            'tiempo_parada_no_programada.numeric' => 'El tiempo de parada no programada debe ser un número válido.',
            'tiempo_parada_no_programada.min' => 'El tiempo de parada no programada no puede ser negativo.',
            'tipo_extendido.required' => 'Debe seleccionar un tipo de extendido.',
            'numero_capas.required' => 'El número de capas es obligatorio.',
            'numero_capas.integer' => 'El número de capas debe ser un número entero.',
            'numero_capas.min' => 'El número de capas no puede ser negativo.',
            'trazado.required' => 'Debe seleccionar un tipo de trazado.',
            'tiempo_trazado.numeric' => 'El tiempo de trazado debe ser un número válido.',
            'tiempo_trazado.min' => 'El tiempo de trazado no puede ser negativo.',
        ];
    }

    /**
     * Calcular tiempo para programada
     */
    private function calcularTiempoParaProgramada($paradaString)
    {
        if ($paradaString === 'DESAYUNO' || $paradaString === 'MEDIA TARDE') {
            return 900; // 15 minutes in seconds
        } elseif ($paradaString === 'NINGUNA') {
            return 0;
        }
        return 0;
    }

    /**
     * Calcular tiempo extendido basado en tipo y capas
     */
    private function calcularTiempoExtendido($tipoExtendido, $numeroCapas)
    {
        $tipo_lower = strtolower($tipoExtendido);

        if (str_contains($tipo_lower, 'largo')) {
            return 40 * $numeroCapas;
        } elseif (str_contains($tipo_lower, 'corto')) {
            return 25 * $numeroCapas;
        }

        return 0;
    }

    /**
     * Calcular meta y eficiencia para corte
     */
    private function calcularMetaYEficiencia($actividad, $tiempoCiclo, $tiempoDisponible, $cantidadProducida)
    {
        $actividad_lower = strtolower($actividad);

        if (str_contains($actividad_lower, 'extender') || str_contains($actividad_lower, 'trazar')) {
            // Para actividades de extender/trazar, meta es cantidad y eficiencia es 1
            $meta = $cantidadProducida;
            $eficiencia = 1;
        } else {
            // Cálculo normal
            $meta = $tiempoCiclo > 0 ? $tiempoDisponible / $tiempoCiclo : 0;
            $eficiencia = $meta == 0 ? 0 : $cantidadProducida / $meta;
        }

        return compact('meta', 'eficiencia');
    }

    /**
     * Almacenar nuevo registro de corte
     */
    public function store(Request $request)
    {
        $startTime = microtime(true);

        $this->log(' CorteService::store INICIADO', [
            'all_data' => $request->all(),
            'method' => $request->method()
        ]);

        // Validación
        try {
            $request->validate($this->getValidationRules(), $this->getValidationMessages());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = reset($errors)[0] ?? 'Error de validación';

            $this->logError('Validación falló', [
                'error' => $firstError,
                'all_errors' => $errors
            ]);

            return [
                'success' => false,
                'message' => $firstError,
                'errors' => $errors,
                'error_type' => 'validation'
            ];
        }

        try {
            // Verificar/crear tiempo de ciclo
            $tiempoCicloResult = $this->horaService->storeOrUpdateTiempoCiclo(
                $request->tela_id,
                $request->maquina_id,
                $request->tiempo_ciclo
            );

            if (!$tiempoCicloResult['success']) {
                $this->logWarning('No se pudo guardar tiempo de ciclo', $tiempoCicloResult);
            }

            // Calcular tiempos
            $tiempo_para_programada = $this->calcularTiempoParaProgramada($request->paradas_programadas);
            $tiempo_extendido = $this->calcularTiempoExtendido($request->tipo_extendido, $request->numero_capas);

            $tiempo_disponible = (3600 * $request->porcion_tiempo) -
                               $tiempo_para_programada -
                               ($request->tiempo_parada_no_programada ?? 0) -
                               $tiempo_extendido -
                               ($request->tiempo_trazado ?? 0);

            $tiempo_disponible = max(0, $tiempo_disponible);

            // Calcular meta y eficiencia
            $calculos = $this->calcularMetaYEficiencia(
                $request->actividad,
                $request->tiempo_ciclo,
                $tiempo_disponible,
                $request->cantidad_producida
            );

            $this->log('Corte - Calculando valores', [
                'tiempo_disponible' => $tiempo_disponible,
                'meta' => $calculos['meta'],
                'eficiencia' => $calculos['eficiencia'],
                'cantidad_producida' => $request->cantidad_producida,
                'tiempo_ciclo' => $request->tiempo_ciclo,
                'actividad' => $request->actividad
            ]);

            // Crear registro
            $registro = RegistroPisoCorte::create([
                'fecha' => $request->fecha,
                'orden_produccion' => $request->orden_produccion,
                'hora_id' => $request->hora_id,
                'operario_id' => $request->operario_id,
                'maquina_id' => $request->maquina_id,
                'porcion_tiempo' => $request->porcion_tiempo,
                'cantidad' => $request->cantidad_producida,
                'tiempo_ciclo' => $request->tiempo_ciclo,
                'paradas_programadas' => $request->paradas_programadas,
                'tiempo_para_programada' => $tiempo_para_programada,
                'paradas_no_programadas' => $request->paradas_no_programadas,
                'tiempo_parada_no_programada' => $request->tiempo_parada_no_programada ?? null,
                'tipo_extendido' => $request->tipo_extendido,
                'numero_capas' => $request->numero_capas,
                'tiempo_extendido' => $tiempo_extendido,
                'trazado' => $request->trazado,
                'tiempo_trazado' => $request->tiempo_trazado,
                'actividad' => $request->actividad,
                'tela_id' => $request->tela_id,
                'tiempo_disponible' => $tiempo_disponible,
                'meta' => $calculos['meta'],
                'eficiencia' => $calculos['eficiencia'],
            ]);

            $this->log('Corte - Registro guardado', [
                'registro_id' => $registro->id,
                'tiempo_disponible_guardado' => $registro->tiempo_disponible,
                'meta_guardada' => $registro->meta,
                'eficiencia_guardada' => $registro->eficiencia,
            ]);

            // Cargar relaciones para broadcasting
            $registro->load(['hora', 'operario', 'maquina', 'tela']);

            // Emitir evento (no-blocking)
            try {
                broadcast(new CorteRecordCreated($registro));
            } catch (\Exception $broadcastError) {
                $this->logWarning('Error al emitir evento de corte', [
                    'error' => $broadcastError->getMessage()
                ]);
            }

            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('CorteService::store completado exitosamente', [
                'registro_id' => $registro->id,
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'message' => 'Registro de piso de corte guardado correctamente.',
                'registro' => $registro,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            $this->logError('Error de base de datos en CorteService::store', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A'
            ]);

            $errorMessage = 'Error al guardar en la base de datos. ';
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                $errorMessage .= 'Este registro ya existe en el sistema.';
            } elseif (str_contains($e->getMessage(), 'FOREIGN KEY constraint failed')) {
                $errorMessage .= 'Uno de los datos referenciados no existe (tela, máquina, operario u hora).';
            } elseif (str_contains($e->getMessage(), 'Column not found')) {
                $errorMessage .= 'Hay un problema con la estructura de la base de datos.';
            } else {
                $errorMessage .= 'Por favor, intenta nuevamente.';
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_type' => 'database',
                'details' => config('app.debug') ? $e->getMessage() : null
            ];
        } catch (\Exception $e) {
            $this->logError('Error general en CorteService::store', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $errorMessage = 'Error al procesar el registro. ';

            if (str_contains($e->getMessage(), 'Call to undefined function')) {
                $errorMessage .= 'Hay un problema con una función del sistema.';
            } elseif (str_contains($e->getMessage(), 'Undefined property')) {
                $errorMessage .= 'Hay un problema con los datos enviados.';
            } elseif (str_contains($e->getMessage(), 'division by zero')) {
                $errorMessage .= 'Error en el cálculo: división por cero. Verifica el tiempo de ciclo.';
            } else {
                $errorMessage .= 'Por favor, contacta al administrador.';
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_type' => 'system',
                'details' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    /**
     * Obtener todos los registros de corte con relaciones
     */
    public function getAll()
    {
        $this->log('Obteniendo todos los registros de corte');

        try {
            $registros = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela'])->get();

            $this->log('Registros de corte obtenidos', ['total' => $registros->count()]);

            return [
                'success' => true,
                'registros' => $registros,
                'total' => $registros->count()
            ];
        } catch (\Exception $e) {
            $this->logError('Error al obtener registros de corte', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error al obtener registros: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener registro de corte por ID
     */
    public function getById($id)
    {
        $this->log('Buscando registro de corte por ID', ['registro_id' => $id]);

        try {
            $registro = RegistroPisoCorte::with(['hora', 'operario', 'maquina', 'tela'])->findOrFail($id);

            $this->log('Registro de corte encontrado', ['registro_id' => $id]);

            return [
                'success' => true,
                'registro' => $registro
            ];
        } catch (\Exception $e) {
            $this->logError('Error al obtener registro de corte', [
                'error' => $e->getMessage(),
                'registro_id' => $id
            ]);

            return [
                'success' => false,
                'message' => 'Registro no encontrado: ' . $e->getMessage()
            ];
        }
    }
}
