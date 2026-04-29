<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_tallas_bodega', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_bodega_id');
            $table->string('talla', 50);
            $table->unsignedInteger('cantidad');
            $table->timestamps();

            $table->foreign('prenda_bodega_id')
                ->references('id')
                ->on('prenda_bodega')
                ->onDelete('cascade');

            $table->index('prenda_bodega_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_tallas_bodega');
    }
};
