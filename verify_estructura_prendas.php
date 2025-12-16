<?php
$m = new mysqli('localhost', 'root', '29522628', 'mundo_bd');

echo "ESTRUCTURA SEGÚN ANÁLISIS:\n";
echo "==========================\n\n";

// 1. Verificar campos en prendas_cot
echo "1️⃣  Campos en prendas_cot:\n";
$r = $m->query("SHOW COLUMNS FROM prendas_cot");
while($c = $r->fetch_assoc()) {
    echo "   - {$c['Field']} ({$c['Type']})\n";
}

// 2. Verificar campos en prenda_variantes_cot
echo "\n2️⃣  Campos en prenda_variantes_cot:\n";
$r = $m->query("SHOW COLUMNS FROM prenda_variantes_cot");
while($c = $r->fetch_assoc()) {
    echo "   - {$c['Field']} ({$c['Type']})\n";
}

// 3. Ver ejemplo de una cotización
echo "\n3️⃣  Ejemplo de cotización con sus prendas:\n";
$r = $m->query("
    SELECT c.id, c.numero_cotizacion, c.cliente_id
    FROM cotizaciones c
    ORDER BY c.id DESC
    LIMIT 1
");
$cot = $r->fetch_assoc();

if ($cot) {
    echo "\n   Cotización #{$cot['numero_cotizacion']} (ID: {$cot['id']}):\n";
    
    $r = $m->query("
        SELECT p.id, p.nombre_producto, p.descripcion
        FROM prendas_cot p
        WHERE p.cotizacion_id = {$cot['id']}
        LIMIT 1
    ");
    
    if ($r->num_rows > 0) {
        $prenda = $r->fetch_assoc();
        echo "   Prenda: {$prenda['nombre_producto']}\n";
        echo "   Descripción: " . ($prenda['descripcion'] ? substr($prenda['descripcion'], 0, 50) . '...' : 'NULL') . "\n";
        
        // Ver variantes
        $r = $m->query("
            SELECT *
            FROM prenda_variantes_cot
            WHERE prenda_id = {$prenda['id']}
        ");
        echo "   Variantes: " . $r->num_rows . " registros\n";
    }
}

echo "\n✅ CONCLUSIÓN:\n";
echo "===============\n";
echo "Las prendas están normalizadas en 'prendas_cot'\n";
echo "Las variantes están en 'prenda_variantes_cot'\n";
echo "El Controller debe extraer datos de estas tablas, no directamente de 'cotizaciones'\n";
