<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tipos_broche_boton')) {
            Schema::create('tipos_broche_boton', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100)->unique();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                
                $table->index('nombre');
            });
            
            // Insertar algunos tipos por defecto
            \DB::table('tipos_broche_boton')->insert([
                ['nombre' => 'Botón', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Broche', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Cierre', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Velcro', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
            
            \Log::info(' [Migración] Tabla tipos_broche_boton creada exitosamente');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_broche_boton');
    }
};
