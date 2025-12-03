<?php

namespace App\Services;

use App\Models\Hora;
use App\Models\TiempoCiclo;
use Illuminate\Http\Request;

class HoraService extends BaseService
{
    /**
     * Obtener tiempo de ciclo por tela y máquina
     */
    public function getTiempoCiclo($telaId, $maquinaId)
    {
        $startTime = microtime(true);
        $this->log('Buscando tiempo de ciclo', ['tela_id' => $telaId, 'maquina_id' => $maquinaId]);

        try {
            $tiempoCiclo = TiempoCiclo::where('tela_id', $telaId)
                ->where('maquina_id', $maquinaId)
                ->first();

            if ($tiempoCiclo) {
                $duration = (microtime(true) - $startTime) * 1000;
                $this->log('Tiempo de ciclo encontrado', [
                    'tiempo_ciclo' => $tiempoCiclo->tiempo_ciclo,
                    'duration_ms' => round($duration, 2)
                ]);

                return [
                    'success' => true,
                    'tiempo_ciclo' => $tiempoCiclo->tiempo_ciclo,
                    'id' => $tiempoCiclo->id
                ];
            }

            $duration = (microtime(true) - $startTime) * 1000;
            $this->logWarning('Tiempo de ciclo NO encontrado', [
                'tela_id' => $telaId,
                'maquina_id' => $maquinaId,
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => false,
                'message' => 'No se encontró tiempo de ciclo para esta combinación de tela y máquina.'
            ];
        } catch (\Exception $e) {
            $this->logError('Error al obtener tiempo de ciclo', [
                'error' => $e->getMessage(),
                'tela_id' => $telaId,
                'maquina_id' => $maquinaId
            ]);

            return [
                'success' => false,
                'message' => 'Error al obtener tiempo de ciclo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear o buscar hora por valor
     * Implementa manejo de race condition con try/catch
     */
    public function findOrCreate($horaValue)
    {
        $startTime = microtime(true);
        $this->log('findOrCreate hora', ['hora_value' => $horaValue]);

        try {
            // Primero intentar buscar sin lock
            $hora = Hora::where('hora', $horaValue)->first();

            if (!$hora) {
                // Solo crear si no existe - usar try/catch por si hay race condition
                try {
                    $hora = Hora::create(['hora' => $horaValue]);
                    $this->log('Hora creada', ['hora_id' => $hora->id, 'hora_value' => $horaValue]);
                } catch (\Exception $e) {
                    // Si falla por duplicate, buscar nuevamente
                    $hora = Hora::where('hora', $horaValue)->first();
                    if (!$hora) {
                        throw $e;
                    }
                    $this->log('Hora encontrada después de race condition', ['hora_id' => $hora->id]);
                }
            } else {
                $this->log('Hora ya existe', ['hora_id' => $hora->id]);
            }

            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'success' => true,
                'id' => $hora->id,
                'hora' => $hora->hora,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error en findOrCreate hora', [
                'error' => $e->getMessage(),
                'hora_value' => $horaValue
            ]);

            return [
                'success' => false,
                'message' => 'Error al crear/buscar hora: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener todas las horas
     */
    public function getAll()
    {
        $startTime = microtime(true);
        $this->log('Obteniendo todas las horas');

        try {
            $horas = Hora::all();
            $duration = (microtime(true) - $startTime) * 1000;

            $this->log('Horas obtenidas', [
                'total' => $horas->count(),
                'duration_ms' => round($duration, 2)
            ]);

            return [
                'success' => true,
                'horas' => $horas,
                'total' => $horas->count()
            ];
        } catch (\Exception $e) {
            $this->logError('Error al obtener horas', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error al obtener horas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener hora por ID
     */
    public function getById($id)
    {
        $this->log('Buscando hora por ID', ['hora_id' => $id]);

        try {
            $hora = Hora::findOrFail($id);

            $this->log('Hora encontrada', ['hora_id' => $id, 'hora_value' => $hora->hora]);

            return [
                'success' => true,
                'hora' => $hora
            ];
        } catch (\Exception $e) {
            $this->logError('Error al obtener hora por ID', [
                'error' => $e->getMessage(),
                'hora_id' => $id
            ]);

            return [
                'success' => false,
                'message' => 'Hora no encontrada: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Buscar horas por valor (LIKE)
     */
    public function search($query)
    {
        $this->log('Buscando horas', ['query' => $query]);

        try {
            $horas = Hora::where('hora', 'like', "%{$query}%")
                ->get();

            $this->log('Búsqueda completada', ['total' => $horas->count()]);

            return [
                'success' => true,
                'horas' => $horas,
                'total' => $horas->count()
            ];
        } catch (\Exception $e) {
            $this->logError('Error en búsqueda de horas', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Error en búsqueda: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crear o actualizar tiempo de ciclo
     */
    public function storeOrUpdateTiempoCiclo($telaId, $maquinaId, $tiempoCiclo)
    {
        $startTime = microtime(true);
        $this->log('Almacenando tiempo de ciclo', [
            'tela_id' => $telaId,
            'maquina_id' => $maquinaId,
            'tiempo_ciclo' => $tiempoCiclo
        ]);

        try {
            $ciclo = TiempoCiclo::where('tela_id', $telaId)
                ->where('maquina_id', $maquinaId)
                ->first();

            if ($ciclo) {
                $ciclo->update(['tiempo_ciclo' => $tiempoCiclo]);
                $this->log('Tiempo de ciclo actualizado', ['ciclo_id' => $ciclo->id]);
            } else {
                $ciclo = TiempoCiclo::create([
                    'tela_id' => $telaId,
                    'maquina_id' => $maquinaId,
                    'tiempo_ciclo' => $tiempoCiclo,
                ]);
                $this->log('Tiempo de ciclo creado', ['ciclo_id' => $ciclo->id]);
            }

            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'success' => true,
                'ciclo' => $ciclo,
                'duration_ms' => round($duration, 2)
            ];
        } catch (\Exception $e) {
            $this->logError('Error al almacenar tiempo de ciclo', [
                'error' => $e->getMessage(),
                'tela_id' => $telaId,
                'maquina_id' => $maquinaId
            ]);

            return [
                'success' => false,
                'message' => 'Error al almacenar tiempo de ciclo: ' . $e->getMessage()
            ];
        }
    }
}
