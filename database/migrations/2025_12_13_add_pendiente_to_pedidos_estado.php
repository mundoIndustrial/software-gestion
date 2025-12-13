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
        // Modificar la columna estado para agregar 'Pendiente' al ENUM
        DB::statement("ALTER TABLE pedidos_produccion MODIFY estado ENUM('Pendiente','Entregado','En Ejecución','No iniciado','Anulada') NOT NULL DEFAULT 'Pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a los valores anteriores
        DB::statement("ALTER TABLE pedidos_produccion MODIFY estado ENUM('Entregado','En Ejecución','No iniciado','Anulada') NOT NULL DEFAULT 'No iniciado'");
    }
};
