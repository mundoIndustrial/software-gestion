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
        Schema::create('epp_bodega_auditoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('epp_bodega_detalle_id');
            $table->string('numero_pedido');
            $table->string('talla');
            $table->text('prenda_nombre')->nullable();
            $table->enum('estado_anterior', ['Pendiente', 'Entregado'])->nullable();
            $table->enum('estado_nuevo', ['Pendiente', 'Entregado']);
            $table->unsignedBigInteger('usuario_id');
            $table->text('usuario_nombre')->nullable();
            $table->text('descripcion_cambio')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('numero_pedido');
            $table->index('talla');
            $table->index('usuario_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epp_bodega_auditoria');
    }
};
