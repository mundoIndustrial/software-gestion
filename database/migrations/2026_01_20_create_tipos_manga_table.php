<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tipos_manga')) {
            Schema::create('tipos_manga', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100)->unique();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                
                $table->index('nombre');
            });
            
            // Insertar algunos tipos por defecto
            \DB::table('tipos_manga')->insert([
                ['nombre' => 'Corta', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Larga', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Tres Cuartos', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nombre' => 'Raglan', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
            
            \Log::info(' [Migración] Tabla tipos_manga creada exitosamente');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_manga');
    }
};
