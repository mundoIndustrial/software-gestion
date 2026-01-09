<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Eliminar el trigger incorrecto
echo "Eliminando trigger incorrecto...\n";
DB::statement("DROP TRIGGER IF EXISTS prevent_logo_in_pedidos_produccion");

// Crear el trigger correcto con el nombre de tabla correcto
echo "Creando trigger correcto...\n";
DB::statement("
    CREATE TRIGGER prevent_logo_in_pedidos_produccion BEFORE INSERT ON pedidos_produccion
    FOR EACH ROW
    BEGIN
        DECLARE tipo_codigo VARCHAR(10);
        
        -- Solo validar si hay cotización asociada
        IF NEW.cotizacion_id IS NOT NULL THEN
            SELECT tipos_cotizacion.codigo INTO tipo_codigo
            FROM cotizaciones
            JOIN tipos_cotizacion ON cotizaciones.tipo_cotizacion_id = tipos_cotizacion.id
            WHERE cotizaciones.id = NEW.cotizacion_id;
            
            IF tipo_codigo = 'L' THEN
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'No se puede crear pedido en pedidos_produccion para cotizaciones LOGO. Usar logo_pedidos en su lugar.';
            END IF;
        END IF;
    END
");

echo "✅ Trigger recreado correctamente con la tabla 'tipos_cotizacion'\n";
