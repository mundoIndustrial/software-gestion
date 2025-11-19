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
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('numero_cotizacion')->unique();
            $table->date('fecha');
            $table->string('cliente');
            $table->string('asesora');
            $table->string('cotizar_segun_indicaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('prendas_cotizacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->onDelete('cascade');
            $table->longText('descripcion')->nullable();
            $table->json('especificaciones')->nullable();
            $table->string('imagen_url')->nullable();
            $table->json('tallas')->nullable(); // Tallas con precios: {"S": 50000, "M": 55000, ...}
            $table->json('aspectos_a_verificar')->nullable();
            $table->string('forma_pago')->nullable();
            $table->string('regimen')->nullable();
            $table->string('filete_envio')->nullable();
            $table->string('se_ha_vendido')->nullable();
            $table->decimal('ultima_venta', 12, 2)->nullable();
            $table->longText('observacion')->nullable();
            $table->string('estado')->default('Pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prendas_cotizacion');
        Schema::dropIfExists('cotizaciones');
    }
};
