<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Para flujos como CORTE-PARA-BODEGA no existe pedido_produccion/prenda_pedido.
        DB::statement('ALTER TABLE consecutivos_recibos_pedidos MODIFY pedido_produccion_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE consecutivos_recibos_pedidos MODIFY prenda_id BIGINT NULL');
    }

    public function down(): void
    {
        // Reversión conservadora (si hay NULLs, este down podría fallar).
        DB::statement('ALTER TABLE consecutivos_recibos_pedidos MODIFY pedido_produccion_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE consecutivos_recibos_pedidos MODIFY prenda_id BIGINT NOT NULL');
    }
};

