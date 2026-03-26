<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('pedidos_produccion') || !Schema::hasColumn('pedidos_produccion', 'estado')) {
            return;
        }

        DB::statement("
            ALTER TABLE `pedidos_produccion`
            MODIFY COLUMN `estado` ENUM(
                'Pendiente',
                'Entregado',
                'En Ejecución',
                'No iniciado',
                'Anulada',
                'PENDIENTE_SUPERVISOR',
                'pendiente_cartera',
                'RECHAZADO_CARTERA',
                'PENDIENTE_INSUMOS',
                'DEVUELTO_A_ASESORA',
                'Borrador'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('pedidos_produccion') || !Schema::hasColumn('pedidos_produccion', 'estado')) {
            return;
        }

        DB::statement("
            UPDATE `pedidos_produccion`
            SET `estado` = 'pendiente_cartera'
            WHERE `estado` = 'Borrador'
        ");

        DB::statement("
            ALTER TABLE `pedidos_produccion`
            MODIFY COLUMN `estado` ENUM(
                'Pendiente',
                'Entregado',
                'En Ejecución',
                'No iniciado',
                'Anulada',
                'PENDIENTE_SUPERVISOR',
                'pendiente_cartera',
                'RECHAZADO_CARTERA',
                'PENDIENTE_INSUMOS',
                'DEVUELTO_A_ASESORA'
            ) NOT NULL
        ");
    }
};

