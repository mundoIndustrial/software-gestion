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
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Agregar cliente_id como foreign key
            $table->unsignedBigInteger('cliente_id')->nullable()->after('asesor_id');
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('set null');
            
            // Eliminar el campo cliente que guardaba el nombre
            $table->dropColumn('cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Restaurar el campo cliente
            $table->string('cliente')->nullable()->after('asesor_id');
            
            // Eliminar la foreign key y el campo cliente_id
            $table->dropForeign(['cliente_id']);
            $table->dropColumn('cliente_id');
        });
    }
};
