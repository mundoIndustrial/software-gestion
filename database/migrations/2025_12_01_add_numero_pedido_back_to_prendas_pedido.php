<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OBJETIVO: Agregar numero_pedido de vuelta a prendas_pedido
     *
     * RAZÓN: prendas_pedido necesita numero_pedido para poder relacionarse
     * con procesos_prenda que usa numero_pedido como FK
     *
     * ESTRUCTURA:
     * prendas_pedido
     *   ├── id (PK)
     *   ├── pedido_produccion_id (FK → pedidos_produccion.id)
     *   ├── numero_pedido (FK → pedidos_produccion.numero_pedido) ← AGREGADO
     *   ├── nombre_prenda, cantidad, descripcion
     *   └── cantidad_talla (JSON)
     */
    public function up(): void
    {
        // Agregar numero_pedido si no existe
        if (!Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->unsignedInteger('numero_pedido')->nullable()->after('pedido_produccion_id');
                $table->index('numero_pedido');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->dropIndex(['numero_pedido']);
                $table->dropColumn('numero_pedido');
            });
        }
    }
};
