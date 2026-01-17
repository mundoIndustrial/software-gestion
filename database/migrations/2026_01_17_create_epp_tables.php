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
        // Crear tabla: epp_categorias (PRIMERO - no tiene dependencias)
        if (!Schema::hasTable('epp_categorias')) {
            Schema::create('epp_categorias', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('codigo', 100)->unique();
                $table->string('nombre', 255);
                $table->longText('descripcion')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->softDeletes();

                // Índices
                $table->index('activo');
            });
        }

        // Crear tabla: epps (SEGUNDO - depende de epp_categorias)
        if (!Schema::hasTable('epps')) {
            Schema::create('epps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('codigo', 50)->unique();
                $table->string('nombre', 255);
                $table->unsignedBigInteger('categoria_id');
                $table->longText('descripcion')->nullable();
                $table->json('tallas_disponibles')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
                $table->softDeletes();

                // Índices y constraints
                $table->foreign('categoria_id')->references('id')->on('epp_categorias')->onDelete('restrict');
                $table->index('categoria_id');
                $table->index('activo');
            });
        }

        // Crear tabla: pedido_epp (TERCERO - depende de epps y pedidos_produccion)
        if (!Schema::hasTable('pedido_epp')) {
            Schema::create('pedido_epp', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('pedido_produccion_id');
                $table->unsignedBigInteger('epp_id');
                $table->integer('cantidad')->default(1);
                $table->json('tallas_medidas')->nullable()->comment('JSON con las tallas y medidas seleccionadas');
                $table->longText('observaciones')->nullable();
                $table->timestamps();
                $table->softDeletes();

                // Índices y constraints
                $table->foreign('pedido_produccion_id')->references('id')->on('pedidos_produccion')->onDelete('cascade');
                $table->foreign('epp_id')->references('id')->on('epps')->onDelete('restrict');
                $table->index('pedido_produccion_id');
                $table->index('epp_id');
            });
        }

        // Crear tabla: pedido_epp_imagenes (CUARTO - depende de pedido_epp)
        if (!Schema::hasTable('pedido_epp_imagenes')) {
            Schema::create('pedido_epp_imagenes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('pedido_epp_id');
                $table->string('archivo', 255);
                $table->boolean('principal')->default(false)->comment('Si es la imagen principal');
                $table->unsignedInteger('orden')->default(0)->comment('Orden de presentación');
                $table->timestamps();

                // Índices y constraints
                $table->foreign('pedido_epp_id')->references('id')->on('pedido_epp')->onDelete('cascade');
                $table->index('pedido_epp_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_epp_imagenes');
        Schema::dropIfExists('pedido_epp');
        Schema::dropIfExists('epps');
        Schema::dropIfExists('epp_categorias');
    }
};
