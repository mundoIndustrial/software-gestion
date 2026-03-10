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
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            // Agregar observaciones si no existe
            if (!Schema::hasColumn('pedido_ancho_general', 'observaciones')) {
                $table->longText('observaciones')->nullable()->after('contenido_mano');
            }
            
            // Modificar tipo_modo para incluir 'mano'
            // IMPORTANTE: En Laravel, para modificar un enum necesitamos usar change()
            // Pero primero verificar si la columna existe
            if (Schema::hasColumn('pedido_ancho_general', 'tipo_modo')) {
                // Obtener el tipo actual del enum
                $types = DB::select("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                                     WHERE TABLE_NAME = 'pedido_ancho_general' 
                                     AND COLUMN_NAME = 'tipo_modo'
                                     AND TABLE_SCHEMA = DATABASE()");
                
                if ($types && !str_contains($types[0]->COLUMN_TYPE, "'mano'")) {
                    // Si 'mano' no está en el enum, agregarlo
                    $table->enum('tipo_modo', ['normal', 'color', 'pieza', 'mano'])->default('normal')->change();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_ancho_general', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_ancho_general', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
        });
    }
};
