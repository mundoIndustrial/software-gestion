<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            if (!Schema::hasColumn('bodega_detalles_talla', 'costura_estado')) {
                $table->enum('costura_estado', ['Pendiente', 'Entregado', 'Anulado', 'Omologar'])
                    ->nullable()
                    ->after('estado_bodega');
            }

            if (!Schema::hasColumn('bodega_detalles_talla', 'epp_estado')) {
                $table->enum('epp_estado', ['Pendiente', 'Entregado', 'Anulado', 'Omologar'])
                    ->nullable()
                    ->after('costura_estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            if (Schema::hasColumn('bodega_detalles_talla', 'epp_estado')) {
                $table->dropColumn('epp_estado');
            }

            if (Schema::hasColumn('bodega_detalles_talla', 'costura_estado')) {
                $table->dropColumn('costura_estado');
            }
        });
    }
};
