<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->enum('tipo_venta', ['M', 'D', 'X'])->nullable();
            $table->json('observaciones_generales')->nullable();
            $table->timestamps();

            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->unique('cotizacion_id');
        });

        Schema::create('prenda_items_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->string('descripcion');
            $table->integer('cantidad')->default(1);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->index('cotizacion_id');
        });

        Schema::create('prenda_img_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_item_id');
            $table->string('ruta');
            $table->timestamps();

            $table->foreign('prenda_item_id')->references('id')->on('prenda_items_cot')->onDelete('cascade');
            $table->index('prenda_item_id');
        });

        Schema::create('prenda_valor_unitario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_item_id');
            $table->decimal('valor_unitario', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('prenda_item_id')->references('id')->on('prenda_items_cot')->onDelete('cascade');
            $table->unique('prenda_item_id');
        });

        if (Schema::hasTable('tipos_cotizacion')) {
            DB::table('tipos_cotizacion')->updateOrInsert(
                ['codigo' => 'PRENDA'],
                [
                    'codigo' => 'PRENDA',
                    'nombre' => 'Prenda',
                    'descripcion' => 'Cotización de Prendas',
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_valor_unitario');
        Schema::dropIfExists('prenda_img_cot');
        Schema::dropIfExists('prenda_items_cot');
        Schema::dropIfExists('prenda_cotizacion');
    }
};
