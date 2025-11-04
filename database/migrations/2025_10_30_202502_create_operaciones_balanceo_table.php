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
        Schema::create('operaciones_balanceo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balanceo_id')->constrained('balanceos')->onDelete('cascade');
            $table->string('letra'); // A, B, C, etc.
            $table->text('operacion'); // Descripción de la operación
            $table->string('precedencia')->nullable(); // Operación precedente
            $table->string('maquina')->nullable(); // FL, PL, 2 AG 1/4, CRR, PRET, PRES
            $table->double('sam'); // Tiempo estándar en segundos
            $table->string('operario')->nullable(); // Nombre del operario
            $table->string('op')->nullable(); // op1, op2, op3, etc.
            $table->enum('seccion', ['DEL', 'TRAS', 'ENS', 'OTRO'])->default('OTRO'); // Delantero, Trasero, Ensamble
            $table->string('operario_a')->nullable(); // Reasignación del operario
            $table->integer('orden')->default(0); // Orden de la operación
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index(['balanceo_id', 'orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operaciones_balanceo');
    }
};
