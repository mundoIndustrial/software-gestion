<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Extender enum maestro de tipos de recibo para consecutivos.
        DB::statement("
            ALTER TABLE consecutivos_recibos
            MODIFY COLUMN tipo_recibo ENUM(
                'COSTURA',
                'ESTAMPADO',
                'BORDADO',
                'REFLECTIVO',
                'GENERAL',
                'DTF',
                'SUBLIMADO',
                'COSTURA-BODEGA',
                'CORTE-PARA-BODEGA'
            ) NOT NULL
        ");

        // 2) Crear el registro maestro de consecutivo para CORTE-PARA-BODEGA si no existe.
        $exists = DB::table('consecutivos_recibos')
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->exists();

        if (!$exists) {
            DB::table('consecutivos_recibos')->insert([
                'tipo_recibo' => 'CORTE-PARA-BODEGA',
                'consecutivo_actual' => 0,
                'consecutivo_inicial' => 1,
                'año' => (int) date('Y'),
                'activo' => 1,
                'notas' => 'Consecutivo para recibo de corte para bodega',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3) Guardar el número de recibo asignado en prenda_bodega.
        Schema::table('prenda_bodega', function (Blueprint $table) {
            if (!Schema::hasColumn('prenda_bodega', 'numero_recibo')) {
                $table->unsignedInteger('numero_recibo')->nullable()->after('id');
                $table->index('numero_recibo', 'idx_prenda_bodega_numero_recibo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prenda_bodega', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_bodega', 'numero_recibo')) {
                $table->dropIndex('idx_prenda_bodega_numero_recibo');
                $table->dropColumn('numero_recibo');
            }
        });

        DB::table('consecutivos_recibos')
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->delete();

        DB::statement("
            ALTER TABLE consecutivos_recibos
            MODIFY COLUMN tipo_recibo ENUM(
                'COSTURA',
                'ESTAMPADO',
                'BORDADO',
                'REFLECTIVO',
                'GENERAL',
                'DTF',
                'SUBLIMADO',
                'COSTURA-BODEGA'
            ) NOT NULL
        ");
    }
};

