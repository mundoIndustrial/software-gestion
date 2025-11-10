<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Trigger BEFORE INSERT para calcular meta y eficiencia automáticamente
        DB::unprepared('
            CREATE TRIGGER before_insert_registro_piso_polo
            BEFORE INSERT ON registro_piso_polo
            FOR EACH ROW
            BEGIN
                -- Calcular META: (tiempo_disponible / tiempo_ciclo) * 0.9
                -- Si tiempo_ciclo es 0 o NULL, meta = 0
                IF NEW.tiempo_ciclo IS NOT NULL AND NEW.tiempo_ciclo > 0 AND NEW.tiempo_disponible IS NOT NULL THEN
                    SET NEW.meta = (NEW.tiempo_disponible / NEW.tiempo_ciclo) * 0.9;
                ELSE
                    SET NEW.meta = 0;
                END IF;
                
                -- Calcular EFICIENCIA: (cantidad / meta) * 100
                -- Si meta es 0 o NULL, eficiencia = 0
                IF NEW.meta IS NOT NULL AND NEW.meta > 0 THEN
                    SET NEW.eficiencia = (NEW.cantidad / NEW.meta) * 100;
                ELSE
                    SET NEW.eficiencia = 0;
                END IF;
            END
        ');

        // Trigger BEFORE UPDATE para recalcular meta y eficiencia cuando cambien los campos relacionados
        DB::unprepared('
            CREATE TRIGGER before_update_registro_piso_polo
            BEFORE UPDATE ON registro_piso_polo
            FOR EACH ROW
            BEGIN
                -- Calcular META: (tiempo_disponible / tiempo_ciclo) * 0.9
                IF NEW.tiempo_ciclo IS NOT NULL AND NEW.tiempo_ciclo > 0 AND NEW.tiempo_disponible IS NOT NULL THEN
                    SET NEW.meta = (NEW.tiempo_disponible / NEW.tiempo_ciclo) * 0.9;
                ELSE
                    SET NEW.meta = 0;
                END IF;
                
                -- Calcular EFICIENCIA: (cantidad / meta) * 100
                IF NEW.meta IS NOT NULL AND NEW.meta > 0 THEN
                    SET NEW.eficiencia = (NEW.cantidad / NEW.meta) * 100;
                ELSE
                    SET NEW.eficiencia = 0;
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS before_insert_registro_piso_polo');
        DB::unprepared('DROP TRIGGER IF EXISTS before_update_registro_piso_polo');
    }
};
