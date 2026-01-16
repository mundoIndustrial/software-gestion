<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * CREAR TABLA: prenda_pedido_variantes
     * 
     * Orden de ejecución:
     * 1. 2026_01_16_normalize_prendas_pedido.php ← Normaliza tabla padre
     * 2. 2026_01_16_create_prenda_variantes_table.php ← Crea tabla hija (ESTE ARCHIVO)
     * 3. 2026_01_16_migrate_prenda_variantes_data.php ← Migra datos a tabla hija
     * 
     * Esta tabla almacena VARIANTES: combinaciones específicas de
     * talla + color + tela + manga + broche/botón + bolsillos
     */
    public function up(): void
    {
        Schema::create('prenda_pedido_variantes', function (Blueprint $table) {
            $table->id();
            
            // FK a prenda padre
            $table->unsignedBigInteger('prenda_pedido_id');
            
            // Identificador de talla
            $table->string('talla', 50);
            
            // Cantidad para esta talla/variante
            $table->unsignedInteger('cantidad')->default(0);
            
            // Catálogos relacionados
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('tela_id')->nullable();
            $table->unsignedBigInteger('tipo_manga_id')->nullable();
            $table->unsignedBigInteger('tipo_broche_boton_id')->nullable();
            
            // Observaciones específicas por característica
            $table->longText('manga_obs')->nullable()->comment('Observaciones sobre tipo de manga');
            $table->longText('broche_boton_obs')->nullable()->comment('Observaciones sobre broche/botón');
            $table->boolean('tiene_bolsillos')->default(false);
            $table->longText('bolsillos_obs')->nullable()->comment('Observaciones sobre bolsillos');
            
            // Timestamps
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('prenda_pedido_id')
                ->references('id')
                ->on('prendas_pedido')
                ->onDelete('cascade');
            
            $table->foreign('color_id')
                ->references('id')
                ->on('colores_prenda')
                ->onDelete('set null');
            
            $table->foreign('tela_id')
                ->references('id')
                ->on('telas_prenda')
                ->onDelete('set null');
            
            $table->foreign('tipo_manga_id')
                ->references('id')
                ->on('tipos_manga')
                ->onDelete('set null');
            
            $table->foreign('tipo_broche_boton_id')
                ->references('id')
                ->on('tipos_broche')
                ->onDelete('set null');
            
            // Índices
            $table->index('prenda_pedido_id');
            $table->index('talla');
            $table->index('color_id');
            $table->index('tela_id');
            $table->index('tipo_manga_id');
            $table->index('tipo_broche_boton_id');
            
            // ✅ CORRECCIÓN: Único solo por prenda y talla
            // Cada prenda puede tener UN registro por talla, independientemente de color/tela/manga/broche
            $table->unique(
                ['prenda_pedido_id', 'talla'],
                'unique_prenda_variante'
            );
        });

        \Log::info('✅ [Migración] Tabla prenda_pedido_variantes creada exitosamente');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenda_pedido_variantes');
    }
};
