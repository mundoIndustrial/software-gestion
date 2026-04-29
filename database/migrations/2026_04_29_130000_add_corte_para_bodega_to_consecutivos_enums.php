<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tabla maestra de consecutivos
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

        // 2) Tabla de consecutivos por pedido/prenda
        DB::statement("
            ALTER TABLE consecutivos_recibos_pedidos
            MODIFY COLUMN tipo_recibo ENUM(
                'COSTURA',
                'ESTAMPADO',
                'BORDADO',
                'REFLECTIVO',
                'DTF',
                'SUBLIMADO',
                'COSTURA-BODEGA',
                'CORTE-PARA-BODEGA'
            ) NOT NULL
        ");

        // 3) Crear registro maestro inicial si no existe
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
                'notas' => 'Consecutivo para RECIBO DE CORTE PARA BODEGA',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Limpiar posibles filas de ese tipo antes de volver el enum
        DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->delete();

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

        DB::statement("
            ALTER TABLE consecutivos_recibos_pedidos
            MODIFY COLUMN tipo_recibo ENUM(
                'COSTURA',
                'ESTAMPADO',
                'BORDADO',
                'REFLECTIVO',
                'DTF',
                'SUBLIMADO',
                'COSTURA-BODEGA'
            ) NOT NULL
        ");
    }
};

