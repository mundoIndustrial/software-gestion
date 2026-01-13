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
        Schema::create('logo_prenda_cot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('logo_cot_id');
            $table->string('nombre_producto');
            $table->longText('descripcion');
            $table->integer('cantidad');
            $table->timestamps();

            // Foreign key
            $table->foreign('logo_cot_id')
                ->references('id')
                ->on('logo_cotizaciones')
                ->onDelete('cascade');

            // Indexes
            $table->index('logo_cot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_prenda_cot');
    }
};
