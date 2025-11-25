<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Renombra user_id a asesor_id en la tabla pedidos_produccion
     * NOTA: Esta migración es un no-op ya que user_id fue droppeada anteriormente
     */
    public function up(): void
    {
        // No-op: la columna user_id fue eliminada por drop_user_id_from_pedidos_produccion
        // La columna asesor_id ya existe desde add_foreign_keys_to_pedidos_produccion
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: revert of a no-op migration
    }
};
