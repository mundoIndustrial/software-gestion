<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agregar 'GENERICO' a la lista de géneros permitidos
     * para soportar la opción "SOLO CANTIDAD"
     */
    public function up(): void
    {
        // PostgreSQL y MySQL tienen diferentes sintaxis para modificar enums
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL
            DB::statement("ALTER TYPE genero_type ADD VALUE 'GENERICO' AFTER 'UNISEX'");
        } elseif ($driver === 'mysql') {
            // MySQL - cambiar el enum completo
            Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
                $table->enum('genero', ['DAMA', 'CABALLERO', 'UNISEX', 'GENERICO'])->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // En MySQL, no podemos eliminar un valor del enum fácilmente
        // Por ahora dejaremos el valor GENERICO en el enum
        // Si es necesario, se podría hacer una migración manual
    }
};
