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
        Schema::create('procesos_pedidos_logo', function (Blueprint $table) {
            $table->id();
            
            // Relación con logo_pedidos
            $table->foreignId('logo_pedido_id')
                ->constrained('logo_pedidos')
                ->onDelete('cascade');
            
            // Área/Estado del proceso
            $table->enum('area', [
                'Creacion de orden',
                'pendiente_confirmar_diseño',
                'en_diseño',
                'logo',
                'estampado'
            ])->default('Creacion de orden')->index();
            
            // Observaciones del proceso
            $table->longText('observaciones')->nullable();
            
            // Fecha en que pasó a esta área
            $table->timestamp('fecha_entrada')->nullable();
            
            // Usuario que cambió el estado
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procesos_pedidos_logo');
    }
};
