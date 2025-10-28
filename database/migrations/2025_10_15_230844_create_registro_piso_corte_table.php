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
        Schema::create('registro_piso_corte', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('modulo')->nullable();
            $table->string('orden_produccion');
            $table->string('tela');
            $table->string('hora');
            $table->string('operario');
            $table->string('actividad');
            $table->string('maquina');
            $table->decimal('tiempo_ciclo', 8, 2);
            $table->decimal('porcion_tiempo', 8, 2);
            $table->integer('cantidad');
            $table->integer('producida')->nullable()->default(0);
            $table->string('paradas_programadas');
            $table->string('paradas_no_programadas')->nullable();
            $table->decimal('tiempo_parada_no_programada', 8, 2)->nullable();
            $table->integer('numero_operarios')->default(1);
            $table->decimal('tiempo_para_programada', 8, 2)->nullable()->default(0.00);
            $table->decimal('tiempo_disponible', 8, 2)->nullable()->default(0.00);
            $table->decimal('meta', 8, 2);
            $table->decimal('eficiencia', 5, 2);
            $table->string('tipo_extendido');
            $table->integer('numero_capas');
            $table->string('trazado');
            $table->decimal('tiempo_trazado', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_piso_corte');
    }
};
