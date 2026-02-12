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
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Campo para guardar el ID de la asesora que anula el pedido
            $table->unsignedBigInteger('anulado_por_asesora_id')->nullable()->after('asesor_id');
            
            // Campo para guardar la fecha y hora cuando la asesora anula el pedido
            $table->timestamp('anulado_por_asesora_en')->nullable()->after('anulado_por_asesora_id');
            
            // Agregar clave foránea si es necesario
            $table->foreign('anulado_por_asesora_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Eliminar clave foránea
            $table->dropForeign(['anulado_por_asesora_id']);
            
            // Eliminar campos
            $table->dropColumn('anulado_por_asesora_en');
            $table->dropColumn('anulado_por_asesora_id');
        });
    }
};
