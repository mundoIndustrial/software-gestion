<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenda_bodega_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prenda_bodega_id')
                ->constrained('prenda_bodega')
                ->cascadeOnDelete();
            $table->string('ruta', 2048);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['prenda_bodega_id', 'orden'], 'idx_prenda_bodega_fotos_prenda_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenda_bodega_fotos');
    }
};
