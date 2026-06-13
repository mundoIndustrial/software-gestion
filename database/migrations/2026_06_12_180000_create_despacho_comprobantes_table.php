<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despacho_comprobantes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_produccion_id')
                ->constrained('pedidos_produccion')
                ->cascadeOnDelete()
                ->unique();
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('numero_pedido')->nullable();
            $table->string('cliente_nombre')->nullable();
            $table->string('cliente_email')->nullable();
            $table->string('comp_factura_no')->nullable();
            $table->timestamp('fecha_entrega')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despacho_comprobantes');
    }
};
