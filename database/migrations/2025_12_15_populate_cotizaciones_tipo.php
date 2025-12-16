<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar cotizaciones que tienen tipo_cotizacion_id=1 (o identificadas como RF)
        // a tener tipo='RF' basado en si tienen registros en reflectivo_cotizacion
        DB::statement(<<<SQL
            UPDATE cotizaciones
            SET tipo = 'RF'
            WHERE id IN (
                SELECT DISTINCT cotizacion_id FROM reflectivo_cotizacion
            )
            AND (tipo IS NULL OR tipo = '')
        SQL);

        // Para cotizaciones normales, asignar según tipo_cotizacion_id
        DB::statement(<<<SQL
            UPDATE cotizaciones
            SET tipo = CASE 
                WHEN tipo_cotizacion_id = 1 THEN 'PL'
                WHEN tipo_cotizacion_id = 2 THEN 'L'
                WHEN tipo_cotizacion_id = 3 THEN 'P'
                ELSE 'P'
            END
            WHERE (tipo IS NULL OR tipo = '')
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('UPDATE cotizaciones SET tipo = NULL WHERE tipo IN ("RF", "PL", "L", "P")');
    }
};
