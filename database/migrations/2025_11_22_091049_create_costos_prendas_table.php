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
        Schema::create('costos_prendas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id')->nullable();
            $table->string('nombre_prenda');
            $table->text('descripcion')->nullable();
            $table->json('items')->comment('Array de items con estructura: [{item: "", precio: ""}]');
            $table->decimal('total_costo', 10, 2)->default(0);
            $table->timestamps();
            
            // Foreign key
            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            
            // Índices
            $table->index('cotizacion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costos_prendas');
    }
};
