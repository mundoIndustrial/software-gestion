<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Hacer que los campos 'genero' y 'talla' sean nullable
     * para permitir registros de "SOLO CANTIDAD" sin especificar género o talla
     * 
     * Cambios:
     * - genero: enum -> nullable enum
     * - talla: varchar -> nullable varchar
     * - tipo_talla: varchar -> nullable varchar
     * - es_sobremedida: tinyint -> nullable tinyint
     */
    public function up(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            // Hacer nullable los campos para permitir "SOLO CANTIDAD"
            $table->string('genero', 50)->nullable()->change();
            $table->string('talla', 50)->nullable()->change();
            $table->string('tipo_talla', 10)->nullable()->change();
            $table->tinyInteger('es_sobremedida')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            // Revertir a no-nullable (pero sin valores por defecto)
            // La migración anterior debe ser reversible manualmente si es necesario
            $table->enum('genero', ['DAMA', 'CABALLERO', 'UNISEX', 'GENERICO'])->nullable(false)->change();
            $table->string('talla', 50)->nullable(false)->change();
            $table->string('tipo_talla', 10)->nullable(false)->change();
            $table->tinyInteger('es_sobremedida')->nullable(false)->default(0)->change();
        });
    }
};
