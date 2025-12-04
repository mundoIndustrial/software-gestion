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
            // Agregar estado
            if (!Schema::hasColumn('pedidos_produccion', 'estado')) {
                $table->enum('estado', [
                    'PENDIENTE_SUPERVISOR',
                    'APROBADO_SUPERVISOR',
                    'EN_PRODUCCION',
                    'FINALIZADO'
                ])->default('PENDIENTE_SUPERVISOR')->after('area');
            }

            // Agregar número_pedido UNIQUE NULLABLE
            if (!Schema::hasColumn('pedidos_produccion', 'numero_pedido')) {
                $table->unsignedInteger('numero_pedido')->nullable()->unique()->after('numero_cotizacion');
            }

            // Agregar timestamp de aprobación supervisor
            if (!Schema::hasColumn('pedidos_produccion', 'aprobado_por_supervisor_en')) {
                $table->timestamp('aprobado_por_supervisor_en')->nullable()->after('estado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos_produccion', 'aprobado_por_supervisor_en')) {
                $table->dropColumn('aprobado_por_supervisor_en');
            }
            if (Schema::hasColumn('pedidos_produccion', 'numero_pedido')) {
                $table->dropColumn('numero_pedido');
            }
            if (Schema::hasColumn('pedidos_produccion', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};
