<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_ancho_metraje_insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->nullable()->index()->constrained('pedidos_produccion')->nullOnDelete();
            $table->unsignedBigInteger('prenda_pedido_id')->nullable()->index();
            $table->unsignedBigInteger('prenda_bodega_id')->nullable()->index();
            $table->foreignId('consecutivo_recibo_id')->nullable()->index()->constrained('consecutivos_recibos_pedidos')->nullOnDelete();
            $table->integer('numero_recibo')->nullable()->index();
            $table->string('tipo_recibo', 50)->nullable()->index();
            $table->string('tipo_evento', 50)->index();
            $table->string('accion', 50)->index();
            $table->string('modo', 20)->nullable()->index();
            $table->string('color', 100)->nullable()->index();
            $table->string('estado_anterior', 100)->nullable()->index();
            $table->string('estado_nuevo', 100)->nullable()->index();
            $table->decimal('ancho_anterior', 10, 2)->nullable();
            $table->decimal('ancho_nuevo', 10, 2)->nullable();
            $table->decimal('metraje_anterior', 10, 2)->nullable();
            $table->decimal('metraje_nuevo', 10, 2)->nullable();
            $table->foreignId('usuario_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre', 150)->nullable();
            $table->json('detalles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_ancho_metraje_insumos');
    }
};
