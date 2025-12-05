<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Clean invalid values in registros_por_orden
        DB::table('registros_por_orden')
            ->whereRaw("total_producido_por_talla NOT REGEXP '^[0-9]+$' OR total_producido_por_talla LIKE '%Total%'")
            ->update(['total_producido_por_talla' => null]);

        // Clean invalid values in registros_por_orden_bodega
        DB::table('registros_por_orden_bodega')
            ->whereRaw("total_producido_por_talla NOT REGEXP '^[0-9]+$' OR total_producido_por_talla LIKE '%Total%'")
            ->update(['total_producido_por_talla' => null]);

        // Alter registros_por_orden table
        Schema::table('registros_por_orden', function (Blueprint $table) {
            $table->integer('total_producido_por_talla')->nullable()->change();
        });

        // Alter registros_por_orden_bodega table
        Schema::table('registros_por_orden_bodega', function (Blueprint $table) {
            $table->integer('total_producido_por_talla')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Revert registros_por_orden table
        Schema::table('registros_por_orden', function (Blueprint $table) {
            $table->string('total_producido_por_talla', 62)->nullable()->change();
        });

        // Revert registros_por_orden_bodega table
        Schema::table('registros_por_orden_bodega', function (Blueprint $table) {
            $table->string('total_producido_por_talla', 62)->nullable()->change();
        });
    }
};
