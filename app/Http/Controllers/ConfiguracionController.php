<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    public function index()
    {
        // Fetch the current database
        $currentDatabase = env('DB_DATABASE');

        // Calculate storage usage for the current database
        $databaseSize = DB::select(
            "SELECT table_schema AS db_name, SUM(data_length + index_length) / 1024 / 1024 AS size_mb FROM information_schema.tables WHERE table_schema = ? GROUP BY table_schema",
            [$currentDatabase]
        );

        $usedStorage = round($databaseSize[0]->size_mb ?? 0, 2);
        $availableStorage = round(1024 - $usedStorage, 2); // Assuming a 1GB limit for simplicity

        // Fetch available databases
        $databases = DB::select('SHOW DATABASES');
        $databases = array_map(function ($db) {
            return $db->Database;
        }, $databases);

        // Fetch available models (example: tables in the current database)
        $models = DB::select('SHOW TABLES');
        $models = array_map(function ($table) use ($currentDatabase) {
            return $table->{'Tables_in_' . $currentDatabase};
        }, $models);

        return view('configuracion', compact('usedStorage', 'availableStorage', 'databases', 'currentDatabase', 'models'));
    }

    public function createDatabase(Request $request)
    {
        $request->validate([
            'databaseName' => 'required|string|max:255',
            'model' => 'nullable|string',
        ]);

        $newDatabase = $request->input('databaseName');
        $model = $request->input('model');
        $currentDatabase = env('DB_DATABASE');

        // Create the new database
        DB::statement("CREATE DATABASE `$newDatabase` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Copy structure from the selected model (if provided)
        if ($model) {
            $tables = DB::select("SHOW TABLES");
            foreach ($tables as $table) {
                $tableName = $table->{'Tables_in_' . $currentDatabase};
                DB::statement("CREATE TABLE `$newDatabase`.`$tableName` LIKE `$currentDatabase`.`$tableName`");
            }
        }

        return redirect()->route('configuracion.index')->with('success', 'Base de datos creada exitosamente.');
    }

    public function selectDatabase(Request $request)
    {
        $request->validate([
            'database' => 'required|string',
        ]);

        $selectedDatabase = $request->input('database');

        // Update the .env file with the selected database
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        $envContent = preg_replace('/DB_DATABASE=.*/', "DB_DATABASE=$selectedDatabase", $envContent);
        file_put_contents($envPath, $envContent);

        // Clear and refresh the application configuration
        Artisan::call('config:clear');

        return redirect()->route('configuracion.index')->with('success', 'Base de datos activa actualizada exitosamente.');
    }

    public function migrateUsers(Request $request)
    {
        $request->validate([
            'targetDatabase' => 'required|string',
        ]);

        $targetDatabase = $request->input('targetDatabase');

        // Migrate users to the target database
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            DB::connection('mysql')->table("$targetDatabase.users")->insert((array) $user);
        }

        return redirect()->route('configuracion.index')->with('success', 'Usuarios migrados exitosamente.');
    }

    public function backupDatabase()
    {
        try {
            $database = env('DB_DATABASE');
            
            // Crear directorio de backups si no existe
            $backupPath = storage_path('app/backups');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            // Nombre del archivo con fecha y hora
            $filename = 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupPath . DIRECTORY_SEPARATOR . $filename;
            
            // Abrir archivo para escritura
            $handle = fopen($filepath, 'w+');
            if (!$handle) {
                throw new \Exception('No se pudo crear el archivo de backup');
            }
            
            // Escribir encabezado del SQL
            fwrite($handle, "-- Backup de la base de datos: {$database}\n");
            fwrite($handle, "-- Fecha: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Generado por Mundo Industrial\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET time_zone = \"+00:00\";\n\n");
            
            // Obtener todas las tablas
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $database;
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                
                // Obtener estructura de la tabla
                fwrite($handle, "\n-- Estructura de tabla para `{$tableName}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                fwrite($handle, $createTable[0]->{'Create Table'} . ";\n\n");
                
                // Obtener datos de la tabla
                $rows = DB::table($tableName)->get();
                
                if ($rows->count() > 0) {
                    fwrite($handle, "-- Volcado de datos para la tabla `{$tableName}`\n");
                    
                    foreach ($rows as $row) {
                        $row = (array) $row;
                        $columns = array_keys($row);
                        $values = array_values($row);
                        
                        // Escapar valores
                        $escapedValues = array_map(function($value) {
                            if (is_null($value)) {
                                return 'NULL';
                            }
                            return "'" . addslashes($value) . "'";
                        }, $values);
                        
                        $insert = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
                        fwrite($handle, $insert);
                    }
                    
                    fwrite($handle, "\n");
                }
            }
            
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
            
            // Verificar que el archivo se creó correctamente
            if (file_exists($filepath) && filesize($filepath) > 0) {
                $sizeInMB = filesize($filepath) / 1024 / 1024;
                
                return response()->json([
                    'success' => true,
                    'message' => 'Backup creado exitosamente',
                    'filename' => $filename,
                    'size' => round($sizeInMB, 2) . ' MB',
                    'path' => $backupPath,
                    'tables' => count($tables)
                ]);
            } else {
                throw new \Exception('El archivo de backup está vacío');
            }
            
        } catch (\Exception $e) {
            // Eliminar archivo si existe y hubo error
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}