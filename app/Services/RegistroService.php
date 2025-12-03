<?php

namespace App\Services;

use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;
use App\Events\ProduccionRecordCreated;
use App\Events\PoloRecordCreated;
use App\Events\CorteRecordCreated;
use Illuminate\Http\Request;

class RegistroService extends BaseService
{
    /**
     * Mapeo de secciones a modelos y eventos
     */
    private function getModelConfig($section)
    {
        return match ($section) {
            'produccion' => [
                'model' => RegistroPisoProduccion::class,
                'event' => ProduccionRecordCreated::class,
            ],
            'polos' => [
                'model' => RegistroPisoPolo::class,
                'event' => PoloRecordCreated::class,
            ],
            'corte' => [
                'model' => RegistroPisoCorte::class,
                'event' => CorteRecordCreated::class,
            ],
        };
    }

    /**
     * Validación centralizada para store
     */
    private function getStoreValidationRules()
    {
        return [
            'registros' => 'required|array',
            'registros.*.fecha' => 'required|date',
            'registros.*.modulo' => 'required|string',
            'registros.*.orden_produccion' => 'required|string',
            'registros.*.hora' => 'required|string',
            'registros.*.tiempo_ciclo' => 'required|numeric',
            'registros.*.porcion_tiempo' => 'required|numeric|min:0|max:1',
            'registros.*.cantidad' => 'nullable|integer',
            'registros.*.paradas_programadas' => 'required|string',
            'registros.*.paradas_no_programadas' => 'nullable|string',
            'registros.*.tiempo_parada_no_programada' => 'nullable|numeric',
            'registros.*.numero_operarios' => 'required|integer',
            'registros.*.tiempo_para_programada' => 'nullable|numeric',
            'registros.*.meta' => 'nullable|numeric',
            'registros.*.eficiencia' => 'nullable|numeric',
            'section' => 'required|string|in:produccion,polos,corte',
        ];
    }

