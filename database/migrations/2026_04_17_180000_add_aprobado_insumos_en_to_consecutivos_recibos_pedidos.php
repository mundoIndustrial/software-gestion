<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            $table->timestamp('aprobado_insumos_en')
                ->nullable()
                ->after('area');

            $table->index('aprobado_insumos_en', 'idx_crp_aprobado_insumos_en');
        });

        // Backfill inicial para recibos que ya estaban aprobados hacia corte.
        DB::table('consecutivos_recibos_pedidos')
            ->whereNull('aprobado_insumos_en')
            ->where(function ($query) {
                $query->whereIn('estado', ['En Ejecución', 'En Ejecucion'])
                    ->orWhere('area', 'CORTE');
            })
            ->update([
                'aprobado_insumos_en' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            $table->dropIndex('idx_crp_aprobado_insumos_en');
            $table->dropColumn('aprobado_insumos_en');
        });
    }
};

