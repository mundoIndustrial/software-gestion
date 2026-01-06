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
        // Eliminar tabla intermedia (no necesaria)
        Schema::dropIfExists('logo_cotizacion_tecnica_prendas');
        Schema::dropIfExists('logo_cotizacion_tecnicas');
        
        Schema::create('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            // Relaciones directas
            $table->bigInteger('logo_cotizacion_id')->unsigned();
            $table->bigInteger('tipo_logo_id')->unsigned();
            
            // Datos de la prenda
            $table->string('nombre_prenda');
            $table->longText('descripcion');
            $table->json('ubicaciones')->nullable(); // Array de ubicaciones (PECHO, ESPALDA, etc)
            $table->json('tallas')->nullable(); // Array de tallas (XS, S, M, L, XL, etc)
            
            // Cantidades
            $table->integer('cantidad')->default(1); // Cantidad por talla
            $table->integer('cantidad_general')->default(1); // Cantidad total general
            
            // Timestamps
            $table->timestamps();
            
            // Índices
            $table->index('logo_cotizacion_id', 'lctp_logo_cot_id_idx');
            $table->index('tipo_logo_id', 'lctp_tipo_logo_id_idx');
            
            // Foreign Keys
            $table->foreign('logo_cotizacion_id', 'lctp_logo_cot_fk')
                ->references('id')
                ->on('logo_cotizaciones')
                ->onDelete('cascade');
                
            $table->foreign('tipo_logo_id', 'lctp_tipo_logo_fk')
                ->references('id')
                ->on('tipo_logo_cotizaciones')
                ->onDelete('restrict');
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
