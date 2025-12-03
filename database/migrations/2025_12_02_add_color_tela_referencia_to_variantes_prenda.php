<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar campos de color, tela y referencia a variantes_prenda
     */
    public function up(): void
    {
        Schema::table('variantes_prenda', function (Blueprint $table) {
            // Agregar campos de texto si no existen
            if (!Schema::hasColumn('variantes_prenda', 'color_nombre')) {
                $table->string('color_nombre')->nullable()->after('color_id')->comment('Nombre del color');
            }
            
            if (!Schema::hasColumn('variantes_prenda', 'tela_nombre')) {
                $table->string('tela_nombre')->nullable()->after('tela_id')->comment('Nombre de la tela');
            }
            
            if (!Schema::hasColumn('variantes_prenda', 'referencia')) {
                $table->string('referencia')->nullable()->after('tela_nombre')->comment('Referencia/código de tela');
            }
        });
    }

    /**
     * Revert changes
     */
    public function down(): void
    {
        Schema::table('variantes_prenda', function (Blueprint $table) {
            $table->dropColumn(['color_nombre', 'tela_nombre', 'referencia']);
        });
    }
};
