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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // INFORMACIÓN BÁSICA
            $table->string('numero_cotizacion')->unique()->nullable();
            $table->date('fecha')->nullable();
            $table->string('cliente')->nullable();
            $table->string('asesora')->nullable();
            
            // PASO 1: CLIENTE
            $table->string('cotizar_segun_indicaciones')->nullable();
            
            // PASO 2: PRODUCTOS
            $table->json('productos')->nullable(); // Array de productos con: nombre_producto, descripcion, fotos, imagen_tela
            $table->json('especificaciones')->nullable(); // Disponibilidad, forma_pago, régimen, se_ha_vendido, última_venta, flete
            
            // PASO 3: BORDADO/ESTAMPADO
            $table->json('imagenes')->nullable(); // Array de imágenes (máx 5)
            $table->json('tecnicas')->nullable(); // Array de técnicas seleccionadas (BORDADO, DTF, ESTAMPADO, SUBLIMADO)
            $table->text('observaciones_tecnicas')->nullable(); // Observaciones de técnicas
            $table->json('ubicaciones')->nullable(); // Ubicaciones por sección (CAMISA, JEAN/SUDADERA, GORRAS)
            $table->json('observaciones_generales')->nullable(); // Array de observaciones generales
            
            // ESTADO Y METADATA
            $table->boolean('es_borrador')->default(true); // true = borrador, false = enviada
            $table->string('estado')->default('enviada'); // enviada, aceptada, rechazada
            $table->timestamps();
            $table->softDeletes();
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
