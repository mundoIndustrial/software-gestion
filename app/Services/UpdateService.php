<?php

namespace App\Services;

use App\Models\RegistroPisoProduccion;
use App\Models\RegistroPisoPolo;
use App\Models\RegistroPisoCorte;
use App\Events\ProduccionRecordCreated;
use App\Events\PoloRecordCreated;
use App\Events\CorteRecordCreated;
use Illuminate\Http\Request;

class UpdateService extends BaseService
{
    /**
     * Reglas de validación para update
     */
    private function getUpdateValidationRules()
    {
        return [
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
        ];
    }

    /**
     * Campos que solo requieren relaciones externas
     */
    private function getExternalRelationFields()
    {
        return ['hora_id', 'operario_id', 'maquina_id', 'tela_id'];
    }

    /**
     * Campos que requieren recálculo
     */
    private function getRecalculationFields()
    {
        return [
            'porcion_tiempo',
            'numero_operarios',
            'tiempo_parada_no_programada',
            'tiempo_para_programada',
            'tiempo_ciclo',
            'cantidad',
            'paradas_programadas',
            'paradas_no_programadas',
            'tipo_extendido',
            'numero_capas',
            'tiempo_trazado'
        ];
    }

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
     * Actualizar un registro con recálculos si es necesario
     */
    public function update(Request $request, $id)
    {
        $startTime = microtime(true);

        $this->log('UpdateService::update INICIADO', [
            'registro_id' => $id,
            'section' => $request->section
        ]);

        try {
            // Validación
            $request->validate(['section' => 'required|string|in:produccion,polos,corte']);

            $config = $this->getModelConfig($request->section);
            $model = $config['model'];
            $event = $config['event'];

            $registro = $model::findOrFail($id);

            // Validar datos a actualizar
            $validated = $request->validate($this->getUpdateValidationRules());

            // Verificar si solo se actualizan campos de relaciones
            $externalFields = $this->getExternalRelationFields();
            $soloRelacionesExternas = true;

            foreach ($validated as $field => $value) {
                if (!in_array($field, $externalFields)) {
                    $soloRelacionesExternas = false;
                    break;
                }
            }

            // RÁPIDO: Solo relaciones externas
            if ($soloRelacionesExternas) {
                return $this->handleExternalRelationsOnly($registro, $validated, $request->section, $event, $startTime);
            }

            // Actualizar básico
            $registro->update($validated);

            // Verificar si necesita recálculo
            $shouldRecalculate = $this->shouldRecalculate($validated);

            if ($shouldRecalculate) {
                return $this->handleRecalculation($registro, $request->section, $event, $startTime);
            } else {
                return $this->handleBroadcastOnly($registro, $request->section, $event, $startTime);
            }
        } catch (\Exception $e) {
            $this->logError('Error en UpdateService::update', [
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
     * Manejar actualización de solo relaciones externas
     */
    private function handleExternalRelationsOnly($registro, $validated, $section, $event, $startTime)
    {
        $this->log('Actualizando solo relaciones externas');

        $registro->update($validated);

        // Emitir evento
        if ($section === 'corte') {
            $registro->load(['hora', 'operario', 'maquina', 'tela']);
        }

        try {
            broadcast(new $event($registro));
        } catch (\Exception $e) {
            $this->logWarning('Error al emitir evento', ['error' => $e->getMessage()]);
        }

        $duration = (microtime(true) - $startTime) * 1000;

        return [
            'success' => true,
            'message' => 'Registro actualizado correctamente.',
            'data' => $registro->toArray(),
            'duration_ms' => round($duration, 2),
            'optimized' => true
        ];
    }

    /**
     * Verificar si necesita recálculo
     */
    private function shouldRecalculate($validated)
    {
        $fieldsToRecalculate = $this->getRecalculationFields();

        foreach ($fieldsToRecalculate as $field) {
            if (array_key_exists($field, $validated)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Manejar recálculo de meta y eficiencia
     */
    private function handleRecalculation($registro, $section, $event, $startTime)
    {
        $this->log('Recalculando meta y eficiencia');

        if ($section === 'corte') {
            $calculations = $this->recalculateCorte($registro);
        } else {
            $calculations = $this->recalculateProduccionPolos($registro);
        }

        $registro->tiempo_disponible = $calculations['tiempo_disponible'];
        $registro->meta = $calculations['meta'];
        $registro->eficiencia = $calculations['eficiencia'];
        $registro->save();

        // Emitir evento
        if ($section === 'corte') {
            $registro->load(['hora', 'operario', 'maquina', 'tela']);
        }

        try {
            broadcast(new $event($registro));
        } catch (\Exception $e) {
            $this->logWarning('Error al emitir evento', ['error' => $e->getMessage()]);
        }

        $duration = (microtime(true) - $startTime) * 1000;

        $this->log('UpdateService::update con recálculo completado', [
            'duration_ms' => round($duration, 2)
        ]);

        return [
            'success' => true,
            'message' => 'Registro actualizado correctamente.',
            'data' => $section === 'corte' ? $registro->toArray() : $calculations,
            'duration_ms' => round($duration, 2),
            'recalculated' => true
        ];
    }

    /**
     * Recalcular para CORTE (sin numero_operarios)
     */
    private function recalculateCorte($registro)
    {
        $tiempo_para_programada = match ($registro->paradas_programadas) {
            'DESAYUNO' => 900,
            'MEDIA TARDE' => 900,
            'NINGUNA' => 0,
            default => 0
        };

        $tiempo_extendido = match ($registro->tipo_extendido) {
            'Trazo Largo' => 40 * ($registro->numero_capas ?? 0),
            'Trazo Corto' => 25 * ($registro->numero_capas ?? 0),
            'Ninguno' => 0,
            default => 0
        };

        $tiempo_disponible = (3600 * $registro->porcion_tiempo) -
                           ($tiempo_para_programada +
                           ($registro->tiempo_parada_no_programada ?? 0) +
                           $tiempo_extendido +
                           ($registro->tiempo_trazado ?? 0));

        $tiempo_disponible = max(0, $tiempo_disponible);

        $meta = $registro->tiempo_ciclo > 0 ? $tiempo_disponible / $registro->tiempo_ciclo : 0;
        $eficiencia = $meta > 0 ? ($registro->cantidad / $meta) : 0;

        return compact('tiempo_disponible', 'meta', 'eficiencia');
    }

    /**
     * Recalcular para PRODUCCIÓN y POLOS (con numero_operarios)
     */
    private function recalculateProduccionPolos($registro)
    {
        $tiempo_para_programada = match ($registro->paradas_programadas) {
            'DESAYUNO' => 900,
            'MEDIA TARDE' => 900,
            'NINGUNA' => 0,
            default => 0
        };

        $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                           ($registro->tiempo_parada_no_programada ?? 0) -
                           $tiempo_para_programada;

        $tiempo_disponible = max(0, $tiempo_disponible);

        $meta = $registro->tiempo_ciclo > 0 ? ($tiempo_disponible / $registro->tiempo_ciclo) * 0.9 : 0;
        $eficiencia = $meta > 0 ? ($registro->cantidad / $meta) : 0;

        return compact('tiempo_disponible', 'meta', 'eficiencia');
    }

    /**
     * Manejar broadcast sin recálculo
     */
    private function handleBroadcastOnly($registro, $section, $event, $startTime)
    {
        $this->log('Actualizando sin recálculo');

        // Emitir evento
        if ($section === 'corte') {
            $registro->load(['hora', 'operario', 'maquina', 'tela']);
        }

        try {
            broadcast(new $event($registro));
        } catch (\Exception $e) {
            $this->logWarning('Error al emitir evento', ['error' => $e->getMessage()]);
        }

        $duration = (microtime(true) - $startTime) * 1000;

        return [
            'success' => true,
            'message' => 'Registro actualizado correctamente.',
            'data' => $section === 'corte' ? $registro->toArray() : null,
            'duration_ms' => round($duration, 2),
            'broadcasted' => true
        ];
    }
}
