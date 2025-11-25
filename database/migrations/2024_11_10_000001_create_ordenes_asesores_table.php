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
        if (!Schema::hasTable('ordenes_asesores')) {
            Schema::create('ordenes_asesores', function (Blueprint $table) {
                $table->id();
                $table->string('numero_orden')->unique();
                $table->foreignId('asesor_id')->constrained('users')->onDelete('cascade');
                $table->string('cliente');
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->text('descripcion')->nullable();
                $table->decimal('monto_total', 10, 2)->default(0);
                $table->integer('cantidad_prendas')->default(0);
                $table->enum('estado', ['pendiente', 'en_proceso', 'completada', 'cancelada'])->default('pendiente');
                $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');
                $table->date('fecha_entrega')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_asesores');
    }
};
