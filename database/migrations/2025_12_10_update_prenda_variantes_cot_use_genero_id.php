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
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            // Remover columna genero (string)
            if (Schema::hasColumn('prenda_variantes_cot', 'genero')) {
                $table->dropColumn('genero');
            }
            
            // Agregar genero_id (FK a generos_prenda)
            if (!Schema::hasColumn('prenda_variantes_cot', 'genero_id')) {
                $table->unsignedBigInteger('genero_id')->nullable()->after('tipo_jean_pantalon')->comment('ID del género (dama, caballero, etc.)');
                
                // Foreign key
                $table->foreign('genero_id')
                    ->references('id')
                    ->on('generos_prenda')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            // Remover foreign key y columna genero_id
            if (Schema::hasColumn('prenda_variantes_cot', 'genero_id')) {
                $table->dropForeign(['genero_id']);
                $table->dropColumn('genero_id');
            }
            
            // Restaurar columna genero (string)
            $table->string('genero')->nullable()->after('tipo_jean_pantalon');
        });
    }
};
