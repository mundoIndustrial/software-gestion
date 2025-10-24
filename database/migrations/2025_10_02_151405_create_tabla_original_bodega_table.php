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
        Schema::create('tabla_original_bodega', function (Blueprint $table) {
            // ENUMS
            $table->enum('estado', ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'])->nullable();
            $table->enum('area', [
                'Corte',
                'Control-Calidad',
                'Costura',
                'Bordado',
                'Creación Orden',
                'Estampado',
                'Entrega',
                'Polos',
                'Sin seleccionar',
                'Taller',
                'Insumos',
                'Lavandería',
                'Arreglos',
                'Despachos'
            ])->nullable();

            // Resto de columnas
            $table->string('tiempo', 65)->nullable();
            $table->string('total_de_dias_', 50)->nullable();
            $table->unsignedInteger('pedido')->primary();
            $table->string('cliente', 96)->nullable();
            $table->text('hora')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('cantidad', 56)->nullable();
            $table->text('novedades')->nullable();
            $table->string('asesora', 111)->nullable();
            $table->string('forma_de_pago', 69)->nullable();
            $table->date('fecha_de_creacion_de_orden')->nullable();
            $table->string('encargado_orden', 67)->nullable();
            $table->string('dias_orden', 50)->nullable();
            $table->string('inventario', 111)->nullable();
            $table->string('encargados_inventario', 55)->nullable();
            $table->string('dias_inventario', 50)->nullable();
            $table->date('insumos_y_telas')->nullable();
            $table->string('encargados_insumos', 56)->nullable();
            $table->string('dias_insumos', 50)->nullable();
            $table->date('corte')->nullable();
            $table->string('encargados_de_corte', 71)->nullable();
            $table->string('dias_corte', 50)->nullable();
            $table->string('bordado', 111)->nullable();
            $table->string('codigo_de_bordado', 67)->nullable();
            $table->string('dias_bordado', 50)->nullable();
            $table->string('estampado', 200)->nullable();
            $table->string('encargados_estampado', 61)->nullable();
            $table->string('dias_estampado', 50)->nullable();
            $table->date('costura')->nullable();
            $table->string('modulo', 68)->nullable();
            $table->string('dias_costura', 56)->nullable();
            $table->string('reflectivo', 50)->nullable();
            $table->string('encargado_reflectivo', 56)->nullable();
            $table->string('total_de_dias_reflectivo', 50)->nullable();
            $table->date('lavanderia')->nullable();
            $table->string('encargado_lavanderia', 58)->nullable();
            $table->string('dias_lavanderia', 50)->nullable();
            $table->date('arreglos')->nullable();
            $table->string('encargado_arreglos', 56)->nullable();
            $table->string('total_de_dias_arreglos', 50)->nullable();
            $table->string('marras', 50)->nullable();
            $table->string('encargados_marras', 56)->nullable();
            $table->string('total_de_dias_marras', 50)->nullable();
            $table->date('control_de_calidad')->nullable();
            $table->string('encargados_calidad', 94)->nullable();
            $table->string('dias_c_c', 50)->nullable();
            $table->date('entrega')->nullable();
            $table->string('encargados_entrega', 67)->nullable();
            $table->date('despacho')->nullable();
            $table->string('column_52', 50)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabla_original_bodega');
    }
};
