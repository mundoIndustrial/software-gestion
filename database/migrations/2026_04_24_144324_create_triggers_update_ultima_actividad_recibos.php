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
        // Trigger para prenda_pedido_tallas
        DB::statement('
            CREATE TRIGGER trigger_prenda_pedido_tallas_insert
            AFTER INSERT ON prenda_pedido_tallas
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pedido_produccion_id FROM prendas_pedido WHERE id = NEW.prenda_pedido_id
                );
            END
        ');

        DB::statement('
            CREATE TRIGGER trigger_prenda_pedido_tallas_update
            AFTER UPDATE ON prenda_pedido_tallas
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pedido_produccion_id FROM prendas_pedido WHERE id = NEW.prenda_pedido_id
                );
            END
        ');

        // Trigger para prenda_pedido_talla_colores
        DB::statement('
            CREATE TRIGGER trigger_prenda_pedido_talla_colores_insert
            AFTER INSERT ON prenda_pedido_talla_colores
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pp.pedido_produccion_id
                    FROM prendas_pedido pp
                    JOIN prenda_pedido_tallas ppt ON ppt.prenda_pedido_id = pp.id
                    WHERE ppt.id = NEW.prenda_pedido_talla_id
                );
            END
        ');

        DB::statement('
            CREATE TRIGGER trigger_prenda_pedido_talla_colores_update
            AFTER UPDATE ON prenda_pedido_talla_colores
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pp.pedido_produccion_id
                    FROM prendas_pedido pp
                    JOIN prenda_pedido_tallas ppt ON ppt.prenda_pedido_id = pp.id
                    WHERE ppt.id = NEW.prenda_pedido_talla_id
                );
            END
        ');

        // Trigger para prenda_pedido_colores_telas
        DB::statement('
            CREATE TRIGGER trigger_prenda_pedido_colores_telas_insert
            AFTER INSERT ON prenda_pedido_colores_telas
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pedido_produccion_id FROM prendas_pedido WHERE id = NEW.prenda_pedido_id
                );
            END
        ');

        DB::statement('
            CREATE TRIGGER trigger_prenda_pedido_colores_telas_update
            AFTER UPDATE ON prenda_pedido_colores_telas
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pedido_produccion_id FROM prendas_pedido WHERE id = NEW.prenda_pedido_id
                );
            END
        ');

        // Trigger para prenda_fotos_pedido
        DB::statement('
            CREATE TRIGGER trigger_prenda_fotos_pedido_insert
            AFTER INSERT ON prenda_fotos_pedido
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pedido_produccion_id FROM prendas_pedido WHERE id = NEW.prenda_pedido_id
                );
            END
        ');

        DB::statement('
            CREATE TRIGGER trigger_prenda_fotos_pedido_update
            AFTER UPDATE ON prenda_fotos_pedido
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pedido_produccion_id FROM prendas_pedido WHERE id = NEW.prenda_pedido_id
                );
            END
        ');

        // Trigger para prenda_fotos_tela_pedido
        DB::statement('
            CREATE TRIGGER trigger_prenda_fotos_tela_pedido_insert
            AFTER INSERT ON prenda_fotos_tela_pedido
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pp.pedido_produccion_id
                    FROM prendas_pedido pp
                    JOIN prenda_pedido_colores_telas ppct ON ppct.prenda_pedido_id = pp.id
                    WHERE ppct.id = NEW.prenda_pedido_colores_telas_id
                );
            END
        ');

        DB::statement('
            CREATE TRIGGER trigger_prenda_fotos_tela_pedido_update
            AFTER UPDATE ON prenda_fotos_tela_pedido
            FOR EACH ROW
            BEGIN
                UPDATE consecutivos_recibos_pedidos
                SET ultima_actividad = NOW()
                WHERE pedido_produccion_id = (
                    SELECT pp.pedido_produccion_id
                    FROM prendas_pedido pp
                    JOIN prenda_pedido_colores_telas ppct ON ppct.prenda_pedido_id = pp.id
                    WHERE ppct.id = NEW.prenda_pedido_colores_telas_id
                );
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_pedido_tallas_insert');
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_pedido_tallas_update');
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_pedido_talla_colores_insert');
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_pedido_talla_colores_update');
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_pedido_colores_telas_insert');
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_pedido_colores_telas_update');
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_fotos_pedido_insert');
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_fotos_pedido_update');
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_fotos_tela_pedido_insert');
        DB::statement('DROP TRIGGER IF EXISTS trigger_prenda_fotos_tela_pedido_update');
    }
};
