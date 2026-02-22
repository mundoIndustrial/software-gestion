<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Ver la estructura
$result = DB::select("SHOW CREATE TABLE prendas_pedido_novedades_recibo");
echo $result[0]->{'Create Table'} . "\n\n";

// Corregir: quitar FK, quitar UNIQUE, agregar INDEX normal, reagregar FK
try {
    // Buscar el nombre de la FK que usa creado_por
    $fks = DB::select("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'prendas_pedido_novedades_recibo' 
        AND COLUMN_NAME = 'creado_por'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    echo "Foreign keys encontradas en creado_por:\n";
    foreach ($fks as $fk) {
        echo "  - " . $fk->CONSTRAINT_NAME . "\n";
    }
    
    if (count($fks) > 0) {
        $fkName = $fks[0]->CONSTRAINT_NAME;
        echo "\nPaso 1: Eliminando FK '$fkName'...\n";
        DB::statement("ALTER TABLE prendas_pedido_novedades_recibo DROP FOREIGN KEY `$fkName`");
        
        echo "Paso 2: Eliminando UNIQUE INDEX idx_creado_por...\n";
        DB::statement("ALTER TABLE prendas_pedido_novedades_recibo DROP INDEX idx_creado_por");
        
        echo "Paso 3: Creando INDEX normal idx_creado_por...\n";
        DB::statement("ALTER TABLE prendas_pedido_novedades_recibo ADD INDEX idx_creado_por (creado_por)");
        
        echo "Paso 4: Reagregando FK '$fkName'...\n";
        // Obtener la tabla y columna referenciada
        $refInfo = DB::select("
            SELECT REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'prendas_pedido_novedades_recibo' 
            AND CONSTRAINT_NAME = '$fkName'
            LIMIT 1
        ");
        
        // Si ya se eliminó la FK, usar valores por defecto
        $refTable = !empty($refInfo) ? $refInfo[0]->REFERENCED_TABLE_NAME : 'users';
        $refCol = !empty($refInfo) ? $refInfo[0]->REFERENCED_COLUMN_NAME : 'id';
        
        DB::statement("ALTER TABLE prendas_pedido_novedades_recibo ADD CONSTRAINT `$fkName` FOREIGN KEY (creado_por) REFERENCES `$refTable`(`$refCol`)");
        
        echo "\n✅ LISTO: Índice corregido de UNIQUE a INDEX normal.\n";
    } else {
        echo "No se encontró FK, intentando directamente...\n";
        DB::statement("ALTER TABLE prendas_pedido_novedades_recibo DROP INDEX idx_creado_por");
        DB::statement("ALTER TABLE prendas_pedido_novedades_recibo ADD INDEX idx_creado_por (creado_por)");
        echo "✅ LISTO\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
