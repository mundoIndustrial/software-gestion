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
        Schema::create('horas_extras_agregadas', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_persona');
            $table->date('fecha');
            $table->decimal('horas_agregadas', 5, 2); // Ej: 1.50 = 1 hora 30 minutos
            $table->text('novedad')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
            
            // Relaciones
            $table->foreign('codigo_persona')
                ->references('codigo_persona')
                ->on('personal')
                ->onDelete('cascade');
            
            $table->foreign('usuario_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Índices para búsqueda rápida
            $table->index(['codigo_persona', 'fecha']);
            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horas_extras_agregadas');
    }
};
