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
        // Crear tabla tipos_cotizacion
        Schema::create('tipos_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // M, D, X
            $table->string('nombre'); // Muestra, Desarrollo, Especial
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar tipos predeterminados
        DB::table('tipos_cotizacion')->insert([
            ['codigo' => 'M', 'nombre' => 'Muestra', 'descripcion' => 'Cotización de muestra', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'D', 'nombre' => 'Desarrollo', 'descripcion' => 'Cotización de desarrollo', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'X', 'nombre' => 'Especial', 'descripcion' => 'Cotización especial', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Agregar columna tipo_cotizacion_id a cotizaciones si no existe
        if (!Schema::hasColumn('cotizaciones', 'tipo_cotizacion_id')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->foreignId('tipo_cotizacion_id')->nullable()->after('numero_cotizacion')->constrained('tipos_cotizacion')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('cotizaciones', 'tipo_cotizacion_id')) {
                $table->dropForeignIdFor(\App\Models\TipoCotizacion::class);
            }
        });

        Schema::dropIfExists('tipos_cotizacion');
    }
};