    /**
     * Crear múltiples registros
     */
    public function store(Request $request)
    {
        $startTime = microtime(true);

        $this->log('RegistroService::store INICIADO', [
            'section' => $request->section,
            'registros_count' => count($request->registros ?? [])
        ]);

        // Validación
        try {
            $request->validate($this->getStoreValidationRules());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->logError('Validación falló en store', ['errors' => $e->errors()]);
            return [
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ];
        }

        try {
            $config = $this->getModelConfig($request->section);
            $model = $config['model'];
            $event = $config['event'];

            $createdRecords = [];

            foreach ($request->registros as $registroData) {
                $paradaProgramada = strtoupper(trim($registroData['paradas_programadas'] ?? ''));
                $tiempo_para_programada = match ($paradaProgramada) {
                    'DESAYUNO',
                    'MEDIA TARDE' => 900,
                    'NINGUNA' => 0,
                    default => 0
                };

                $porcion_tiempo = floatval($registroData['porcion_tiempo'] ?? 0);
                $numero_operarios = floatval($registroData['numero_operarios'] ?? 0);
                $tiempo_parada_no_programada = floatval($registroData['tiempo_parada_no_programada'] ?? 0);
                $tiempo_ciclo = floatval($registroData['tiempo_ciclo'] ?? 0);
                $cantidad = floatval($registroData['cantidad'] ?? 0);

                $tiempo_disponible = (3600 * $porcion_tiempo * $numero_operarios)
                                    - $tiempo_parada_no_programada
                                    - $tiempo_para_programada;
                $tiempo_disponible = max(0, $tiempo_disponible);

                $meta = $tiempo_ciclo > 0 ? ($tiempo_disponible / $tiempo_ciclo) * 0.9 : 0;
                $eficiencia = $meta > 0 ? ($cantidad / $meta) : 0;

                $record = $model::create([
                    'fecha' => $registroData['fecha'],
                    'modulo' => $registroData['modulo'],
                    'orden_produccion' => $registroData['orden_produccion'],
                    'hora' => $registroData['hora'],
                    'tiempo_ciclo' => $registroData['tiempo_ciclo'],
                    'porcion_tiempo' => $registroData['porcion_tiempo'],
                    'cantidad' => $registroData['cantidad'] ?? 0,
                    'paradas_programadas' => $registroData['paradas_programadas'],
                    'paradas_no_programadas' => $registroData['paradas_no_programadas'] ?? null,
                    'tiempo_parada_no_programada' => $registroData['tiempo_parada_no_programada'] ?? null,
                    'numero_operarios' => $registroData['numero_operarios'],
                    'tiempo_para_programada' => $tiempo_para_programada,
                    'tiempo_disponible' => $tiempo_disponible,
                    'meta' => $meta,
                    'eficiencia' => $eficiencia,
                ]);

                $createdRecords[] = $record;

                // Emitir evento (no-blocking)
                try {
                    broadcast(new $event($record));
                } catch (\Exception $broadcastError) {
                    $this->logWarning('Error al emitir evento', [
                        'section' => $request->section,
                        'error' => $broadcastError->getMessage()
                    ]);
                }
            }

            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('RegistroService::store completado', [
                'section' => $request->section,
                'registros_creados' => count($createdRecords),
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'message' => 'Registros guardados correctamente.',
                'registros' => $createdRecords,
                'section' => $request->section,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error en RegistroService::store', [
                'error' => $e->getMessage(),
                'section' => $request->section
            ]);

            return [
                'success' => false,
                'message' => 'Error al guardar los registros: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar un registro
     */
    public function update(Request $request, $id)
    {
        $startTime = microtime(true);

        $this->log('RegistroService::update INICIADO', [
            'registro_id' => $id,
            'section' => $request->section
        ]);

        try {
            $request->validate(['section' => 'required|string|in:produccion,polos,corte']);

            $config = $this->getModelConfig($request->section);
            $model = $config['model'];
            $event = $config['event'];

            $registro = $model::findOrFail($id);

            // Validación de campos a actualizar
            $validated = $request->validate([
                'fecha' => 'sometimes|date',
                'modulo' => 'sometimes|string',
                'orden_produccion' => 'sometimes|string',
                'hora' => 'sometimes|string',
                'hora_id' => 'sometimes|integer|exists:horas,id',
                'operario_id' => 'sometimes|integer|exists:users,id',
                'maquina_id' => 'sometimes|integer|exists:maquinas,id',
                'tela_id' => 'sometimes|integer|exists:telas,id',
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

            $registro->update($validated);

            // Emitir evento
            try {
                if ($request->section === 'corte') {
                    $registro->load(['hora', 'operario', 'maquina', 'tela']);
                }
                broadcast(new $event($registro));
            } catch (\Exception $e) {
                $this->logWarning('Error al emitir evento de actualización', [
                    'error' => $e->getMessage()
                ]);
            }

            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('RegistroService::update completado', [
                'registro_id' => $id,
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'message' => 'Registro actualizado correctamente.',
                'data' => $registro->toArray(),
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error en RegistroService::update', [
                'error' => $e->getMessage(),
                'registro_id' => $id
            ]);

            return [
                'success' => false,
                'message' => 'Error al actualizar el registro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar un registro
     */
    public function destroy($id, $section)
    {
        $this->log('RegistroService::destroy INICIADO', [
            'registro_id' => $id,
            'section' => $section
        ]);

        try {
            $config = $this->getModelConfig($section);
            $model = $config['model'];
            $event = $config['event'];

            $registro = $model::find($id);

            if (!$registro) {
                $this->log('Registro ya fue eliminado', ['registro_id' => $id]);
                return [
                    'success' => true,
                    'message' => 'El registro ya fue eliminado.',
                    'id' => $id,
                    'already_deleted' => true
                ];
            }

            $registroId = $registro->id;
            $registro->delete();

            // Emitir evento de eliminación
            try {
                broadcast(new $event((object)['id' => $registroId, 'deleted' => true]));
            } catch (\Exception $e) {
                $this->logWarning('Error al emitir evento de eliminación', [
                    'error' => $e->getMessage()
                ]);
            }

            $this->log('RegistroService::destroy completado', ['registro_id' => $registroId]);

            return [
                'success' => true,
                'message' => 'Registro eliminado correctamente.',
                'id' => $registroId
            ];
        } catch (\Exception $e) {
            $this->logError('Error en RegistroService::destroy', [
                'error' => $e->getMessage(),
                'registro_id' => $id
            ]);

            return [
                'success' => false,
                'message' => 'Error al eliminar el registro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Duplicar un registro
     */
    public function duplicate($id, $section)
    {
        $startTime = microtime(true);

        $this->log('RegistroService::duplicate INICIADO', [
            'registro_id' => $id,
            'section' => $section
        ]);

        try {
            $config = $this->getModelConfig($section);
            $model = $config['model'];
            $event = $config['event'];

            $relaciones = $section === 'corte' ? ['hora', 'operario', 'maquina', 'tela'] : [];
            $registroOriginal = $relaciones
                ? $model::with($relaciones)->findOrFail($id)
                : $model::findOrFail($id);

            // Crear array con datos del original
            $datosNuevos = $registroOriginal->toArray();

            // Remover campos que no se deben duplicar
            unset($datosNuevos['id']);
            unset($datosNuevos['created_at']);
            unset($datosNuevos['updated_at']);

            // Remover relaciones
            foreach ($relaciones as $rel) {
                unset($datosNuevos[$rel]);
            }

            // Crear registro duplicado
            $registroDuplicado = $model::create($datosNuevos);

            // Cargar relaciones si es necesario
            if ($relaciones) {
                $registroDuplicado->load($relaciones);
            }

            // Emitir evento
            try {
                broadcast(new $event($registroDuplicado));
            } catch (\Exception $e) {
                $this->logWarning('Error al emitir evento de duplicación', [
                    'error' => $e->getMessage()
                ]);
            }

            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('RegistroService::duplicate completado', [
                'original_id' => $id,
                'duplicado_id' => $registroDuplicado->id,
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'message' => 'Registro duplicado correctamente.',
                'registro' => $registroDuplicado,
                'section' => $section,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error en RegistroService::duplicate', [
                'error' => $e->getMessage(),
                'registro_id' => $id
            ]);

            return [
                'success' => false,
                'message' => 'Error al duplicar el registro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener todos los registros de una sección
     */
    public function getAll($section)
    {
        $this->log('RegistroService::getAll', ['section' => $section]);

        try {
            $config = $this->getModelConfig($section);
            $model = $config['model'];

            $registros = $model::all();

            $this->log('Registros obtenidos', [
                'section' => $section,
                'total' => $registros->count()
            ]);

            return [
                'success' => true,
                'registros' => $registros,
                'total' => $registros->count()
            ];
        } catch (\Exception $e) {
            $this->logError('Error en RegistroService::getAll', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error al obtener registros: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener un registro por ID
     */
    public function getById($id, $section)
    {
        $this->log('RegistroService::getById', [
            'registro_id' => $id,
            'section' => $section
        ]);

        try {
            $config = $this->getModelConfig($section);
            $model = $config['model'];

            $relaciones = $section === 'corte' ? ['hora', 'operario', 'maquina', 'tela'] : [];
            $registro = $relaciones
                ? $model::with($relaciones)->findOrFail($id)
                : $model::findOrFail($id);

            return [
                'success' => true,
                'registro' => $registro
            ];
        } catch (\Exception $e) {
            $this->logError('Error en RegistroService::getById', [
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
