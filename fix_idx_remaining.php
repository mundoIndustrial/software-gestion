<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Fix idx_editado_por: UNIQUE -> INDEX
try {
    echo "Corrigiendo idx_editado_por...\n";
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo DROP FOREIGN KEY prendas_pedido_novedades_recibo_editado_por_foreign");
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo DROP INDEX idx_editado_por");
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo ADD INDEX idx_editado_por (editado_por)");
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo ADD CONSTRAINT prendas_pedido_novedades_recibo_editado_por_foreign FOREIGN KEY (editado_por) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE");
    echo "  ✅ idx_editado_por corregido\n";
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

// Fix idx_resuelto_por: UNIQUE -> INDEX
try {
    echo "Corrigiendo idx_resuelto_por...\n";
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo DROP FOREIGN KEY prendas_pedido_novedades_recibo_resuelto_por_foreign");
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo DROP INDEX idx_resuelto_por");
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo ADD INDEX idx_resuelto_por (resuelto_por)");
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo ADD CONSTRAINT prendas_pedido_novedades_recibo_resuelto_por_foreign FOREIGN KEY (resuelto_por) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE");
    echo "  ✅ idx_resuelto_por corregido\n";
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

// Fix idx_prenda_pedido_numero_recibo: UNIQUE -> INDEX  
// (una misma prenda puede tener múltiples novedades con el mismo numero_recibo)
try {
    echo "Corrigiendo idx_prenda_pedido_numero_recibo...\n";
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo DROP INDEX idx_prenda_pedido_numero_recibo");
    DB::statement("ALTER TABLE prendas_pedido_novedades_recibo ADD INDEX idx_prenda_pedido_numero_recibo (prenda_pedido_id, numero_recibo)");
    echo "  ✅ idx_prenda_pedido_numero_recibo corregido\n";
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

// Verificar resultado
echo "\nEstructura final:\n";
$result = DB::select("SHOW CREATE TABLE prendas_pedido_novedades_recibo");
echo $result[0]->{'Create Table'} . "\n";
