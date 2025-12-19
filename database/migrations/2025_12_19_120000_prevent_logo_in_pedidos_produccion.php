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
        // Agregar constraint para prevenir que pedidos_produccion se cree para cotizaciones LOGO
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Crear índice en cotizacion_id para mejor rendimiento
            $table->index('cotizacion_id');
        });
        
        // Crear trigger en MySQL para bloquear inserts de LOGO en pedidos_produccion
        DB::statement("
            CREATE TRIGGER prevent_logo_in_pedidos_produccion 
            BEFORE INSERT ON pedidos_produccion
            FOR EACH ROW
            BEGIN
                DECLARE tipo_codigo VARCHAR(10);
                
                SELECT tipo_cotizaciones.codigo INTO tipo_codigo
                FROM cotizaciones
                JOIN tipo_cotizaciones ON cotizaciones.tipo_cotizacion_id = tipo_cotizaciones.id
                WHERE cotizaciones.id = NEW.cotizacion_id;
                
                IF tipo_codigo = 'L' THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'No se puede crear pedido en pedidos_produccion para cotizaciones LOGO. Usar logo_pedidos en su lugar.';
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS prevent_logo_in_pedidos_produccion");
        
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropIndex(['cotizacion_id']);
        });
    }
};
