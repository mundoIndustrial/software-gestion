<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_vistos_supervisor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_id')->comment('ID del pedido en pedidos_produccion');
            $table->unsignedBigInteger('user_id')->comment('ID del supervisor que marcó como visto');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['pedido_id', 'user_id'], 'pedido_user_visto_unique');
            $table->index('user_id');
            $table->index('pedido_id');

            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedidos_produccion')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_vistos_supervisor');
    }
};
