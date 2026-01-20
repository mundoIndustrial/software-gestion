<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corregir el índice UNIQUE en prenda_pedido_variantes
     * 
     * PROBLEMA: Índice actual = (prenda_pedido_id, talla, color_id, tela_id, tipo_manga_id, tipo_broche_boton_id)
     * 
     * Esto causaba duplicados cuando dos prendas diferentes tenían los mismos atributos (color, tela, manga, broche)
     * 
     * SOLUCIÓN: Índice correcto = (prenda_pedido_id, talla)
     * - Cada prenda solo puede tener UNA variante por talla
     * - Los atributos (color, tela, manga, broche) son configurables por variante
     * - No hay restricción sobre repetir atributos en diferentes prendas/tallas
     */
    public function up(): void
    {
        if (Schema::hasTable('prenda_pedido_variantes')) {
            Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
                // Dropear índice antiguo si existe
                try {
                    $table->dropUnique('unique_prenda_variante');
                } catch (\Exception $e) {
                    \Log::warning(' No se pudo eliminar índice unique_prenda_variante', [
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Crear nuevo índice ÚNICO correcto
                $table->unique(
                    ['prenda_pedido_id', 'talla'],
                    'unique_prenda_talla'
                );
            });
            
            \Log::info(' Índice UNIQUE corregido en prenda_pedido_variantes');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('prenda_pedido_variantes')) {
            Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
                try {
                    $table->dropUnique('unique_prenda_talla');
                } catch (\Exception $e) {
                    \Log::warning(' Error revertiendo cambios', ['error' => $e->getMessage()]);
                }
            });
        }
    }
};
