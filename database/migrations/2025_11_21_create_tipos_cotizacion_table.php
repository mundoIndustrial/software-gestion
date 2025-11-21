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
        Schema::create('tipos_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // M, P, G, etc.
            $table->string('nombre'); // Muestra, Prototipo, Grande, etc.
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar tipos de cotización por defecto
        DB::table('tipos_cotizacion')->insert([
            ['codigo' => 'M', 'nombre' => 'Muestra', 'descripcion' => 'Cotización de muestra', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'P', 'nombre' => 'Prototipo', 'descripcion' => 'Cotización de prototipo', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'G', 'nombre' => 'Grande', 'descripcion' => 'Cotización grande', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_cotizacion');
    }
};
