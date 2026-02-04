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
        Schema::create('recibo_prendas', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->string('numero_pedido')->index();
            $table->foreignId('asesor_id')->constrained('asesors')->onDelete('restrict');
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('restrict');
            $table->foreignId('articulo_id')->constrained('articulos')->onDelete('restrict');
            
            // Datos del pedido
            $table->integer('cantidad')->default(1);
            $table->text('observaciones')->nullable();
            
            // Fechas
            $table->date('fecha_entrega')->nullable()->index();
            $table->timestamp('fecha_entrega_real')->nullable();
            
            // Estado
            $table->enum('estado', ['pendiente', 'entregado', 'retrasado', 'cancelado'])
                ->default('pendiente')
                ->index();
            
            // Quién entregó
            $table->foreignId('usuario_bodeguero_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            // Auditoría
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para búsquedas
            $table->index(['numero_pedido', 'estado']);
            $table->index(['asesor_id', 'estado']);
            $table->index(['empresa_id', 'estado']);
            $table->fullText(['numero_pedido', 'observaciones']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recibo_prendas');
    }
};
