<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variantes_prenda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prenda_cotizacion_id')->constrained('prendas_cotizaciones')->onDelete('cascade');
            $table->foreignId('tipo_prenda_id')->constrained('tipos_prenda')->onDelete('cascade');
            
            // Opciones comunes (siempre)
            $table->foreignId('color_id')->nullable()->constrained('colores_prenda')->onDelete('set null');
            $table->foreignId('tela_id')->nullable()->constrained('telas_prenda')->onDelete('set null');
            $table->foreignId('genero_id')->nullable()->constrained('generos_prenda')->onDelete('set null');
            
            // Variaciones condicionales (NULL si no aplica)
            $table->foreignId('tipo_manga_id')->nullable()->constrained('tipos_manga')->onDelete('set null');
            $table->foreignId('tipo_broche_id')->nullable()->constrained('tipos_broche')->onDelete('set null');
            
            // Campos adicionales
            $table->boolean('tiene_bolsillos')->default(false);
            $table->boolean('tiene_reflectivo')->default(false);
            $table->json('cantidad_talla')->nullable(); // {"S": 10, "M": 15, "L": 20}
            $table->longText('descripcion_adicional')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variantes_prenda');
    }
};
