<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prenda_recibo_completado', function (Blueprint $table) {
            // Agregar columna id_parcial si no existe
            if (!Schema::hasColumn('prenda_recibo_completado', 'id_parcial')) {
                $table->unsignedBigInteger('id_parcial')->nullable()->after('numero_recibo');
            }

            // Actualizar la restricción única para incluir id_parcial
            // Primero, eliminar la restricción antigua si existe
            try {
                $table->dropUnique(['id_recibo', 'area']);
            } catch (\Exception $e) {
                // La restricción podría no existir
            }

            // Agregar la nueva restricción única
            $table->unique(['id_recibo', 'area', 'id_parcial'], 'idx_recibo_area_parcial_unique');
        });
    }

    public function down(): void
    {
        Schema::table('prenda_recibo_completado', function (Blueprint $table) {
            // Revertir cambios
            if (Schema::hasColumn('prenda_recibo_completado', 'id_parcial')) {
                $table->dropColumn('id_parcial');
            }

            try {
                $table->dropUnique('idx_recibo_area_parcial_unique');
            } catch (\Exception $e) {
                // La restricción podría no existir
            }

            // Restaurar la restricción antigua
            $table->unique(['id_recibo', 'area']);
        });
    }
};
