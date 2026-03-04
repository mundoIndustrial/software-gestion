<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla pivote: cada usuario puede marcar recibos como "vistos"
        Schema::create('recibos_vistos_insumos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consecutivo_recibo_id')->comment('ID del recibo en consecutivos_recibos_pedidos');
            $table->unsignedBigInteger('user_id')->comment('ID del usuario que marcó como visto');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['consecutivo_recibo_id', 'user_id'], 'recibo_user_unique');
            $table->index('user_id');
            $table->index('consecutivo_recibo_id');

            $table->foreign('consecutivo_recibo_id')
                ->references('id')
                ->on('consecutivos_recibos_pedidos')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos_vistos_insumos');
    }
};
