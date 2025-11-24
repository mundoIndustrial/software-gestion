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
        // Primero, actualizar los valores existentes a valores válidos del enum
        DB::table('cotizaciones')
            ->where('estado', '!=', 'enviada')
            ->where('estado', '!=', 'entregar')
            ->where('estado', '!=', 'anular')
            ->update(['estado' => 'enviada']);
        
        // Ahora cambiar el campo estado a enum
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->enum('estado', ['enviada', 'entregar', 'anular'])->default('enviada')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Revertir a string
            $table->string('estado')->default('enviada')->change();
        });
    }
};
