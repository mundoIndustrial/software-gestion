<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OBJETIVO: Eliminar pedido_produccion_id de prendas_pedido
     *
     * RAZÓN: Solo necesitamos numero_pedido para relacionarnos con procesos_prenda
     * La relación con pedidos_produccion se hace via numero_pedido
     *
     * ESTRUCTURA FINAL:
     * prendas_pedido
     *   ├── id (PK)
     *   ├── numero_pedido (FK → pedidos_produccion.numero_pedido) ← ÚNICA relación
     *   ├── nombre_prenda, cantidad, descripcion
     *   └── cantidad_talla (JSON)
     */
    public function up(): void
    {
        if (Schema::hasColumn('prendas_pedido', 'pedido_produccion_id')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                // Eliminar la foreign key
                try {
                    $table->dropForeign(['pedido_produccion_id']);
                } catch (\Exception $e) {
                    // Ignorar si no existe
                }
                
                // Eliminar la columna
                $table->dropColumn('pedido_produccion_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('prendas_pedido', 'pedido_produccion_id')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->foreignId('pedido_produccion_id')
                    ->after('id')
                    ->constrained('pedidos_produccion')
                    ->onDelete('cascade');
            });
        }
    }
};
