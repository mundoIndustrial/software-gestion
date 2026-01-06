<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Limpiar primero si existen
        DB::statement('DROP TABLE IF EXISTS logo_cotizacion_tecnica_prendas');
        DB::statement('DROP TABLE IF EXISTS logo_cotizacion_tecnicas');
        DB::statement('DROP TABLE IF EXISTS tipo_logo_cotizaciones');

        // 1. Tabla de tipos de técnicas
        Schema::create('tipo_logo_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('codigo', 10)->unique();
            $table->text('descripcion')->nullable();
            $table->string('color', 7)->default('#3498db');
            $table->string('icono')->default('fa-tools');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('codigo');
            $table->index('activo');
        });

        // 2. Tabla relación Logo - Técnica
        Schema::create('logo_cotizacion_tecnicas', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('logo_cotizacion_id');
            $table->unsignedBigInteger('tipo_logo_cotizacion_id');
            
            $table->text('observaciones_tecnica')->nullable();
            $table->text('instrucciones_especiales')->nullable();
            
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['logo_cotizacion_id', 'tipo_logo_cotizacion_id'], 'lct_unique');
            $table->index('tipo_logo_cotizacion_id');
            $table->index('activo');
            
            $table->foreign('logo_cotizacion_id', 'lct_logo_cot_fk')
                ->references('id')
                ->on('logo_cotizaciones')
                ->onDelete('cascade');
            
            $table->foreign('tipo_logo_cotizacion_id', 'lct_tipo_fk')
                ->references('id')
                ->on('tipo_logo_cotizaciones')
                ->onDelete('restrict');
        });

        // 3. Tabla de prendas por técnica
        Schema::create('logo_cotizacion_tecnica_prendas', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('logo_cotizacion_tecnica_id');
            
            $table->string('nombre_prenda');
            $table->text('descripcion');
            $table->json('ubicaciones');
            $table->json('tallas')->nullable();
            $table->integer('cantidad')->default(1);
            
            $table->text('especificaciones')->nullable();
            $table->string('color_hilo')->nullable();
            $table->integer('puntos_estimados')->nullable();
            
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index('logo_cotizacion_tecnica_id');
            $table->index('activo');
            
            $table->foreign('logo_cotizacion_tecnica_id', 'lctp_lct_fk')
                ->references('id')
                ->on('logo_cotizacion_tecnicas')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logo_cotizacion_tecnica_prendas');
        Schema::dropIfExists('logo_cotizacion_tecnicas');
        Schema::dropIfExists('tipo_logo_cotizaciones');
    }
};
