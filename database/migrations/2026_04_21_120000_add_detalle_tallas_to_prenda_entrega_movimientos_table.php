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
        Schema::table('prenda_entrega_movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_entrega_movimientos', 'detalle_tallas')) {
                $table->json('detalle_tallas')->nullable()->after('cantidad_entregada');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_entrega_movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_entrega_movimientos', 'detalle_tallas')) {
                $table->dropColumn('detalle_tallas');
            }
        });
    }
};

