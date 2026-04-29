<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recibos_logo_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consecutivo_recibo_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('checked')->default(false);
            $table->timestamps();

            $table->unique(['consecutivo_recibo_id', 'user_id'], 'recibos_logo_checks_recibo_user_unique');
            $table->index(['user_id', 'checked'], 'recibos_logo_checks_user_checked_idx');

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
        Schema::dropIfExists('recibos_logo_checks');
    }
};
