<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta tabla almacena las prendas específicas para cada técnica.
     * Por ejemplo: BORDADO -> Camisa (pecho, espalda), Pantalón (pierna)
     */
    public function up(): void
    {
        Schema::create('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->unsignedBigInteger('logo_cotizacion_tecnica_id');
            $table->foreign('logo_cotizacion_tecnica_id', 'lctp_lct_fk')
                ->references('id')
                ->on('logo_cotizacion_tecnicas')
                ->onDelete('cascade');
            
            // Datos de la prenda
            $table->string('nombre_prenda'); // Camisa, Pantalón, etc
            $table->text('descripcion'); // Descripción de dónde va (pecho, espalda, etc)
            $table->json('ubicaciones'); // Array de ubicaciones: ['PECHO', 'ESPALDA']
            $table->json('tallas')->nullable(); // Array de tallas: ['XS', 'S', 'M', 'L', 'XL']
            $table->integer('cantidad')->default(1); // Cantidad de prendas
            
            // Detalles técnicos
            $table->text('especificaciones')->nullable(); // Especificaciones de bordado/estampado
            $table->string('color_hilo')->nullable(); // Color de hilo para bordado
            $table->integer('puntos_estimados')->nullable(); // Para bordado
            
            // Control
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            // Índices
            $table->index('logo_cotizacion_tecnica_id');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_cotizacion_tecnica_prendas');
    }
};
