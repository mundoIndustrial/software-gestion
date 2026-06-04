<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('prenda_recibo_completado_tallas')) {
            Schema::create('prenda_recibo_completado_tallas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('prenda_recibo_completado_id');
                $table->string('talla', 50);
                $table->integer('cantidad');
                $table->string('genero', 50)->nullable();
                $table->string('color_nombre', 191)->nullable();
                $table->timestamps();

                $table->index(['prenda_recibo_completado_id', 'talla'], 'prc_tallas_recibo_talla_idx');
            });

            return;
        }

        $indexExiste = collect(DB::select("SHOW INDEX FROM prenda_recibo_completado_tallas WHERE Key_name = 'prc_tallas_recibo_talla_idx'"))->isNotEmpty();

        if (!$indexExiste) {
            Schema::table('prenda_recibo_completado_tallas', function (Blueprint $table) {
                $table->index(['prenda_recibo_completado_id', 'talla'], 'prc_tallas_recibo_talla_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_recibo_completado_tallas');
    }
};
