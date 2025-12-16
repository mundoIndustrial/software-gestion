<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Consolida fotos de telas:
     * - Elimina tela_fotos_pedido (tabla redundante)
     * - Mantiene prenda_fotos_tela_pedido como tabla única para fotos de telas de cada prenda
     */
    public function up(): void
    {
        // Eliminar tela_fotos_pedido si existe
        if (Schema::hasTable('tela_fotos_pedido')) {
            Schema::dropIfExists('tela_fotos_pedido');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Por seguridad, no restauramos la tabla antigua
        // Esta migración consolida la estructura
    }
};
