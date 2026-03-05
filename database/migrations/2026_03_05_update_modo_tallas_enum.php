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
        // Primero cambiar enum para aceptar NULL
        DB::statement("ALTER TABLE pedidos_procesos_prenda_detalles MODIFY COLUMN modo_tallas VARCHAR(50) NULL");
        
        // Mapear datos existentes a nuevos valores
        DB::statement("UPDATE pedidos_procesos_prenda_detalles SET modo_tallas = 'generico' WHERE modo_tallas IN ('para_todas', 'por_tallas') OR modo_tallas IS NULL");

        // Cambiar a nuevo enum
        DB::statement("ALTER TABLE pedidos_procesos_prenda_detalles MODIFY COLUMN modo_tallas ENUM('generico', 'general', 'especifico') DEFAULT 'generico'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al enum anterior
        DB::statement("ALTER TABLE pedidos_procesos_prenda_detalles MODIFY COLUMN modo_tallas ENUM('para_todas', 'por_tallas') DEFAULT 'para_todas'");
    }
};
