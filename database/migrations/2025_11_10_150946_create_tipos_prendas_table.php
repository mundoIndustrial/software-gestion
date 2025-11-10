<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipos_prendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias_prendas')->onDelete('cascade');
            $table->string('nombre'); // Camisa, Pantalón, Polo, etc.
            $table->string('slug');
            $table->text('descripcion')->nullable();
            $table->json('opciones_disponibles')->nullable(); // Opciones que aplican a este tipo
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
            
            $table->unique(['categoria_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_prendas');
    }
};
