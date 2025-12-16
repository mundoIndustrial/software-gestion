<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Estructura definitiva para prendas_pedido:
     * 1. Agregar FK faltantes en prenda_fotos_pedido y tela_fotos_pedido
     * 2. Crear tabla prenda_fotos_logo_pedido para logos de prendas
     * 3. Crear tabla prenda_fotos_tela_pedido para fotos de telas específicas
     */
    public function up(): void
    {
        // Las FKs para prenda_fotos_pedido y tela_fotos_pedido ya existen
        // Solo crearemos las tablas faltantes

        // 3. Crear tabla prenda_fotos_logo_pedido - Para logos específicos de prendas
        if (!Schema::hasTable('prenda_fotos_logo_pedido')) {
            Schema::create('prenda_fotos_logo_pedido', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prenda_pedido_id');
                $table->string('ruta_original')->nullable()->comment('URL del logo');
                $table->string('ruta_webp')->nullable();
                $table->string('ruta_miniatura')->nullable();
                $table->integer('orden')->default(1);
                $table->integer('ancho')->nullable();
                $table->integer('alto')->nullable();
                $table->integer('tamaño')->nullable();
                $table->string('ubicacion')->nullable()->comment('Ubicación del logo en la prenda');
                $table->longText('observaciones')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('prenda_pedido_id')
                    ->references('id')
                    ->on('prendas_pedido')
                    ->onDelete('cascade');
                $table->index('prenda_pedido_id');
                $table->index('orden');
            });
        }

        // 4. Crear tabla prenda_fotos_tela_pedido - Para fotos específicas de telas en cada prenda
        if (!Schema::hasTable('prenda_fotos_tela_pedido')) {
            Schema::create('prenda_fotos_tela_pedido', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prenda_pedido_id');
                $table->unsignedBigInteger('tela_id')->nullable();
                $table->unsignedBigInteger('color_id')->nullable();
                $table->string('ruta_original')->nullable()->comment('URL de la foto de tela');
                $table->string('ruta_webp')->nullable();
                $table->string('ruta_miniatura')->nullable();
                $table->integer('orden')->default(1);
                $table->integer('ancho')->nullable();
                $table->integer('alto')->nullable();
                $table->integer('tamaño')->nullable();
                $table->longText('observaciones')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('prenda_pedido_id')
                    ->references('id')
                    ->on('prendas_pedido')
                    ->onDelete('cascade');
                $table->foreign('tela_id')
                    ->references('id')
                    ->on('telas_prenda')
                    ->onDelete('set null');
                $table->foreign('color_id')
                    ->references('id')
                    ->on('colores_prenda')
                    ->onDelete('set null');
                $table->index('prenda_pedido_id');
                $table->index('tela_id');
                $table->index('color_id');
                $table->index('orden');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenda_fotos_tela_pedido');
        Schema::dropIfExists('prenda_fotos_logo_pedido');
        
        // Las FKs se eliminan con las columnas si las tablas se dropean
        // Si queremos reversibilidad completa:
        if (Schema::hasTable('tela_fotos_pedido')) {
            Schema::table('tela_fotos_pedido', function (Blueprint $table) {
                try {
                    $table->dropForeign(['prenda_pedido_id']);
                } catch (\Exception $e) {
                    // FK no existe
                }
            });
        }

        if (Schema::hasTable('prenda_fotos_pedido')) {
            Schema::table('prenda_fotos_pedido', function (Blueprint $table) {
                try {
                    $table->dropForeign(['prenda_pedido_id']);
                } catch (\Exception $e) {
                    // FK no existe
                }
            });
        }
    }
};
