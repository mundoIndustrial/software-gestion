<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Corrige la relación de prenda_logo_fotos:
     * - Cambiar de pedido_produccion_id a prenda_pedido_id
     * - Vincularse a prendas_pedido en lugar de pedidos_produccion
     */
    public function up(): void
    {
        // Si la tabla existe, actualizar su estructura
        if (Schema::hasTable('prenda_logo_fotos')) {
            Schema::table('prenda_logo_fotos', function (Blueprint $table) {
                // Primero eliminar la FK existente si está
                try {
                    $table->dropForeign('prenda_logo_fotos_pedido_produccion_id_foreign');
                } catch (\Exception $e) {
                    // FK no existe, continuar
                }
            });

            // Ahora agregar la columna prenda_pedido_id si no existe
            $columns = DB::select("SHOW COLUMNS FROM prenda_logo_fotos WHERE Field = 'prenda_pedido_id'");
            if (empty($columns)) {
                Schema::table('prenda_logo_fotos', function (Blueprint $table) {
                    $table->unsignedBigInteger('prenda_pedido_id')->nullable()->after('id');
                });
            }

            // Agregar la FK correcta
            Schema::table('prenda_logo_fotos', function (Blueprint $table) {
                try {
                    $table->foreign('prenda_pedido_id')
                        ->references('id')
                        ->on('prendas_pedido')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    // FK ya existe
                }
            });

            // Ahora podemos dropear la columna pedido_produccion_id si queremos
            // PERO primero debe estar seguro de que no hay datos que dependen de ello
            // Por ahora solo la dejamos (backward compatibility)
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('prenda_logo_fotos')) {
            Schema::table('prenda_logo_fotos', function (Blueprint $table) {
                try {
                    $table->dropForeign('prenda_logo_fotos_prenda_pedido_id_foreign');
                } catch (\Exception $e) {
                    // FK no existe
                }
                
                try {
                    $table->dropColumn('prenda_pedido_id');
                } catch (\Exception $e) {
                    // Columna no existe
                }
            });
        }
    }
};
