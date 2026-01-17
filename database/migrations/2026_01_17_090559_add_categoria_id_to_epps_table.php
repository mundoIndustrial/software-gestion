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
        Schema::table('epps', function (Blueprint $table) {
            // Agregar solo la FK si la columna ya existe
            if (!Schema::hasColumn('epps', 'categoria_id')) {
                $table->unsignedBigInteger('categoria_id')->after('nombre');
            }
            
            try {
                $table->foreign('categoria_id')
                    ->references('id')
                    ->on('epp_categorias')
                    ->onDelete('restrict');
            } catch (\Exception $e) {
                // FK ya existe
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epps', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropColumn('categoria_id');
        });
    }
};
