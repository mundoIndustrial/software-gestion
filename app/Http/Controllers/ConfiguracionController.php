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
        // Solo Admin puede acceder
        if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

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
        // Solo Admin puede crear bases de datos
        if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

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
        // Solo Admin puede cambiar la base de datos
        if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

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
        // Solo Admin puede migrar usuarios
        if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

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

    public function downloadBackup()
    {
        try {
            $database = env('DB_DATABASE');
            
            // Crear directorio temporal si no existe
            $tempPath = storage_path('app/temp');
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }
            
            // Nombre del archivo con fecha y hora
            $filename = 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $tempPath . DIRECTORY_SEPARATOR . $filename;
            
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
            
            // Descargar el archivo
            return response()->download($filepath, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadToGoogleDrive()
    {
        try {
            // Aumentar límites de tiempo y memoria para evitar timeouts
            set_time_limit(600); // 10 minutos
            ini_set('memory_limit', '512M');
            
            $folderId = env('GOOGLE_DRIVE_FOLDER_ID');
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');

            if (!$refreshToken || !$folderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive no está configurado. Verifica GOOGLE_DRIVE_REFRESH_TOKEN y GOOGLE_DRIVE_FOLDER_ID en el .env'
                ], 400);
            }
            
            \Log::info('Iniciando backup a Google Drive');
            
            // Obtener access token (renovándolo automáticamente si es necesario)
            $tokenResult = $this->getGoogleDriveAccessToken($refreshToken);
            
            if (!$tokenResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $tokenResult['message']
                ], 400);
            }
            
            $accessToken = $tokenResult['token'];

            $database = env('DB_DATABASE');
            \Log::info('Generando backup de la base de datos: ' . $database);
            
            // Crear backup temporal
            $tempPath = storage_path('app/temp');
            if (!file_exists($tempPath)) {
                mkdir($tempPath, 0755, true);
            }
            
            $filename = 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $tempPath . DIRECTORY_SEPARATOR . $filename;
            
            // Generar el backup
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
            
            $tableCount = count($tables);
            $currentTable = 0;
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                $currentTable++;
                
                \Log::info("Procesando tabla {$currentTable}/{$tableCount}: {$tableName}");
                
                fwrite($handle, "\n-- Estructura de tabla para `{$tableName}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                fwrite($handle, $createTable[0]->{'Create Table'} . ";\n\n");
                
                // Procesar datos en chunks para evitar problemas de memoria
                $rowCount = DB::table($tableName)->count();
                
                if ($rowCount > 0) {
                    fwrite($handle, "-- Volcado de datos para la tabla `{$tableName}` ({$rowCount} registros)\n");
                    
                    $chunkSize = 500; // Procesar 500 registros a la vez
                    
                    DB::table($tableName)->orderBy(DB::raw('1'))->chunk($chunkSize, function($rows) use ($handle, $tableName) {
                        foreach ($rows as $row) {
                            $row = (array) $row;
                            $columns = array_keys($row);
                            $values = array_values($row);
                            
                            $escapedValues = array_map(function($value) {
                                if (is_null($value)) {
                                    return 'NULL';
                                }
                                return "'" . addslashes($value) . "'";
                            }, $values);
                            
                            $insert = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escapedValues) . ");\n";
                            fwrite($handle, $insert);
                        }
                    });
                    
                    fwrite($handle, "\n");
                }
            }
            
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);
            
            \Log::info('Backup SQL generado, preparando subida a Google Drive');
            
            // Obtener tamaño del archivo antes de leerlo
            $fileSize = filesize($filepath);
            \Log::info('Tamaño del archivo: ' . round($fileSize / 1024 / 1024, 2) . ' MB');
            
            // Subir archivo a Google Drive usando el Access Token
            $fileContent = file_get_contents($filepath);
            
            $metadata = [
                'name' => $filename,
                'parents' => [$folderId]
            ];
            
            $boundary = uniqid();
            $delimiter = "\r\n--" . $boundary . "\r\n";
            $closeDelimiter = "\r\n--" . $boundary . "--";
            
            $multipartBody = $delimiter;
            $multipartBody .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
            $multipartBody .= json_encode($metadata);
            $multipartBody .= $delimiter;
            $multipartBody .= "Content-Type: application/sql\r\n\r\n";
            $multipartBody .= $fileContent;
            $multipartBody .= $closeDelimiter;
            
            $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: multipart/related; boundary=' . $boundary,
                'Content-Length: ' . strlen($multipartBody)
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipartBody);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Timeout de 5 minutos para la subida
            
            \Log::info('Subiendo archivo a Google Drive...');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                \Log::error('Error de cURL: ' . $curlError);
                throw new \Exception('Error de conexión con Google Drive: ' . $curlError);
            }
            
            \Log::info('Respuesta de Google Drive - HTTP Code: ' . $httpCode);
            
            // Eliminar archivo temporal
            unlink($filepath);
            
            if ($httpCode === 200 || $httpCode === 201) {
                $responseData = json_decode($response, true);
                
                \Log::info('Backup subido exitosamente a Google Drive', [
                    'file_id' => $responseData['id'] ?? null,
                    'filename' => $filename
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Backup subido exitosamente a Google Drive',
                    'filename' => $filename,
                    'size' => round($fileSize / 1024 / 1024, 2) . ' MB',
                    'drive_file_id' => $responseData['id'] ?? null
                ]);
            } else {
                $errorData = json_decode($response, true);
                $errorMessage = $errorData['error']['message'] ?? $response;
                
                \Log::error('Error al subir a Google Drive', [
                    'http_code' => $httpCode,
                    'error' => $errorMessage
                ]);
                
                throw new \Exception('Error al subir a Google Drive (HTTP ' . $httpCode . '): ' . $errorMessage);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error en uploadToGoogleDrive: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Eliminar archivo temporal si existe
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener access token de Google Drive usando OAuth
     * Renueva automáticamente el token si es necesario
     */
    private function getGoogleDriveAccessToken($refreshToken)
    {
        try {
            // Credenciales OAuth
            $clientId = '377832184815-ulbdp631n4irovrer0it0gk8rfsvetfj.apps.googleusercontent.com';
            $clientSecret = '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';
            
            // Intentar usar el access token actual si existe y es válido
            $currentToken = env('GOOGLE_DRIVE_ACCESS_TOKEN');
            if ($currentToken) {
                // Verificar si el token actual todavía funciona
                $testCh = curl_init('https://www.googleapis.com/drive/v3/about?fields=user');
                curl_setopt($testCh, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($testCh, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $currentToken]);
                curl_setopt($testCh, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($testCh, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($testCh, CURLOPT_TIMEOUT, 10);
                curl_setopt($testCh, CURLOPT_CONNECTTIMEOUT, 10);
                
                $testResponse = curl_exec($testCh);
                $testCode = curl_getinfo($testCh, CURLINFO_HTTP_CODE);
                $testError = curl_error($testCh);
                curl_close($testCh);
                
                // Si hay error de conexión, continuar con renovación
                if ($testError) {
                    \Log::warning('Error al verificar token actual: ' . $testError);
                } elseif ($testCode === 200) {
                    \Log::info('Access token actual todavía es válido');
                    return ['success' => true, 'token' => $currentToken];
                }
            }
            
            // El token expiró o no existe, renovarlo
            \Log::info('Renovando access token...');
            
            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token'
            ]));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL temporalmente
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Si hay error de cURL, retornarlo inmediatamente
            if ($curlError) {
                \Log::error('Error de cURL al renovar token: ' . $curlError);
                return ['success' => false, 'message' => 'Error de conexión con Google: ' . $curlError];
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                $newAccessToken = $data['access_token'] ?? null;
                
                if ($newAccessToken) {
                    // Actualizar el .env con el nuevo token
                    $this->updateEnvFile('GOOGLE_DRIVE_ACCESS_TOKEN', $newAccessToken);
                    \Artisan::call('config:clear');
                    
                    \Log::info('Access token renovado exitosamente');
                    return ['success' => true, 'token' => $newAccessToken];
                }
            }
            
            $errorData = json_decode($response, true);
            $errorMessage = 'Error al renovar access token';
            
            if (isset($errorData['error'])) {
                $errorMessage .= ': ' . ($errorData['error_description'] ?? $errorData['error']);
            } else {
                $errorMessage .= ' (HTTP ' . $httpCode . ')';
            }
            
            \Log::error('Error al renovar access token', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            
            return ['success' => false, 'message' => $errorMessage];
            
        } catch (\Exception $e) {
            \Log::error('Excepción al obtener access token: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error de conexión: ' . $e->getMessage()];
        }
    }
    
    private function updateEnvFile($key, $value)
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        
        // Buscar y reemplazar la línea
        $pattern = "/^{$key}=.*/m";
        if (preg_match($pattern, $envContent)) {
            $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
        } else {
            $envContent .= "\n{$key}={$value}";
        }
        
        file_put_contents($envPath, $envContent);
    }

}