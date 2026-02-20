<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('epp_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->enum('tipo_venta', ['M', 'D', 'X'])->nullable();
            $table->json('observaciones_generales')->nullable();
            $table->timestamps();

            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->unique('cotizacion_id');
        });

        Schema::create('epp_items_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->string('nombre');
            $table->integer('cantidad')->default(1);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->index('cotizacion_id');
        });

        Schema::create('epp_img_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('epp_item_id');
            $table->string('ruta');
            $table->timestamps();

            $table->foreign('epp_item_id')->references('id')->on('epp_items_cot')->onDelete('cascade');
            $table->index('epp_item_id');
        });

        Schema::create('epp_valor_unitario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('epp_item_id');
            $table->decimal('valor_unitario', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('epp_item_id')->references('id')->on('epp_items_cot')->onDelete('cascade');
            $table->unique('epp_item_id');
        });

        if (Schema::hasTable('tipos_cotizacion')) {
            DB::table('tipos_cotizacion')->updateOrInsert(
                ['codigo' => 'EPP'],
                [
                    'codigo' => 'EPP',
                    'nombre' => 'EPP',
                    'descripcion' => 'Cotización EPP',
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('epp_valor_unitario');
        Schema::dropIfExists('epp_img_cot');
        Schema::dropIfExists('epp_items_cot');
        Schema::dropIfExists('epp_cotizacion');

        if (Schema::hasTable('tipos_cotizacion')) {
            DB::table('tipos_cotizacion')->where('codigo', 'EPP')->delete();
        }
    }
};
