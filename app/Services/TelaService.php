<?php

namespace App\Services;

use App\Models\Tela;
use Illuminate\Http\Request;

/**
 * TelaService
 * 
 * Encapsula toda la lógica de telas:
 * - CRUD: crear, leer, actualizar, eliminar telas
 * - Búsqueda rápida con índice
 * - Find or Create para operaciones atómicas
 * - Validación de duplicados
 * - Race condition handling
 * 
 * @author Refactor Service Layer - Fase 3
 */
class TelaService extends BaseService
{
    /**
     * Buscar telas por nombre
     * 
     * Búsqueda rápida usando índice MySQL (LIKE desde inicio)
     * Limita a 10 resultados para autocompletar
     * 
     * @param string $query Búsqueda parcial
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search($query)
    {
        $this->log('Buscando telas', ['query' => $query]);

        $telas = Tela::where('nombre_tela', 'like', $query . '%')
            ->select('id', 'nombre_tela')
            ->limit(10)
            ->get();

        $this->log('Telas encontradas', ['count' => $telas->count()]);
        return $telas;
    }

    /**
     * Crear nueva tela
     * 
     * Validaciones:
     * - Verifica que no exista nombre duplicado
     * - Normaliza nombre a UPPERCASE
     * 
     * @param \Illuminate\Http\Request $request
     * @return array ['success' => bool, 'message' => string, 'tela' => Tela|null]
     */
    public function store(Request $request)
    {
        $this->log('Creando tela', ['nombre' => $request->nombre_tela ?? 'N/A']);

        try {
            // Verificar si ya existe la tela
            $nombreNormalizado = strtoupper($request->nombre_tela);
            $telaExistente = Tela::where('nombre_tela', $nombreNormalizado)->first();
            
            if ($telaExistente) {
                $this->logWarning('Tela duplicada', ['nombre' => $nombreNormalizado, 'id' => $telaExistente->id]);
                return [
                    'success' => false,
                    'message' => "La tela \"{$nombreNormalizado}\" ya existe en el sistema.",
                    'error_type' => 'duplicate',
                    'tela' => $telaExistente
                ];
            }

            // Validar entrada
            $request->validate([
                'nombre_tela' => 'required|string|max:255',
            ]);

            // Crear tela
            $tela = Tela::create([
                'nombre_tela' => $nombreNormalizado,
            ]);

            $this->log('Tela creada', ['id' => $tela->id, 'nombre' => $tela->nombre_tela]);

            return [
                'success' => true,
                'message' => 'Tela creada correctamente.',
                'tela' => $tela
            ];
        } catch (\Exception $e) {
            $this->logError('Error al crear tela', [
                'error' => $e->getMessage(),
                'nombre' => $request->nombre_tela ?? 'N/A'
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al crear la tela: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar o crear tela por nombre
     * 
     * Operación atómica con manejo de race conditions:
     * - Intenta buscar primero
     * - Si no existe, crea con try/catch
     * - Si falla por duplicate, busca nuevamente
     * 
     * @param string $nombre Nombre de la tela (será normalizado a UPPERCASE)
     * @return array ['id' => int, 'nombre_tela' => string]
     */
    public function findOrCreate($nombre)
    {
        $startTime = microtime(true);
        $nombreNormalizado = strtoupper($nombre);
        
        $this->log('Buscando/creando tela', ['nombre' => $nombreNormalizado]);

        // Buscar primero (sin lock para no bloquear)
        $tela = Tela::where('nombre_tela', $nombreNormalizado)->first();

        if (!$tela) {
            // Crear si no existe - con try/catch por race conditions
            try {
                $tela = Tela::create(['nombre_tela' => $nombreNormalizado]);
                
                $duration = (microtime(true) - $startTime) * 1000;
                $this->log('Tela creada (findOrCreate)', [
                    'id' => $tela->id,
                    'nombre' => $nombreNormalizado,
                    'operation_time_ms' => round($duration, 2)
                ]);
            } catch (\Exception $e) {
                // Si falla por duplicate, buscar nuevamente
                $tela = Tela::where('nombre_tela', $nombreNormalizado)->first();
                if (!$tela) {
                    // Si aún no existe, re-lanzar error
                    $this->logError('Error al crear tela en findOrCreate', [
                        'nombre' => $nombreNormalizado,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
                
                $this->log('Tela encontrada después de race condition', [
                    'id' => $tela->id,
                    'nombre' => $nombreNormalizado
                ]);
            }
        } else {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->log('Tela encontrada (findOrCreate)', [
                'id' => $tela->id,
                'nombre' => $nombreNormalizado,
                'operation_time_ms' => round($duration, 2)
            ]);
        }

        return [
            'id' => $tela->id,
            'nombre_tela' => $tela->nombre_tela
        ];
    }

    /**
     * Obtener todas las telas
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        $this->log('Obteniendo todas las telas');
        return Tela::select('id', 'nombre_tela')->get();
    }

    /**
     * Obtener tela por ID
     * 
     * @param int $id ID de la tela
     * @return Tela|null
     */
    public function getById($id)
    {
        $this->log('Obteniendo tela por ID', ['id' => $id]);
        return Tela::findOrFail($id);
    }

    /**
     * Actualizar tela
     * 
     * @param int $id ID de la tela
     * @param array $data Datos a actualizar
     * @return array ['success' => bool, 'message' => string, 'tela' => Tela|null]
     */
    public function update($id, array $data)
    {
        $this->log('Actualizando tela', ['id' => $id, 'fields' => array_keys($data)]);

        try {
            $tela = Tela::findOrFail($id);
            
            // Normalizar nombre si viene en los datos
            if (isset($data['nombre_tela'])) {
                $data['nombre_tela'] = strtoupper($data['nombre_tela']);
                
                // Verificar duplicado
                $duplicado = Tela::where('nombre_tela', $data['nombre_tela'])
                    ->where('id', '!=', $id)
                    ->first();
                
                if ($duplicado) {
                    $this->logWarning('Nombre duplicado al actualizar tela', ['id' => $id, 'nombre' => $data['nombre_tela']]);
                    return [
                        'success' => false,
                        'message' => 'El nombre ya existe para otra tela',
                        'error_type' => 'duplicate'
                    ];
                }
            }

            $tela->update($data);
            
            $this->log('Tela actualizada', ['id' => $id]);

            return [
                'success' => true,
                'message' => 'Tela actualizada correctamente',
                'tela' => $tela
            ];
        } catch (\Exception $e) {
            $this->logError('Error al actualizar tela', ['id' => $id, 'error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar tela
     * 
     * @param int $id ID de la tela
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($id)
    {
        $this->log('Eliminando tela', ['id' => $id]);

        try {
            $tela = Tela::findOrFail($id);
            $tela->delete();
            
            $this->log('Tela eliminada', ['id' => $id, 'nombre' => $tela->nombre_tela]);

            return [
                'success' => true,
                'message' => 'Tela eliminada correctamente'
            ];
        } catch (\Exception $e) {
            $this->logError('Error al eliminar tela', ['id' => $id, 'error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ];
        }
    }
}
