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
        Schema::create('historial_cambios_pedidos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->string('estado_anterior', 50)->nullable();
            $table->string('estado_nuevo', 50);
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nombre', 255)->nullable();
            $table->string('rol_usuario', 100)->nullable();
            $table->text('razon_cambio')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('datos_adicionales')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedidos_produccion')
                ->onDelete('cascade');
            $table->foreign('usuario_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index('pedido_id');
            $table->index('estado_nuevo');
            $table->index('created_at');
            $table->index('usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_cambios_pedidos');
    }
};
