<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar columna 'nombre' si no existe
        if (!Schema::hasColumn('festivos', 'nombre')) {
            Schema::table('festivos', function (Blueprint $table) {
                $table->string('nombre')->nullable()->after('fecha');
            });
        }

        // Agregar columna 'es_trasladado' si no existe
        if (!Schema::hasColumn('festivos', 'es_trasladado')) {
            Schema::table('festivos', function (Blueprint $table) {
                $table->boolean('es_trasladado')->default(false)->after('descripcion');
            });
        }

        // Insertar festivos colombianos 2025 si la tabla está vacía
        $count = DB::table('festivos')->count();
        if ($count === 0) {
            $festivos = [
                ['fecha' => '2025-01-01', 'nombre' => 'Año Nuevo', 'descripcion' => 'Día de Año Nuevo'],
                ['fecha' => '2025-01-08', 'nombre' => 'Reyes Magos', 'descripcion' => 'Epifanía'],
                ['fecha' => '2025-04-17', 'nombre' => 'Jueves Santo', 'descripcion' => 'Jueves Santo'],
                ['fecha' => '2025-04-18', 'nombre' => 'Viernes Santo', 'descripcion' => 'Viernes Santo'],
                ['fecha' => '2025-05-01', 'nombre' => 'Día del Trabajo', 'descripcion' => 'Día Internacional del Trabajo'],
                ['fecha' => '2025-06-02', 'nombre' => 'Corpus Christi', 'descripcion' => 'Corpus Christi'],
                ['fecha' => '2025-06-09', 'nombre' => 'Sagrado Corazón', 'descripcion' => 'Sagrado Corazón'],
                ['fecha' => '2025-06-30', 'nombre' => 'San Pedro y San Pablo', 'descripcion' => 'San Pedro y San Pablo'],
                ['fecha' => '2025-07-01', 'nombre' => 'San Pedro y San Pablo (Observancia)', 'descripcion' => 'San Pedro y San Pablo (Observancia)'],
                ['fecha' => '2025-08-07', 'nombre' => 'Batalla de Boyacá', 'descripcion' => 'Batalla de Boyacá'],
                ['fecha' => '2025-08-15', 'nombre' => 'Asunción de María', 'descripcion' => 'Asunción de María'],
                ['fecha' => '2025-11-01', 'nombre' => 'Todos los Santos', 'descripcion' => 'Todos los Santos'],
                ['fecha' => '2025-11-17', 'nombre' => 'Independencia de Cartagena', 'descripcion' => 'Independencia de Cartagena'],
                ['fecha' => '2025-12-08', 'nombre' => 'Inmaculada Concepción', 'descripcion' => 'Inmaculada Concepción'],
                ['fecha' => '2025-12-25', 'nombre' => 'Navidad', 'descripcion' => 'Navidad'],
            ];

            foreach ($festivos as $festivo) {
                DB::table('festivos')->insert([
                    'fecha' => $festivo['fecha'],
                    'nombre' => $festivo['nombre'],
                    'descripcion' => $festivo['descripcion'],
                    'es_trasladado' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover columnas agregadas
        if (Schema::hasColumn('festivos', 'nombre')) {
            Schema::table('festivos', function (Blueprint $table) {
                $table->dropColumn('nombre');
            });
        }

        if (Schema::hasColumn('festivos', 'es_trasladado')) {
            Schema::table('festivos', function (Blueprint $table) {
                $table->dropColumn('es_trasladado');
            });
        }
    }
};
