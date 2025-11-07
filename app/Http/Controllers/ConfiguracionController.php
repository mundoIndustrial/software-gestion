<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

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
}