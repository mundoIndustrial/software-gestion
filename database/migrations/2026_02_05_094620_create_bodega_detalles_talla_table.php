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
        Schema::create('bodega_detalles_talla', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->unsignedBigInteger('pedido_produccion_id')->index();
            $table->unsignedBigInteger('recibo_prenda_id')->nullable()->index();
            
            // Identificadores
            $table->string('numero_pedido')->index();
            $table->string('talla');
            $table->string('prenda_nombre')->nullable();
            $table->string('asesor')->nullable();
            $table->string('empresa')->nullable();
            
            // Campos de bodega específicos
            $table->integer('cantidad')->default(0);
            $table->integer('pendientes')->default(0);
            $table->text('observaciones_bodega')->nullable(); // Observaciones específicas de bodega, no del pedido
            $table->dateTime('fecha_entrega')->nullable();
            $table->enum('estado_bodega', ['Pendiente', 'Parcial', 'Entregado', 'Retrasado'])->default('Pendiente');
            
            // Auditoría
            $table->unsignedBigInteger('usuario_bodega_id')->nullable();
            $table->string('usuario_bodega_nombre')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices compuestos
            $table->index(['numero_pedido', 'talla']);
            $table->index(['pedido_produccion_id', 'talla']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bodega_detalles_talla');
    }
};
