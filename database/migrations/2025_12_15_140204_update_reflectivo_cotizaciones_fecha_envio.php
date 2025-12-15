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
        // Actualizar todas las cotizaciones que fueron enviadas pero no tienen fecha_envio
        // Asignar la fecha de creación como fecha_envio
        \Illuminate\Support\Facades\DB::table('cotizaciones')
            ->where('es_borrador', false)
            ->whereNull('fecha_envio')
            ->update(['fecha_envio' => \Illuminate\Support\Facades\DB::raw('DATE(created_at)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en el rollback
    }
};
