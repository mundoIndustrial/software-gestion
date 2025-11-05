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
        Schema::create('registro_piso_produccion', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('modulo');
            $table->string('orden_produccion');
            $table->string('hora', 50);
            $table->double('tiempo_ciclo');
            $table->double('porcion_tiempo');
            $table->integer('cantidad');
            $table->integer('producida');
            $table->string('paradas_programadas');
            $table->string('paradas_no_programadas')->nullable();
            $table->double('tiempo_parada_no_programada')->nullable();
            $table->integer('numero_operarios');
            $table->double('tiempo_para_programada');
            $table->double('tiempo_disponible')->nullable()->default(0.00);
            $table->double('meta');
            $table->double('eficiencia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_piso_produccion');
    }
};
