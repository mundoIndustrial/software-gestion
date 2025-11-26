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
        // Cambiar el enum para incluir 'borrador'
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->enum('estado', ['enviada', 'entregar', 'anular', 'borrador'])->default('enviada')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Primero actualizar cualquier 'borrador' a 'enviada'
            DB::table('cotizaciones')->where('estado', 'borrador')->update(['estado' => 'enviada']);
            
            // Revertir el enum
            $table->enum('estado', ['enviada', 'entregar', 'anular'])->default('enviada')->change();
        });
    }
};
