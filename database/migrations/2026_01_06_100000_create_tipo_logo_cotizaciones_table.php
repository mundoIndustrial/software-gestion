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
        Schema::create('tipo_logo_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // BORDADO, ESTAMPADO, SUBLIMADO, DTF
            $table->string('codigo', 10)->unique(); // BOR, EST, SUB, DTF
            $table->text('descripcion')->nullable();
            $table->string('color', 7)->default('#3498db'); // Color para UI
            $table->string('icono')->default('fa-tools'); // Icono FontAwesome
            $table->integer('orden')->default(0); // Para ordenamiento
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('codigo');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_logo_cotizaciones');
    }
};
