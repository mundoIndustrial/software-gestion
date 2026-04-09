<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            if (!Schema::hasColumn('bodega_detalles_talla', 'fecha_entrega_bodega')) {
                $table->dateTime('fecha_entrega_bodega')->nullable()->after('fecha_entrega');
            }
        });

        DB::table('bodega_detalles_talla')
            ->where('estado_bodega', 'Entregado')
            ->whereNull('fecha_entrega_bodega')
            ->update([
                'fecha_entrega_bodega' => DB::raw('COALESCE(fecha_entrega, updated_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            if (Schema::hasColumn('bodega_detalles_talla', 'fecha_entrega_bodega')) {
                $table->dropColumn('fecha_entrega_bodega');
            }
        });
    }
};
