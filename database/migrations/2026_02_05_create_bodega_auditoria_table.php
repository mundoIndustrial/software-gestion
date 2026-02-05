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
        Schema::create('bodega_auditoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bodega_detalles_talla_id')->index();
            $table->string('numero_pedido')->index();
            $table->string('talla');
            $table->string('campo_modificado'); // Qué campo cambió (pendientes, observaciones_bodega, fecha_entrega)
            $table->longText('valor_anterior')->nullable();
            $table->longText('valor_nuevo');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nombre')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('accion')->default('update'); // create, update, delete
            $table->text('descripcion')->nullable();
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index(['numero_pedido', 'talla']);
            $table->index(['usuario_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bodega_auditoria');
    }
};
