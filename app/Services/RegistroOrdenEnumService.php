<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * RegistroOrdenEnumService
 * 
 * Responsabilidad: Obtener opciones de ENUM de la BD
 * Extrae la lógica de lectura de columnas ENUM del controlador
 * 
 * CUMPLE SRP: Solo maneja enums
 */
class RegistroOrdenEnumService
{
    /**
     * Obtener valores ENUM de una columna específica
     * 
     * @param string $table - Nombre de la tabla
     * @param string $column - Nombre de la columna ENUM
     * @return array - Array de valores ENUM
     * @throws \Exception
     */
    public function getEnumOptions(string $table, string $column): array
    {
        try {
            $columnInfo = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column]);
            
            if (empty($columnInfo)) {
                return [];
            }

            $type = $columnInfo[0]->Type;
            preg_match_all("/'([^']+)'/", $type, $matches);
            
            return $matches[1] ?? [];
        } catch (\Exception $e) {
            \Log::warning("Error al obtener opciones ENUM para {$table}.{$column}", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Verificar si una columna es tipo ENUM
     * 
     * @param string $table - Nombre de la tabla
     * @param string $column - Nombre de la columna
     * @return bool
     */
    public function isEnumColumn(string $table, string $column): bool
    {
        try {
            $columnInfo = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column]);
            
            if (empty($columnInfo)) {
                return false;
            }

            return strpos($columnInfo[0]->Type, 'enum') === 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
