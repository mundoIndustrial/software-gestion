<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar el ENUM para agregar RECHAZADO_CARTERA
        DB::statement("ALTER TABLE pedidos_produccion MODIFY COLUMN estado ENUM('Pendiente','Entregado','En Ejecución','No iniciado','Anulada','PENDIENTE_SUPERVISOR','pendiente_cartera','RECHAZADO_CARTERA')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el ENUM sin RECHAZADO_CARTERA
        DB::statement("ALTER TABLE pedidos_produccion MODIFY COLUMN estado ENUM('Pendiente','Entregado','En Ejecución','No iniciado','Anulada','PENDIENTE_SUPERVISOR','pendiente_cartera')");
    }
};
