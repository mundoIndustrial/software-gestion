<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Elimina la estructura prendas_ped (tabla nueva sin usar)
     * Mantiene prendas_pedido que es la tabla en uso con 2921 registros
     */
    public function up(): void
    {
        // Eliminar en orden inverso por dependencias
        Schema::dropIfExists('prenda_variantes_ped');
        Schema::dropIfExists('prenda_tallas_ped');
        Schema::dropIfExists('prenda_tela_fotos_ped');
        Schema::dropIfExists('prenda_telas_ped');
        Schema::dropIfExists('prenda_fotos_ped');
        Schema::dropIfExists('prendas_ped');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Por seguridad, no restauramos la estructura antigua
        // Esta migración es destructiva
    }
};
