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
        Schema::table('prendas_pedido_novedades_recibo', function (Blueprint $table) {
            $table->boolean('editado')->default(false)->after('notas_adicionales');
            $table->timestamp('editado_en')->nullable()->after('editado');
            $table->bigInteger('editado_por')->nullable()->after('editado_en');
            
            // Índices
            $table->index('editado');
            $table->index('editado_en');
            $table->index('editado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido_novedades_recibo', function (Blueprint $table) {
            $table->dropIndex(['editado']);
            $table->dropIndex(['editado_en']);
            $table->dropIndex(['editado_por']);
            
            $table->dropColumn('editado');
            $table->dropColumn('editado_en');
            $table->dropColumn('editado_por');
        });
    }
};
