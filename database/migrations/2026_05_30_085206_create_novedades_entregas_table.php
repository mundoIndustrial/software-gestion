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
        Schema::create('novedades_entregas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prenda_pedido_id')->nullable();
            $table->unsignedBigInteger('consecutivo_recibo_id')->nullable();
            $table->unsignedBigInteger('recibo_parcial_id')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('encargado')->nullable();
            $table->text('observaciones');
            $table->string('area')->default('Costura');
            $table->timestamps();
            
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');
            $table->index('consecutivo_recibo_id');
            $table->index('recibo_parcial_id');
            $table->index('prenda_pedido_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('novedades_entregas');
    }
};
