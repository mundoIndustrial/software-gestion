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
        Schema::create('horario_por_roles', function (Blueprint $table) {
            $table->id();
            
            // Relación con roles
            $table->unsignedBigInteger('id_rol');
            $table->foreign('id_rol')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
            
            // Horarios en formato TIME (HH:MM:SS)
            $table->time('entrada_manana')->nullable();
            $table->time('salida_manana')->nullable();
            $table->time('entrada_tarde')->nullable();
            $table->time('salida_tarde')->nullable();
            
            $table->timestamps();
            
            // Índice único para evitar duplicados por rol
            $table->unique('id_rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horario_por_roles');
    }
};
