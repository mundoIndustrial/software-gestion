<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea todas las tablas necesarias para almacenar prendas de pedidos
     * con la misma estructura que cotizaciones pero para pedidos.
     * Las imágenes solo copian URLs de cotizaciones (sin duplicar archivos)
     */
    public function up(): void
    {
        // 1. Tabla prendas_ped
        Schema::create('prendas_ped', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_produccion_id');
            $table->string('nombre_producto');
            $table->longText('descripcion')->nullable();
            $table->integer('cantidad')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pedido_produccion_id')
                ->references('id')
                ->on('pedidos_produccion')
                ->onDelete('cascade');
            $table->index('pedido_produccion_id');
        });

        // 2. Tabla prenda_fotos_ped
        Schema::create('prenda_fotos_ped', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_ped_id');
            $table->string('ruta_original')->nullable()->comment('URL copiada de cotizacion');
            $table->string('ruta_webp')->nullable();
            $table->string('ruta_miniatura')->nullable();
            $table->integer('orden')->default(1);
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->integer('tamaño')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prenda_ped_id')
                ->references('id')
                ->on('prendas_ped')
                ->onDelete('cascade');
            $table->index('prenda_ped_id');
            $table->index('orden');
        });

        // 3. Tabla prenda_telas_ped
        Schema::create('prenda_telas_ped', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_ped_id');
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('tela_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prenda_ped_id')
                ->references('id')
                ->on('prendas_ped')
                ->onDelete('cascade');
            $table->index('prenda_ped_id');
            $table->index('color_id');
            $table->index('tela_id');
        });

        // 4. Tabla prenda_tela_fotos_ped
        Schema::create('prenda_tela_fotos_ped', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_tela_ped_id');
            $table->string('ruta_original')->nullable()->comment('URL copiada de cotizacion');
            $table->string('ruta_webp')->nullable();
            $table->string('ruta_miniatura')->nullable();
            $table->integer('orden')->default(1);
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->integer('tamaño')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prenda_tela_ped_id')
                ->references('id')
                ->on('prenda_telas_ped')
                ->onDelete('cascade');
            $table->index('prenda_tela_ped_id');
            $table->index('orden');
        });

        // 5. Tabla prenda_tallas_ped
        Schema::create('prenda_tallas_ped', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_ped_id');
            $table->string('talla')->nullable();
            $table->integer('cantidad')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prenda_ped_id')
                ->references('id')
                ->on('prendas_ped')
                ->onDelete('cascade');
            $table->index('prenda_ped_id');
            $table->index('talla');
        });

        // 6. Tabla prenda_variantes_ped
        Schema::create('prenda_variantes_ped', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_ped_id');
            $table->string('tipo_prenda')->nullable();
            $table->boolean('es_jean_pantalon')->default(false);
            $table->string('tipo_jean_pantalon')->nullable();
            $table->unsignedBigInteger('genero_id')->nullable();
            $table->string('color')->nullable();
            $table->boolean('tiene_bolsillos')->default(false);
            $table->longText('obs_bolsillos')->nullable();
            $table->boolean('aplica_manga')->default(false);
            $table->unsignedBigInteger('tipo_manga_id')->nullable();
            $table->longText('obs_manga')->nullable();
            $table->boolean('aplica_broche')->default(false);
            $table->unsignedBigInteger('tipo_broche_id')->nullable();
            $table->longText('obs_broche')->nullable();
            $table->boolean('tiene_reflectivo')->default(false);
            $table->longText('obs_reflectivo')->nullable();
            $table->longText('descripcion_adicional')->nullable();
            $table->json('telas_multiples')->nullable()->comment('JSON con múltiples telas seleccionadas');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('prenda_ped_id')
                ->references('id')
                ->on('prendas_ped')
                ->onDelete('cascade');
            $table->index('prenda_ped_id');
            $table->index('genero_id');
            $table->index('tipo_manga_id');
            $table->index('tipo_broche_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenda_variantes_ped');
        Schema::dropIfExists('prenda_tallas_ped');
        Schema::dropIfExists('prenda_tela_fotos_ped');
        Schema::dropIfExists('prenda_telas_ped');
        Schema::dropIfExists('prenda_fotos_ped');
        Schema::dropIfExists('prendas_ped');
    }
};
