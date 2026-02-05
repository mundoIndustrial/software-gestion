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
        // Cambiar el enum de estado_bodega para que acepte: Pendiente, Entregado, Anulado
        DB::statement("ALTER TABLE bodega_detalles_talla MODIFY COLUMN estado_bodega ENUM('Pendiente', 'Entregado', 'Anulado') DEFAULT 'Pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir al enum original
        DB::statement("ALTER TABLE bodega_detalles_talla MODIFY COLUMN estado_bodega ENUM('Pendiente', 'Parcial', 'Entregado', 'Retrasado') DEFAULT 'Pendiente'");
    }
};
