<?php

namespace App\Infrastructure\Http\Controllers\Tableros;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $this->ensureAdminAccess();

        $currentDatabase = $this->getCurrentDatabaseName();

        $databaseSize = DB::select(
            "SELECT table_schema AS db_name, SUM(data_length + index_length) / 1024 / 1024 AS size_mb FROM information_schema.tables WHERE table_schema = ? GROUP BY table_schema",
            [$currentDatabase]
        );

        $usedStorage = round($databaseSize[0]->size_mb ?? 0, 2);
        $availableStorage = round(1024 - $usedStorage, 2);

        $databases = DB::select('SHOW DATABASES');
        $databases = array_map(function ($db) {
            return $db->Database;
        }, $databases);

        $models = DB::select('SHOW TABLES');
        $models = array_map(function ($table) use ($currentDatabase) {
            return $table->{'Tables_in_' . $currentDatabase};
        }, $models);

        return view('configuracion', compact('usedStorage', 'availableStorage', 'databases', 'currentDatabase', 'models'));
    }

    public function createDatabase(Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'databaseName' => 'required|string|max:255',
            'model' => 'nullable|string',
        ]);

        $newDatabase = $request->input('databaseName');
        $model = $request->input('model');
        $currentDatabase = $this->getCurrentDatabaseName();

        DB::statement("CREATE DATABASE `$newDatabase` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        if ($model) {
            $tables = DB::select('SHOW TABLES');
            foreach ($tables as $table) {
                $tableName = $table->{'Tables_in_' . $currentDatabase};
                DB::statement("CREATE TABLE `$newDatabase`.`$tableName` LIKE `$currentDatabase`.`$tableName`");
            }
        }

        return redirect()->route('configuracion.index')->with('success', 'Base de datos creada exitosamente.');
    }

    public function selectDatabase(Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'database' => 'required|string',
        ]);

        $selectedDatabase = $request->input('database');

        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        $envContent = preg_replace('/DB_DATABASE=.*/', "DB_DATABASE=$selectedDatabase", $envContent);
        file_put_contents($envPath, $envContent);

        Artisan::call('config:clear');

        return redirect()->route('configuracion.index')->with('success', 'Base de datos activa actualizada exitosamente.');
    }

    public function migrateUsers(Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'targetDatabase' => 'required|string',
        ]);

        $targetDatabase = $request->input('targetDatabase');

        $users = DB::table('users')->get();
        foreach ($users as $user) {
            DB::connection('mysql')->table("$targetDatabase.users")->insert((array) $user);
        }

        return redirect()->route('configuracion.index')->with('success', 'Usuarios migrados exitosamente.');
    }

    public function backupDatabase()
    {
        $this->ensureAdminAccess();

        try {
            $backupPath = storage_path('app/backups');
            $this->ensureDirectoryExists($backupPath);

            $database = $this->getCurrentDatabaseName();
            $filename = $this->buildBackupFilename($database);
            $filepath = $backupPath . DIRECTORY_SEPARATOR . $filename;

            $tables = $this->generateBackupSqlFile($filepath, $database);

            if (file_exists($filepath) && filesize($filepath) > 0) {
                $sizeInMB = filesize($filepath) / 1024 / 1024;

                return response()->json([
                    'success' => true,
                    'message' => 'Backup creado exitosamente',
                    'filename' => $filename,
                    'size' => round($sizeInMB, 2) . ' MB',
                    'path' => $backupPath,
                    'tables' => count($tables),
                ]);
            }

            throw new \Exception('El archivo de backup esta vacio');
        } catch (\Exception $e) {
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function downloadBackup()
    {
        $this->ensureAdminAccess();

        try {
            $tempPath = storage_path('app/temp');
            $this->ensureDirectoryExists($tempPath);

            $database = $this->getCurrentDatabaseName();
            $filename = $this->buildBackupFilename($database);
            $filepath = $tempPath . DIRECTORY_SEPARATOR . $filename;

            $this->generateBackupSqlFile($filepath, $database);

            return response()->download($filepath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function uploadToGoogleDrive()
    {
        $this->ensureAdminAccess();

        try {
            set_time_limit(600);
            ini_set('memory_limit', '512M');

            $folderId = env('GOOGLE_DRIVE_FOLDER_ID');
            $refreshToken = env('GOOGLE_DRIVE_REFRESH_TOKEN');

            if (!$refreshToken || !$folderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Google Drive no esta configurado. Verifica GOOGLE_DRIVE_REFRESH_TOKEN y GOOGLE_DRIVE_FOLDER_ID en el .env',
                ], 400);
            }

            \Log::info('Iniciando backup a Google Drive');

            $tokenResult = $this->getGoogleDriveAccessToken($refreshToken);

            if (!$tokenResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $tokenResult['message'],
                ], 400);
            }

            $accessToken = $tokenResult['token'];
            $database = $this->getCurrentDatabaseName();

            \Log::info('Generando backup de la base de datos: ' . $database);

            $tempPath = storage_path('app/temp');
            $this->ensureDirectoryExists($tempPath);

            $filename = $this->buildBackupFilename($database);
            $filepath = $tempPath . DIRECTORY_SEPARATOR . $filename;

            $this->generateBackupSqlFile($filepath, $database, true);

            \Log::info('Backup SQL generado, preparando subida a Google Drive');

            $fileSize = filesize($filepath);
            \Log::info('tamano del archivo: ' . round($fileSize / 1024 / 1024, 2) . ' MB');

            $fileContent = file_get_contents($filepath);

            $metadata = [
                'name' => $filename,
                'parents' => [$folderId],
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
                'Content-Length: ' . strlen($multipartBody),
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipartBody);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);

            \Log::info('Subiendo archivo a Google Drive...');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                \Log::error('Error de cURL: ' . $curlError);
                throw new \Exception('Error de conexion con Google Drive: ' . $curlError);
            }

            \Log::info('Respuesta de Google Drive - HTTP Code: ' . $httpCode);

            unlink($filepath);

            if ($httpCode === 200 || $httpCode === 201) {
                $responseData = json_decode($response, true);

                \Log::info('Backup subido exitosamente a Google Drive', [
                    'file_id' => $responseData['id'] ?? null,
                    'filename' => $filename,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Backup subido exitosamente a Google Drive',
                    'filename' => $filename,
                    'size' => round($fileSize / 1024 / 1024, 2) . ' MB',
                    'drive_file_id' => $responseData['id'] ?? null,
                ]);
            }

            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? $response;

            \Log::error('Error al subir a Google Drive', [
                'http_code' => $httpCode,
                'error' => $errorMessage,
            ]);

            throw new \Exception('Error al subir a Google Drive (HTTP ' . $httpCode . '): ' . $errorMessage);
        } catch (\Exception $e) {
            \Log::error('Error en uploadToGoogleDrive: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener access token de Google Drive usando OAuth
     * Renueva automaticamente el token si es necesario
     */
    private function getGoogleDriveAccessToken($refreshToken)
    {
        try {
            $clientId = '377832184815-ulbdp631n4irovrer0it0gk8rfsvetfj.apps.googleusercontent.com';
            $clientSecret = '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';

            $currentToken = env('GOOGLE_DRIVE_ACCESS_TOKEN');
            if ($currentToken) {
                $testCh = curl_init('https://www.googleapis.com/drive/v3/about?fields=user');
                curl_setopt($testCh, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($testCh, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $currentToken]);
                curl_setopt($testCh, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($testCh, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($testCh, CURLOPT_TIMEOUT, 10);

                $testResponse = curl_exec($testCh);
                $testHttpCode = curl_getinfo($testCh, CURLINFO_HTTP_CODE);
                $testCurlError = curl_error($testCh);
                curl_close($testCh);

                if (!$testCurlError && $testHttpCode === 200) {
                    \Log::info('Usando access token actual valido');
                    return ['success' => true, 'token' => $currentToken];
                }

                \Log::info('Access token actual expirado o invalido, renovando...');
            }

            \Log::info('Renovando access token...');

            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                \Log::error('Error de cURL al renovar token: ' . $curlError);
                return ['success' => false, 'message' => 'Error de conexion con Google: ' . $curlError];
            }

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                $newAccessToken = $data['access_token'] ?? null;

                if ($newAccessToken) {
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
                'response' => $response,
            ]);

            return ['success' => false, 'message' => $errorMessage];
        } catch (\Exception $e) {
            \Log::error('Excepcion al obtener access token: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error de conexion: ' . $e->getMessage()];
        }
    }

    private function updateEnvFile($key, $value)
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        $pattern = "/^{$key}=.*/m";
        if (preg_match($pattern, $envContent)) {
            $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
        } else {
            $envContent .= "\n{$key}={$value}";
        }

        file_put_contents($envPath, $envContent);
    }

    private function ensureAdminAccess(): void
    {
        if (!auth()->check() || !auth()->user()->role || auth()->user()->role->name !== 'admin') {
            abort(403, 'Accion no autorizada.');
        }
    }

    private function getCurrentDatabaseName(): string
    {
        return DB::connection()->getDatabaseName();
    }

    private function buildBackupFilename(string $database): string
    {
        return 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

    private function generateBackupSqlFile(string $filepath, string $database, bool $logProgress = false): array
    {
        $handle = fopen($filepath, 'w+');
        if (!$handle) {
            throw new \Exception('No se pudo crear el archivo de backup');
        }

        try {
            $sessionTimeZone = $this->getDatabaseSessionTimeZone();

            fwrite($handle, "-- Backup de la base de datos: {$database}\n");
            fwrite($handle, "-- Fecha: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Generado por Mundo Industrial\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET time_zone = '{$sessionTimeZone}';\n\n");

            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $database;
            $tableCount = count($tables);

            foreach ($tables as $index => $table) {
                $tableName = $table->$tableKey;

                if ($logProgress) {
                    \Log::info('Procesando tabla ' . ($index + 1) . '/' . $tableCount . ': ' . $tableName);
                }

                fwrite($handle, "\n-- Estructura de tabla para `{$tableName}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                fwrite($handle, $createTable[0]->{'Create Table'} . ";\n\n");

                $rowCount = DB::table($tableName)->count();
                if ($rowCount <= 0) {
                    continue;
                }

                fwrite($handle, "-- Volcado de datos para la tabla `{$tableName}` ({$rowCount} registros)\n");

                DB::table($tableName)
                    ->orderBy(DB::raw('1'))
                    ->chunk(500, function ($rows) use ($handle, $tableName) {
                        foreach ($rows as $row) {
                            $row = (array) $row;
                            $columns = array_keys($row);
                            $values = array_map([$this, 'escapeSqlValue'], array_values($row));

                            $insert = "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                            fwrite($handle, $insert);
                        }
                    });

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");

            return $tables;
        } finally {
            fclose($handle);
        }
    }

    private function escapeSqlValue($value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        return "'" . addslashes((string) $value) . "'";
    }

    private function getDatabaseSessionTimeZone(): string
    {
        $timezone = DB::selectOne('SELECT @@session.time_zone as tz');
        $tz = $timezone->tz ?? 'SYSTEM';

        return $tz === 'SYSTEM' ? 'SYSTEM' : (string) $tz;
    }
}
