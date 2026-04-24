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
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            if (!Schema::hasColumn('bodega_detalles_talla', 'fecha_pendiente')) {
                $table->dateTime('fecha_pendiente')
                    ->nullable()
                    ->after('fecha_entrega_bodega')
                    ->comment('Fecha cuando el registro cambió a estado Pendiente');
            }
        });

        // Llenar la columna con la fecha para registros que ya están en estado Pendiente
        DB::table('bodega_detalles_talla')
            ->where('estado_bodega', 'Pendiente')
            ->whereNull('fecha_pendiente')
            ->update([
                'fecha_pendiente' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            if (Schema::hasColumn('bodega_detalles_talla', 'fecha_pendiente')) {
                $table->dropColumn('fecha_pendiente');
            }
        });
    }
};
