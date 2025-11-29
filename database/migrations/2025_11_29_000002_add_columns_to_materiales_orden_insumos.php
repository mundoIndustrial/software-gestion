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
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            // Agregar nuevas columnas de fechas
            $table->date('fecha_orden')->nullable()->after('fecha_llegada')->comment('Fecha en que se creó la orden');
            $table->date('fecha_pago')->nullable()->after('fecha_orden')->comment('Fecha en que se pagó el insumo');
            $table->date('fecha_despacho')->nullable()->after('fecha_pago')->comment('Fecha en que se despachó el insumo');
            
            // Columna para observaciones
            $table->text('observaciones')->nullable()->after('fecha_despacho')->comment('Observaciones del insumo');
            
            // Columna para días de demora (calculada automáticamente)
            $table->integer('dias_demora')->nullable()->after('observaciones')->comment('Días de demora (fecha_llegada - fecha_pedido)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materiales_orden_insumos', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_orden',
                'fecha_pago',
                'fecha_despacho',
                'observaciones',
                'dias_demora'
            ]);
        });
    }
};
