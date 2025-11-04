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
        Schema::create('balanceos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prenda_id')->constrained('prendas')->onDelete('cascade');
            $table->string('version')->default('1.0'); // Versión del balanceo
            $table->integer('total_operarios')->default(0);
            $table->integer('turnos')->default(1);
            $table->double('horas_por_turno')->default(8.00);
            $table->double('tiempo_disponible_horas')->nullable();
            $table->double('tiempo_disponible_segundos')->nullable();
            $table->double('sam_total')->default(0);
            $table->integer('meta_teorica')->nullable();
            $table->double('meta_real')->nullable();
            $table->string('operario_cuello_botella')->nullable();
            $table->double('tiempo_cuello_botella')->nullable();
            $table->double('sam_real')->nullable();
            $table->integer('meta_sugerida_85')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balanceos');
    }
};
