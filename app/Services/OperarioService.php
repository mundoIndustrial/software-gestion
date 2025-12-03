<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * OperarioService
 * 
 * Encapsula toda la lógica de operarios (usuarios):
 * - CRUD: crear, leer, actualizar, eliminar operarios
 * - Búsqueda rápida con índice
 * - Find or Create para operaciones atómicas
 * - Validación de duplicados
 * 
 * @author Refactor Service Layer - Fase 3
 */
class OperarioService extends BaseService
{
    /**
     * Buscar operarios por nombre
     * 
     * Búsqueda rápida usando índice MySQL (LIKE desde inicio)
     * Limita a 10 resultados para autocompletar
     * 
     * @param string $query Búsqueda parcial
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search($query)
    {
        $this->log('Buscando operarios', ['query' => $query]);

        $operarios = User::where('name', 'like', $query . '%')
            ->select('id', 'name')
            ->limit(10)
            ->get();

        $this->log('Operarios encontrados', ['count' => $operarios->count()]);
        return $operarios;
    }

    /**
     * Crear nuevo operario
     * 
     * Validaciones:
     * - Verifica que no exista nombre duplicado (case-insensitive)
     * - Normaliza nombre a UPPERCASE
     * - Genera email automático
     * - Asigna rol de cortador (roles_ids = [3])
     * 
     * @param \Illuminate\Http\Request $request
     * @return array ['success' => bool, 'message' => string, 'operario' => User|null]
     */
    public function store(Request $request)
    {
        $this->log('Creando operario', ['name' => $request->name ?? 'N/A']);

        try {
            // Verificar si ya existe el operario (case-insensitive)
            $nombreNormalizado = strtoupper($request->name);
            $operarioExistente = User::where('name', $nombreNormalizado)->first();
            
            if ($operarioExistente) {
                $this->logWarning('Operario duplicado', ['name' => $nombreNormalizado, 'id' => $operarioExistente->id]);
                return [
                    'success' => false,
                    'message' => "El operario \"{$nombreNormalizado}\" ya existe en el sistema.",
                    'error_type' => 'duplicate',
                    'operario' => $operarioExistente
                ];
            }

            // Validar entrada
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            // Crear operario con email auto-generado y rol de cortador
            $operario = User::create([
                'name' => $nombreNormalizado,
                'email' => strtolower(str_replace(' ', '.', $nombreNormalizado)) . '@example.com',
                'password' => bcrypt('password'),
                'roles_ids' => [3], // Cortador role
            ]);

            $this->log('Operario creado', ['id' => $operario->id, 'name' => $operario->name]);

            return [
                'success' => true,
                'message' => 'Operario creado correctamente.',
                'operario' => $operario
            ];
        } catch (\Exception $e) {
            $this->logError('Error al crear operario', [
                'error' => $e->getMessage(),
                'name' => $request->name ?? 'N/A'
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al crear el operario: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Buscar o crear operario por nombre
     * 
     * Operación atómica: intenta buscar primero, crea solo si no existe
     * Optimización: evita bcrypt en la mayoría de casos (búsqueda exitosa)
     * 
     * @param string $name Nombre del operario (será normalizado a UPPERCASE)
     * @return array ['id' => int, 'name' => string]
     */
    public function findOrCreate($name)
    {
        $startTime = microtime(true);
        $nombreNormalizado = strtoupper($name);
        
        $this->log('Buscando/creando operario', ['name' => $nombreNormalizado]);

        // Buscar primero (operación más común)
        $searchStart = microtime(true);
        $operario = User::where('name', $nombreNormalizado)->first();
        $searchTime = (microtime(true) - $searchStart) * 1000;

        if (!$operario) {
            // Crear si no existe
            $createStart = microtime(true);
            $operario = User::create([
                'name' => $nombreNormalizado,
                'email' => strtolower(str_replace(' ', '', $nombreNormalizado)) . '@mundoindustrial.com',
                'password' => bcrypt('password123')
            ]);
            $createTime = (microtime(true) - $createStart) * 1000;

            $this->log('Operario creado (findOrCreate)', [
                'id' => $operario->id,
                'name' => $nombreNormalizado,
                'search_time_ms' => round($searchTime, 2),
                'create_time_ms' => round($createTime, 2),
                'total_time_ms' => round($searchTime + $createTime, 2)
            ]);
        } else {
            $totalTime = (microtime(true) - $startTime) * 1000;
            $this->log('Operario encontrado (findOrCreate)', [
                'id' => $operario->id,
                'name' => $nombreNormalizado,
                'total_time_ms' => round($totalTime, 2)
            ]);
        }

        return [
            'id' => $operario->id,
            'name' => $operario->name
        ];
    }

    /**
     * Obtener todos los operarios
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        $this->log('Obteniendo todos los operarios');
        return User::select('id', 'name')->get();
    }

    /**
     * Obtener operario por ID
     * 
     * @param int $id ID del operario
     * @return User|null
     */
    public function getById($id)
    {
        $this->log('Obteniendo operario por ID', ['id' => $id]);
        return User::findOrFail($id);
    }

    /**
     * Actualizar operario
     * 
     * @param int $id ID del operario
     * @param array $data Datos a actualizar
     * @return array ['success' => bool, 'message' => string, 'operario' => User|null]
     */
    public function update($id, array $data)
    {
        $this->log('Actualizando operario', ['id' => $id, 'fields' => array_keys($data)]);

        try {
            $operario = User::findOrFail($id);
            
            // Normalizar nombre si viene en los datos
            if (isset($data['name'])) {
                $data['name'] = strtoupper($data['name']);
                
                // Verificar duplicado
                $duplicado = User::where('name', $data['name'])
                    ->where('id', '!=', $id)
                    ->first();
                
                if ($duplicado) {
                    $this->logWarning('Nombre duplicado al actualizar', ['id' => $id, 'name' => $data['name']]);
                    return [
                        'success' => false,
                        'message' => 'El nombre ya existe para otro operario',
                        'error_type' => 'duplicate'
                    ];
                }
            }

            $operario->update($data);
            
            $this->log('Operario actualizado', ['id' => $id]);

            return [
                'success' => true,
                'message' => 'Operario actualizado correctamente',
                'operario' => $operario
            ];
        } catch (\Exception $e) {
            $this->logError('Error al actualizar operario', ['id' => $id, 'error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar operario
     * 
     * @param int $id ID del operario
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($id)
    {
        $this->log('Eliminando operario', ['id' => $id]);

        try {
            $operario = User::findOrFail($id);
            $operario->delete();
            
            $this->log('Operario eliminado', ['id' => $id, 'name' => $operario->name]);

            return [
                'success' => true,
                'message' => 'Operario eliminado correctamente'
            ];
        } catch (\Exception $e) {
            $this->logError('Error al eliminar operario', ['id' => $id, 'error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ];
        }
    }
}
