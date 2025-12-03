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
        // Primero, actualizar los valores existentes que no sean válidos
        DB::table('cotizaciones')
            ->whereNotIn('estado', ['borrador', 'enviada', 'entregar', 'anular'])
            ->update(['estado' => 'enviada']);
        
        // Agregar 'borrador' al enum de estado en cotizaciones
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->enum('estado', ['borrador', 'enviada', 'entregar', 'anular'])->default('borrador')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a los valores anteriores
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->enum('estado', ['enviada', 'entregar', 'anular'])->default('enviada')->change();
        });
    }
};
