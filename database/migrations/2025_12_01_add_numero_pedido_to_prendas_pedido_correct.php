<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OBJETIVO: Agregar numero_pedido a prendas_pedido
     *
     * ESTRUCTURA FINAL:
     * prendas_pedido
     *   ├── id (PK)
     *   ├── pedido_produccion_id (FK → pedidos_produccion.id)
     *   ├── numero_pedido (FK → pedidos_produccion.numero_pedido) ← RELACIÓN CON PROCESOS
     *   ├── nombre_prenda, cantidad, descripcion
     *   └── cantidad_talla (JSON)
     *
     * RELACIONES:
     * - prendas_pedido.numero_pedido → pedidos_produccion.numero_pedido
     * - procesos_prenda.numero_pedido → pedidos_produccion.numero_pedido
     */
    public function up(): void
    {
        // 1. Agregar columna numero_pedido si no existe
        if (!Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->unsignedInteger('numero_pedido')->nullable()->after('pedido_produccion_id');
            });
        }

        // 2. Migrar datos desde pedido_produccion_id
        if (Schema::hasColumn('prendas_pedido', 'pedido_produccion_id')) {
            DB::statement('
                UPDATE prendas_pedido pp
                SET pp.numero_pedido = (
                    SELECT pp2.numero_pedido
                    FROM pedidos_produccion pp2
                    WHERE pp2.id = pp.pedido_produccion_id
                )
                WHERE pp.numero_pedido IS NULL
                AND pp.pedido_produccion_id IS NOT NULL
            ');
        }

        // 3. Crear índice en numero_pedido
        if (!Schema::hasIndex('prendas_pedido', 'prendas_pedido_numero_pedido_index')) {
            Schema::table('prendas_pedido', function (Blueprint $table) {
                $table->index('numero_pedido');
            });
        }

        // 4. Crear foreign key a pedidos_produccion.numero_pedido
        if (!Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
            return; // Si no existe la columna, no crear FK
        }

        try {
            DB::statement('
                ALTER TABLE prendas_pedido
                ADD CONSTRAINT prendas_pedido_numero_pedido_foreign
                FOREIGN KEY (numero_pedido)
                REFERENCES pedidos_produccion(numero_pedido)
                ON DELETE CASCADE
            ');
        } catch (\Exception $e) {
            // Ignorar si ya existe
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            // Eliminar foreign key si existe
            try {
                DB::statement('ALTER TABLE prendas_pedido DROP FOREIGN KEY prendas_pedido_numero_pedido_foreign');
            } catch (\Exception $e) {
                // Ignorar
            }

            // Eliminar índice si existe
            if (Schema::hasIndex('prendas_pedido', 'prendas_pedido_numero_pedido_index')) {
                $table->dropIndex('prendas_pedido_numero_pedido_index');
            }

            // Eliminar columna si existe
            if (Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
                $table->dropColumn('numero_pedido');
            }
        });
    }
};
