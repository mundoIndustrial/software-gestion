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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_epp');
    }
};
