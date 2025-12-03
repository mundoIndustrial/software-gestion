<?php

namespace App\Services;

use App\Models\Maquina;
use Illuminate\Http\Request;

/**
 * MaquinaService
 * 
 * Encapsula toda la lógica de máquinas:
 * - CRUD: crear, leer, actualizar, eliminar máquinas
 * - Búsqueda rápida con índice
 * - Find or Create para operaciones atómicas
 * - Validación de duplicados
 * - Race condition handling
 * 
 * @author Refactor Service Layer - Fase 3
 */
class MaquinaService extends BaseService
{
    /**
     * Buscar máquinas por nombre
     * 
     * Búsqueda rápida usando índice MySQL (LIKE desde inicio)
     * Limita a 10 resultados para autocompletar
     * 
     * @param string $query Búsqueda parcial
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search($query)
    {
        $this->log('Buscando máquinas', ['query' => $query]);

        $maquinas = Maquina::where('nombre_maquina', 'like', $query . '%')
            ->select('id', 'nombre_maquina')
            ->limit(10)
            ->get();

        $this->log('Máquinas encontradas', ['count' => $maquinas->count()]);
        return $maquinas;
    }

    /**
     * Crear nueva máquina
     * 
     * Validaciones:
     * - Verifica que no exista nombre duplicado
     * - Normaliza nombre a UPPERCASE
     * 
     * @param \Illuminate\Http\Request $request
     * @return array ['success' => bool, 'message' => string, 'maquina' => Maquina|null]
     */
    public function store(Request $request)
    {
        $this->log('Creando máquina', ['nombre' => $request->nombre_maquina ?? 'N/A']);

        try {
            // Verificar si ya existe la máquina
            $nombreNormalizado = strtoupper($request->nombre_maquina);
            $maquinaExistente = Maquina::where('nombre_maquina', $nombreNormalizado)->first();
            
            if ($maquinaExistente) {
                $this->logWarning('Máquina duplicada', ['nombre' => $nombreNormalizado, 'id' => $maquinaExistente->id]);
                return [
                    'success' => false,
                    'message' => "La máquina \"{$nombreNormalizado}\" ya existe en el sistema.",
                    'error_type' => 'duplicate',
                    'maquina' => $maquinaExistente
                ];
            }

            // Validar entrada
            $request->validate([
                'nombre_maquina' => 'required|string|max:255',
            ]);

            // Crear máquina
            $maquina = Maquina::create([
                'nombre_maquina' => $nombreNormalizado,
            ]);

            $this->log('Máquina creada', ['id' => $maquina->id, 'nombre' => $maquina->nombre_maquina]);

            return [
                'success' => true,
                'message' => 'Máquina creada correctamente.',
                'maquina' => $maquina
            ];
        } catch (\Exception $e) {
            $this->logError('Error al crear máquina', [
                'error' => $e->getMessage(),
                'nombre' => $request->nombre_maquina ?? 'N/A'
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al crear la máquina: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar o crear máquina por nombre
     * 
     * Operación atómica con manejo de race conditions:
     * - Intenta buscar primero
     * - Si no existe, crea con try/catch
     * - Si falla por duplicate, busca nuevamente
     * 
     * @param string $nombre Nombre de la máquina (será normalizado a UPPERCASE)
     * @return array ['id' => int, 'nombre_maquina' => string]
     */
    public function findOrCreate($nombre)
    {
        $startTime = microtime(true);
        $nombreNormalizado = strtoupper($nombre);
        
        $this->log('Buscando/creando máquina', ['nombre' => $nombreNormalizado]);

        // Buscar primero (sin lock para no bloquear)
        $maquina = Maquina::where('nombre_maquina', $nombreNormalizado)->first();

        if (!$maquina) {
            // Crear si no existe - con try/catch por race conditions
            try {
                $maquina = Maquina::create(['nombre_maquina' => $nombreNormalizado]);
                
                $duration = (microtime(true) - $startTime) * 1000;
                $this->log('Máquina creada (findOrCreate)', [
                    'id' => $maquina->id,
                    'nombre' => $nombreNormalizado,
                    'operation_time_ms' => round($duration, 2)
                ]);
            } catch (\Exception $e) {
                // Si falla por duplicate, buscar nuevamente
                $maquina = Maquina::where('nombre_maquina', $nombreNormalizado)->first();
                if (!$maquina) {
                    // Si aún no existe, re-lanzar error
                    $this->logError('Error al crear máquina en findOrCreate', [
                        'nombre' => $nombreNormalizado,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
                
                $this->log('Máquina encontrada después de race condition', [
                    'id' => $maquina->id,
                    'nombre' => $nombreNormalizado
                ]);
            }
        } else {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->log('Máquina encontrada (findOrCreate)', [
                'id' => $maquina->id,
                'nombre' => $nombreNormalizado,
                'operation_time_ms' => round($duration, 2)
            ]);
        }

        return [
            'id' => $maquina->id,
            'nombre_maquina' => $maquina->nombre_maquina
        ];
    }

    /**
     * Obtener todas las máquinas
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        $this->log('Obteniendo todas las máquinas');
        return Maquina::select('id', 'nombre_maquina')->get();
    }

    /**
     * Obtener máquina por ID
     * 
     * @param int $id ID de la máquina
     * @return Maquina|null
     */
    public function getById($id)
    {
        $this->log('Obteniendo máquina por ID', ['id' => $id]);
        return Maquina::findOrFail($id);
    }

    /**
     * Actualizar máquina
     * 
     * @param int $id ID de la máquina
     * @param array $data Datos a actualizar
     * @return array ['success' => bool, 'message' => string, 'maquina' => Maquina|null]
     */
    public function update($id, array $data)
    {
        $this->log('Actualizando máquina', ['id' => $id, 'fields' => array_keys($data)]);

        try {
            $maquina = Maquina::findOrFail($id);
            
            // Normalizar nombre si viene en los datos
            if (isset($data['nombre_maquina'])) {
                $data['nombre_maquina'] = strtoupper($data['nombre_maquina']);
                
                // Verificar duplicado
                $duplicado = Maquina::where('nombre_maquina', $data['nombre_maquina'])
                    ->where('id', '!=', $id)
                    ->first();
                
                if ($duplicado) {
                    $this->logWarning('Nombre duplicado al actualizar máquina', ['id' => $id, 'nombre' => $data['nombre_maquina']]);
                    return [
                        'success' => false,
                        'message' => 'El nombre ya existe para otra máquina',
                        'error_type' => 'duplicate'
                    ];
                }
            }

            $maquina->update($data);
            
            $this->log('Máquina actualizada', ['id' => $id]);

            return [
                'success' => true,
                'message' => 'Máquina actualizada correctamente',
                'maquina' => $maquina
            ];
        } catch (\Exception $e) {
            $this->logError('Error al actualizar máquina', ['id' => $id, 'error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar máquina
     * 
     * @param int $id ID de la máquina
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($id)
    {
        $this->log('Eliminando máquina', ['id' => $id]);

        try {
            $maquina = Maquina::findOrFail($id);
            $maquina->delete();
            
            $this->log('Máquina eliminada', ['id' => $id, 'nombre' => $maquina->nombre_maquina]);

            return [
                'success' => true,
                'message' => 'Máquina eliminada correctamente'
            ];
        } catch (\Exception $e) {
            $this->logError('Error al eliminar máquina', ['id' => $id, 'error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ];
        }
    }
}
